<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAdminRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_support_cannot_open_products_or_roles_in_blade_admin(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Support');

        $this->actingAs($user, 'admin')
            ->get(route('admin.products.index'))
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get(route('admin.roles.index'))
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get(route('admin.orders.index'))
            ->assertOk();
    }

    public function test_product_manager_can_manage_attributes_but_not_orders(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Product Manager');

        $this->actingAs($user, 'admin')
            ->get(route('admin.colors.index'))
            ->assertOk();

        $this->actingAs($user, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk();

        $this->actingAs($user, 'admin')
            ->get(route('admin.orders.index'))
            ->assertForbidden();
    }

    public function test_product_manager_dashboard_redirects_to_products(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Product Manager');

        $this->actingAs($user, 'admin')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.products.index'));
    }

    public function test_order_manager_cannot_access_coupons_or_attributes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Order Manager');

        $this->actingAs($user, 'admin')
            ->get(route('admin.coupons.index'))
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get(route('admin.colors.index'))
            ->assertForbidden();

        $this->actingAs($user, 'admin')
            ->get(route('admin.customers.index'))
            ->assertOk();
    }

    public function test_product_manager_has_attributes_manage_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Product Manager');

        $this->assertTrue($user->can('attributes.manage'));
        $this->assertFalse($user->can('roles.manage'));
        $this->assertFalse($user->can('reports.view'));
    }
}
