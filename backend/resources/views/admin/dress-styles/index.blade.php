@extends('admin.layouts.app')
@section('title', 'Dress Styles')
@section('content')
<div class="ma-page-title">
    <div><h1>Dress styles</h1><p>Manage Casual / Formal / Party / Gym options</p></div>
    <a class="ma-btn-primary" href="{{ route('admin.dress-styles.create') }}">+ New Style</a>
</div>
@if(session('success'))<div class="ma-alert success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="ma-alert error">{{ session('error') }}</div>@endif
<div class="ma-card">
    <div class="ma-table-wrap">
        <table class="ma-table">
            <thead><tr><th>Name</th><th>Slug</th><th>Order</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($styles as $style)
                <tr>
                    <td>{{ $style->name }}</td>
                    <td>{{ $style->slug }}</td>
                    <td>{{ $style->sort_order }}</td>
                    <td><span class="ma-badge {{ $style->is_active ? 'delivered' : 'cancelled' }}">{{ $style->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="ma-actions">
                        <a class="ma-btn-ghost" href="{{ route('admin.dress-styles.edit', $style) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.dress-styles.destroy', $style) }}" onsubmit="return confirm('Delete dress style?')">@csrf @method('DELETE')
                            <button class="ma-btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="ma-empty">No dress styles yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pager', ['paginator' => $styles])
</div>
@endsection
