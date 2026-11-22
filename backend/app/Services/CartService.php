<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function resolveCart(Request $request): Cart
    {
        $user = $request->user();

        if ($user) {
            return Cart::firstOrCreate(['user_id' => $user->id]);
        }

        $sessionId = $request->header('X-Cart-Token')
            ?: $request->cookie('cart_token');

        if (!$sessionId) {
            $sessionId = (string) Str::uuid();
        }

        return Cart::firstOrCreate(
            ['session_id' => $sessionId, 'user_id' => null]
        );
    }

    public function mergeGuestCartIntoUser(User $user, ?string $guestToken): ?Cart
    {
        if (!$guestToken) {
            return Cart::firstOrCreate(['user_id' => $user->id]);
        }

        return DB::transaction(function () use ($user, $guestToken) {
            $guestCart = Cart::where('session_id', $guestToken)
                ->whereNull('user_id')
                ->lockForUpdate()
                ->first();

            $userCart = Cart::firstOrCreate(['user_id' => $user->id]);

            if (!$guestCart) {
                return $userCart;
            }

            foreach ($guestCart->items as $guestItem) {
                $existing = $userCart->items()
                    ->where('product_id', $guestItem->product_id)
                    ->first();

                $product = Product::find($guestItem->product_id);
                if (!$product || !$product->is_active) {
                    continue;
                }

                $qty = min(
                    ($existing?->quantity ?? 0) + $guestItem->quantity,
                    $product->stock
                );

                if ($qty < 1) {
                    continue;
                }

                $userCart->items()->updateOrCreate(
                    ['product_id' => $guestItem->product_id],
                    ['quantity' => $qty]
                );
            }

            if ($guestCart->coupon_id && !$userCart->coupon_id) {
                $userCart->loadMissing('items.product');
                $probeSubtotal = $userCart->items->sum(
                    fn (CartItem $item) => (float) ($item->product?->price ?? 0) * $item->quantity
                );
                $coupon = Coupon::find($guestCart->coupon_id);
                try {
                    if ($coupon) {
                        $this->assertCouponValid($coupon, $probeSubtotal);
                        $userCart->update(['coupon_id' => $coupon->id]);
                    }
                } catch (ValidationException) {
                    // Drop expired/invalid guest coupons instead of carrying them over.
                    $userCart->update(['coupon_id' => null]);
                }
            }

            $guestCart->items()->delete();
            $guestCart->delete();

            return $userCart->fresh(['items.product.images', 'coupon']);
        });
    }

    public function totals(Cart $cart): array
    {
        $cart->loadMissing('items.product.images', 'coupon');

        $subtotal = $cart->items->sum(
            fn (CartItem $item) => (float) $item->product->price * $item->quantity
        );

        $discount = $this->calculateDiscount($cart->coupon, $subtotal);

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'shipping' => 0.0,
            'total' => round(max(0, $subtotal - $discount), 2),
        ];
    }

    public function calculateDiscount(?Coupon $coupon, float $subtotal): float
    {
        if (!$coupon) {
            return 0.0;
        }

        if ($coupon->type === 'percentage') {
            $discount = $subtotal * ((float) $coupon->value / 100);

            if ($coupon->maximum_discount !== null) {
                $discount = min($discount, (float) $coupon->maximum_discount);
            }
        } else {
            $discount = (float) $coupon->value;
        }

        return min($discount, $subtotal);
    }

    public function assertCouponValid(Coupon $coupon, float $subtotal): void
    {
        if (!$coupon->is_active) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or inactive coupon.'],
            ]);
        }

        $now = now();

        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            throw ValidationException::withMessages([
                'code' => ['This coupon is not active yet.'],
            ]);
        }

        if ($coupon->expires_at && $now->gt($coupon->expires_at)) {
            throw ValidationException::withMessages([
                'code' => ['This coupon has expired.'],
            ]);
        }

        if (
            $coupon->usage_limit !== null &&
            $coupon->used_count >= $coupon->usage_limit
        ) {
            throw ValidationException::withMessages([
                'code' => ['This coupon usage limit has been reached.'],
            ]);
        }

        if ($subtotal < (float) $coupon->minimum_order_amount) {
            throw ValidationException::withMessages([
                'code' => [
                    'Minimum order amount is ' .
                    number_format((float) $coupon->minimum_order_amount, 2),
                ],
            ]);
        }
    }

    public function pruneUnavailableItems(Cart $cart): Cart
    {
        $cart->loadMissing('items.product');

        foreach ($cart->items as $item) {
            $product = $item->product;

            if (!$product || !$product->is_active || $product->stock < 1) {
                $item->delete();
                continue;
            }

            if ($item->quantity > $product->stock) {
                $item->update(['quantity' => $product->stock]);
            }
        }

        return $cart->fresh(['items.product.images', 'coupon']) ?? $cart;
    }

    public function toArray(Cart $cart): array
    {
        $cart = $this->pruneUnavailableItems($cart);
        $totals = $this->totals($cart);

        return [
            'id' => $cart->id,
            // Only expose guest cart token when the cart is still anonymous.
            'session_id' => $cart->user_id ? null : $cart->session_id,
            'items' => $cart->items->map(fn (CartItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'name' => $item->product->name,
                'slug' => $item->product->slug,
                'sku' => $item->product->sku,
                'price' => (float) $item->product->price,
                'compare_at_price' => $item->product->compare_at_price
                    ? (float) $item->product->compare_at_price
                    : null,
                'quantity' => $item->quantity,
                'line_total' => round((float) $item->product->price * $item->quantity, 2),
                'stock' => $item->product->stock,
                'color' => $item->product->color,
                'size' => $item->product->size,
                'dress_style' => $item->product->dress_style,
                'image' => $this->absoluteMediaUrl(optional(
                    $item->product->images->sortByDesc('is_primary')->sortBy('sort_order')->first()
                )->image_path),
            ])->values(),
            'coupon' => $cart->coupon ? [
                'code' => $cart->coupon->code,
                'type' => $cart->coupon->type,
                'value' => (float) $cart->coupon->value,
            ] : null,
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'shipping' => $totals['shipping'],
            'total' => $totals['total'],
        ];
    }

    private function absoluteMediaUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return rtrim(config('app.url'), '/') . '/' . ltrim($path, '/');
    }
}
