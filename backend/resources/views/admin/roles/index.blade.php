@extends('admin.layouts.app')
@section('title', 'Roles')
@section('content')
<div class="ma-page-title">
    <div><h1>Roles & permissions</h1><p>Manage staff access matrix</p></div>
    <a class="ma-btn-secondary" href="{{ route('admin.roles.staff') }}">Staff assignments</a>
</div>
@if(session('success'))<div class="ma-alert success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="ma-alert error">{{ session('error') }}</div>@endif

<div class="ma-card" style="margin-bottom:16px">
    <h3>Create role</h3>
    <form method="POST" action="{{ route('admin.roles.store') }}" class="ma-form">
        @csrf
        <label><span>Role name</span><input type="text" name="name" required></label>
        <div class="ma-form-grid">
            @foreach($permissions as $permission)
                <label class="ma-check"><input type="checkbox" name="permissions[]" value="{{ $permission->name }}"><span>{{ $permission->name }}</span></label>
            @endforeach
        </div>
        <button class="ma-btn-primary" type="submit">Create role</button>
    </form>
</div>

@foreach($roles as $role)
    <div class="ma-card" style="margin-bottom:16px">
        <div class="ma-page-title" style="margin-bottom:12px">
            <div>
                <h3 style="margin:0">{{ $role->name }}</h3>
                <p class="ma-muted-xs">{{ $role->users_count }} users</p>
            </div>
            @if($role->name !== 'Super Admin')
                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete role?')">@csrf @method('DELETE')
                    <button class="ma-btn-danger" type="submit">Delete</button>
                </form>
            @endif
        </div>
        @if($role->name === 'Super Admin')
            <p class="ma-muted-xs">Protected role — all permissions.</p>
        @else
            <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="ma-form">
                @csrf
                @method('PUT')
                <label><span>Name</span><input type="text" name="name" value="{{ $role->name }}" required></label>
                <div class="ma-form-grid">
                    @foreach($permissions as $permission)
                        <label class="ma-check">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked($role->permissions->contains('name', $permission->name))>
                            <span>{{ $permission->name }}</span>
                        </label>
                    @endforeach
                </div>
                <button class="ma-btn-primary" type="submit">Update role</button>
            </form>
        @endif
    </div>
@endforeach
@endsection
