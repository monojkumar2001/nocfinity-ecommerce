@extends('admin.layouts.app')
@section('title', $mode === 'create' ? 'New Dress Style' : 'Edit Dress Style')
@section('content')
<div class="ma-page-title">
    <div><h1>{{ $mode === 'create' ? 'Create dress style' : 'Edit dress style' }}</h1></div>
    <a class="ma-btn-secondary" href="{{ route('admin.dress-styles.index') }}">Back</a>
</div>
@if($errors->any())
    <div class="ma-alert error"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ $mode === 'create' ? route('admin.dress-styles.store') : route('admin.dress-styles.update', $style) }}" class="ma-card ma-form">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif
    <div class="ma-form-grid">
        <label><span>Name</span><input type="text" name="name" value="{{ old('name', $style->name) }}" required></label>
        <label><span>Sort order</span><input type="number" min="0" name="sort_order" value="{{ old('sort_order', $style->sort_order) }}"></label>
    </div>
    <label class="ma-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $style->is_active))><span>Active</span></label>
    <button class="ma-btn-primary" type="submit">{{ $mode === 'create' ? 'Create' : 'Update' }}</button>
</form>
@endsection
