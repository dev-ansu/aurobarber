<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IntegrationSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LicenseService
{
    private const CACHE_KEY = 'aurosoft_license_status';
    private const STATE_FILE = 'license_state.json';

    public function isEnabled(): bool
    {
        // Se este ambiente for o servidor SaaS Master central, ele não checa licença de si mesmo
        if ((bool) env('SAAS_ENABLED', false)) {
            return false;
        }

        $enabled = config('license.enabled', false);

        if ($enabled) {
            return true;
        }

        // Também verifica se foi ativado via IntegrationSetting no banco
        $setting = $this->getSetting();

        return (bool) ($setting?->base_url && $setting?->token);
    }

    public function getKey(): string
    {
        $setting = $this->getSetting();

        return (string) ($setting?->token ?: config('license.key', ''));
    }

    public function getWebhookUrl(): string
    {
        $setting = $this->getSetting();

        return (string) ($setting?->base_url ?: config('license.webhook_url', ''));
    }

    /**
     * Retorna a lista de servidores em cascata para validação de licença.
     * Ordem:
     * 1. Servidor Principal Casa (ansu.tailfd86d3.ts.net)
     * 2. Servidor Secundário Trabalho (pc-13.tailfd86d3.ts.net)
     * 3. Webhook Customizado do .env (se houver)
     * 4. Planilha Google Sheets na nuvem
     *
     * @return array<int, string>
     */
    public function getVerificationEndpoints(): array
    {
        $servers = config('license.verification_servers', [
            'http://ansu.tailfd86d3.ts.net:8000/api/v1/license/verify',
            'http://pc-13.tailfd86d3.ts.net:8000/api/v1/license/verify',
            'https://script.google.com/macros/s/AKfycbw-ComMVkrhDLc7_V8Ntu7OdrcDS8Cd4-PzREUgXP7gpdJ7WPsXytdixUVKdPz9CzaJ/exec',
        ]);

        $customUrl = $this->getWebhookUrl();
        $endpoints = [];

        foreach ($servers as $url) {
            if (! empty($url) && ! in_array($url, $endpoints, true)) {
                $endpoints[] = $url;
            }
        }

        if (! empty($customUrl) && ! in_array($customUrl, $endpoints, true)) {
            $endpoints[] = $customUrl;
        }

        return $endpoints;
    }

    public function getGraceDays(): int
    {
        return (int) config('license.grace_days', 5);
    }

    /**
     * @return array{
     *     active: bool,
     *     status: string,
     *     message: string,
     *     school_name: ?string,
     *     due_date: ?string,
     *     support_phone: ?string,
     *     last_checked_at: ?string,
     *     offline: bool
     * }
     */
    public function checkLicense(bool $forceRefresh = false): array
    {
        if (! $this->isEnabled()) {
            return [
                'active' => true,
                'status' => 'disabled',
                'message' => 'Validação de licença desativada no ambiente local.',
                'school_name' => null,
                'due_date' => null,
                'support_phone' => config('license.support_phone', ''),
                'last_checked_at' => now()->toIso8601String(),
                'offline' => false,
            ];
        }

        $key = $this->getKey();
        $endpoints = $this->getVerificationEndpoints();

        if (empty($key) || empty($endpoints)) {
            return [
                'active' => false,
                'status' => 'unlicensed',
                'message' => 'Nenhuma licença de uso ativa foi configurada para este sistema. O acesso está bloqueado.',
                'school_name' => null,
                'due_date' => null,
                'support_phone' => config('license.support_phone', ''),
                'last_checked_at' => now()->toIso8601String(),
                'offline' => false,
                'validated_via' => null,
            ];
        }

        $cacheKey = $this->getCacheKey();

        if (! $forceRefresh) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        // Tenta consultar a cascata de servidores de validação (Casa -> Trabalho -> Google Sheets)
        $data = null;
        $validatedVia = null;
        $machineId = $this->getMachineFingerprint();
        $telemetry = $this->collectTelemetry();

        foreach ($endpoints as $url) {
            try {
                $isGoogleScript = str_contains($url, 'script.google.com');
                $timeout = $isGoogleScript ? 10 : 3;

                if (! $isGoogleScript) {
                    $response = Http::timeout($timeout)
                        ->withHeaders(['User-Agent' => 'Aurosoft-LMS-License-Client/1.0'])
                        ->post($url, [
                            'key' => $key,
                            'license_key' => $key,
                            'cnpj' => $key,
                            'machine_id' => $machineId,
                            'hardware_id' => $machineId,
                            'telemetry' => $telemetry,
                        ]);

                    if (! $response->successful()) {
                        $response = Http::timeout($timeout)
                            ->withHeaders(['User-Agent' => 'Aurosoft-LMS-License-Client/1.0'])
                            ->get($url, [
                                'key' => $key,
                                'cnpj' => $key,
                                'machine_id' => $machineId,
                                'telemetry' => json_encode($telemetry),
                            ]);
                    }
                } else {
                    $response = Http::timeout($timeout)
                        ->withHeaders(['User-Agent' => 'Aurosoft-LMS-License-Client/1.0'])
                        ->get($url, [
                            'key' => $key,
                            'cnpj' => $key,
                            'machine_id' => $machineId,
                        ]);
                }

                if ($response->successful()) {
                    $json = $response->json();
                    if (is_array($json)) {
                        $data = $json;
                        $validatedVia = $url;
                        break;
                    }
                }
            } catch (\Throwable $e) {
                Log::info("Servidor de licença [{$url}] indisponível: {$e->getMessage()}. Tentando próximo alvo...");
                continue;
            }
        }

        if (is_array($data)) {
            $rawAtivo = $data['ativo'] ?? $data['active'] ?? false;
            $isActive = filter_var($rawAtivo, FILTER_VALIDATE_BOOLEAN)
                || in_array(strtolower((string) $rawAtivo), ['ativo', 'active', 'pago', 's', 'sim'], true);

            $dueDateRaw = isset($data['vencimento']) ? trim((string) $data['vencimento']) : null;
            $isExpired = false;

            // Trava de expiração por data de vencimento:
            // Mesmo que o status na planilha esteja como "ativo", se a data de vencimento passou, bloqueia automaticamente.
            if ($isActive && ! empty($dueDateRaw)) {
                try {
                    $dueDateCarbon = str_contains($dueDateRaw, '/')
                        ? Carbon::createFromFormat('d/m/Y', explode(' ', $dueDateRaw)[0])
                        : Carbon::parse($dueDateRaw);

                    if ($dueDateCarbon->endOfDay()->isPast()) {
                        $isActive = false;
                        $isExpired = true;
                    }
                } catch (\Throwable) {
                    // Se a data estiver em formato irreconhecível, não quebra a checagem
                }
            }

            $status = $isExpired
                ? 'expired'
                : ($isActive ? 'active' : ($data['status'] ?? 'blocked'));
            $defaultMessage = $isActive
                ? 'Licença regular.'
                : ($isExpired
                    ? "A assinatura deste sistema venceu em {$dueDateRaw}. O acesso foi suspenso até a renovação."
                    : 'Acesso suspenso. Mensalidade em aberto ou licença não autorizada.');

            $remoteMessage = (string) ($data['mensagem'] ?? $data['message'] ?? '');
            if ($isExpired && ($remoteMessage === '' || str_contains(strtolower($remoteMessage), 'regular') || str_contains(strtolower($remoteMessage), 'ativa'))) {
                $message = "A assinatura deste sistema venceu em {$dueDateRaw}. O acesso foi suspenso até a renovação.";
            } else {
                $message = $remoteMessage ?: $defaultMessage;
            }

            $result = [
                'active' => $isActive,
                'status' => (string) $status,
                'message' => $message,
                'school_name' => isset($data['escola']) ? (string) $data['escola'] : null,
                'due_date' => $dueDateRaw,
                'support_phone' => (string) ($data['suporte'] ?? config('license.support_phone', '')),
                'last_checked_at' => now()->toIso8601String(),
                'offline' => false,
                'validated_via' => $validatedVia,
            ];

            $this->savePersistentState($result);

            $cacheTtl = $isActive
                ? now()->addHours((int) config('license.cache_hours', 12))
                : now()->addSeconds(30);

            Cache::put($this->getCacheKey(), $result, $cacheTtl);
            Cache::put(self::CACHE_KEY, $result, $cacheTtl);

            return $result;
        }

        // Se falhou (sem internet ou Google fora do ar), avalia a carência offline
        return $this->evaluateOfflineGrace();
    }

    public function isLicenseActive(): bool
    {
        $status = $this->checkLicense(false);

        return $status['active'] === true;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget($this->getCacheKey());
    }

    private function getCacheKey(): string
    {
        return self::CACHE_KEY . '_' . md5((string) $this->getKey());
    }

    private function evaluateOfflineGrace(): array
    {
        $state = $this->getPersistentState();
        $graceDays = $this->getGraceDays();

        if ($state && ! empty($state['last_checked_at'])) {
            $lastChecked = Carbon::parse($state['last_checked_at']);
            $daysSinceCheck = (int) $lastChecked->diffInDays(now());

            // Se a última verificação válida foi há menos de X dias de carência
            if ($daysSinceCheck <= $graceDays && ($state['active'] ?? false)) {
                $daysRemaining = $graceDays - $daysSinceCheck;

                return [
                    'active' => true,
                    'status' => 'offline_grace',
                    'message' => "Operando offline. Última validação online há {$daysSinceCheck} dia(s). Carência restante: {$daysRemaining} dia(s).",
                    'school_name' => $state['school_name'] ?? null,
                    'due_date' => $state['due_date'] ?? null,
                    'support_phone' => $state['support_phone'] ?? config('license.support_phone', ''),
                    'last_checked_at' => $state['last_checked_at'],
                    'offline' => true,
                    'validated_via' => "Modo Offline (Carência restante: {$daysRemaining} dias)",
                ];
            }
        }

        return [
            'active' => false,
            'status' => 'offline_expired',
            'message' => "Não foi possível validar a licença nos últimos {$graceDays} dias. Conecte o computador à internet para autenticar.",
            'school_name' => $state['school_name'] ?? null,
            'due_date' => $state['due_date'] ?? null,
            'support_phone' => $state['support_phone'] ?? config('license.support_phone', ''),
            'last_checked_at' => $state['last_checked_at'] ?? null,
            'offline' => true,
            'validated_via' => 'Sem conexão com os servidores de licença',
        ];
    }

    private function getPersistentState(): ?array
    {
        if (Storage::disk('local')->exists(self::STATE_FILE)) {
            try {
                $content = Storage::disk('local')->get(self::STATE_FILE);
                $decoded = json_decode($content, true);

                return is_array($decoded) ? $decoded : null;
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function savePersistentState(array $data): void
    {
        try {
            Storage::disk('local')->put(self::STATE_FILE, json_encode($data, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            Log::error('Erro ao salvar estado local da licença: ' . $e->getMessage());
        }
    }

    public function getMachineFingerprint(): string
    {
        static $fingerprint = null;

        if ($fingerprint !== null) {
            return $fingerprint;
        }

        $rawId = null;

        if (PHP_OS_FAMILY === 'Linux') {
            foreach (['/etc/machine-id', '/var/lib/dbus/machine-id'] as $file) {
                if (@file_exists($file) && @is_readable($file)) {
                    $content = trim((string) @file_get_contents($file));
                    if (! empty($content)) {
                        $rawId = $content;
                        break;
                    }
                }
            }
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $output = @shell_exec('wmic csproduct get uuid 2>nul');
            if ($output) {
                $lines = array_filter(array_map('trim', explode("\n", (string) $output)));
                $last = end($lines);
                if (! empty($last) && $last !== 'UUID') {
                    $rawId = $last;
                }
            }

            if (! $rawId) {
                $ps = @shell_exec('powershell -NoProfile -Command "(Get-CimInstance Win32_ComputerSystemProduct).UUID" 2>nul');
                if ($ps) {
                    $rawId = trim((string) $ps);
                }
            }
        }

        if (empty($rawId)) {
            $rawId = gethostname().'_'.php_uname('m');
        }

        $fingerprint = hash('sha256', 'aurosoft_hw_'.$rawId);

        return $fingerprint;
    }

    private function getSetting(): ?IntegrationSetting
    {
        try {
            return IntegrationSetting::query()->where('provider', 'license')->first();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Coleta telemetria leve da máquina cliente para monitoramento no SaaS Master.
     *
     * @return array<string, mixed>
     */
    protected function collectTelemetry(): array
    {
        try {
            /** @var SystemDiagnosticService $diagnostic */
            $diagnostic = app(SystemDiagnosticService::class);
            $server = $diagnostic->getServerHealth();
            $db = $diagnostic->getDatabaseHealth();
            $tailscale = $diagnostic->checkTailscale();
            $code = $diagnostic->getCodeIntegrity();

            return [
                'hostname' => $server['hostname'] ?? null,
                'os' => $server['os'] ?? null,
                'php_version' => $server['php_version'] ?? null,
                'disk_free_gb' => $server['disk_free_gb'] ?? null,
                'disk_total_gb' => $server['disk_total_gb'] ?? null,
                'disk_used_percent' => $server['disk_used_percent'] ?? null,
                'memory_usage_mb' => $server['memory_usage_mb'] ?? null,
                'db_size_mb' => $db['size_mb'] ?? null,
                'db_latency_ms' => $db['latency_ms'] ?? null,
                'tailscale_ip' => $tailscale['ip'] ?? null,
                'tailscale_domain' => $tailscale['domain'] ?? null,
                'git_branch' => $code['branch'] ?? null,
                'git_commit' => $code['commit_hash'] ?? null,
                'git_message' => $code['commit_message'] ?? null,
                'git_date' => $code['commit_date'] ?? null,
                'is_clean' => $code['is_clean'] ?? true,
                'synced_at' => now()->toIso8601String(),
            ];
        } catch (\Throwable) {
            return [];
        }
    }
}

