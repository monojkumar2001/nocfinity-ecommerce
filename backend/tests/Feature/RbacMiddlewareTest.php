<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['customer']);

        $this->getJson('/api/v1/admin/dashboard')
            ->assertStatus(403);
    }

    public function test_customer_token_cannot_access_admin_even_with_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Support');
        Sanctum::actingAs($user, ['customer']);

        $this->getJson('/api/v1/admin/orders')
            ->assertStatus(403);
    }

    public function test_product_manager_can_view_products_but_not_manage_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Product Manager');
        Sanctum::actingAs($user, ['admin']);

        $this->getJson('/api/v1/admin/roles')
            ->assertStatus(403);

        $this->getJson('/api/v1/admin/products')
            ->assertOk();

        $this->assertTrue($user->can('attributes.manage'));
    }

    public function test_order_manager_can_update_status_but_not_manage_coupons(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Order Manager');
        Sanctum::actingAs($user, ['admin']);

        $this->getJson('/api/v1/admin/coupons')
            ->assertStatus(403);

        $this->getJson('/api/v1/admin/orders')
            ->assertOk();
    }

    public function test_super_admin_has_reports_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Sanctum::actingAs($user, ['admin']);

        $this->getJson('/api/v1/admin/dashboard')
            ->assertOk();
    }

    public function test_super_admin_role_cannot_be_edited_or_deleted(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Sanctum::actingAs($user, ['admin']);

        $role = Role::findByName('Super Admin', 'web');

        $this->putJson('/api/v1/admin/roles/' . $role->id, [
            'name' => 'Hacked',
            'permissions' => [],
        ])->assertStatus(403);

        $this->deleteJson('/api/v1/admin/roles/' . $role->id)
            ->assertStatus(403);
    }

    public function test_super_admin_user_roles_cannot_be_edited(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('Super Admin');

        $target = User::factory()->create();
        $target->assignRole('Super Admin');

        Sanctum::actingAs($actor, ['admin']);

        $this->putJson('/api/v1/admin/staff/' . $target->id . '/roles', [
            'roles' => ['Admin'],
        ])->assertStatus(403);
    }

    public function test_custom_role_with_permissions_grants_staff_access(): void
    {
        $role = Role::create(['name' => 'Catalog Viewer', 'guard_name' => 'web']);
        $role->givePermissionTo('products.view');

        $user = User::factory()->create();
        $user->assignRole($role);
        Sanctum::actingAs($user, ['admin']);

        $this->getJson('/api/v1/admin/products')->assertOk();
        $this->getJson('/api/v1/admin/orders')->assertStatus(403);
    }

    public function test_can_create_custom_role_and_assign_permission_matrix(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        Sanctum::actingAs($user, ['admin']);

        $create = $this->postJson('/api/v1/admin/roles', [
            'name' => 'Promo Lead',
            'permissions' => ['coupons.manage', 'reports.view'],
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Promo Lead');

        $roleId = $create->json('data.id');

        $this->putJson('/api/v1/admin/roles/' . $roleId, [
            'name' => 'Promo Lead',
            'permissions' => ['coupons.manage'],
        ])->assertOk()
            ->assertJsonPath('data.permissions.0', 'coupons.manage');
    }

    public function test_staff_can_hold_multiple_roles(): void
    {
        $actor = User::factory()->create();
        $actor->assignRole('Super Admin');
        Sanctum::actingAs($actor, ['admin']);

        $member = User::factory()->create();
        $member->assignRole('Support');

        $this->putJson('/api/v1/admin/staff/' . $member->id . '/roles', [
            'roles' => ['Support', 'Order Manager'],
        ])->assertOk()
            ->assertJsonPath('data.roles', fn ($roles) => collect($roles)->sort()->values()->all() === ['Order Manager', 'Support']);

        $this->assertTrue($member->fresh()->hasAllRoles(['Support', 'Order Manager']));
    }

    public function test_all_seeded_permissions_exist(): void
    {
        $expected = [
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
            'roles.manage',
        ];

        foreach ($expected as $name) {
            $this->assertTrue(Permission::where('name', $name)->exists(), "Missing permission: {$name}");
        }

        foreach (['Super Admin', 'Admin', 'Product Manager', 'Order Manager', 'Support'] as $role) {
            $this->assertTrue(Role::where('name', $role)->exists(), "Missing role: {$role}");
        }
    }
}
