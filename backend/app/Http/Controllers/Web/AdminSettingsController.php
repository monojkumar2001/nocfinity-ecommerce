<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\PasswordRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminSettingsController extends Controller
{
    public function edit()
    {
        $user = Auth::guard('admin')->user();

        return view('admin.settings.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::guard('admin')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'current_password' => ['nullable', 'string'],
            'password' => PasswordRules::optional(),
        ], PasswordRules::messages());

        $user->name = $validated['name'];

        if (!empty($validated['password'])) {
            if (
                empty($validated['current_password'])
                || !Hash::check($validated['current_password'], $user->password)
            ) {
                throw ValidationException::withMessages([
                    'current_password' => ['Current password is incorrect.'],
                ]);
            }

            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'Settings updated successfully.');
    }
}
