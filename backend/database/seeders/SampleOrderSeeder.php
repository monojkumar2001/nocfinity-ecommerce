<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleOrderSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@nocfinity.test')->first();
        if (!$customer) {
            return;
        }

        $products = Product::take(6)->get();
        if ($products->count() < 3) {
            return;
        }

        $samples = [
            ['status' => 'pending', 'payment_status' => 'pending', 'paid' => false],
            ['status' => 'paid', 'payment_status' => 'paid', 'paid' => true],
            ['status' => 'processing', 'payment_status' => 'paid', 'paid' => true],
            ['status' => 'shipped', 'payment_status' => 'paid', 'paid' => true],
            ['status' => 'delivered', 'payment_status' => 'paid', 'paid' => true],
        ];

        foreach ($samples as $index => $sample) {
            $slice = $products->slice($index % 3, 2)->values();
            if ($slice->isEmpty()) {
                $slice = $products->take(2);
            }

            $subtotal = $slice->sum(fn ($p) => (float) $p->price);
            $orderNumber = sprintf('ORD-%s-%06d', now()->format('Y'), $index + 1);

            $order = Order::updateOrCreate(
                ['order_number' => $orderNumber],
                [
                    'user_id' => $customer->id,
                    'status' => $sample['status'],
                    'payment_status' => $sample['payment_status'],
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'shipping_amount' => 0,
                    'total_amount' => $subtotal,
                    'shipping_address' => [
                        'name' => $customer->name,
                        'phone' => '+1 555 0100',
                        'line1' => '123 Demo Street',
                        'line2' => null,
                        'city' => 'Austin',
                        'state' => 'TX',
                        'postal_code' => '78701',
                        'country' => 'US',
                    ],
                    'paid_at' => $sample['paid'] ? now()->subDays(5 - $index) : null,
                    'stock_decremented' => $sample['paid'],
                    'stock_restored' => false,
                    'created_at' => now()->subDays(10 - $index),
                ]
            );

            $order->items()->delete();
            foreach ($slice as $product) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'price' => $product->price,
                    'quantity' => 1,
                    'total' => $product->price,
                ]);
            }

            if ($sample['paid']) {
                Payment::updateOrCreate(
                    ['transaction_id' => 'pi_sample_' . $order->id],
                    [
                        'order_id' => $order->id,
                        'gateway' => 'stripe',
                        'amount' => $order->total_amount,
                        'currency' => 'USD',
                        'status' => 'succeeded',
                        'paid_at' => $order->paid_at,
                        'payment_data' => ['sample' => true],
                    ]
                );
            }
        }
    }
}
