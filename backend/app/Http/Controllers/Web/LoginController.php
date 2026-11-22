<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\StaffAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route(StaffAccess::firstAllowedRoute(Auth::guard('admin')->user()));
        }

        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'web-login:' . strtolower($credentials['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => ['Too many login attempts. Please try again later.'],
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();
        $valid = $user && Hash::check($credentials['password'], $user->password);

        if (!$valid) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => ['Invalid email or password.'],
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $remember = $request->boolean('remember');

        if ($user->isStaff()) {
            Auth::guard('admin')->login($user, $remember);

            return redirect()->intended(route(StaffAccess::firstAllowedRoute($user)));
        }

        Auth::guard('web')->login($user, $remember);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function dashboard()
    {
        $user = Auth::guard('web')->user() ?? Auth::guard('admin')->user();
        $frontend = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');

        return view('auth.dashboard', [
            'user' => $user,
            'frontend' => $frontend,
            'isStaff' => $user?->isStaff() ?? false,
        ]);
    }
}
