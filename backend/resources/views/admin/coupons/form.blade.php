@extends('admin.layouts.app')
@section('title', $mode === 'create' ? 'New Coupon' : 'Edit Coupon')
@section('content')
<div class="ma-page-title">
    <div><h1>{{ $mode === 'create' ? 'Create coupon' : 'Edit coupon' }}</h1></div>
    <a class="ma-btn-secondary" href="{{ route('admin.coupons.index') }}">Back</a>
</div>
@if($errors->any())
    <div class="ma-alert error"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ $mode === 'create' ? route('admin.coupons.store') : route('admin.coupons.update', $coupon) }}" class="ma-card ma-form">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif
    <div class="ma-form-grid">
        <label><span>Code</span><input type="text" name="code" value="{{ old('code', $coupon->code) }}" required></label>
        <label>
            <span>Type</span>
            <select name="type" required>
                <option value="percentage" @selected(old('type', $coupon->type)==='percentage')>Percentage</option>
                <option value="fixed" @selected(old('type', $coupon->type)==='fixed')>Fixed</option>
            </select>
        </label>
        <label><span>Value</span><input type="number" step="0.01" min="0" name="value" value="{{ old('value', $coupon->value) }}" required></label>
        <label><span>Minimum order</span><input type="number" step="0.01" min="0" name="minimum_order_amount" value="{{ old('minimum_order_amount', $coupon->minimum_order_amount) }}"></label>
        <label><span>Max discount</span><input type="number" step="0.01" min="0" name="maximum_discount" value="{{ old('maximum_discount', $coupon->maximum_discount) }}"></label>
        <label><span>Usage limit</span><input type="number" min="1" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}"></label>
        <label><span>Starts at</span><input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($coupon->starts_at)->format('Y-m-d\TH:i')) }}"></label>
        <label><span>Expires at</span><input type="datetime-local" name="expires_at" value="{{ old('expires_at', optional($coupon->expires_at)->format('Y-m-d\TH:i')) }}"></label>
    </div>
    <label class="ma-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $coupon->is_active))><span>Active</span></label>
    <button class="ma-btn-primary" type="submit">{{ $mode === 'create' ? 'Create' : 'Update' }}</button>
</form>
@endsection
