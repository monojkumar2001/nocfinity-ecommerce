@extends('admin.layouts.app')
@section('title', 'Sizes')
@section('content')
<div class="ma-page-title">
    <div><h1>Sizes</h1><p>Manage product size options</p></div>
    <a class="ma-btn-primary" href="{{ route('admin.sizes.create') }}">+ New Size</a>
</div>
@if(session('success'))<div class="ma-alert success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="ma-alert error">{{ session('error') }}</div>@endif
<div class="ma-card">
    <div class="ma-table-wrap">
        <table class="ma-table">
            <thead><tr><th>Name</th><th>Order</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($sizes as $size)
                <tr>
                    <td>{{ $size->name }}</td>
                    <td>{{ $size->sort_order }}</td>
                    <td><span class="ma-badge {{ $size->is_active ? 'delivered' : 'cancelled' }}">{{ $size->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="ma-actions">
                        <a class="ma-btn-ghost" href="{{ route('admin.sizes.edit', $size) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.sizes.destroy', $size) }}" onsubmit="return confirm('Delete size?')">@csrf @method('DELETE')
                            <button class="ma-btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="ma-empty">No sizes yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pager', ['paginator' => $sizes])
</div>
@endsection
