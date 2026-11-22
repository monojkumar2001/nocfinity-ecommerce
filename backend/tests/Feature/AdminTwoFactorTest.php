<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class AdminTwoFactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_requires_totp_when_enabled(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@nocfinity.test')->firstOrFail();
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret();
        $service->enable($admin, $secret, $service->generateRecoveryCodes());

        $response = $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@nocfinity.test',
            'password' => 'Admin@12345',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.requires_two_factor', true)
            ->assertJsonMissingPath('data.token');

        $challenge = $response->json('data.challenge_token');
        $code = (new Google2FA())->getCurrentOtp($secret);

        $this->postJson('/api/v1/admin/login/2fa', [
            'challenge_token' => $challenge,
            'code' => $code,
        ])->assertOk()
            ->assertJsonPath('data.requires_two_factor', false)
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_staff_can_enable_two_factor(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@nocfinity.test')->firstOrFail();
        Sanctum::actingAs($admin, ['admin']);

        $setup = $this->postJson('/api/v1/admin/2fa/setup')->assertOk();
        $secret = $setup->json('data.secret');
        $code = (new Google2FA())->getCurrentOtp($secret);

        $this->postJson('/api/v1/admin/2fa/confirm', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonStructure(['data' => ['recovery_codes']]);

        $this->assertNotNull($admin->fresh()->two_factor_confirmed_at);
    }
}
