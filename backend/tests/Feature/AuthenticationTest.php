<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_customer_can_register_and_receive_sanctum_token(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/register', [
            'name' => 'Demo Customer',
            'email' => 'demo@example.com',
            'password' => 'Customer@123',
            'password_confirmation' => 'Customer@123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'email']]]);

        $this->assertDatabaseHas('users', ['email' => 'demo@example.com']);
        Notification::assertSentTo(User::where('email', 'demo@example.com')->first(), VerifyEmail::class);
    }

    public function test_customer_can_login_with_sanctum_token(): void
    {
        $user = User::factory()->create([
            'email' => 'buyer@example.com',
            'password' => Hash::make('Customer@123'),
        ]);

        $this->postJson('/api/v1/login', [
            'email' => 'buyer@example.com',
            'password' => 'Customer@123',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'buyer@example.com')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertTrue($user->tokens()->where('name', 'customer-token')->exists());
    }

    public function test_staff_must_use_admin_login_endpoint(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('Admin@12345'),
        ]);
        $admin->assignRole('Super Admin');

        $this->postJson('/api/v1/login', [
            'email' => 'admin@example.com',
            'password' => 'Admin@12345',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Invalid email or password.');

        $this->postJson('/api/v1/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'Admin@12345',
        ])
            ->assertOk()
            ->assertJsonPath('data.guard', 'admin')
            ->assertJsonPath('data.user.is_staff', true);
    }

    public function test_customer_cannot_use_admin_login(): void
    {
        User::factory()->create([
            'email' => 'buyer@example.com',
            'password' => Hash::make('Customer@123'),
        ]);

        $this->postJson('/api/v1/admin/login', [
            'email' => 'buyer@example.com',
            'password' => 'Customer@123',
        ])->assertStatus(401);
    }

    public function test_customer_token_cannot_access_admin_routes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin, ['customer']);

        $this->getJson('/api/v1/admin/dashboard')->assertStatus(403);
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        RateLimiter::clear('login:buyer@example.com|127.0.0.1');

        User::factory()->create([
            'email' => 'buyer@example.com',
            'password' => Hash::make('Customer@123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'buyer@example.com',
                'password' => 'WrongPass1!',
            ])->assertStatus(401);
        }

        $this->postJson('/api/v1/login', [
            'email' => 'buyer@example.com',
            'password' => 'WrongPass1!',
        ])->assertStatus(429);
    }

    public function test_forgot_password_sends_reset_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/v1/forgot-password', [
            'email' => 'reset@example.com',
        ])->assertOk();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_can_be_reset_with_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => Hash::make('OldPass@123'),
        ]);

        $token = Password::createToken($user);

        $this->postJson('/api/v1/reset-password', [
            'email' => 'reset@example.com',
            'token' => $token,
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ])->assertOk();

        $this->assertTrue(Hash::check('NewPass@123', $user->fresh()->password));
    }
}
