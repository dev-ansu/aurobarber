<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LicenseVerificationController extends Controller
{
    /**
     * Endpoint oficial de verificação remota de licença do Aurosoft.
     * Suporta GET e POST para retrocompatibilidade total com o LicenseService atual.
     * 
     * GET|POST /api/v1/license/verify
     */
    public function verify(Request $request): JsonResponse
    {
        $licenseKey = trim((string) ($request->input('license_key') ?? $request->input('key') ?? $request->input('cnpj') ?? ''));
        $hardwareId = $request->input('hardware_id') ?? $request->input('machine_id');
        $hardwareId = $hardwareId ? trim((string) $hardwareId) : null;
        $ip = $request->ip();

        if (empty($licenseKey)) {
            return response()->json([
                'active' => false,
                'ativo' => false,
                'valid' => false,
                'status' => 'invalid_request',
                'message' => 'Chave de licença não informada.',
                'mensagem' => 'Chave de licença não informada.',
            ], 422);
        }

        /** @var License|null $license */
        $license = License::where('license_key', $licenseKey)
            ->orWhere('cnpj', $licenseKey)
            ->orWhere('notes', 'like', "%{$licenseKey}%")
            ->first();

        // Se pesquisou por CNPJ com ou sem máscara
        if (! $license && strlen($licenseKey) >= 8) {
            $digitsOnly = preg_replace('/\D/', '', $licenseKey);
            if (! empty($digitsOnly)) {
                $license = License::all()->first(function (License $lic) use ($digitsOnly): bool {
                    return preg_replace('/\D/', '', (string) $lic->cnpj) === $digitsOnly
                        || str_contains(preg_replace('/\D/', '', (string) $lic->notes), $digitsOnly);
                });
            }
        }

        if (! $license) {
            return response()->json([
                'valid' => false,
                'status' => 'not_found',
                'message' => 'Licença não encontrada no servidor Aurosoft. Verifique a chave ou contate o suporte.',
            ], 404);
        }

        // Se a licença estiver suspensa ou cancelada
        if (in_array($license->status, ['suspended', 'cancelled'])) {
            return response()->json([
                'active' => false,
                'ativo' => false,
                'valid' => false,
                'status' => $license->status,
                'message' => 'Esta assinatura Aurosoft foi suspensa ou cancelada. Entre em contato com a equipe de suporte.',
                'mensagem' => 'Esta assinatura Aurosoft foi suspensa ou cancelada. Entre em contato com a equipe de suporte.',
                'client_name' => $license->client_name,
                'escola' => $license->trade_name ?: $license->client_name,
                'suporte' => $license->contact_phone ?: config('license.support_phone', '85997284507'),
            ], 200);
        }

        // Validação de Hardware ID para licenças On-Premise (anti-pirataria)
        if ($license->type === 'on_premise' && ! empty($license->hardware_id) && ! empty($hardwareId)) {
            if ($license->hardware_id !== $hardwareId) {
                return response()->json([
                    'active' => false,
                    'ativo' => false,
                    'valid' => false,
                    'status' => 'hardware_mismatch',
                    'message' => 'Esta licença está vinculada a outro computador/servidor. Transferência de máquina requer autorização.',
                    'mensagem' => 'Esta licença está vinculada a outro computador/servidor. Transferência de máquina requer autorização.',
                    'suporte' => $license->contact_phone ?: config('license.support_phone', '85997284507'),
                ], 200);
            }
        }

        // Verifica validade e carência offline
        $isExpired = $license->isExpired();
        $isValid = $license->isValid();

        // Extrai telemetria de integridade enviada pelo cliente (se houver)
        $telemetry = $request->input('telemetry');
        if (is_string($telemetry)) {
            $decoded = json_decode($telemetry, true);
            if (is_array($decoded)) {
                $telemetry = $decoded;
            }
        }
        if (! is_array($telemetry)) {
            $telemetry = null;
        }

        // Registra o heartbeat e telemetria no banco
        $license->recordHeartbeat($hardwareId, $ip, $telemetry);

        if (! $isValid) {
            return response()->json([
                'active' => false,
                'ativo' => false,
                'valid' => false,
                'status' => 'expired',
                'message' => 'O período de validade da sua licença expirou, incluindo os dias de tolerância.',
                'mensagem' => 'O período de validade da sua licença expirou, incluindo os dias de tolerância.',
                'client_name' => $license->client_name,
                'escola' => $license->trade_name ?: $license->client_name,
                'vencimento' => $license->expires_at?->format('d/m/Y'),
                'expires_at' => $license->expires_at?->format('Y-m-d'),
                'suporte' => $license->contact_phone ?: config('license.support_phone', '85997284507'),
            ], 200);
        }

        // Gera token assinado para cache offline na máquina do cliente
        $payload = [
            'license_key' => $license->license_key,
            'client_name' => $license->client_name,
            'expires_at' => $license->expires_at?->format('Y-m-d'),
            'verified_at' => now()->toIso8601String(),
            'grace_period_days' => $license->grace_period_days,
            'offline_until' => now()->addDays($license->grace_period_days)->toIso8601String(),
            'max_schools' => $license->max_schools,
        ];

        $signature = hash_hmac('sha256', json_encode($payload), config('app.key'));

        return response()->json([
            'active' => true,
            'ativo' => true,
            'valid' => true,
            'status' => $license->status,
            'warning' => $isExpired ? 'grace_period' : null,
            'client_name' => $license->client_name,
            'trade_name' => $license->trade_name,
            'escola' => $license->trade_name ?: $license->client_name,
            'cnpj' => $license->cnpj,
            'type' => $license->type,
            'vencimento' => $license->expires_at?->format('d/m/Y'),
            'due_date' => $license->expires_at?->format('Y-m-d'),
            'expires_at' => $license->expires_at?->format('Y-m-d'),
            'days_remaining' => $license->daysRemaining(),
            'grace_period_days' => $license->grace_period_days,
            'max_schools' => $license->max_schools,
            'max_students' => $license->max_students,
            'suporte' => $license->contact_phone ?: config('license.support_phone', '85997284507'),
            'offline_token' => base64_encode(json_encode([
                'payload' => $payload,
                'signature' => $signature,
            ])),
        ]);
    }
}
