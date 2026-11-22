@extends('admin.layouts.app')
@section('title', 'Categories')
@section('content')
<div class="ma-page-title">
    <div><h1>Categories</h1><p>Organize the product catalog</p></div>
    @can('categories.create')
    <a class="ma-btn-primary" href="{{ route('admin.categories.create') }}">+ New Category</a>
    @endcan
</div>
@if(session('success'))<div class="ma-alert success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="ma-alert error">{{ session('error') }}</div>@endif
<div class="ma-card">
    <div class="ma-table-wrap">
        <table class="ma-table">
            <thead><tr><th>Name</th><th>Slug</th><th>Parent</th><th>Products</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($categories as $category)
                <tr>
                    <td>{{ $category->name }}</td>
                    <td>{{ $category->slug }}</td>
                    <td>{{ $category->parent?->name ?? '—' }}</td>
                    <td>{{ $category->products_count }}</td>
                    <td><span class="ma-badge {{ $category->is_active ? 'delivered' : 'cancelled' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td class="ma-actions">
                        @can('categories.edit')
                        <a class="ma-btn-ghost" href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                        @endcan
                        @can('categories.delete')
                        <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete category?')">@csrf @method('DELETE')
                            <button class="ma-btn-danger" type="submit">Delete</button>
                        </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="ma-empty">No categories.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('admin.partials.pager', ['paginator' => $categories])
</div>
@endsection
