<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            ProductAttributeSeeder::class,
            CatalogSeeder::class,
            CouponSeeder::class,
            SampleOrderSeeder::class,
        ]);
    }
}
