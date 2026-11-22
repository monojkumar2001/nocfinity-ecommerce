<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(min(50, (int) $request->input('per_page', 10)));

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

    public function show(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return ApiResponse::error('Order not found.', 404);
        }

        return ApiResponse::success($this->transform($order->load('items'), true));
    }

    private function transform(Order $order, bool $detailed = false): array
    {
        $data = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total_amount' => (float) $order->total_amount,
            'item_count' => $order->items->sum('quantity'),
            'created_at' => $order->created_at?->toIso8601String(),
        ];

        // List + detail both include line items so the account UI can show order contents.
        $data['items'] = $order->items->map(fn ($item) => [
            'product_name' => $item->product_name,
            'product_sku' => $item->product_sku,
            'price' => (float) $item->price,
            'quantity' => $item->quantity,
            'total' => (float) $item->total,
        ])->values();

        if ($detailed) {
            $data['subtotal'] = (float) $order->subtotal;
            $data['discount_amount'] = (float) $order->discount_amount;
            $data['shipping_amount'] = (float) $order->shipping_amount;
            $data['shipping_address'] = $order->shipping_address;
            $data['customer_note'] = $order->customer_note;
            $data['status_timeline'] = $this->statusTimeline($order->status);
        }

        return $data;
    }

    /**
     * @return list<array{key: string, label: string, state: 'done'|'current'|'upcoming'|'cancelled'}>
     */
    private function statusTimeline(string $status): array
    {
        if ($status === 'cancelled') {
            return [
                ['key' => 'pending', 'label' => 'Placed', 'state' => 'done'],
                ['key' => 'cancelled', 'label' => 'Cancelled', 'state' => 'cancelled'],
            ];
        }

        $steps = [
            ['key' => 'pending', 'label' => 'Placed'],
            ['key' => 'paid', 'label' => 'Paid'],
            ['key' => 'processing', 'label' => 'Processing'],
            ['key' => 'shipped', 'label' => 'Shipped'],
            ['key' => 'delivered', 'label' => 'Delivered'],
        ];

        $order = array_column($steps, 'key');
        $index = array_search($status, $order, true);
        if ($index === false) {
            $index = 0;
        }

        return array_map(function (array $step, int $i) use ($index) {
            $state = $i < $index ? 'done' : ($i === $index ? 'current' : 'upcoming');

            return [
                'key' => $step['key'],
                'label' => $step['label'],
                'state' => $state,
            ];
        }, $steps, array_keys($steps));
    }
}
