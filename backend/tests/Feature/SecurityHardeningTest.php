<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_checkout_ignores_client_sent_prices_and_totals(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        Sanctum::actingAs($user, ['customer']);

        $category = Category::create(['name' => 'Audio', 'slug' => 'audio', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Secure Buds',
            'slug' => 'secure-buds',
            'sku' => 'SKU-SEC',
            'price' => 100,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1, // tampered — must be ignored
        ])->assertOk();

        $cart = $this->getJson('/api/v1/cart')->assertOk()->json('data');
        $this->assertSame(100.0, (float) $cart['items'][0]['price']);
        $this->assertSame(100.0, (float) $cart['total']);

        // Checkout body must not accept forged totals (validation ignores unknown keys).
        $response = $this->postJson('/api/v1/checkout', [
            'shipping_address' => [
                'name' => 'A',
                'phone' => '1',
                'line1' => 'L',
                'city' => 'C',
                'state' => 'S',
                'postal_code' => '1',
                'country' => 'US',
            ],
            'total_amount' => 0.01,
            'subtotal' => 0.01,
            'price' => 0.01,
        ]);

        // Without Stripe credentials this may 500/422; assert we never create a $0.01 order.
        if ($response->status() === 201) {
            $this->assertEquals(100.0, (float) $response->json('data.order.total_amount'));
        } else {
            $this->assertTrue(in_array($response->status(), [422, 500], true));
            $this->assertDatabaseMissing('orders', ['total_amount' => 0.01]);
        }
    }

    public function test_staff_login_via_customer_portal_returns_generic_error(): void
    {
        $staff = User::factory()->create([
            'email' => 'staff@example.test',
            'password' => 'Staff@12345',
        ]);
        $staff->assignRole('Support');

        $this->postJson('/api/v1/login', [
            'email' => 'staff@example.test',
            'password' => 'Staff@12345',
        ])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Invalid email or password.');
    }

    public function test_product_description_strips_html_on_write(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        Sanctum::actingAs($admin, ['admin']);

        $category = Category::create(['name' => 'Audio', 'slug' => 'audio-2', 'is_active' => true]);

        $this->postJson('/api/v1/admin/products', [
            'category_id' => $category->id,
            'name' => '<b>Safe Name</b>',
            'sku' => 'SKU-XSS-1',
            'description' => '<script>alert(1)</script>Hello',
            'price' => 10,
            'stock' => 3,
            'is_active' => true,
            'image_urls' => [],
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Safe Name')
            ->assertJsonPath('data.description', 'Hello');
    }

    public function test_customer_cannot_see_password_hash_in_user_payload(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['customer']);

        $payload = $this->getJson('/api/v1/user')->assertOk()->json('data.user');

        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('remember_token', $payload);
    }
}
