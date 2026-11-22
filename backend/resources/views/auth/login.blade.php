<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · {{ config('app.name', 'SHOP.CO') }}</title>
    <style>
        :root {
            --bg: #f2f0f1;
            --black: #000;
            --muted: rgba(0,0,0,.6);
            --border: rgba(0,0,0,.1);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, sans-serif;
            background: var(--bg);
            color: var(--black);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px 32px;
        }
        .logo {
            font-weight: 800;
            font-size: 28px;
            letter-spacing: -0.03em;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        h1 {
            font-size: 32px;
            line-height: 1.1;
            margin: 24px 0 8px;
            letter-spacing: -0.02em;
        }
        .sub { color: var(--muted); margin: 0 0 28px; font-size: 15px; }
        label { display: block; font-size: 14px; margin-bottom: 8px; }
        input[type="email"], input[type="password"] {
            width: 100%;
            height: 48px;
            border-radius: 62px;
            border: 1px solid var(--border);
            background: #f0f0f0;
            padding: 0 20px;
            font-size: 14px;
            outline: none;
            margin-bottom: 16px;
        }
        input:focus { border-color: #000; background: #fff; }
        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .row label { margin: 0; display: flex; align-items: center; gap: 8px; color: var(--muted); }
        button {
            width: 100%;
            height: 52px;
            border: 0;
            border-radius: 62px;
            background: #000;
            color: #fff;
            font-size: 15px;
            cursor: pointer;
        }
        button:hover { background: #222; }
        .error {
            background: rgba(255,51,51,.08);
            color: #ff3333;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .hint {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            font-size: 13px;
            color: var(--muted);
            line-height: 1.5;
        }
        .hint code {
            display: block;
            background: #f0f0f0;
            border-radius: 8px;
            padding: 8px 10px;
            margin-top: 6px;
            color: #000;
            font-size: 12px;
        }
        a { color: #000; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">{{ config('app.name', 'SHOP.CO') }}</div>
        <h1>Welcome back</h1>
        <p class="sub">Sign in to continue to the demo dashboard.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', 'admin@nocfinity.test') }}" required autofocus>

            <label for="password">Password</label>
            <input id="password" type="password" name="password" value="Admin@12345" required>

            <div class="row">
                <label><input type="checkbox" name="remember" value="1"> Remember me</label>
                <a href="{{ rtrim(config('app.frontend_url', 'http://localhost:3000'), '/') }}/forgot-password">Forgot password?</a>
            </div>

            <button type="submit">Login</button>
        </form>

        <div class="hint">
            Demo accounts
            <code>admin@nocfinity.test / Admin@12345</code>
            <code>customer@nocfinity.test / Customer@123</code>
            <div style="margin-top:12px">
                Storefront:
                <a href="{{ rtrim(config('app.frontend_url', 'http://localhost:3000'), '/') }}">
                    {{ rtrim(config('app.frontend_url', 'http://localhost:3000'), '/') }}
                </a>
            </div>
        </div>
    </div>
</body>
</html>
