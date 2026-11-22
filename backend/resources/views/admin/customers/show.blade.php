@extends('admin.layouts.app')
@section('title', $customer->name)
@section('content')
<div class="ma-page-title">
    <div>
        <h1>{{ $customer->name }}</h1>
        <p>{{ $customer->email }}</p>
    </div>
    <a class="ma-btn-secondary" href="{{ route('admin.customers.index') }}">Back</a>
</div>
<div class="ma-card">
    <h3>Recent orders</h3>
    <div class="ma-table-wrap">
        <table class="ma-table">
            <thead><tr><th>Order</th><th>Status</th><th>Payment</th><th>Total</th><th>Date</th></tr></thead>
            <tbody>
            @forelse($customer->orders as $order)
                <tr>
                    <td><a class="ma-btn-ghost" href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                    <td><span class="ma-badge {{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                    <td><span class="ma-badge {{ $order->payment_status }}">{{ ucfirst($order->payment_status) }}</span></td>
                    <td>${{ number_format((float)$order->total_amount, 2) }}</td>
                    <td>{{ $order->created_at?->format('M d, Y') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="ma-empty">No orders yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
