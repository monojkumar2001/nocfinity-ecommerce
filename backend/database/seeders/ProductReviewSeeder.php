<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductReviewSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::where('email', 'customer@nocfinity.test')->first();
        if (!$customer) {
            return;
        }

        $products = Product::query()->where('is_active', true)->take(6)->get();
        $samples = [
            ['rating' => 5, 'title' => 'Excellent quality', 'body' => 'Fits perfectly and arrived quickly.'],
            ['rating' => 4, 'title' => 'Great value', 'body' => 'Soft fabric and true to size.'],
            ['rating' => 5, 'title' => 'Would buy again', 'body' => 'Exactly as described in the photos.'],
            ['rating' => 3, 'title' => 'Decent', 'body' => 'Good overall, shipping took a bit longer.'],
            ['rating' => 4, 'title' => 'Happy with it', 'body' => 'Comfortable for everyday wear.'],
            ['rating' => 5, 'title' => 'Love it', 'body' => 'Premium feel for the price.'],
        ];

        foreach ($products as $index => $product) {
            $sample = $samples[$index % count($samples)];
            ProductReview::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'user_id' => $customer->id,
                ],
                [
                    'rating' => $sample['rating'],
                    'title' => $sample['title'],
                    'body' => $sample['body'],
                    'is_approved' => true,
                ]
            );
        }
    }
}
