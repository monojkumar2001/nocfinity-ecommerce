@extends('admin.layouts.app')
@section('title', $mode === 'create' ? 'New Color' : 'Edit Color')
@section('content')
<div class="ma-page-title">
    <div><h1>{{ $mode === 'create' ? 'Create color' : 'Edit color' }}</h1></div>
    <a class="ma-btn-secondary" href="{{ route('admin.colors.index') }}">Back</a>
</div>
@if($errors->any())
    <div class="ma-alert error"><ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
@endif
<form method="POST" action="{{ $mode === 'create' ? route('admin.colors.store') : route('admin.colors.update', $color) }}" class="ma-card ma-form">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif
    <div class="ma-form-grid">
        <label><span>Name</span><input type="text" name="name" value="{{ old('name', $color->name) }}" required></label>
        <label><span>Hex</span><input type="text" name="hex" value="{{ old('hex', $color->hex) }}" placeholder="#000000"></label>
        <label><span>Sort order</span><input type="number" min="0" name="sort_order" value="{{ old('sort_order', $color->sort_order) }}"></label>
    </div>
    <label class="ma-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $color->is_active))><span>Active</span></label>
    <button class="ma-btn-primary" type="submit">{{ $mode === 'create' ? 'Create' : 'Update' }}</button>
</form>
@endsection
