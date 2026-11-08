<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\TwoFactorCodeMail;
use App\Models\User;
use App\Models\UserTrustedDevice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TwoFactorAuthService
{
    public const COOKIE_PREFIX = 'aurosoft_device_';
    public const TRUSTED_DAYS = 30;

    /**
     * Verifica se o usuário tem 2FA ativado.
     * Super administradores no SaaS Master também podem ter por padrão se configurado.
     */
    public function isEnabled(User $user): bool
    {
        return (bool) ($user->two_factor_enabled ?? false);
    }

    /**
     * Verifica se o dispositivo/navegador atual é confiável para este usuário.
     */
    public function isDeviceTrusted(User $user, Request $request): bool
    {
        $cookieName = self::COOKIE_PREFIX . $user->id;
        $cookieValue = $request->cookie($cookieName);

        if (empty($cookieValue) || ! is_string($cookieValue)) {
            return false;
        }

        $hash = hash('sha256', $cookieValue);

        $trustedDevice = UserTrustedDevice::query()
            ->where('user_id', $user->id)
            ->where('device_token_hash', $hash)
            ->where('expires_at', '>', now())
            ->first();

        if (! $trustedDevice) {
            return false;
        }

        // Atualiza a data do último uso de forma assíncrona/silenciosa
        try {
            $trustedDevice->updateQuietly([
                'last_used_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // Ignora se não conseguir atualizar timestamp
        }

        return true;
    }

    /**
     * Gera um código de 6 dígitos seguro, armazena com validade de 10 minutos e dispara o e-mail.
     */
    public function generateAndSendCode(User $user, Request $request): string
    {
        $code = sprintf('%06d', random_int(100000, 999999));

        $user->forceFill([
            'two_factor_code' => Hash::make($code),
            'two_factor_expires_at' => now()->addMinutes(10),
        ])->saveQuietly();

        try {
            Mail::to($user->email)->send(new TwoFactorCodeMail(
                user: $user,
                code: $code,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            ));
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar código 2FA por e-mail: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        return $code;
    }

    /**
     * Valida o código digitado pelo usuário.
     */
    public function verifyCode(User $user, string $code): bool
    {
        $cleanCode = trim($code);

        if (strlen($cleanCode) !== 6 || ! ctype_digit($cleanCode)) {
            return false;
        }

        if (empty($user->two_factor_code) || empty($user->two_factor_expires_at)) {
            return false;
        }

        if (Carbon::parse($user->two_factor_expires_at)->isPast()) {
            return false;
        }

        if (! Hash::check($cleanCode, $user->two_factor_code)) {
            return false;
        }

        // Código válido! Limpa para evitar reuso
        $user->forceFill([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->saveQuietly();

        return true;
    }

    /**
     * Registra o dispositivo atual como confiável por 30 dias.
     */
    public function trustCurrentDevice(User $user, Request $request, int $days = self::TRUSTED_DAYS): void
    {
        $plainToken = Str::random(64);
        $hash = hash('sha256', $plainToken);

        UserTrustedDevice::create([
            'user_id' => $user->id,
            'device_token_hash' => $hash,
            'name' => Str::limit($request->userAgent() ?? 'Navegador Web', 150),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_used_at' => now(),
            'expires_at' => now()->addDays($days),
        ]);

        $cookieName = self::COOKIE_PREFIX . $user->id;
        $minutes = $days * 24 * 60;

        Cookie::queue(
            Cookie::make(
                name: $cookieName,
                value: $plainToken,
                minutes: $minutes,
                path: '/',
                domain: null,
                secure: $request->isSecure(),
                httpOnly: true,
                sameSite: 'lax',
            )
        );
    }
}
