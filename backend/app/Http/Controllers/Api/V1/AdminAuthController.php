<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'admin-login:' . strtolower($validated['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return ApiResponse::error(
                'Too many login attempts. Please try again later.',
                429,
                ['retry_after' => RateLimiter::availableIn($key)]
            );
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !$user->isStaff() || !Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return ApiResponse::error('Invalid email or password.', 401);
        }

        RateLimiter::clear($key);

        // Session admin guard when available (SPA/Blade); always issue admin-scoped Sanctum token.
        if ($request->hasSession()) {
            Auth::guard('admin')->login($user);
        }

        $token = $user->createToken('admin-token', ['admin'])->plainTextToken;

        return ApiResponse::success([
            'user' => $this->userPayload($user),
            'token' => $token,
            'token_type' => 'Bearer',
            'guard' => 'admin',
        ], 'Admin login successful.');
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user?->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        if (Auth::guard('admin')->check()) {
            Auth::guard('admin')->logout();
        }

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return ApiResponse::success(null, 'Admin logout successful.');
    }

    public function me(Request $request)
    {
        return ApiResponse::success([
            'user' => $this->userPayload($request->user()),
            'guard' => 'admin',
        ]);
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing('roles.permissions');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => optional($user->email_verified_at)?->toIso8601String(),
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'is_staff' => true,
        ];
    }
}
