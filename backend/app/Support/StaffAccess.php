<?php

namespace App\Support;

use App\Models\User;

class StaffAccess
{
    /**
     * Preferred Blade admin landing paths by permission.
     *
     * @return array<int, array{route: string, permission: string}>
     */
    public static function menu(): array
    {
        return [
            ['route' => 'admin.dashboard', 'permission' => 'reports.view', 'label' => 'Dashboard'],
            ['route' => 'admin.products.index', 'permission' => 'products.view', 'label' => 'Products'],
            ['route' => 'admin.orders.index', 'permission' => 'orders.view', 'label' => 'Orders'],
            ['route' => 'admin.categories.index', 'permission' => 'categories.view', 'label' => 'Categories'],
            ['route' => 'admin.colors.index', 'permission' => 'attributes.manage', 'label' => 'Colors'],
            ['route' => 'admin.sizes.index', 'permission' => 'attributes.manage', 'label' => 'Sizes'],
            ['route' => 'admin.dress-styles.index', 'permission' => 'attributes.manage', 'label' => 'Dress Styles'],
            ['route' => 'admin.coupons.index', 'permission' => 'coupons.manage', 'label' => 'Coupons'],
            ['route' => 'admin.customers.index', 'permission' => 'customers.view', 'label' => 'Customers'],
            ['route' => 'admin.roles.index', 'permission' => 'roles.manage', 'label' => 'Roles'],
        ];
    }

    public static function firstAllowedRoute(?User $user): string
    {
        if (!$user) {
            return 'login';
        }

        foreach (self::menu() as $item) {
            if ($user->can($item['permission'])) {
                return $item['route'];
            }
        }

        return 'login';
    }

    public static function canSee(User $user, string $permission): bool
    {
        return $user->can($permission);
    }
}
