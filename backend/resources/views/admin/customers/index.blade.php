@extends('admin.layouts.app')
@section('title', 'Customers')
@section('content')
<div class="ma-page-title">
    <div><h1>Customers</h1><p>Registered storefront customers</p></div>
</div>
<div class="ma-card" style="margin-bottom:16px">
    <form method="GET" class="ma-inline-form">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name or email…">
        <button class="ma-btn-secondary" type="submit">Search</button>
    </form>
</div>
<div class="ma-card">
    <div class="ma-table-wrap">
        <table class="ma-table">
            <thead><tr><th>Name</th><th>Email</th><th>Orders</th><th>Joined</th><th></th></tr></thead>
            <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->orders_count }}</td>
                    <td>{{ $customer->created_at?->format('M d, Y') }}</td>
                    <td class="ma-actions"><a class="ma-btn-ghost" href="{{ route('admin.customers.show', $customer) }}">View</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="ma-empty">No customers.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pager', ['paginator' => $customers])
</div>
@endsection
