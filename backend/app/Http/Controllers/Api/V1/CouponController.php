<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Coupon;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function apply(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $cart = $this->cartService->resolveCart($request);
        $cart->load('items.product');

        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => ['Your cart is empty.'],
            ]);
        }

        $coupon = Coupon::where('code', strtoupper(trim($validated['code'])))->first();

        if (!$coupon) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or inactive coupon.'],
            ]);
        }

        $subtotal = $cart->items->sum(
            fn ($item) => (float) $item->product->price * $item->quantity
        );

        $this->cartService->assertCouponValid($coupon, $subtotal);

        $cart->update(['coupon_id' => $coupon->id]);

        return ApiResponse::success(
            $this->cartService->toArray($cart->fresh(['items.product.images', 'coupon'])),
            'Coupon applied successfully.'
        );
    }

    public function remove(Request $request)
    {
        $cart = $this->cartService->resolveCart($request);
        $cart->update(['coupon_id' => null]);

        return ApiResponse::success(
            $this->cartService->toArray($cart->fresh(['items.product.images', 'coupon'])),
            'Coupon removed successfully.'
        );
    }
}
