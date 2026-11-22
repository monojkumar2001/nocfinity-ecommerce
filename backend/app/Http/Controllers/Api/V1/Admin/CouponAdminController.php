<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponAdminController extends Controller
{
    public function index(Request $request)
    {
        $coupons = Coupon::orderByDesc('created_at')
            ->paginate(min(50, (int) $request->input('per_page', 20)));

        return ApiResponse::success(
            $coupons->getCollection()->map(fn (Coupon $c) => $this->transform($c))->values(),
            'Coupons retrieved.',
            200,
            [
                'current_page' => $coupons->currentPage(),
                'last_page' => $coupons->lastPage(),
                'per_page' => $coupons->perPage(),
                'total' => $coupons->total(),
            ]
        );
    }

    public function store(Request $request)
    {
        $validated = $this->rules($request);
        $validated['code'] = strtoupper($validated['code']);
        $coupon = Coupon::create($validated);

        return ApiResponse::success($this->transform($coupon), 'Coupon created.', 201);
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $this->rules($request, $coupon);
        $validated['code'] = strtoupper($validated['code']);
        $coupon->update($validated);

        return ApiResponse::success($this->transform($coupon), 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();

        return ApiResponse::success(null, 'Coupon deleted.');
    }

    private function rules(Request $request, ?Coupon $coupon = null): array
    {
        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('coupons', 'code')->ignore($coupon?->id),
            ],
            'type' => ['required', 'in:fixed,percentage'],
            'value' => ['required', 'numeric', 'min:0'],
            'minimum_order_amount' => ['nullable', 'numeric', 'min:0'],
            'maximum_discount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    private function transform(Coupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
            'minimum_order_amount' => (float) $coupon->minimum_order_amount,
            'maximum_discount' => $coupon->maximum_discount !== null
                ? (float) $coupon->maximum_discount
                : null,
            'usage_limit' => $coupon->usage_limit,
            'used_count' => $coupon->used_count,
            'starts_at' => $coupon->starts_at?->toIso8601String(),
            'expires_at' => $coupon->expires_at?->toIso8601String(),
            'is_active' => $coupon->is_active,
        ];
    }
}
