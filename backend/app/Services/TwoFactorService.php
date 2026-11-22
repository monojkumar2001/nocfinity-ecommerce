<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(private readonly Google2FA $google2fa = new Google2FA())
    {
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function otpauthUrl(User $user, string $secret): string
    {
        $issuer = config('app.name', 'Nocfinity');

        return $this->google2fa->getQRCodeUrl($issuer, $user->email, $secret);
    }

    public function qrSvg(string $otpauthUrl): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        return (new Writer($renderer))->writeString($otpauthUrl);
    }

    public function verifySecret(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code, 1);
    }

    public function verifyUser(User $user, string $code): bool
    {
        if (!$user->two_factor_secret || !$user->two_factor_confirmed_at) {
            return false;
        }

        $secret = decrypt($user->two_factor_secret);

        if ($this->verifySecret($secret, $code)) {
            return true;
        }

        return $this->consumeRecoveryCode($user, $code);
    }

    /**
     * @return list<string>
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => Str::upper(Str::random(4) . '-' . Str::random(4)))
            ->all();
    }

    public function enable(User $user, string $secret, array $recoveryCodes): void
    {
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => encrypt(json_encode(array_values($recoveryCodes))),
        ])->save();
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    public function createLoginChallenge(User $user): string
    {
        $token = Str::random(64);
        Cache::put($this->challengeKey($token), $user->id, now()->addMinutes(5));

        return $token;
    }

    public function userFromChallenge(string $token): ?User
    {
        $userId = Cache::get($this->challengeKey($token));
        if (!$userId) {
            return null;
        }

        return User::query()->find($userId);
    }

    public function forgetChallenge(string $token): void
    {
        Cache::forget($this->challengeKey($token));
    }

    private function challengeKey(string $token): string
    {
        return 'admin-2fa-challenge:' . hash('sha256', $token);
    }

    private function consumeRecoveryCode(User $user, string $code): bool
    {
        if (!$user->two_factor_recovery_codes) {
            return false;
        }

        $codes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        if (!is_array($codes)) {
            return false;
        }

        $normalized = Str::upper(trim($code));
        $matchIndex = null;

        foreach ($codes as $index => $stored) {
            if (hash_equals(Str::upper((string) $stored), $normalized)) {
                $matchIndex = $index;
                break;
            }
        }

        if ($matchIndex === null) {
            return false;
        }

        unset($codes[$matchIndex]);
        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode(array_values($codes))),
        ])->save();

        return true;
    }
}
