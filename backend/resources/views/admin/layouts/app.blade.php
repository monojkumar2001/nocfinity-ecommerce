<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') · SHOP.CO Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/master-admin.css') }}">
    @stack('head')
</head>
<body class="master-admin">
@php
    $user = auth('admin')->user();
    $frontend = rtrim(config('app.frontend_url', 'http://localhost:3000'), '/');
    $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=3b82f6&color=fff';
    $can = fn (string $permission) => $user->can($permission);
@endphp

<div class="ma-shell">
    <aside class="ma-sidebar" id="maSidebar">
        <div class="ma-brand">
            <a class="ma-brand-logo" href="{{ route('admin.dashboard') }}" aria-label="SHOP.CO Admin">
                <img src="{{ asset('images/shopco-logo.png') }}" alt="SHOP.CO">
            </a>
        </div>

        <div class="ma-welcome">
            <img src="{{ $avatar }}" alt="{{ $user->name }}">
            <div>
                <span>Welcome</span>
                <strong>{{ $user->name }}</strong>
            </div>
        </div>

        <nav class="ma-nav">
            <div class="ma-nav-label">Menu</div>
            @if($can('reports.view'))
            <a class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 13h6V4H4v9zm0 7h6v-5H4v5zm8 0h6V11h-6v9zm0-18v7h6V2h-6z"/></svg>
                Dashboard
            </a>
            @endif
            @if($can('products.view'))
            <a class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
                Products
            </a>
            @endif
            @if($can('orders.view'))
            <a class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 7h12l1 12H5L6 7z"/><path d="M9 7V5a3 3 0 016 0v2"/></svg>
                Orders
            </a>
            @endif

            @if($can('categories.view') || $can('attributes.manage') || $can('coupons.manage'))
            <div class="ma-nav-label">Catalog</div>
            @endif
            @if($can('categories.view'))
            <a class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/></svg>
                Categories
            </a>
            @endif
            @if($can('attributes.manage'))
            <a class="{{ request()->routeIs('admin.colors.*') ? 'active' : '' }}" href="{{ route('admin.colors.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/></svg>
                Colors
            </a>
            <a class="{{ request()->routeIs('admin.sizes.*') ? 'active' : '' }}" href="{{ route('admin.sizes.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M7 7v10M17 7v10M10 17h4"/></svg>
                Sizes
            </a>
            <a class="{{ request()->routeIs('admin.dress-styles.*') ? 'active' : '' }}" href="{{ route('admin.dress-styles.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 4l6 3 6-3v6l-6 10L6 10V4z"/></svg>
                Dress Styles
            </a>
            @endif
            @if($can('coupons.manage'))
            <a class="{{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}" href="{{ route('admin.coupons.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 9a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2a2 2 0 100-4V9z"/></svg>
                Coupons
            </a>
            @endif

            @if($can('customers.view') || $can('roles.manage'))
            <div class="ma-nav-label">Users</div>
            @endif
            @if($can('customers.view'))
            <a class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}" href="{{ route('admin.customers.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="3"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                Customers
            </a>
            @endif
            @if($can('roles.manage'))
            <a class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7l8-4z"/></svg>
                Roles
            </a>
            @endif

            <div class="ma-nav-label">Account</div>
            <a class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.edit') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.8-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-1-1.5 1.7 1.7 0 00-1.8.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.8 1.7 1.7 0 00-1.5-1H3a2 2 0 110-4h.1a1.7 1.7 0 001.5-1 1.7 1.7 0 00-.3-1.8l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.8.3H9a1.7 1.7 0 001-1.5V3a2 2 0 114 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.8-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.8V9c.2.6.7 1 1.5 1H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z"/></svg>
                Settings
            </a>
        </nav>

        <div class="ma-sidebar-foot">
            SHOP.CO Admin © {{ date('Y') }}<br>All Rights Reserved.
        </div>
    </aside>

    <div class="ma-main">
        <header class="ma-topbar">
            <button class="ma-icon-btn" type="button" id="maMenuBtn" aria-label="Toggle menu">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <div class="ma-search">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
                <input type="search" placeholder="Search here...">
            </div>
            <div class="ma-top-actions">
                <button class="ma-icon-btn" type="button" title="Messages">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 01-4 4H8l-5 3V7a4 4 0 014-4h10a4 4 0 014 4z"/></svg>
                </button>
                <button class="ma-icon-btn" type="button" title="Notifications">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 10-12 0c0 7-3 7-3 7h18s-3 0-3-7"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
                </button>
                <img class="ma-avatar" src="{{ $avatar }}" alt="{{ $user->name }}">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="ma-icon-btn" type="submit" title="Logout">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                    </button>
                </form>
            </div>
        </header>

        <main class="ma-content">
            @yield('content')
        </main>
    </div>
</div>

<script>
document.getElementById('maMenuBtn')?.addEventListener('click', () => {
    document.getElementById('maSidebar')?.classList.toggle('open');
});
</script>
@stack('scripts')
</body>
</html>
