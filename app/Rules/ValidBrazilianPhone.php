<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidBrazilianPhone implements ValidationRule
{
    /**
     * Lista de DDDs oficiais válidos no Brasil (ANATEL).
     */
    protected const VALID_DDDS = [
        11, 12, 13, 14, 15, 16, 17, 18, 19,
        21, 22, 24, 27, 28,
        31, 32, 33, 34, 35, 37, 38,
        41, 42, 43, 44, 45, 46, 47, 48, 49,
        51, 53, 54, 55,
        61, 62, 63, 64, 65, 66, 67, 68, 69,
        71, 73, 74, 75, 77, 79,
        81, 82, 83, 84, 85, 86, 87, 88, 89,
        91, 92, 93, 94, 95, 96, 97, 98, 99,
    ];

    /**
     * Sequências fakes comumente digitadas.
     */
    protected const FAKE_SEQUENCES = [
        '12345678901',
        '01234567890',
        '98765432109',
        '1234567890',
        '0123456789',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || blank($value)) {
            return;
        }

        $digits = preg_replace('/\D/', '', $value);
        $len = strlen($digits);

        // 1. Telefone brasileiro com DDD deve ter exatamente 10 dígitos (fixo) ou 11 dígitos (celular)
        if ($len !== 10 && $len !== 11) {
            $fail('O telefone ou WhatsApp deve conter o DDD e número válido com 10 ou 11 dígitos (ex: (85) 99999-9999).');
            return;
        }

        // 2. Rejeita dígitos repetidos (ex: 11111111111, 00000000000)
        if (preg_match('/^(\d)\1+$/', $digits)) {
            $fail('O número de telefone informado não é válido.');
            return;
        }

        // 3. Rejeita sequências fakes óbvias
        if (in_array($digits, self::FAKE_SEQUENCES, true)) {
            $fail('Por favor, informe um número de telefone ou WhatsApp real.');
            return;
        }

        // 4. Valida se o DDD é oficial
        $ddd = (int) substr($digits, 0, 2);
        if (! in_array($ddd, self::VALID_DDDS, true)) {
            $fail("O DDD '{$ddd}' informado não é um DDD oficial válido no Brasil.");
            return;
        }

        // 5. Se for celular (11 dígitos), o terceiro dígito (primeiro do número) DEVE ser 9
        if ($len === 11) {
            $ninthDigit = $digits[2];
            if ($ninthDigit !== '9') {
                $fail('Celulares com 11 dígitos no Brasil devem iniciar com o dígito 9 após o DDD.');
                return;
            }
        }
    }
}
