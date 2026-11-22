<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public const SUPER_ADMIN = 'Super Admin';

    public function index()
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get()->map(fn (Role $role) => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->values(),
            'is_super' => $role->name === self::SUPER_ADMIN,
            'users_count' => $role->users_count,
        ]);

        $permissions = Permission::orderBy('name')->pluck('name')->values();

        return ApiResponse::success([
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'unique:roles,name',
                Rule::notIn([self::SUPER_ADMIN]),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return ApiResponse::success([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions()->pluck('name'),
            'is_super' => false,
        ], 'Role created.', 201);
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === self::SUPER_ADMIN) {
            return ApiResponse::error('Super Admin role cannot be edited or deleted.', 403);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'name')->ignore($role->id),
                Rule::notIn([self::SUPER_ADMIN]),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return ApiResponse::success([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions()->pluck('name'),
            'is_super' => false,
        ], 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === self::SUPER_ADMIN) {
            return ApiResponse::error('Super Admin role cannot be edited or deleted.', 403);
        }

        if ($role->users()->exists()) {
            return ApiResponse::error('Cannot delete a role that is still assigned to staff.', 422);
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return ApiResponse::success(null, 'Role deleted.');
    }
}
