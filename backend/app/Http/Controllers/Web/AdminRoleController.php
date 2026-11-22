<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminRoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->withCount('users')->orderBy('name')->get();
        $permissions = Permission::orderBy('name')->get();

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Role created.');
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === 'Super Admin') {
            return back()->with('error', 'Super Admin role cannot be edited.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'Role updated.');
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'Super Admin') {
            return back()->with('error', 'Super Admin role cannot be deleted.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted.');
    }

    public function staff()
    {
        $staff = User::staff()->with('roles')->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();

        return view('admin.roles.staff', compact('staff', 'roles'));
    }

    public function updateStaffRoles(Request $request, User $user)
    {
        if ($user->isSuperAdmin()) {
            return back()->with('error', 'Super Admin user roles cannot be changed.');
        }

        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => [
                'string',
                'exists:roles,name',
                Rule::notIn(['Super Admin']),
            ],
        ]);

        $roles = $data['roles'] ?? [];
        if ($roles === []) {
            return back()->with('error', 'Staff must keep at least one role.');
        }

        $user->syncRoles($roles);

        return redirect()->route('admin.roles.staff')->with('success', 'Staff roles updated.');
    }
}
