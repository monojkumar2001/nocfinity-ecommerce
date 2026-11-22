<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ProductFilters;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        Storage::disk('public')->makeDirectory('products');

        $men = Category::updateOrCreate(
            ['slug' => 'men'],
            ['name' => 'Men', 'description' => 'Men clothing', 'is_active' => true, 'parent_id' => null]
        );

        $women = Category::updateOrCreate(
            ['slug' => 'women'],
            ['name' => 'Women', 'description' => 'Women clothing', 'is_active' => true, 'parent_id' => null]
        );

        // Filter sidebar "category" options (product type)
        $children = [
            ['name' => 'T-shirts', 'slug' => 't-shirts', 'parent' => $men],
            ['name' => 'Shorts', 'slug' => 'shorts', 'parent' => $men],
            ['name' => 'Shirts', 'slug' => 'shirts', 'parent' => $men],
            ['name' => 'Hoodie', 'slug' => 'hoodie', 'parent' => $men],
            ['name' => 'Jeans', 'slug' => 'jeans', 'parent' => $men],
        ];

        $categoryMap = [];
        foreach ($children as $row) {
            $categoryMap[$row['slug']] = Category::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'name' => $row['name'],
                    'description' => "{$row['name']} collection",
                    'is_active' => true,
                    'parent_id' => $row['parent']->id,
                ]
            );
        }

        // Keep women style categories as browse aliases (optional), linked under Women
        foreach (['Casual' => 'casual', 'Formal' => 'formal', 'Party' => 'party', 'Gym' => 'gym'] as $name => $slug) {
            Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => "{$name} dress style",
                    'is_active' => true,
                    'parent_id' => $women->id,
                ]
            );
        }

        // name, category slug, dress_style, color, size, price, compare, stock, imgA, imgB
        $products = [
            ['T-shirt with Tape Details', 't-shirts', 'Casual', 'Black', 'Large', 120, null, 45, 1, 9],
            ['Skinny Fit Jeans', 'jeans', 'Casual', 'Blue', 'Medium', 240, 260, 30, 2, 18],
            ['Checkered Shirt', 'shirts', 'Casual', 'Red', 'Large', 180, null, 28, 3, 10],
            ['Sleeve Striped T-shirt', 't-shirts', 'Casual', 'Orange', 'X-Large', 130, 160, 40, 4, 11],
            ['Vertical Striped Shirt', 'shirts', 'Formal', 'Green', 'Medium', 212, 232, 22, 5, 12],
            ['Courage Graphic T-shirt', 't-shirts', 'Party', 'Purple', 'Large', 145, null, 50, 6, 13],
            ['Loose Fit Bermuda Shorts', 'shorts', 'Casual', 'Yellow', 'Large', 80, null, 60, 7, 14],
            ['Faded Skinny Jeans', 'jeans', 'Casual', 'Blue', 'Small', 210, null, 25, 8, 17],
            ['Gradient Graphic T-shirt', 't-shirts', 'Party', 'Pink', 'Medium', 145, null, 35, 9, 15],
            ['Polo with Tipping Details', 't-shirts', 'Formal', 'Cyan', 'Large', 180, null, 32, 10, 16],
            ['Black Striped T-shirt', 't-shirts', 'Casual', 'Black', 'X-Large', 120, 150, 38, 11, 19],
            ['Classic Denim Jacket', 'hoodie', 'Casual', 'Blue', 'Large', 260, 299, 18, 16, 20],
            ['Oversized Hoodie Black', 'hoodie', 'Casual', 'Black', 'XX-Large', 190, null, 40, 14, 21],
            ['Relaxed Fit Chino Shorts', 'shorts', 'Casual', 'White', 'Medium', 95, 110, 42, 15, 22],
            ['Oxford Formal Shirt', 'shirts', 'Formal', 'White', 'Large', 175, null, 20, 12, 23],
            ['Party Sequin Top', 't-shirts', 'Party', 'Pink', 'Small', 220, 250, 15, 13, 24],
            ['Everyday Casual Tee', 't-shirts', 'Casual', 'Green', 'Medium', 90, null, 55, 1, 10],
            ['Performance Gym Tee', 't-shirts', 'Gym', 'Red', 'Large', 110, null, 48, 6, 13],
            ['Training Gym Shorts', 'shorts', 'Gym', 'Black', 'Medium', 85, 99, 50, 7, 14],
            ['Zip-Up Soft Hoodie', 'hoodie', 'Gym', 'Purple', 'X-Large', 205, 235, 24, 5, 14],
        ];

        foreach ($products as $index => [$name, $catSlug, $style, $color, $size, $price, $compare, $stock, $imgA, $imgB]) {
            $category = $categoryMap[$catSlug];
            $sku = 'SKU-' . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);

            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'category_id' => $category->id,
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'description' => "{$name} — crafted for everyday comfort and street-ready style. Soft fabric, modern fit, and a clean finish that matches the SHOP.CO lookbook.",
                    'price' => $price,
                    'compare_at_price' => $compare,
                    'stock' => $stock,
                    'color' => $color,
                    'size' => $size,
                    'dress_style' => $style,
                    'is_active' => true,
                ]
            );

            $product->images()->delete();

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $this->publicUrl("products/product-{$imgA}.png"),
                'alt_text' => $name,
                'is_primary' => true,
                'sort_order' => 0,
            ]);

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $this->publicUrl("products/product-{$imgB}.png"),
                'alt_text' => "{$name} alternate",
                'is_primary' => false,
                'sort_order' => 1,
            ]);
        }

        $this->command?->info(
            'Seeded ' . Product::count() . ' products with category/color/size/dress_style. Colors=' .
            count(ProductFilters::COLORS) . ' sizes=' . count(ProductFilters::SIZES)
        );
    }

    private function publicUrl(string $relativePath): string
    {
        $absolute = storage_path('app/public/' . $relativePath);

        if (!File::exists($absolute)) {
            return 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800&h=800&fit=crop';
        }

        $path = Storage::disk('public')->url($relativePath);

        if (str_starts_with($path, 'http')) {
            return $path;
        }

        return rtrim(config('app.url'), '/') . $path;
    }
}
