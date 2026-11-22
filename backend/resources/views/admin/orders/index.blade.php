@extends('admin.layouts.app')
@section('title', 'Orders')
@section('content')
<div class="ma-page-title">
    <div>
        <h1>Orders</h1>
        <p>Track and update customer orders</p>
    </div>
</div>
@if(session('success'))<div class="ma-alert success">{{ session('success') }}</div>@endif
<div class="ma-card" style="margin-bottom:16px">
    <form method="GET" class="ma-inline-form">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Order # or customer…">
        <select name="status" style="height:40px;border-radius:12px;border:1px solid var(--ma-border);background:var(--ma-panel-2);color:var(--ma-text);padding:0 12px">
            <option value="">All statuses</option>
            @foreach(['pending','processing','shipped','delivered','cancelled'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button class="ma-btn-secondary" type="submit">Filter</button>
    </form>
</div>
<div class="ma-card">
    <div class="ma-table-wrap">
        @if($orders->isEmpty())
            <div class="ma-empty">No orders found.</div>
        @else
            <table class="ma-table">
                <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Payment</th><th>Total</th><th>Date</th><th></th></tr></thead>
                <tbody>
                @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->order_number }}</td>
                        <td>{{ $order->user?->name ?? '—' }}<div class="ma-muted-xs">{{ $order->user?->email }}</div></td>
                        <td><span class="ma-badge {{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                        <td><span class="ma-badge {{ $order->payment_status }}">{{ ucfirst($order->payment_status) }}</span></td>
                        <td>${{ number_format((float)$order->total_amount, 2) }}</td>
                        <td>{{ $order->created_at?->format('M d, Y H:i') }}</td>
                        <td class="ma-actions"><a class="ma-btn-ghost" href="{{ route('admin.orders.show', $order) }}">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
    @include('admin.partials.pager', ['paginator' => $orders])
</div>
@endsection
