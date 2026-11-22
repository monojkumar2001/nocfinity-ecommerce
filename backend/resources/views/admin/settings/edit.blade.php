@extends('admin.layouts.app')

@section('title', 'Settings')

@section('content')
<div class="ma-page-title">
    <div>
        <h1>Settings</h1>
        <p>Update your profile name and password</p>
    </div>
</div>

@if(session('status'))
    <div class="ma-alert success">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="ma-alert error">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.settings.update') }}" class="ma-card ma-form" style="max-width: 520px;">
    @csrf
    @method('PUT')

    <label>
        <span>Email</span>
        <input type="email" value="{{ $user->email }}" disabled>
    </label>

    <label>
        <span>Display name</span>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
    </label>

    <label>
        <span>Current password</span>
        <input type="password" name="current_password" autocomplete="current-password">
    </label>

    <label>
        <span>New password</span>
        <input type="password" name="password" autocomplete="new-password" placeholder="Leave blank to keep current">
    </label>

    <label>
        <span>Confirm new password</span>
        <input type="password" name="password_confirmation" autocomplete="new-password">
    </label>

    <p style="margin: 0 0 16px; font-size: 12px; color: var(--ma-muted);">
        Password must be at least 8 characters with upper, lower, number, and special character.
    </p>

    <button class="ma-btn-primary" type="submit">Save settings</button>
</form>
@endsection
