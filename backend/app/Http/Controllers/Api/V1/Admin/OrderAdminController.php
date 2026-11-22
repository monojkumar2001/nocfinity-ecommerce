<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Services\CheckoutService;
use Illuminate\Http\Request;

class OrderAdminController extends Controller
{
    public function __construct(private readonly CheckoutService $checkoutService) {}

    public function index(Request $request)
    {
        $orders = Order::with(['user', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->string('search') . '%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('order_number', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $term)->orWhere('name', 'like', $term));
                });
            })
            ->orderByDesc('created_at')
            ->paginate(min(50, (int) $request->input('per_page', 15)));

        return ApiResponse::success(
            $orders->getCollection()->map(fn (Order $o) => $this->transform($o))->values(),
            'Orders retrieved.',
            200,
            [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        );
    }

    public function show(Order $order)
    {
        return ApiResponse::success($this->transform($order->load(['user', 'items', 'payments']), true));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:processing,shipped,delivered,cancelled'],
        ]);

        $order = $this->checkoutService->updateStatus(
            $order,
            $validated['status'],
            $request->user()
        );

        return ApiResponse::success($this->transform($order->load(['user', 'items']), true), 'Order status updated.');
    }

    private function transform(Order $order, bool $detailed = false): array
    {
        $data = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'allowed_transitions' => CheckoutService::allowedTransitionsFrom($order->status),
            'total_amount' => (float) $order->total_amount,
            'customer' => [
                'id' => $order->user?->id,
                'name' => $order->user?->name,
                'email' => $order->user?->email,
            ],
            'created_at' => $order->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $data['subtotal'] = (float) $order->subtotal;
            $data['discount_amount'] = (float) $order->discount_amount;
            $data['shipping_amount'] = (float) $order->shipping_amount;
            $data['shipping_address'] = $order->shipping_address;
            $data['customer_note'] = $order->customer_note;
            $data['items'] = $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'price' => (float) $item->price,
                'quantity' => $item->quantity,
                'total' => (float) $item->total,
            ]);
            $data['payments'] = $order->payments->map(fn ($p) => [
                'gateway' => $p->gateway,
                'transaction_id' => $p->transaction_id,
                'amount' => (float) $p->amount,
                'status' => $p->status,
                'paid_at' => $p->paid_at?->toIso8601String(),
            ]);
        }

        return $data;
    }
}
