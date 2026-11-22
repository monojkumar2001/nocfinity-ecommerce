<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $super = User::updateOrCreate(
            ['email' => 'admin@nocfinity.test'],
            [
                'name' => 'Super Admin',
                'password' => 'Admin@12345',
                'email_verified_at' => now(),
            ]
        );
        $super->syncRoles(['Super Admin']);

        $staff = [
            ['email' => 'admin.ops@nocfinity.test', 'name' => 'Ops Admin', 'role' => 'Admin', 'password' => 'Staff@12345'],
            ['email' => 'manager@nocfinity.test', 'name' => 'Product Manager', 'role' => 'Product Manager', 'password' => 'Staff@12345'],
            ['email' => 'orders@nocfinity.test', 'name' => 'Order Manager', 'role' => 'Order Manager', 'password' => 'Staff@12345'],
            ['email' => 'support@nocfinity.test', 'name' => 'Support Agent', 'role' => 'Support', 'password' => 'Staff@12345'],
        ];

        foreach ($staff as $row) {
            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'password' => $row['password'],
                    'email_verified_at' => now(),
                ]
            );
            $user->syncRoles([$row['role']]);
        }

        User::updateOrCreate(
            ['email' => 'customer@nocfinity.test'],
            [
                'name' => 'Demo Customer',
                'password' => 'Customer@123',
                'email_verified_at' => now(),
            ]
        );
    }
}
