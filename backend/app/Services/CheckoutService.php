<?php

namespace App\Services;

use App\Mail\OrderStatusUpdated;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Stripe\StripeClient;
use Throwable;

class CheckoutService
{
    /**
     * Fulfillment lifecycle (payment sets pending → paid):
     * pending → paid → processing → shipped → delivered, plus cancelled.
     */
    public const TRANSITIONS = [
        'pending' => ['cancelled'],
        'paid' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered', 'cancelled'],
        'delivered' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly CartService $cartService,
        private readonly OrderNumberGenerator $orderNumbers,
    ) {}

    protected function stripe(): StripeClient
    {
        $secret = config('services.stripe.secret');

        if (! is_string($secret) || $secret === '' || str_contains($secret, 'replace_me')) {
            throw ValidationException::withMessages([
                'payment' => [
                    'Stripe is not configured. Set a real STRIPE_SECRET (and STRIPE_KEY) in backend/.env, then clear config cache.',
                ],
            ]);
        }

        return new StripeClient($secret);
    }

    protected function createPaymentIntent(array $params): object
    {
        return $this->stripe()->paymentIntents->create($params);
    }

    protected function retrievePaymentIntent(string $id): object
    {
        return $this->stripe()->paymentIntents->retrieve($id);
    }

    protected function capturePaymentIntent(string $id): object
    {
        return $this->stripe()->paymentIntents->capture($id);
    }

    protected function cancelPaymentIntent(string $id): object
    {
        return $this->stripe()->paymentIntents->cancel($id);
    }

    /**
     * Create a pending order + Stripe PaymentIntent (manual capture).
     * Stock is NOT decremented yet — only validated under row locks.
     */
    public function initiate(User $user, Cart $cart, array $shippingAddress, ?string $note = null): array
    {
        $cart->load('items.product', 'coupon');

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => ['Your cart is empty.'],
            ]);
        }

        return DB::transaction(function () use ($user, $cart, $shippingAddress, $note) {
            $lockedItems = [];

            foreach ($cart->items as $item) {
                $product = Product::whereKey($item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => ["{$product->name} is no longer available."],
                    ]);
                }

                if ($product->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => [
                            "Insufficient stock for {$product->name}. Only {$product->stock} left.",
                        ],
                    ]);
                }

                $lockedItems[] = [$item, $product];
            }

            if ($cart->coupon) {
                $subtotalProbe = collect($lockedItems)->sum(
                    fn ($pair) => (float) $pair[1]->price * $pair[0]->quantity
                );
                $this->cartService->assertCouponValid($cart->coupon, $subtotalProbe);
            }

            $subtotal = 0.0;
            foreach ($lockedItems as [$item, $product]) {
                $subtotal += (float) $product->price * $item->quantity;
            }

            $discount = $this->cartService->calculateDiscount($cart->coupon, $subtotal);
            $shipping = 0.0;
            $total = round(max(0, $subtotal - $discount + $shipping), 2);

            $order = Order::create([
                'user_id' => $user->id,
                'coupon_id' => $cart->coupon_id,
                'order_number' => $this->orderNumbers->generate(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => round($subtotal, 2),
                'discount_amount' => round($discount, 2),
                'shipping_amount' => $shipping,
                'total_amount' => $total,
                'shipping_address' => $shippingAddress,
                'customer_note' => $note,
                'stock_decremented' => false,
                'stock_restored' => false,
            ]);

            foreach ($lockedItems as [$item, $product]) {
                $lineTotal = round((float) $product->price * $item->quantity, 2);
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'price' => $product->price,
                    'quantity' => $item->quantity,
                    'total' => $lineTotal,
                ]);
            }

            // Authorize only — capture after stock re-check on confirm.
            $intent = $this->createPaymentIntent([
                'amount' => (int) round($total * 100),
                'currency' => config('services.stripe.currency', 'usd'),
                'capture_method' => 'manual',
                'automatic_payment_methods' => ['enabled' => true],
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => (string) $user->id,
                ],
            ]);

            Payment::create([
                'order_id' => $order->id,
                'gateway' => 'stripe',
                'transaction_id' => $intent->id,
                'amount' => $total,
                'currency' => strtoupper(config('services.stripe.currency', 'usd')),
                'status' => 'pending',
                'payment_data' => [
                    'client_secret' => $intent->client_secret,
                    'capture_method' => 'manual',
                ],
            ]);

            return [
                'order' => $order->load('items'),
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
            ];
        });
    }

    /**
     * Confirm authorized payment: re-check stock under locks, decrement,
     * capture funds, mark paid. Rejects clearly if stock changed.
     */
    public function confirmPaid(Order $order, string $paymentIntentId): Order
    {
        /** @var array{type: string, message: string}|null $reject */
        $reject = null;

        $result = DB::transaction(function () use ($order, $paymentIntentId, &$reject) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($order->payment_status === 'paid' && $order->stock_decremented) {
                return $order->load('items', 'payments');
            }

            if ($order->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'order' => ['This order was cancelled.'],
                ]);
            }

            $payment = Payment::where('order_id', $order->id)
                ->where('transaction_id', $paymentIntentId)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                throw ValidationException::withMessages([
                    'payment' => ['Payment intent does not belong to this order.'],
                ]);
            }

            $intent = $this->retrievePaymentIntent($paymentIntentId);
            $authorized = in_array($intent->status, ['requires_capture', 'succeeded'], true);

            if (!$authorized) {
                $reject = [
                    'type' => 'payment',
                    'message' => 'Payment was not successful. Order was not marked as paid.',
                ];

                return null;
            }

            if ((int) $intent->amount !== (int) round((float) $order->total_amount * 100)) {
                throw ValidationException::withMessages([
                    'payment' => ['Payment amount mismatch.'],
                ]);
            }

            // Re-validate coupon under lock (usage / expiry may have changed since initiate).
            if ($order->coupon_id) {
                $coupon = \App\Models\Coupon::whereKey($order->coupon_id)->lockForUpdate()->first();
                if ($coupon) {
                    try {
                        $this->cartService->assertCouponValid($coupon, (float) $order->subtotal);
                    } catch (ValidationException $e) {
                        $reject = [
                            'type' => 'coupon',
                            'message' => $e->errors()['code'][0] ?? 'Coupon is no longer valid.',
                            'cancel_intent' => true,
                        ];

                        return null;
                    }
                }
            }

            // Re-validate stock under row locks — reject before capture if sold out.
            foreach ($order->items as $item) {
                if (!$item->product_id) {
                    continue;
                }

                $product = Product::whereKey($item->product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$product || !$product->is_active || $product->stock < $item->quantity) {
                    $available = $product?->stock ?? 0;
                    $reject = [
                        'type' => 'stock',
                        'message' => "Stock changed since checkout for {$item->product_name}. "
                            . "Only {$available} left — order was not charged. Please update your cart and try again.",
                        'cancel_intent' => true,
                    ];

                    return null;
                }
            }

            // Capture funds only after stock is confirmed under locks.
            if ($intent->status === 'requires_capture') {
                $intent = $this->capturePaymentIntent($paymentIntentId);
            }

            if ($intent->status !== 'succeeded') {
                $reject = [
                    'type' => 'payment',
                    'message' => 'Payment capture failed. Order was not marked as paid.',
                ];

                return null;
            }

            // Decrement inventory only once payment has succeeded.
            foreach ($order->items as $item) {
                if (!$item->product_id) {
                    continue;
                }

                $product = Product::whereKey($item->product_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($product->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        'stock' => [
                            "Stock changed for {$item->product_name} during payment capture. Contact support with order {$order->order_number}.",
                        ],
                    ]);
                }

                $product->decrement('stock', $item->quantity);
            }

            $order->update([
                'status' => 'paid',
                'payment_status' => 'paid',
                'paid_at' => now(),
                'stock_decremented' => true,
                'stock_restored' => false,
            ]);

            $payment->update([
                'status' => 'succeeded',
                'paid_at' => now(),
                'payment_data' => array_merge($payment->payment_data ?? [], [
                    'status' => $intent->status,
                ]),
            ]);

            if ($order->coupon_id) {
                $order->coupon()?->increment('used_count');
            }

            Cart::where('user_id', $order->user_id)->each(function (Cart $cart) {
                $cart->items()->delete();
                $cart->update(['coupon_id' => null]);
            });

            $order->load('user', 'items');
            $this->notifyOrderStatus($order);

            return $order->load('items', 'payments');
        });

        if ($reject !== null) {
            if (!empty($reject['cancel_intent'])) {
                $this->safeCancelPaymentIntent($paymentIntentId);
            }

            Payment::where('order_id', $order->id)
                ->where('transaction_id', $paymentIntentId)
                ->update(['status' => 'failed']);

            Order::whereKey($order->id)->update(['payment_status' => 'failed']);

            throw ValidationException::withMessages([
                $reject['type'] => [$reject['message']],
            ]);
        }

        return $result;
    }

    /**
     * Admin fulfillment status updates with enforced transitions.
     * Stock restores only for cancelled orders that previously decremented inventory.
     */
    public function updateStatus(Order $order, string $status, User $actor): Order
    {
        $allowed = array_keys(self::TRANSITIONS);

        if (!in_array($status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ['Invalid order status.'],
            ]);
        }

        // Payment sets status to paid — admins must not invent a paid state.
        if ($status === 'paid') {
            throw ValidationException::withMessages([
                'status' => ['Paid status is set automatically when payment succeeds.'],
            ]);
        }

        return DB::transaction(function () use ($order, $status) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $previous = $order->status;

            if ($previous === $status) {
                return $order->load('user', 'items');
            }

            $next = self::TRANSITIONS[$previous] ?? [];
            if (!in_array($status, $next, true)) {
                throw ValidationException::withMessages([
                    'status' => [
                        "Cannot change order from “{$previous}” to “{$status}”. "
                        . 'Allowed: ' . (empty($next) ? 'none' : implode(', ', $next)) . '.',
                    ],
                ]);
            }

            if ($status === 'cancelled') {
                if ($order->stock_decremented && !$order->stock_restored) {
                    $this->restoreStockForOrder($order);
                    $order->stock_restored = true;
                }
                $order->cancelled_at = now();
            }

            $order->status = $status;
            $order->save();

            $order->load('user', 'items');
            $this->notifyOrderStatus($order);

            return $order;
        });
    }

    protected function notifyOrderStatus(Order $order): void
    {
        if (!$order->user?->email) {
            return;
        }

        try {
            Mail::to($order->user->email)->send(new OrderStatusUpdated($order));
        } catch (Throwable $e) {
            // Never fail payment/fulfillment because Mailpit/SMTP is down.
            Log::warning('Order status email failed', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return list<string>
     */
    public static function allowedTransitionsFrom(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }

    private function restoreStockForOrder(Order $order): void
    {
        foreach ($order->items as $item) {
            if (!$item->product_id) {
                continue;
            }

            $product = Product::whereKey($item->product_id)
                ->lockForUpdate()
                ->first();

            if ($product) {
                $product->increment('stock', $item->quantity);
            }
        }
    }

    private function safeCancelPaymentIntent(string $paymentIntentId): void
    {
        try {
            $this->cancelPaymentIntent($paymentIntentId);
        } catch (\Throwable) {
            // Intent may already be cancelled / non-cancellable in test doubles.
        }
    }
}
