<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class SensitiveClientData
{
    public static function encrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return self::isEncrypted($value) ? $value : Crypt::encryptString($value);
    }

    public static function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '' || ! self::isEncrypted($value)) {
            return $value;
        }

        return Crypt::decryptString($value);
    }

    public static function isEncrypted(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        $decoded = base64_decode($value, true);

        if (! is_string($decoded)) {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload)
            && isset($payload['iv'], $payload['value'], $payload['mac'])
            && is_string($payload['iv'])
            && is_string($payload['value'])
            && is_string($payload['mac']);
    }

    public static function phoneHash(?string $phone): ?string
    {
        $normalized = preg_replace('/\D/', '', (string) $phone) ?? '';
        if (strlen($normalized) >= 12 && str_starts_with($normalized, '55')) {
            $normalized = substr($normalized, 2);
        }

        return self::blindIndex($normalized);
    }

    public static function emailHash(?string $email): ?string
    {
        return self::blindIndex(mb_strtolower(trim((string) $email)));
    }

    public static function maskPhone(?string $phone): string
    {
        if (blank($phone)) {
            return '—';
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';
        if (strlen($digits) >= 10) {
            $localDigits = strlen($digits) >= 12 && str_starts_with($digits, '55')
                ? substr($digits, 2)
                : $digits;

            return substr($localDigits, 0, 2).' '.str_repeat('*', max(4, strlen($localDigits) - 6)).' '.substr($localDigits, -4);
        }

        return str_repeat('*', max(4, strlen($digits)));
    }

    private static function blindIndex(string $normalized): ?string
    {
        if ($normalized === '') {
            return null;
        }

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }
}
