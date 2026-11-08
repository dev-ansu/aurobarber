<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class License extends Model
{
    use HasFactory;

    protected $table = 'public.licenses';

    protected $fillable = [
        'client_name',
        'trade_name',
        'cnpj',
        'postal_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'license_key',
        'slug',
        'custom_domain',
        'contact_name',
        'contact_email',
        'contact_phone',
        'type',
        'status',
        'starts_at',
        'expires_at',
        'grace_period_days',
        'monthly_price',
        'max_schools',
        'max_students',
        'hardware_id',
        'schema_name',
        'is_demo',
        'last_checked_at',
        'last_ip',
        'telemetry',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'expires_at' => 'date',
        'grace_period_days' => 'integer',
        'monthly_price' => 'decimal:2',
        'max_schools' => 'integer',
        'max_students' => 'integer',
        'is_demo' => 'boolean',
        'last_checked_at' => 'datetime',
        'telemetry' => 'array',
    ];

    /**
     * Retorna a URL pública ou local para acesso ao painel do tenant.
     */
    public function getTenantUrl(): ?string
    {
        if (! empty($this->custom_domain)) {
            $scheme = app()->isProduction() ? 'https://' : 'http://';
            return $scheme . rtrim($this->custom_domain, '/');
        }

        if (empty($this->slug)) {
            return null;
        }

        $baseHost = request()?->getHost() ?? env('APP_DOMAIN', 'localhost');
        $port = request()?->getPort();
        $scheme = (request()?->isSecure() || app()->isProduction()) ? 'https://' : 'http://';

        // Se estiver rodando localmente (ex: localhost ou 127.0.0.1)
        if (str_contains($baseHost, 'localhost') || str_contains($baseHost, '127.0.0.1') || app()->environment('local')) {
            $portStr = ($port && ! in_array($port, [80, 443])) ? ":{$port}" : ':8000';
            return "http://{$this->slug}.localhost{$portStr}";
        }

        // Em produção / VPS
        $rootDomain = config('app.saas_domain', env('SAAS_DOMAIN', 'aurosoft.com.br'));
        return "{$scheme}{$this->slug}.{$rootDomain}";
    }

    /**
     * Verifica se o schema PostgreSQL do tenant já foi provisionado no banco.
     */
    public function isSchemaProvisioned(): bool
    {
        if (empty($this->schema_name)) {
            return false;
        }

        $exists = \Illuminate\Support\Facades\DB::selectOne(
            "SELECT 1 FROM information_schema.schemata WHERE schema_name = ?",
            [$this->schema_name]
        );

        return $exists !== null;
    }

    /**
     * Gera uma chave de licença única e elegante: AURO-ANO-CLIENTE-HASH
     */
    public static function generateKey(?string $prefix = null): string
    {
        $year = date('Y');
        $tag = $prefix ? Str::upper(Str::slug(substr($prefix, 0, 4), '')) : 'SAAS';
        $random = Str::upper(Str::random(8));

        return "AURO-{$year}-{$tag}-{$random}";
    }

    /**
     * Verifica se a licença está válida neste momento
     */
    public function isValid(): bool
    {
        if (in_array($this->status, ['suspended', 'cancelled'])) {
            return false;
        }

        if ($this->expires_at === null) {
            return true;
        }

        $now = Carbon::today();
        $toleranceLimit = Carbon::parse($this->expires_at)->addDays($this->grace_period_days);

        return $now->lte($toleranceLimit);
    }

    /**
     * Verifica se a licença já passou da data de expiração (mesmo em carência)
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return Carbon::today()->gt($this->expires_at);
    }

    /**
     * Dias restantes até expirar (negativo se já expirou)
     */
    public function daysRemaining(): int
    {
        if ($this->expires_at === null) {
            return 9999;
        }

        return (int) Carbon::today()->diffInDays($this->expires_at, false);
    }

    /**
     * Registra um heartbeat (verificação remota de licença) e telemetria de integridade
     */
    public function recordHeartbeat(?string $hardwareId = null, ?string $ip = null, ?array $telemetry = null): void
    {
        $data = [
            'last_checked_at' => now(),
            'last_ip' => $ip,
        ];

        if ($telemetry !== null && count($telemetry) > 0) {
            $data['telemetry'] = $telemetry;
        }

        // Se a licença On-Premise ainda não estiver amarrada a um Hardware ID, vincula no primeiro uso
        if ($this->type === 'on_premise' && empty($this->hardware_id) && ! empty($hardwareId)) {
            $data['hardware_id'] = $hardwareId;
        }

        $this->update($data);
    }

    /**
     * Renova a licença por mais N dias a partir de hoje (ou do vencimento atual se futuro)
     */
    public function renew(int $days = 30): void
    {
        $baseDate = ($this->expires_at && Carbon::parse($this->expires_at)->isFuture())
            ? Carbon::parse($this->expires_at)
            : Carbon::today();

        $this->update([
            'expires_at' => $baseDate->addDays($days),
            'status' => 'active',
        ]);
    }

    /**
     * Suspende o acesso da licença imediatamente
     */
    public function suspend(?string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
            'notes' => $reason ? ($this->notes ? "{$this->notes}\n[Suspensão]: {$reason}" : "[Suspensão]: {$reason}") : $this->notes,
        ]);
    }

    /**
     * Reativa a licença
     */
    public function activate(): void
    {
        $this->update([
            'status' => 'active',
        ]);
    }

    /**
     * Retorna o endereço de rede (Tailscale DNS ou IP) para comunicação direta com o cliente.
     */
    public function getTargetServerUrl(): ?string
    {
        $telemetry = $this->telemetry ?? [];
        $domain = $telemetry['tailscale_domain'] ?? null;
        $ip = $telemetry['tailscale_ip'] ?? $this->last_ip ?? null;

        if (! empty($domain)) {
            return str_starts_with($domain, 'http') ? rtrim($domain, '/') : "https://{$domain}";
        }

        if (! empty($this->notes)) {
            if (preg_match('/(https?:\/\/[a-zA-Z0-9\-\.]+\.ts\.net(?::\d+)?)/i', $this->notes, $matches)) {
                return rtrim($matches[1], '/');
            }
            if (preg_match('/([a-zA-Z0-9\-\.]+\.ts\.net)/i', $this->notes, $matches)) {
                return "https://{$matches[1]}";
            }
        }

        if (! empty($ip)) {
            return str_starts_with($ip, 'http') ? $ip : "http://{$ip}:8000";
        }

        return null;
    }

    /**
     * Realiza uma consulta direta (ao vivo) de telemetria no servidor do cliente via Tailscale.
     *
     * @return array{success: bool, latency_ms: float, message: string, telemetry?: array}
     */
    public function fetchLiveTelemetry(): array
    {
        $targetUrl = $this->getTargetServerUrl();

        if (empty($targetUrl)) {
            return [
                'success' => false,
                'latency_ms' => 0.0,
                'message' => 'Nenhum endereço Tailscale foi detectado para este cliente ainda.',
            ];
        }

        $endpoint = rtrim($targetUrl, '/') . '/api/v1/internal/telemetry';
        $startTime = microtime(true);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(15)
                ->connectTimeout(5)
                ->withHeaders(['User-Agent' => 'Aurosoft-SaaS-Master/1.0'])
                ->get($endpoint, [
                    'key' => $this->license_key,
                    'license_key' => $this->license_key,
                ]);

            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $json = $response->json();
                $telemetry = $json['telemetry'] ?? $json;

                if (is_array($telemetry)) {
                    $telemetry['last_live_ping_latency_ms'] = $latencyMs;
                    $telemetry['last_live_ping_at'] = now()->toIso8601String();

                    $this->update([
                        'telemetry' => $telemetry,
                        'last_checked_at' => now(),
                    ]);

                    return [
                        'success' => true,
                        'latency_ms' => $latencyMs,
                        'telemetry' => $telemetry,
                        'message' => "Servidor respondeu com sucesso em {$latencyMs} ms!",
                    ];
                }
            }

            return [
                'success' => false,
                'latency_ms' => $latencyMs,
                'message' => "Servidor respondeu com HTTP {$response->status()}.",
            ];
        } catch (\Throwable $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => false,
                'latency_ms' => $latencyMs,
                'message' => "Falha ao conectar: {$e->getMessage()} ({$latencyMs} ms). Verifique se a máquina ou o Tailscale estão ativos.",
            ];
        }
    }

    /**
     * Consulta o pacote completo de diagnóstico remoto do cliente (Developer Remoto).
     *
     * @return array{success: bool, latency_ms: float, message: string, telemetry?: array, details?: array}
     */
    public function fetchFullDiagnostics(?string $action = null): array
    {
        $targetUrl = $this->getTargetServerUrl();

        if (empty($targetUrl)) {
            return [
                'success' => false,
                'latency_ms' => 0.0,
                'message' => 'Nenhum endereço Tailscale foi detectado para este cliente ainda.',
            ];
        }

        $endpoint = rtrim($targetUrl, '/') . '/api/v1/internal/telemetry';
        $startTime = microtime(true);

        $params = [
            'key' => $this->license_key,
            'license_key' => $this->license_key,
            'full' => 1,
        ];

        if ($action !== null) {
            $params['action'] = $action;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(20)
                ->connectTimeout(5)
                ->withHeaders(['User-Agent' => 'Aurosoft-SaaS-Master/1.0'])
                ->get($endpoint, $params);

            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $json = $response->json();
                $telemetry = $json['telemetry'] ?? [];
                $details = $json['details'] ?? [];

                if (is_array($telemetry) && count($telemetry) > 0) {
                    $telemetry['last_live_ping_latency_ms'] = $latencyMs;
                    $telemetry['last_live_ping_at'] = now()->toIso8601String();

                    $this->update([
                        'telemetry' => $telemetry,
                        'last_checked_at' => now(),
                    ]);
                }

                return [
                    'success' => true,
                    'latency_ms' => $latencyMs,
                    'telemetry' => $telemetry,
                    'details' => $details,
                    'message' => "Diagnóstico remoto obtido com sucesso em {$latencyMs} ms!",
                ];
            }

            return [
                'success' => false,
                'latency_ms' => $latencyMs,
                'message' => "Servidor do cliente respondeu com HTTP {$response->status()}.",
            ];
        } catch (\Throwable $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'success' => false,
                'latency_ms' => $latencyMs,
                'message' => "Falha de conexão Tailscale: {$e->getMessage()} ({$latencyMs} ms).",
            ];
        }
    }
}
