<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CouponAndStockTest extends TestCase
{
    use RefreshDatabase;

    private function seedProduct(int $stock = 2): Product
    {
        $category = Category::create([
            'name' => 'Phones',
            'slug' => 'phones',
            'is_active' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Test Phone',
            'slug' => 'test-phone',
            'sku' => 'SKU-TEST-1',
            'description' => 'desc',
            'price' => 100,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $product = $this->seedProduct(2);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ])->assertStatus(422);
    }

    public function test_out_of_stock_product_cannot_be_added(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $product = $this->seedProduct(0);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertStatus(422);
    }

    public function test_expired_coupon_is_rejected(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $product = $this->seedProduct(5);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertOk();

        Coupon::create([
            'code' => 'OLD5',
            'type' => 'percentage',
            'value' => 5,
            'minimum_order_amount' => 0,
            'is_active' => true,
            'starts_at' => now()->subDays(10),
            'expires_at' => now()->subDay(),
        ]);

        $this->postJson('/api/v1/cart/coupon', ['code' => 'OLD5'])
            ->assertStatus(422);
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        $user = User::factory()->create([
            'email' => 'limit@example.com',
            'password' => 'Correct@123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/login', [
                'email' => 'limit@example.com',
                'password' => 'Wrong@12345',
            ])->assertStatus(401);
        }

        $this->postJson('/api/v1/login', [
            'email' => 'limit@example.com',
            'password' => 'Wrong@12345',
        ])->assertStatus(429);
    }
}
