<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard · {{ config('app.name') }}</title>
    <style>
        body { margin:0; font-family: Inter, system-ui, sans-serif; background:#f2f0f1; color:#000; }
        .wrap { max-width: 920px; margin: 0 auto; padding: 40px 20px; }
        .card { background:#fff; border:1px solid rgba(0,0,0,.1); border-radius:20px; padding:28px; }
        h1 { margin:0 0 8px; font-size:32px; }
        .muted { color:rgba(0,0,0,.6); }
        .grid { display:grid; gap:14px; margin-top:24px; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); }
        a.btn {
            display:flex; align-items:center; justify-content:center; height:52px;
            border-radius:62px; text-decoration:none; font-size:14px;
        }
        .primary { background:#000; color:#fff; }
        .secondary { background:#fff; color:#000; border:1px solid rgba(0,0,0,.1); }
        form { margin-top: 20px; }
        button {
            height:44px; padding:0 20px; border-radius:62px; border:0; background:#fff;
            border:1px solid rgba(0,0,0,.1); cursor:pointer;
        }
        .pill { display:inline-block; background:#f0f0f0; border-radius:999px; padding:6px 12px; font-size:13px; margin-right:6px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Hello, {{ $user->name }}</h1>
        <p class="muted">{{ $user->email }}</p>
        <div style="margin-top:12px">
            @foreach($user->getRoleNames() as $role)
                <span class="pill">{{ $role }}</span>
            @endforeach
            @if($user->getRoleNames()->isEmpty())
                <span class="pill">Customer</span>
            @endif
        </div>

        <div class="grid">
            <a class="btn primary" href="{{ $frontend }}">Open Storefront</a>
            <a class="btn secondary" href="{{ $frontend }}/products">Browse Products</a>
            @if($isStaff)
                <a class="btn secondary" href="{{ route('admin.dashboard') }}">Master Admin</a>
                <a class="btn secondary" href="{{ $frontend }}/admin">Next Admin</a>
            @else
                <a class="btn secondary" href="{{ $frontend }}/orders">My Orders</a>
            @endif
            <a class="btn secondary" href="{{ url('/api/v1/health') }}" target="_blank">API Health</a>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>
</div>
</body>
</html>
