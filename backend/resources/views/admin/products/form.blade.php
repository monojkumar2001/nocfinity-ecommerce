@extends('admin.layouts.app')

@section('title', $mode === 'create' ? 'New Product' : 'Edit Product')

@section('content')
<div class="ma-page-title">
    <div>
        <h1>{{ $mode === 'create' ? 'Create product' : 'Edit product' }}</h1>
        <p>{{ $mode === 'create' ? 'Add a new catalog item' : 'Update “'.$product->name.'”' }}</p>
    </div>
    <a class="ma-btn-secondary" href="{{ route('admin.products.index') }}">Back to list</a>
</div>

@if($errors->any())
    <div class="ma-alert error">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form
    method="POST"
    action="{{ $mode === 'create' ? route('admin.products.store') : route('admin.products.update', $product) }}"
    enctype="multipart/form-data"
    class="ma-card ma-form"
>
    @csrf
    @if($mode === 'edit')
        @method('PUT')
    @endif

    <div class="ma-form-grid">
        <label>
            <span>Name</span>
            <input type="text" name="name" value="{{ old('name', $product->name) }}" required>
        </label>

        <label>
            <span>SKU</span>
            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required>
        </label>

        <label>
            <span>Category</span>
            <select name="category_id" required>
                <option value="">Select category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                        {{ $category->name }} ({{ $category->slug }})
                    </option>
                @endforeach
            </select>
        </label>

        <label>
            <span>Price</span>
            <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" required>
        </label>

        <label>
            <span>Compare at price</span>
            <input type="number" step="0.01" min="0" name="compare_at_price" value="{{ old('compare_at_price', $product->compare_at_price) }}">
        </label>

        <label>
            <span>Stock</span>
            <input type="number" min="0" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" required>
        </label>

        <label>
            <span>Color</span>
            <select name="color">
                <option value="">Select color</option>
                @foreach($colors as $color)
                    <option value="{{ $color }}" @selected(old('color', $product->color) === $color)>{{ $color }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span>Size</span>
            <select name="size">
                <option value="">Select size</option>
                @foreach($sizes as $size)
                    <option value="{{ $size }}" @selected(old('size', $product->size) === $size)>{{ $size }}</option>
                @endforeach
            </select>
        </label>

        <label>
            <span>Dress style</span>
            <select name="dress_style">
                <option value="">Select style</option>
                @foreach($dressStyles as $style)
                    <option value="{{ $style }}" @selected(old('dress_style', $product->dress_style) === $style)>{{ $style }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <label class="ma-full">
        <span>Description</span>
        <textarea name="description" rows="5">{{ old('description', $product->description) }}</textarea>
    </label>

    <label class="ma-check">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active))>
        <span>Active (visible on storefront)</span>
    </label>

    <div class="ma-form-grid">
        <label class="ma-full">
            <span>Upload images</span>
            <input type="file" name="images[]" accept="image/*" multiple>
        </label>
        <label class="ma-full">
            <span>Or image URL</span>
            <input type="url" name="image_url" value="{{ old('image_url') }}" placeholder="https://…">
        </label>
    </div>

    @if($mode === 'edit' && $product->images->isNotEmpty())
        <div class="ma-image-row">
            @foreach($product->images as $image)
                <img src="{{ $image->image_path }}" alt="{{ $image->alt_text }}">
            @endforeach
        </div>
    @endif

    <div class="ma-form-actions">
        <button type="submit" class="ma-btn-primary">
            {{ $mode === 'create' ? 'Create product' : 'Update product' }}
        </button>
    </div>
</form>
@endsection
