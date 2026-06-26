<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PanamaDv implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (! preg_match('/^\d{1,2}$/', (string) $value)) {
            $fail('El DV debe ser numérico (1 o 2 dígitos).');
        }
    }
}
