<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_view_another_users_order(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $order = Order::create([
            'user_id' => $owner->id,
            'order_number' => 'ORD-2026-000001',
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 100,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 100,
            'shipping_address' => [
                'name' => 'Owner',
                'phone' => '1',
                'line1' => 'A',
                'city' => 'B',
                'state' => 'C',
                'postal_code' => '1',
                'country' => 'US',
            ],
        ]);

        Sanctum::actingAs($intruder);

        $this->getJson('/api/v1/orders/' . $order->id)
            ->assertStatus(404);
    }

    public function test_customer_can_view_own_order(): void
    {
        $owner = User::factory()->create();

        $order = Order::create([
            'user_id' => $owner->id,
            'order_number' => 'ORD-2026-000002',
            'status' => 'paid',
            'payment_status' => 'paid',
            'subtotal' => 50,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 50,
            'shipping_address' => [
                'name' => 'Owner',
                'phone' => '1',
                'line1' => 'A',
                'city' => 'B',
                'state' => 'C',
                'postal_code' => '1',
                'country' => 'US',
            ],
        ]);

        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/orders/' . $order->id)
            ->assertOk()
            ->assertJsonPath('data.order_number', 'ORD-2026-000002');
    }
}
