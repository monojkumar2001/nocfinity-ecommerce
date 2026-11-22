<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'type' => 'percentage',
                'value' => 10,
                'minimum_order_amount' => 50,
                'maximum_discount' => 100,
                'usage_limit' => 1000,
                'used_count' => 0,
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addMonths(6),
                'is_active' => true,
            ]
        );

        Coupon::updateOrCreate(
            ['code' => 'SAVE20'],
            [
                'type' => 'fixed',
                'value' => 20,
                'minimum_order_amount' => 100,
                'maximum_discount' => null,
                'usage_limit' => 500,
                'used_count' => 3,
                'starts_at' => now()->subDay(),
                'expires_at' => now()->addMonths(3),
                'is_active' => true,
            ]
        );

        Coupon::updateOrCreate(
            ['code' => 'EXPIRED5'],
            [
                'type' => 'percentage',
                'value' => 5,
                'minimum_order_amount' => 0,
                'usage_limit' => 10,
                'used_count' => 0,
                'starts_at' => now()->subMonths(2),
                'expires_at' => now()->subDay(),
                'is_active' => true,
            ]
        );
    }
}
