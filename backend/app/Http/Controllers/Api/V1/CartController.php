<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index(Request $request)
    {
        $cart = $this->cartService->resolveCart($request);
        $payload = $this->cartService->toArray($cart);

        $response = ApiResponse::success($payload);

        if (!$request->user() && $cart->session_id) {
            // httpOnly cookie (XSS-resistant). SPA also receives X-Cart-Token for cross-origin clients.
            $response->headers->setCookie(
                Cookie::make(
                    'cart_token',
                    $cart->session_id,
                    60 * 24 * 30,
                    '/',
                    null,
                    $request->isSecure(),
                    true,
                    false,
                    'Lax'
                )
            );
            $response->headers->set('X-Cart-Token', $cart->session_id);
        }

        return $response;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ]);

        $quantity = $validated['quantity'] ?? 1;

        DB::transaction(function () use ($request, $validated, $quantity) {
            $product = Product::whereKey($validated['product_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (!$product->is_active || $product->stock < 1) {
                throw ValidationException::withMessages([
                    'product_id' => ['This product is out of stock.'],
                ]);
            }

            $cart = $this->cartService->resolveCart($request);
            $item = $cart->items()->where('product_id', $product->id)->first();
            $newQuantity = ($item?->quantity ?? 0) + $quantity;

            if ($newQuantity > $product->stock) {
                throw ValidationException::withMessages([
                    'quantity' => ['Insufficient product stock.'],
                ]);
            }

            $cart->items()->updateOrCreate(
                ['product_id' => $product->id],
                ['quantity' => $newQuantity]
            );
        });

        return $this->index($request);
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($request, $cartItem, $validated) {
            $cart = $this->cartService->resolveCart($request);
            $item = $cart->items()->whereKey($cartItem->id)->firstOrFail();

            $product = Product::whereKey($item->product_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$product->is_active || $product->stock < 1) {
                $item->delete();

                throw ValidationException::withMessages([
                    'quantity' => ['This product is out of stock and was removed from your cart.'],
                ]);
            }

            if ($validated['quantity'] > $product->stock) {
                throw ValidationException::withMessages([
                    'quantity' => ['Insufficient product stock.'],
                ]);
            }

            $item->update(['quantity' => $validated['quantity']]);
        });

        return $this->index($request);
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        $cart = $this->cartService->resolveCart($request);
        $cart->items()->whereKey($cartItem->id)->firstOrFail()->delete();

        return $this->index($request);
    }
}
