<?php

namespace Tests\Feature;

use App\Mail\OrderStatusUpdated;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\OrderNumberGenerator;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CheckoutStockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeService(object $intent, ?object $captured = null): CheckoutService
    {
        return new class($this->app->make(CartService::class), new OrderNumberGenerator(), $intent, $captured) extends CheckoutService {
            public function __construct(
                CartService $cartService,
                OrderNumberGenerator $orderNumbers,
                private object $fakeIntent,
                private ?object $fakeCaptured,
            ) {
                parent::__construct($cartService, $orderNumbers);
            }

            public bool $cancelled = false;

            protected function retrievePaymentIntent(string $id): object
            {
                return $this->fakeIntent;
            }

            protected function capturePaymentIntent(string $id): object
            {
                return $this->fakeCaptured ?? (object) [
                    'status' => 'succeeded',
                    'amount' => $this->fakeIntent->amount,
                ];
            }

            protected function cancelPaymentIntent(string $id): object
            {
                $this->cancelled = true;

                return (object) ['status' => 'canceled', 'id' => $id];
            }
        };
    }

    private function seedPendingOrder(User $user, Product $product, int $qty, float $lineTotal): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-2026-000010',
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => $lineTotal,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => $lineTotal,
            'shipping_address' => [
                'name' => 'A', 'phone' => '1', 'line1' => 'L', 'city' => 'C',
                'state' => 'S', 'postal_code' => '1', 'country' => 'US',
            ],
            'stock_decremented' => false,
            'stock_restored' => false,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'price' => $product->price,
            'quantity' => $qty,
            'total' => $lineTotal,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'gateway' => 'stripe',
            'transaction_id' => 'pi_test_123',
            'amount' => $lineTotal,
            'currency' => 'USD',
            'status' => 'pending',
        ]);

        return $order;
    }

    public function test_confirm_paid_decrements_stock_and_marks_order_paid(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $category = Category::create(['name' => 'Audio', 'slug' => 'audio', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Buds',
            'slug' => 'buds',
            'sku' => 'SKU-BUDS',
            'price' => 50,
            'stock' => 5,
            'is_active' => true,
        ]);

        $order = $this->seedPendingOrder($user, $product, 2, 100.0);

        $intent = (object) [
            'status' => 'requires_capture',
            'amount' => 10000,
        ];

        $service = $this->makeService($intent);
        $result = $service->confirmPaid($order, 'pi_test_123');

        $this->assertSame('paid', $result->status);
        $this->assertSame('paid', $result->payment_status);
        $this->assertTrue($result->stock_decremented);
        $this->assertSame(3, $product->fresh()->stock);

        Mail::assertSent(OrderStatusUpdated::class);
    }

    public function test_oversell_is_rejected_when_stock_drops_before_confirm(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Audio', 'slug' => 'audio-2', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Mic',
            'slug' => 'mic',
            'sku' => 'SKU-MIC',
            'price' => 80,
            'stock' => 1,
            'is_active' => true,
        ]);

        $order = $this->seedPendingOrder($user, $product, 2, 160.0);

        $intent = (object) [
            'status' => 'requires_capture',
            'amount' => 16000,
        ];

        $service = $this->makeService($intent);

        try {
            $service->confirmPaid($order, 'pi_test_123');
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Stock changed', $e->errors()['stock'][0]);
        }

        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertTrue($service->cancelled);
    }

    public function test_cancelled_paid_order_restores_stock(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $category = Category::create(['name' => 'Audio', 'slug' => 'audio-3', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Speaker',
            'slug' => 'speaker',
            'sku' => 'SKU-SPK',
            'price' => 40,
            'stock' => 10,
            'is_active' => true,
        ]);

        $order = $this->seedPendingOrder($user, $product, 3, 120.0);
        $intent = (object) ['status' => 'requires_capture', 'amount' => 12000];
        $service = $this->makeService($intent);
        $service->confirmPaid($order, 'pi_test_123');

        $this->assertSame(7, $product->fresh()->stock);

        $updated = $service->updateStatus($order->fresh(), 'cancelled', $admin);

        $this->assertSame('cancelled', $updated->status);
        $this->assertTrue($updated->stock_restored);
        $this->assertSame(10, $product->fresh()->stock);
        Mail::assertSent(OrderStatusUpdated::class, 2);
    }

    public function test_admin_cannot_skip_lifecycle_or_set_paid_manually(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Order Manager');
        Sanctum::actingAs($admin, ['admin']);

        $user = User::factory()->create();
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-2026-000099',
            'status' => 'paid',
            'payment_status' => 'paid',
            'subtotal' => 10,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 10,
            'shipping_address' => [
                'name' => 'A', 'phone' => '1', 'line1' => 'L', 'city' => 'C',
                'state' => 'S', 'postal_code' => '1', 'country' => 'US',
            ],
            'stock_decremented' => true,
            'stock_restored' => false,
        ]);

        $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertStatus(422);

        $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'paid'])
            ->assertStatus(422);

        $this->patchJson("/api/v1/admin/orders/{$order->id}/status", ['status' => 'processing'])
            ->assertOk()
            ->assertJsonPath('data.status', 'processing');
    }

    public function test_order_number_format(): void
    {
        $user = User::factory()->create();
        $generator = new OrderNumberGenerator();
        $year = now()->format('Y');

        $first = $generator->generate();
        $this->assertSame(sprintf('ORD-%s-000001', $year), $first);

        Order::create([
            'user_id' => $user->id,
            'order_number' => $first,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 1,
            'discount_amount' => 0,
            'shipping_amount' => 0,
            'total_amount' => 1,
            'shipping_address' => [
                'name' => 'A', 'phone' => '1', 'line1' => 'L', 'city' => 'C',
                'state' => 'S', 'postal_code' => '1', 'country' => 'US',
            ],
        ]);

        $second = $generator->generate();
        $this->assertSame(sprintf('ORD-%s-000002', $year), $second);
        $this->assertMatchesRegularExpression("/^ORD-{$year}-\\d{6}$/", $second);
    }

    public function test_confirm_rejects_foreign_payment_intent(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Audio', 'slug' => 'audio-4', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cable',
            'slug' => 'cable',
            'sku' => 'SKU-CBL',
            'price' => 10,
            'stock' => 5,
            'is_active' => true,
        ]);

        $order = $this->seedPendingOrder($user, $product, 1, 10.0);
        $service = $this->makeService((object) ['status' => 'requires_capture', 'amount' => 1000]);

        $this->expectException(ValidationException::class);
        $service->confirmPaid($order, 'pi_someone_else');
    }
}
