@extends('admin.layouts.app')
@section('title', 'Coupons')
@section('content')
<div class="ma-page-title">
    <div><h1>Coupons</h1><p>Percentage and fixed discounts</p></div>
    <a class="ma-btn-primary" href="{{ route('admin.coupons.create') }}">+ New Coupon</a>
</div>
@if(session('success'))<div class="ma-alert success">{{ session('success') }}</div>@endif
<div class="ma-card">
    <div class="ma-table-wrap">
        <table class="ma-table">
            <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Used</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($coupons as $coupon)
                <tr>
                    <td>{{ $coupon->code }}</td>
                    <td>{{ $coupon->type }}</td>
                    <td>{{ $coupon->type === 'percentage' ? $coupon->value.'%' : '$'.number_format((float)$coupon->value, 2) }}</td>
                    <td>{{ $coupon->used_count }}{{ $coupon->usage_limit ? ' / '.$coupon->usage_limit : '' }}</td>
                    <td><span class="ma-badge {{ $coupon->is_active ? 'delivered' : 'cancelled' }}">{{ $coupon->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="ma-actions">
                        <a class="ma-btn-ghost" href="{{ route('admin.coupons.edit', $coupon) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Delete coupon?')">@csrf @method('DELETE')
                            <button class="ma-btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="ma-empty">No coupons.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pager', ['paginator' => $coupons])
</div>
@endsection
