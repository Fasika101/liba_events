<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EthiopianPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^\+251[0-9]{9}$/', $value)) {
            $fail('The :attribute must be a valid Ethiopian mobile number (+251 followed by 9 digits, e.g. +251912345678).');
        }
    }

    public static function normalize(string $digits): string
    {
        $digits = preg_replace('/\D/', '', $digits) ?? '';

        if (str_starts_with($digits, '251')) {
            $digits = substr($digits, 3);
        }

        return '+251'.substr($digits, 0, 9);
    }
}
