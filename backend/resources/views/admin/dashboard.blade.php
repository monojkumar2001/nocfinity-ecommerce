@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $fmt = fn ($n) => number_format((float) $n, 0);
    $money = fn ($n) => '$' . number_format((float) $n, 2);
    $chg = fn ($n) => ($n > 0 ? '+' : '') . number_format((float) $n, 1) . '%';
@endphp

<div class="ma-page-title">
    <div>
        <h1>Dashboard</h1>
        <p>Ecommerce overview for {{ config('app.name') }}</p>
    </div>
</div>

<div class="ma-stats">
    <div class="ma-stat blue">
        <div class="ma-stat-top">
            <span>Revenue</span>
            <a class="ma-view" href="{{ $frontend }}/admin/orders" target="_blank">View</a>
        </div>
        <h2>{{ $money($stats['revenue']) }}</h2>
        <div class="trend {{ $stats['revenue_change'] >= 0 ? 'up' : 'down' }}">
            <svg class="ma-spark" viewBox="0 0 92 34" fill="none">
                <path d="M1 24 C18 26, 24 8, 40 14 S64 30, 91 6" stroke="rgba(255,255,255,.75)" stroke-width="2" fill="none"/>
            </svg>
            <span>{{ $chg($stats['revenue_change']) }} vs prior 30d</span>
        </div>
    </div>

    <div class="ma-stat teal">
        <div class="ma-stat-top">
            <span>Orders</span>
            <a class="ma-view" href="{{ $frontend }}/admin/orders" target="_blank">View</a>
        </div>
        <h2>{{ $fmt($stats['orders']) }}</h2>
        <div class="trend {{ $stats['orders_change'] >= 0 ? 'up' : 'down' }}">
            <svg class="ma-spark" viewBox="0 0 92 34" fill="none">
                <path d="M1 10 C20 8, 28 28, 44 20 S68 4, 91 22" stroke="rgba(255,255,255,.75)" stroke-width="2" fill="none"/>
            </svg>
            <span>{{ $chg($stats['orders_change']) }} vs prior 30d</span>
        </div>
    </div>

    <div class="ma-stat slate">
        <div class="ma-stat-top">
            <span>Customers</span>
            <a class="ma-view" href="{{ $frontend }}/admin/customers" target="_blank">View</a>
        </div>
        <h2>{{ $fmt($stats['customers']) }}</h2>
        <div class="trend {{ $stats['customers_change'] >= 0 ? 'up' : 'down' }}">
            <svg class="ma-spark" viewBox="0 0 92 34" fill="none">
                <path d="M1 26 C16 24, 26 10, 40 12 S62 28, 91 8" stroke="rgba(255,255,255,.75)" stroke-width="2" fill="none"/>
            </svg>
            <span>{{ $chg($stats['customers_change']) }} new trend</span>
        </div>
    </div>
</div>

<div class="ma-grid-2">
    <div class="ma-card">
        <h3>Sales Audience</h3>
        <div class="ma-metrics">
            <div class="ma-metric">
                <span>Products</span>
                <strong>{{ $fmt($stats['products']) }}</strong>
            </div>
            <div class="ma-metric">
                <span>Today Orders</span>
                <strong>{{ $fmt($stats['todays_orders']) }}</strong>
            </div>
            <div class="ma-metric">
                <span>Today Revenue</span>
                <strong>{{ $money($stats['todays_revenue']) }}</strong>
            </div>
            <div class="ma-metric">
                <span>Low Stock</span>
                <strong>{{ $fmt($stats['low_stock']) }}</strong>
            </div>
        </div>
        <div class="ma-chart-wrap">
            <canvas id="audienceChart"></canvas>
        </div>
    </div>

    <div class="ma-card">
        <h3>Last Week</h3>
        <div class="ma-chart-wrap">
            <canvas id="weekChart"></canvas>
        </div>
        <div class="ma-fab-col">
            <button class="ma-fab green" type="button" title="Refresh">+</button>
            <button class="ma-fab red" type="button" title="Alert">!</button>
            <button class="ma-fab yellow" type="button" title="Notes">…</button>
        </div>
    </div>
</div>

<div class="ma-grid-3">
    <div class="ma-card">
        <h3>Monthly Sale</h3>
        <div class="ma-kpi">
            <strong>{{ $money($stats['monthly_sale']) }}</strong>
            <span style="color:var(--ma-muted);font-size:12px">{{ now()->format('M Y') }}</span>
        </div>
        @php
            $goal = max($stats['monthly_sale'] * 1.25, 1);
            $pct = min(100, round(($stats['monthly_sale'] / $goal) * 100));
        @endphp
        <div class="ma-progress" style="--p: {{ $pct }}%"><i></i></div>
        <p style="color:var(--ma-muted);font-size:12px;margin:12px 0 0">{{ $pct }}% of stretch target</p>
    </div>

    <div class="ma-card">
        <h3>Total Growth</h3>
        <div class="ma-chart-wrap sm">
            <canvas id="growthChart"></canvas>
        </div>
    </div>

    <div class="ma-card">
        <h3>Order Status</h3>
        <div class="ma-chart-wrap xs">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

<div class="ma-card">
    <h3>Recent Orders</h3>
    <div class="ma-table-wrap">
        @if($recent->isEmpty())
            <div class="ma-empty">No orders yet. Place a checkout from the storefront to populate this table.</div>
        @else
            <table class="ma-table">
                <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Total</th>
                    <th>Date</th>
                </tr>
                </thead>
                <tbody>
                @foreach($recent as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->user?->name ?? 'Guest' }}</td>
                        <td><span class="ma-badge {{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                        <td><span class="ma-badge {{ $order->payment_status }}">{{ ucfirst($order->payment_status) }}</span></td>
                        <td>{{ $money($order->total_amount) }}</td>
                        <td>{{ optional($order->created_at)->format('M d, H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
const labels = @json($charts['audience_labels']);
const revenue = @json($charts['audience_revenue']);
const orders = @json($charts['audience_orders']);
const weekLabels = @json($charts['week_labels']);
const weekNew = @json($charts['week_new']);
const weekReturning = @json($charts['week_returning']);
const statusData = @json(array_values($charts['status']));
const statusLabels = @json(array_keys($charts['status']));

Chart.defaults.color = '#8b93ad';
Chart.defaults.borderColor = 'rgba(255,255,255,0.06)';
Chart.defaults.font.family = 'Inter, system-ui, sans-serif';

new Chart(document.getElementById('audienceChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [
            {
                label: 'Revenue',
                data: revenue,
                borderColor: '#ec4899',
                backgroundColor: 'rgba(236,72,153,.18)',
                fill: true,
                tension: .4,
                pointRadius: 0,
            },
            {
                label: 'Orders',
                data: orders,
                borderColor: '#38bdf8',
                backgroundColor: 'rgba(56,189,248,.12)',
                fill: true,
                tension: .4,
                pointRadius: 0,
                yAxisID: 'y1',
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { grid: { color: 'rgba(255,255,255,.05)' } },
            y1: { position: 'right', grid: { drawOnChartArea: false } },
        },
    },
});

new Chart(document.getElementById('weekChart'), {
    type: 'bar',
    data: {
        labels: weekLabels,
        datasets: [
            { label: 'New Users', data: weekNew, backgroundColor: '#f43f5e', borderRadius: 6, barPercentage: .55 },
            { label: 'Active Buyers', data: weekReturning, backgroundColor: '#3b82f6', borderRadius: 6, barPercentage: .55 },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { labels: { boxWidth: 10 } } },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(255,255,255,.05)' } },
        },
    },
});

new Chart(document.getElementById('growthChart'), {
    type: 'line',
    data: {
        labels,
        datasets: [{
            data: revenue,
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34,197,94,.12)',
            fill: true,
            tension: .35,
            pointRadius: 0,
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { display: false },
            y: { display: false },
        },
    },
});

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: statusLabels.map((s) => s.charAt(0).toUpperCase() + s.slice(1)),
        datasets: [{
            data: statusData,
            backgroundColor: ['#f59e0b', '#3b82f6', '#38bdf8', '#22c55e', '#f43f5e'],
            borderWidth: 0,
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '62%',
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
    },
});
</script>
@endpush
