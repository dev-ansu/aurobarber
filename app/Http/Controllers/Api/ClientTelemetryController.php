<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SystemDiagnosticService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientTelemetryController extends Controller
{
    /**
     * Endpoint interno para consulta de telemetria e saúde sob demanda via Tailscale.
     * GET|POST /api/v1/internal/telemetry
     */
    public function report(Request $request, SystemDiagnosticService $diagnostic): JsonResponse
    {
        $key = trim((string) ($request->input('key') ?? $request->input('license_key') ?? ''));
        $expectedKey = (string) config('license.key');

        // Se uma chave de licença estiver configurada no cliente, exige correspondência
        if (! empty($expectedKey) && ! empty($key) && $key !== $expectedKey) {
            return response()->json([
                'status' => 'unauthorized',
                'message' => 'Chave de autenticação da licença inválida.',
            ], 403);
        }

        // Suporte a ações remotas disparadas pelo SaaS
        $action = $request->input('action');
        if ($action === 'backup_reminder') {
            try {
                \Filament\Notifications\Notification::make()
                    ->title('⚠️ Aviso de Segurança: Realize o Backup do Aurosoft')
                    ->body('Lembrete do Suporte Técnico: por segurança, realize o backup dos dados da escola e copie uma cópia para o Pen Drive para evitar perda de dados.')
                    ->warning()
                    ->persistent()
                    ->sendToDatabase(\App\Models\User::all());
            } catch (\Throwable) {
                // Silencioso
            }
        } elseif ($action === 'clear_cache') {
            try {
                \Illuminate\Support\Facades\Cache::flush();
            } catch (\Throwable) {
                // Silencioso
            }
        }

        $server = $diagnostic->getServerHealth();
        $db = $diagnostic->getDatabaseHealth();
        $code = $diagnostic->getCodeIntegrity();
        $tailscale = $diagnostic->checkTailscale();
        $backup = $diagnostic->getBackupHealth();
        $om = $diagnostic->checkOuroModernoApi();

        $payload = [
            'hostname' => $server['hostname'] ?? gethostname(),
            'os' => $server['os'] ?? PHP_OS_FAMILY,
            'php_version' => $server['php_version'] ?? PHP_VERSION,
            'disk_free_gb' => $server['disk_free_gb'] ?? null,
            'disk_total_gb' => $server['disk_total_gb'] ?? null,
            'disk_used_percent' => $server['disk_used_percent'] ?? null,
            'memory_usage_mb' => $server['memory_usage_mb'] ?? null,
            'memory_limit' => $server['memory_limit'] ?? null,
            'db_version' => $db['version'] ?? null,
            'db_size_mb' => $db['size_mb'] ?? null,
            'db_latency_ms' => $db['latency_ms'] ?? null,
            'tailscale_ip' => $tailscale['ip'] ?? null,
            'tailscale_domain' => $tailscale['domain'] ?? null,
            'git_branch' => $code['branch'] ?? null,
            'git_commit' => $code['commit_hash'] ?? null,
            'git_message' => $code['commit_message'] ?? null,
            'git_date' => $code['commit_date'] ?? null,
            'is_clean' => $code['is_clean'] ?? true,
            'backup_last_date' => $backup['last_local_date'] ?? null,
            'backup_age_hours' => $backup['last_local_age_hours'] ?? null,
            'backup_healthy' => $backup['is_healthy'] ?? false,
            'ouro_moderno_status' => $om['status'] ?? 'unknown',
            'client_app_version' => config('app.version', '1.6.5'),
            'synced_at' => now()->toIso8601String(),
        ];

        $isFull = $request->boolean('full') || $request->input('full') == '1';

        $response = [
            'status' => 'ok',
            'school_name' => config('app.name', 'Aurosoft LMS'),
            'telemetry' => $payload,
        ];

        if ($isFull) {
            $response['details'] = [
                'server' => $server,
                'db' => $db,
                'code' => $code,
                'tailscale' => $tailscale,
                'backup' => $backup,
                'ouro_moderno' => $diagnostic->checkOuroModernoApis(),
                'recent_logs' => $diagnostic->getRecentLogs(25),
            ];
        }

        return response()->json($response);
    }
}

