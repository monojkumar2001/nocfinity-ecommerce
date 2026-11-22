<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchaser_can_review_product(): void
    {
        $this->seed();

        $customer = User::where('email', 'customer@nocfinity.test')->firstOrFail();
        $product = Product::query()->whereHas('orderItems.order', function ($q) use ($customer) {
            $q->where('user_id', $customer->id)
                ->whereIn('status', ['paid', 'processing', 'shipped', 'delivered']);
        })->firstOrFail();

        // Remove seeded review so this customer can post again.
        $product->reviews()->where('user_id', $customer->id)->delete();

        Sanctum::actingAs($customer, ['customer']);

        $this->postJson('/api/v1/products/' . $product->slug . '/reviews', [
            'rating' => 5,
            'title' => 'Awesome',
            'body' => 'Really happy with this purchase.',
        ])->assertCreated()
            ->assertJsonPath('data.rating', 5);

        $this->getJson('/api/v1/products/' . $product->slug . '/reviews')
            ->assertOk()
            ->assertJsonPath('meta.reviews_count', 1);
    }

    public function test_non_purchaser_cannot_review(): void
    {
        $this->seed();

        $user = User::factory()->create([
            'email' => 'nopurchase@example.com',
            'password' => 'Password1!',
        ]);
        $product = Product::query()->where('is_active', true)->firstOrFail();

        Sanctum::actingAs($user, ['customer']);

        $this->postJson('/api/v1/products/' . $product->slug . '/reviews', [
            'rating' => 4,
            'body' => 'Looks nice',
        ])->assertStatus(422);
    }
}
