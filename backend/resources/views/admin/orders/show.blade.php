@extends('admin.layouts.app')
@section('title', 'Order '.$order->order_number)
@section('content')
<div class="ma-page-title">
    <div>
        <h1>{{ $order->order_number }}</h1>
        <p>{{ $order->user?->name }} · {{ $order->user?->email }}</p>
    </div>
    <a class="ma-btn-secondary" href="{{ route('admin.orders.index') }}">Back</a>
</div>
@if(session('success'))<div class="ma-alert success">{{ session('success') }}</div>@endif
<div class="ma-grid-2">
    <div class="ma-card">
        <h3>Items</h3>
        <div class="ma-table-wrap">
            <table class="ma-table">
                <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}<div class="ma-muted-xs">{{ $item->product_sku }}</div></td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format((float)$item->price, 2) }}</td>
                        <td>${{ number_format((float)$item->total, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="ma-card">
        <h3>Summary</h3>
        <p class="ma-muted-xs">Status: <span class="ma-badge {{ $order->status }}">{{ ucfirst($order->status) }}</span></p>
        <p class="ma-muted-xs" style="margin-top:8px">Payment: <span class="ma-badge {{ $order->payment_status }}">{{ ucfirst($order->payment_status) }}</span></p>
        <p style="margin-top:14px">Subtotal: ${{ number_format((float)$order->subtotal, 2) }}</p>
        <p>Discount: ${{ number_format((float)$order->discount_amount, 2) }}</p>
        <p><strong>Total: ${{ number_format((float)$order->total_amount, 2) }}</strong></p>
        @can('orders.update_status')
        @if(!empty($allowed))
            <form method="POST" action="{{ route('admin.orders.status', $order) }}" style="margin-top:18px" class="ma-inline-form">
                @csrf
                @method('PATCH')
                <select name="status" style="height:40px;border-radius:12px;border:1px solid var(--ma-border);background:var(--ma-panel-2);color:var(--ma-text);padding:0 12px">
                    @foreach($allowed as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <button class="ma-btn-primary" type="submit">Update status</button>
            </form>
        @endif
        @endcan
    </div>
</div>
@endsection
