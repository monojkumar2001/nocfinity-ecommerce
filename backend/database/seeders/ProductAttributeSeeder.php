<?php

namespace Database\Seeders;

use App\Models\DressStyle;
use App\Models\ProductColor;
use App\Models\ProductSize;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductAttributeSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['Green', '#00C12B'],
            ['Red', '#F50606'],
            ['Yellow', '#F5DD06'],
            ['Orange', '#F57906'],
            ['Cyan', '#06CAF5'],
            ['Blue', '#063AF5'],
            ['Purple', '#7D06F5'],
            ['Pink', '#F506A4'],
            ['White', '#FFFFFF'],
            ['Black', '#000000'],
        ];

        foreach ($colors as $i => [$name, $hex]) {
            ProductColor::updateOrCreate(
                ['name' => $name],
                ['hex' => $hex, 'sort_order' => $i, 'is_active' => true]
            );
        }

        $sizes = [
            'XX-Small', 'X-Small', 'Small', 'Medium', 'Large',
            'X-Large', 'XX-Large', '3X-Large', '4X-Large',
        ];

        foreach ($sizes as $i => $name) {
            ProductSize::updateOrCreate(
                ['name' => $name],
                ['sort_order' => $i, 'is_active' => true]
            );
        }

        foreach (['Casual', 'Formal', 'Party', 'Gym'] as $i => $name) {
            DressStyle::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $i, 'is_active' => true]
            );
        }
    }
}
