<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PanamaRuc implements ValidationRule
{
    public function __construct(
        private readonly bool $requiresDv = false,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $value = (string) $value;

        if (strlen($value) > 30) {
            $fail('El RUC/Cédula no puede exceder 30 caracteres.');

            return;
        }

        if (! preg_match('/^[A-Za-z0-9\-\.\s\/]+$/', $value)) {
            $fail('El RUC/Cédula solo puede contener letras, números, guiones, puntos, espacios o barras.');
        }
    }
}
