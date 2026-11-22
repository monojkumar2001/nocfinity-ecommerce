<?php

namespace App\Support;

/**
 * Shared strong password policy for customers and staff.
 * Backend is source of truth; frontend mirrors these constraints in forms.
 */
class PasswordRules
{
    public static function messages(): array
    {
        return [
            'password.regex' => 'Password must include upper, lower, number, and special character.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function required(bool $confirmed = true): array
    {
        $rules = [
            'required',
            'string',
            'min:8',
            'regex:/[A-Z]/',
            'regex:/[a-z]/',
            'regex:/[0-9]/',
            'regex:/[^A-Za-z0-9]/',
        ];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    /**
     * @return list<string>
     */
    public static function optional(bool $confirmed = true): array
    {
        $rules = [
            'nullable',
            'string',
            'min:8',
            'regex:/[A-Z]/',
            'regex:/[a-z]/',
            'regex:/[0-9]/',
            'regex:/[^A-Za-z0-9]/',
        ];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }
}
