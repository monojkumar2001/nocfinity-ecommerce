<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = User::query()
            ->customers()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('name', 'like', $term)->orWhere('email', 'like', $term);
                });
            })
            ->withCount('orders')
            ->orderByDesc('created_at')
            ->paginate(min(50, (int) $request->input('per_page', 20)));

        return ApiResponse::success(
            $customers->getCollection()->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'orders_count' => $u->orders_count,
                'created_at' => $u->created_at?->toIso8601String(),
            ])->values(),
            'Customers retrieved.',
            200,
            [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ]
        );
    }

    public function show(User $user)
    {
        if ($user->isStaff()) {
            return ApiResponse::error('Customer not found.', 404);
        }

        $orders = $user->orders()->with('items')->orderByDesc('created_at')->get();

        $mapped = $orders->map(fn ($o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'status' => $o->status,
            'payment_status' => $o->payment_status,
            'total_amount' => (float) $o->total_amount,
            'created_at' => $o->created_at?->toIso8601String(),
        ]);

        return ApiResponse::success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at?->toIso8601String(),
            'stats' => [
                'orders_count' => $orders->count(),
                'total_spent' => round((float) $orders
                    ->where('payment_status', 'paid')
                    ->sum('total_amount'), 2),
            ],
            'orders' => $mapped->values(),
        ]);
    }
}
