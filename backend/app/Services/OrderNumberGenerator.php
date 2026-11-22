<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderNumberGenerator
{
    public function generate(): string
    {
        $year = now()->format('Y');

        return DB::transaction(function () use ($year) {
            $latest = Order::query()
                ->where('order_number', 'like', "ORD-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->value('order_number');

            $sequence = 1;

            if ($latest && preg_match('/ORD-\d{4}-(\d+)$/', $latest, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return sprintf('ORD-%s-%06d', $year, $sequence);
        });
    }
}
