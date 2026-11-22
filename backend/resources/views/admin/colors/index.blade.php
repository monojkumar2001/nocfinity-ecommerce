@extends('admin.layouts.app')
@section('title', 'Colors')
@section('content')
<div class="ma-page-title">
    <div><h1>Colors</h1><p>Manage product color options</p></div>
    <a class="ma-btn-primary" href="{{ route('admin.colors.create') }}">+ New Color</a>
</div>
@if(session('success'))<div class="ma-alert success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="ma-alert error">{{ session('error') }}</div>@endif
<div class="ma-card">
    <div class="ma-table-wrap">
        <table class="ma-table">
            <thead><tr><th>Swatch</th><th>Name</th><th>Hex</th><th>Order</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($colors as $color)
                <tr>
                    <td><span style="display:inline-block;width:28px;height:28px;border-radius:999px;border:1px solid rgba(255,255,255,.2);background:{{ $color->hex ?? '#333' }}"></span></td>
                    <td>{{ $color->name }}</td>
                    <td>{{ $color->hex ?? '—' }}</td>
                    <td>{{ $color->sort_order }}</td>
                    <td><span class="ma-badge {{ $color->is_active ? 'delivered' : 'cancelled' }}">{{ $color->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="ma-actions">
                        <a class="ma-btn-ghost" href="{{ route('admin.colors.edit', $color) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.colors.destroy', $color) }}" onsubmit="return confirm('Delete color?')">@csrf @method('DELETE')
                            <button class="ma-btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="ma-empty">No colors yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pager', ['paginator' => $colors])
</div>
@endsection
