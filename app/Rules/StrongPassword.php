<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $minLength = config('security.password.min_length', 8);
        $requireMixedCase = config('security.password.require_mixed_case', true);
        $requireNumbers = config('security.password.require_numbers', true);
        $requireSymbols = config('security.password.require_symbols', false);

        if (strlen($value) < $minLength) {
            $fail("The {$attribute} must be at least {$minLength} characters long.");
        }

        if ($requireMixedCase && !preg_match('/[a-z]/', $value)) {
            $fail("The {$attribute} must contain at least one lowercase letter.");
        }

        if ($requireMixedCase && !preg_match('/[A-Z]/', $value)) {
            $fail("The {$attribute} must contain at least one uppercase letter.");
        }

        if ($requireNumbers && !preg_match('/[0-9]/', $value)) {
            $fail("The {$attribute} must contain at least one number.");
        }

        if ($requireSymbols && !preg_match('/[^a-zA-Z0-9]/', $value)) {
            $fail("The {$attribute} must contain at least one special character.");
        }

        // Check for common weak passwords
        $weakPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'letmein', 'welcome', 'monkey'
        ];

        if (in_array(strtolower($value), $weakPasswords)) {
            $fail("The {$attribute} is too common. Please choose a stronger password.");
        }
    }
}