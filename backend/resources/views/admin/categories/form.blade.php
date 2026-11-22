@extends('admin.layouts.app')
@section('title', $mode === 'create' ? 'New Category' : 'Edit Category')
@section('content')
<div class="ma-page-title">
    <div><h1>{{ $mode === 'create' ? 'Create category' : 'Edit category' }}</h1></div>
    <a class="ma-btn-secondary" href="{{ route('admin.categories.index') }}">Back</a>
</div>
@if($errors->any())
    <div class="ma-alert error"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ $mode === 'create' ? route('admin.categories.store') : route('admin.categories.update', $category) }}" class="ma-card ma-form">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif
    <div class="ma-form-grid">
        <label><span>Name</span><input type="text" name="name" value="{{ old('name', $category->name) }}" required></label>
        <label>
            <span>Parent</span>
            <select name="parent_id">
                <option value="">None (top level)</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id)==$parent->id)>{{ $parent->name }}</option>
                @endforeach
            </select>
        </label>
    </div>
    <label class="ma-full"><span>Description</span><textarea name="description" rows="4">{{ old('description', $category->description) }}</textarea></label>
    <label class="ma-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $category->is_active))><span>Active</span></label>
    <button class="ma-btn-primary" type="submit">{{ $mode === 'create' ? 'Create' : 'Update' }}</button>
</form>
@endsection
