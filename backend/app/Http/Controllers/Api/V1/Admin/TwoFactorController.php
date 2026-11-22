<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor)
    {
    }

    public function status(Request $request)
    {
        $user = $request->user();

        return ApiResponse::success([
            'enabled' => (bool) $user->two_factor_confirmed_at,
            'confirmed_at' => optional($user->two_factor_confirmed_at)?->toIso8601String(),
        ]);
    }

    public function setup(Request $request)
    {
        $user = $request->user();
        $secret = $this->twoFactor->generateSecret();
        $otpauth = $this->twoFactor->otpauthUrl($user, $secret);

        // Hold pending secret until confirmed (10 minutes).
        cache()->put($this->setupKey($user->id), $secret, now()->addMinutes(10));

        return ApiResponse::success([
            'secret' => $secret,
            'otpauth_url' => $otpauth,
            'qr_svg' => $this->twoFactor->qrSvg($otpauth),
        ], 'Scan the QR code with your authenticator app.');
    }

    public function confirm(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $secret = cache()->get($this->setupKey($user->id));

        if (!$secret || !$this->twoFactor->verifySecret($secret, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => ['Invalid authentication code.'],
            ]);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();
        $this->twoFactor->enable($user, $secret, $recoveryCodes);
        cache()->forget($this->setupKey($user->id));

        return ApiResponse::success([
            'enabled' => true,
            'recovery_codes' => $recoveryCodes,
        ], 'Two-factor authentication enabled.');
    }

    public function disable(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Current password is incorrect.'],
            ]);
        }

        if (!$this->twoFactor->verifyUser($user, $validated['code'])) {
            throw ValidationException::withMessages([
                'code' => ['Invalid authentication code.'],
            ]);
        }

        $this->twoFactor->disable($user);

        return ApiResponse::success(['enabled' => false], 'Two-factor authentication disabled.');
    }

    private function setupKey(int $userId): string
    {
        return 'admin-2fa-setup:' . $userId;
    }
}
