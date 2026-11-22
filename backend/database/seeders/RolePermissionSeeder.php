<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionNames = [
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',

            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',

            // Colors / sizes / dress styles
            'attributes.manage',

            'orders.view',
            'orders.update_status',

            'coupons.manage',
            'customers.view',
            'reports.view',
            'roles.manage',
        ];

        $permissions = collect($permissionNames)->map(
            fn ($name) => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ])
        );

        $roles = [
            'Super Admin' => $permissionNames,

            'Admin' => [
                'products.view',
                'products.create',
                'products.edit',
                'products.delete',
                'categories.view',
                'categories.create',
                'categories.edit',
                'categories.delete',
                'attributes.manage',
                'orders.view',
                'orders.update_status',
                'coupons.manage',
                'customers.view',
                'reports.view',
            ],

            'Product Manager' => [
                'products.view',
                'products.create',
                'products.edit',
                'products.delete',
                'categories.view',
                'categories.create',
                'categories.edit',
                'categories.delete',
                'attributes.manage',
            ],

            'Order Manager' => [
                'orders.view',
                'orders.update_status',
                'customers.view',
            ],

            'Support' => [
                'orders.view',
                'customers.view',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions(
                $permissions->whereIn('name', $rolePermissions)->values()
            );
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
