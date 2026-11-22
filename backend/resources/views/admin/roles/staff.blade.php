@extends('admin.layouts.app')
@section('title', 'Staff Roles')
@section('content')
<div class="ma-page-title">
    <div><h1>Staff assignments</h1><p>Assign roles to staff users</p></div>
    <a class="ma-btn-secondary" href="{{ route('admin.roles.index') }}">Back to roles</a>
</div>
@if(session('success'))<div class="ma-alert success">{{ session('success') }}</div>@endif
@if(session('error'))<div class="ma-alert error">{{ session('error') }}</div>@endif
@foreach($staff as $user)
    <div class="ma-card" style="margin-bottom:16px">
        <h3 style="margin:0 0 6px">{{ $user->name }}</h3>
        <p class="ma-muted-xs">{{ $user->email }}</p>
        <form method="POST" action="{{ route('admin.roles.staff.update', $user) }}" class="ma-form" style="margin-top:12px">
            @csrf
            @method('PUT')
            <div class="ma-form-grid">
                @foreach($roles as $role)
                    <label class="ma-check">
                        <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                            @checked($user->roles->contains('name', $role->name))
                            @disabled($user->hasRole('Super Admin') && $role->name === 'Super Admin')>
                        <span>{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>
            @unless($user->hasRole('Super Admin'))
                <button class="ma-btn-primary" type="submit">Save roles</button>
            @else
                <p class="ma-muted-xs">Super Admin is protected.</p>
            @endunless
        </form>
    </div>
@endforeach
@endsection
