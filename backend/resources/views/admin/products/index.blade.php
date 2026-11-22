@extends('admin.layouts.app')

@section('title', 'Products')

@section('content')
<div class="ma-page-title">
    <div>
        <h1>Products</h1>
        <p>Create, edit and manage catalog items</p>
    </div>
    @can('products.create')
    <a class="ma-btn-primary" href="{{ route('admin.products.create') }}">+ New Product</a>
    @endcan
</div>

@if(session('success'))
    <div class="ma-alert success">{{ session('success') }}</div>
@endif

<div class="ma-card" style="margin-bottom:16px">
    <form method="GET" action="{{ route('admin.products.index') }}" class="ma-inline-form">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search name or SKU…">
        <button type="submit" class="ma-btn-secondary">Search</button>
        @if(request('search'))
            <a href="{{ route('admin.products.index') }}" class="ma-btn-ghost">Clear</a>
        @endif
    </form>
</div>

<div class="ma-card">
    <div class="ma-table-wrap">
        @if($products->isEmpty())
            <div class="ma-empty">No products found.</div>
        @else
            <table class="ma-table">
                <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Style</th>
                    <th>Color / Size</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach($products as $product)
                    @php
                        $thumb = optional($product->images->sortByDesc('is_primary')->first())->image_path;
                    @endphp
                    <tr>
                        <td>
                            <div class="ma-product-cell">
                                @if($thumb)
                                    <img src="{{ $thumb }}" alt="">
                                @else
                                    <span class="ma-thumb-ph"></span>
                                @endif
                                <div>
                                    <strong>{{ $product->name }}</strong>
                                    <div class="ma-muted-xs">#{{ $product->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $product->sku }}</td>
                        <td>{{ $product->category?->name ?? '—' }}</td>
                        <td>{{ $product->dress_style ?? '—' }}</td>
                        <td>{{ $product->color ?? '—' }} / {{ $product->size ?? '—' }}</td>
                        <td>${{ number_format((float) $product->price, 2) }}</td>
                        <td>{{ $product->stock }}</td>
                        <td>
                            <span class="ma-badge {{ $product->is_active ? 'delivered' : 'cancelled' }}">
                                {{ $product->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="ma-actions">
                            @can('products.edit')
                            <a href="{{ route('admin.products.edit', $product) }}" class="ma-btn-ghost">Edit</a>
                            @endcan
                            @can('products.delete')
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ma-btn-danger">Delete</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    @if($products->hasPages())
        <div class="ma-pagination" style="display:flex;gap:10px;align-items:center;justify-content:flex-end">
            @if($products->onFirstPage())
                <span class="ma-btn-secondary" style="opacity:.5">Previous</span>
            @else
                <a class="ma-btn-secondary" href="{{ $products->previousPageUrl() }}">Previous</a>
            @endif
            <span>Page {{ $products->currentPage() }} / {{ $products->lastPage() }}</span>
            @if($products->hasMorePages())
                <a class="ma-btn-secondary" href="{{ $products->nextPageUrl() }}">Next</a>
            @else
                <span class="ma-btn-secondary" style="opacity:.5">Next</span>
            @endif
        </div>
    @endif
</div>
@endsection
