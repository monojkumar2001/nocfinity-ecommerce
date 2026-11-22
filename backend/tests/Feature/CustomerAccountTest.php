<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_update_profile(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'email_verified_at' => now(),
        ]);
        Sanctum::actingAs($user, ['customer']);

        $this->putJson('/api/v1/profile', [
            'name' => 'New Name',
            'email' => 'old@example.com',
        ])->assertOk()
            ->assertJsonPath('data.user.name', 'New Name');
    }

    public function test_customer_can_change_password(): void
    {
        $user = User::factory()->create([
            'password' => 'OldPass@123',
        ]);
        Sanctum::actingAs($user, ['customer']);

        $this->putJson('/api/v1/password', [
            'current_password' => 'OldPass@123',
            'password' => 'NewPass@123',
            'password_confirmation' => 'NewPass@123',
        ])->assertOk();

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('NewPass@123', $user->fresh()->password)
        );
    }

    public function test_customer_order_detail_includes_status_timeline(): void
    {
        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-2026-000001',
            'status' => 'processing',
            'payment_status' => 'paid',
            'subtotal' => 50,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 50,
            'shipping_address' => [
                'name' => 'A', 'phone' => '1', 'line1' => 'L', 'city' => 'C',
                'state' => 'S', 'postal_code' => '1', 'country' => 'US',
            ],
            'stock_decremented' => true,
            'stock_restored' => false,
        ]);

        Sanctum::actingAs($user, ['customer']);

        $this->getJson('/api/v1/orders/' . $order->id)
            ->assertOk()
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonStructure(['data' => ['status_timeline']]);
    }
}
