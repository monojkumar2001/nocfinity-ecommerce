<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => \App\Support\PasswordRules::required(),
        ], \App\Support\PasswordRules::messages());

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        event(new Registered($user));

        $guestToken = $request->header('X-Cart-Token');
        $this->cartService->mergeGuestCartIntoUser($user, $guestToken);

        $token = $user->createToken('customer-token', ['customer'])->plainTextToken;

        return ApiResponse::success([
            'user' => $this->userPayload($user->fresh()),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Registration successful. Please verify your email.', 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login:' . strtolower($validated['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return ApiResponse::error(
                'Too many login attempts. Please try again later.',
                429,
                ['retry_after' => RateLimiter::availableIn($key)]
            );
        }

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return ApiResponse::error('Invalid email or password.', 401);
        }

        // Staff must use /admin/login — keep the same generic message (no account enumeration).
        if ($user->isStaff()) {
            RateLimiter::hit($key, 60);

            return ApiResponse::error('Invalid email or password.', 401);
        }

        RateLimiter::clear($key);

        $guestToken = $request->header('X-Cart-Token');
        $this->cartService->mergeGuestCartIntoUser($user, $guestToken);

        $token = $user->createToken('customer-token', ['customer'])->plainTextToken;

        return ApiResponse::success([
            'user' => $this->userPayload($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful.');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return ApiResponse::success(null, 'Logout successful.');
    }

    public function me(Request $request)
    {
        return ApiResponse::success([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $emailChanged = strcasecmp($user->email, $validated['email']) !== 0;

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return ApiResponse::success([
            'user' => $this->userPayload($user->fresh()),
        ], $emailChanged
            ? 'Profile updated. Please verify your new email address.'
            : 'Profile updated.');
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => \App\Support\PasswordRules::required(),
        ], \App\Support\PasswordRules::messages());

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->password = $validated['password'];
        $user->setRememberToken(Str::random(60));
        $user->save();

        // Invalidate other API tokens; keep the current session token.
        $currentId = $user->currentAccessToken()->id ?? null;
        if ($currentId) {
            $user->tokens()->where('id', '!=', $currentId)->delete();
        }

        return ApiResponse::success([
            'user' => $this->userPayload($user->fresh()),
        ], 'Password updated.');
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return ApiResponse::success(
            null,
            'If that email exists, a password reset link has been sent.'
        );
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => \App\Support\PasswordRules::required(),
        ], \App\Support\PasswordRules::messages());

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['Unable to reset password with the provided token.'],
            ]);
        }

        return ApiResponse::success(null, 'Password has been reset.');
    }

    public function verifyEmail(Request $request, int $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return ApiResponse::error('Invalid verification link.', 403);
        }

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $frontend = rtrim(config('app.frontend_url', 'http://localhost:3000'), '/');

        if ($request->expectsJson()) {
            return ApiResponse::success([
                'user' => $this->userPayload($user->fresh()),
            ], 'Email verified.');
        }

        return redirect()->away($frontend . '/login?verified=1');
    }

    public function resendVerification(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success(null, 'Email already verified.');
        }

        $user->sendEmailVerificationNotification();

        return ApiResponse::success(null, 'Verification link sent.');
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing('roles.permissions');

        $permissions = $user->getAllPermissions()->pluck('name')->values();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => optional($user->email_verified_at)?->toIso8601String(),
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $permissions,
            'is_staff' => $user->isStaff(),
        ];
    }
}
