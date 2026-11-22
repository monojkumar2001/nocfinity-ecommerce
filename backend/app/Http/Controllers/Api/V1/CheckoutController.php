<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipping_address' => ['required', 'array'],
            'shipping_address.name' => ['required', 'string', 'max:255'],
            'shipping_address.phone' => ['required', 'string', 'max:50'],
            'shipping_address.line1' => ['required', 'string', 'max:255'],
            'shipping_address.line2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required', 'string', 'max:120'],
            'shipping_address.state' => ['required', 'string', 'max:120'],
            'shipping_address.postal_code' => ['required', 'string', 'max:30'],
            'shipping_address.country' => ['required', 'string', 'max:120'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            // Intentionally ignore any client-sent totals/prices.
        ]);

        $cart = $this->cartService->resolveCart($request);

        $result = $this->checkoutService->initiate(
            $request->user(),
            $cart,
            $validated['shipping_address'],
            $validated['customer_note'] ?? null,
        );

        return ApiResponse::success([
            'order' => $this->orderPayload($result['order']),
            'client_secret' => $result['client_secret'],
            'payment_intent_id' => $result['payment_intent_id'],
        ], 'Checkout initiated.', 201);
    }

    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_intent_id' => ['required', 'string'],
        ]);

        $order = Order::whereKey($validated['order_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $order = $this->checkoutService->confirmPaid(
            $order,
            $validated['payment_intent_id']
        );

        return ApiResponse::success([
            'order' => $this->orderPayload($order),
        ], 'Payment confirmed.');
    }

    private function orderPayload(Order $order): array
    {
        $order->loadMissing('items');

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'subtotal' => (float) $order->subtotal,
            'discount_amount' => (float) $order->discount_amount,
            'shipping_amount' => (float) $order->shipping_amount,
            'total_amount' => (float) $order->total_amount,
            'shipping_address' => $order->shipping_address,
            'items' => $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'price' => (float) $item->price,
                'quantity' => $item->quantity,
                'total' => (float) $item->total,
            ]),
            'created_at' => $order->created_at?->toIso8601String(),
        ];
    }
}
