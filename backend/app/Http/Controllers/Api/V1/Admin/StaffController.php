<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use App\Support\PasswordRules;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::staff()
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => $this->staffPayload($u));

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'is_super' => $role->name === 'Super Admin',
            ]);

        return ApiResponse::success([
            'staff' => $staff,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => PasswordRules::required(confirmed: false),
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name'),
                Rule::notIn(['Super Admin']),
            ],
        ], PasswordRules::messages());

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($validated['roles']);

        return ApiResponse::success(
            $this->staffPayload($user->fresh('roles')),
            'Staff member created.',
            201
        );
    }

    public function updateRoles(Request $request, User $user)
    {
        if ($user->isSuperAdmin()) {
            return ApiResponse::error('Super Admin cannot be edited or deleted by others.', 403);
        }

        $validated = $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name'),
                Rule::notIn(['Super Admin']),
            ],
        ]);

        $user->syncRoles($validated['roles']);

        return ApiResponse::success(
            $this->staffPayload($user->fresh('roles')),
            'Staff roles updated.'
        );
    }

    private function staffPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'is_super' => $user->isSuperAdmin(),
        ];
    }
}
