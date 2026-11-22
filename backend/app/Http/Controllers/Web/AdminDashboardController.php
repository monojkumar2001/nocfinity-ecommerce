<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\StaffAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user?->can('reports.view')) {
            $fallback = StaffAccess::firstAllowedRoute($user);
            if ($fallback === 'login' || $fallback === 'admin.dashboard') {
                abort(403, 'Missing permission: reports.view');
            }

            return redirect()->route($fallback);
        }

        $today = Carbon::today();
        $threshold = (int) config('app.low_stock_threshold', env('LOW_STOCK_THRESHOLD', 5));
        $revenue = (float) Order::where('payment_status', 'paid')->sum('total_amount');
        $ordersCount = Order::count();
        $customers = User::customers()->count();
        $products = Product::where('is_active', true)->count();
        $lowStock = Product::where('stock', '<=', $threshold)->where('is_active', true)->count();
        $todaysOrders = Order::whereDate('created_at', $today)->count();
        $todaysRevenue = (float) Order::whereDate('created_at', $today)
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $prevRevenue = (float) Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [now()->subDays(60)->startOfDay(), now()->subDays(30)->endOfDay()])
            ->sum('total_amount');
        $currRevenue = (float) Order::where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->sum('total_amount');
        $revenueChange = $prevRevenue > 0
            ? round((($currRevenue - $prevRevenue) / $prevRevenue) * 100, 1)
            : ($currRevenue > 0 ? 100 : 0);

        $prevOrders = Order::whereBetween('created_at', [now()->subDays(60)->startOfDay(), now()->subDays(30)->endOfDay()])->count();
        $currOrders = Order::where('created_at', '>=', now()->subDays(29)->startOfDay())->count();
        $ordersChange = $prevOrders > 0
            ? round((($currOrders - $prevOrders) / $prevOrders) * 100, 1)
            : ($currOrders > 0 ? 100 : 0);

        $prevCustomers = User::customers()
            ->whereBetween('created_at', [now()->subDays(60)->startOfDay(), now()->subDays(30)->endOfDay()])
            ->count();
        $currCustomers = User::customers()
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->count();
        $customersChange = $prevCustomers > 0
            ? round((($currCustomers - $prevCustomers) / $prevCustomers) * 100, 1)
            : ($currCustomers > 0 ? 100 : 0);

        $salesDays = Order::query()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(total_amount) as revenue'), DB::raw('COUNT(*) as orders'))
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $audienceLabels = [];
        $audienceRevenue = [];
        $audienceOrders = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $audienceLabels[] = now()->subDays($i)->format('M d');
            $row = $salesDays->get($day);
            $audienceRevenue[] = round((float) ($row->revenue ?? 0), 2);
            $audienceOrders[] = (int) ($row->orders ?? 0);
        }

        $weekLabels = [];
        $weekNew = [];
        $weekReturning = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $weekLabels[] = $day->format('D');
            $start = $day->copy()->startOfDay();
            $end = $day->copy()->endOfDay();
            $weekNew[] = User::customers()
                ->whereBetween('created_at', [$start, $end])
                ->count();
            $weekReturning[] = Order::whereBetween('created_at', [$start, $end])
                ->distinct('user_id')
                ->count('user_id');
        }

        $statusBreakdown = Order::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $monthlySale = (float) Order::where('payment_status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');

        $recent = Order::with('user')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return view('admin.dashboard', [
            'stats' => [
                'revenue' => $revenue,
                'orders' => $ordersCount,
                'customers' => $customers,
                'products' => $products,
                'low_stock' => $lowStock,
                'todays_orders' => $todaysOrders,
                'todays_revenue' => $todaysRevenue,
                'revenue_change' => $revenueChange,
                'orders_change' => $ordersChange,
                'customers_change' => $customersChange,
                'monthly_sale' => $monthlySale,
            ],
            'charts' => [
                'audience_labels' => $audienceLabels,
                'audience_revenue' => $audienceRevenue,
                'audience_orders' => $audienceOrders,
                'week_labels' => $weekLabels,
                'week_new' => $weekNew,
                'week_returning' => $weekReturning,
                'status' => [
                    'pending' => (int) ($statusBreakdown['pending'] ?? 0),
                    'processing' => (int) ($statusBreakdown['processing'] ?? 0),
                    'shipped' => (int) ($statusBreakdown['shipped'] ?? 0),
                    'delivered' => (int) ($statusBreakdown['delivered'] ?? 0),
                    'cancelled' => (int) ($statusBreakdown['cancelled'] ?? 0),
                ],
            ],
            'recent' => $recent,
            'frontend' => rtrim(config('app.frontend_url', 'http://localhost:3000'), '/'),
        ]);
    }
}
