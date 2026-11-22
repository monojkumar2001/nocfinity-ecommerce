<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Update</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111; line-height: 1.5;">
    <h2>Order {{ $order->order_number }}</h2>
    <p>Hi {{ $order->user->name ?? 'there' }},</p>
    <p>
        Your order status is now:
        <strong>{{ ucfirst($order->status) }}</strong>
        (payment: {{ $order->payment_status }}).
    </p>
    <p>Total: ${{ number_format((float) $order->total_amount, 2) }}</p>
    <ul>
        @foreach ($order->items as $item)
            <li>{{ $item->product_name }} × {{ $item->quantity }} — ${{ number_format((float) $item->total, 2) }}</li>
        @endforeach
    </ul>
    <p>Thank you for shopping with {{ config('app.name') }}.</p>
</body>
</html>
