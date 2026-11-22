<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $threshold = (int) config('app.low_stock_threshold', env('LOW_STOCK_THRESHOLD', 5));

        $todaysOrders = Order::whereDate('created_at', $today)->count();
        $todaysRevenue = (float) Order::whereDate('created_at', $today)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $totalCustomers = User::customers()->count();

        $lowStock = Product::where('stock', '<=', $threshold)->where('is_active', true)->count();

        $sales = Order::query()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(total_amount) as revenue'), DB::raw('COUNT(*) as orders'))
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $recent = Order::with('user')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get()
            ->map(fn (Order $o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'customer' => $o->user?->name,
                'status' => $o->status,
                'payment_status' => $o->payment_status,
                'total_amount' => (float) $o->total_amount,
                'created_at' => $o->created_at?->toIso8601String(),
            ]);

        return ApiResponse::success([
            'stats' => [
                'todays_orders' => $todaysOrders,
                'todays_revenue' => round($todaysRevenue, 2),
                'total_customers' => $totalCustomers,
                'low_stock_count' => $lowStock,
            ],
            'sales_chart' => $sales,
            'recent_orders' => $recent,
        ]);
    }
}
