<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\IntegrationSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Throwable;

class SystemDiagnosticService
{
    public function __construct(
        protected LicenseService $licenseService
    ) {}

    /**
     * Retorna métricas de saúde do servidor (PHP, Memória, Disco, SAPI).
     */
    public function getServerHealth(): array
    {
        $memoryLimit = ini_get('memory_limit') ?: 'N/A';
        $memoryUsageBytes = memory_get_usage(true);
        $memoryPeakBytes = memory_get_peak_usage(true);

        $basePath = base_path();
        $diskFree = @disk_free_space($basePath);
        $diskTotal = @disk_total_space($basePath);

        $diskUsed = ($diskTotal && $diskFree) ? ($diskTotal - $diskFree) : null;
        $diskUsedPercent = ($diskTotal && $diskTotal > 0 && $diskUsed !== null)
            ? round(($diskUsed / $diskTotal) * 100, 1)
            : null;

        $requiredExtensions = [
            'pdo_pgsql' => extension_loaded('pdo_pgsql'),
            'pdo' => extension_loaded('pdo'),
            'curl' => extension_loaded('curl'),
            'openssl' => extension_loaded('openssl'),
            'mbstring' => extension_loaded('mbstring'),
            'gd' => extension_loaded('gd'),
            'zip' => extension_loaded('zip'),
            'fileinfo' => extension_loaded('fileinfo'),
            'bcmath' => extension_loaded('bcmath'),
            'json' => extension_loaded('json'),
        ];

        return [
            'php_version' => PHP_VERSION,
            'php_sapi' => PHP_SAPI,
            'os' => PHP_OS_FAMILY.' ('.php_uname('s').' '.php_uname('r').')',
            'hostname' => gethostname() ?: 'desconhecido',
            'memory_usage_mb' => round($memoryUsageBytes / 1024 / 1024, 2),
            'memory_peak_mb' => round($memoryPeakBytes / 1024 / 1024, 2),
            'memory_limit' => $memoryLimit,
            'disk_free_gb' => $diskFree !== false ? round($diskFree / 1024 / 1024 / 1024, 2) : null,
            'disk_total_gb' => $diskTotal !== false ? round($diskTotal / 1024 / 1024 / 1024, 2) : null,
            'disk_used_percent' => $diskUsedPercent,
            'max_execution_time' => ini_get('max_execution_time') ?: 'N/A',
            'upload_max_filesize' => ini_get('upload_max_filesize') ?: 'N/A',
            'extensions' => $requiredExtensions,
            'all_extensions_ok' => ! in_array(false, $requiredExtensions, true),
        ];
    }

    /**
     * Retorna a saúde e latência do banco de dados relacional.
     */
    public function getDatabaseHealth(): array
    {
        $startTime = microtime(true);
        $driver = config('database.default');

        try {
            DB::select('SELECT 1');
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            $version = 'Desconhecida';
            $dbSizeMb = null;
            $tableCount = 0;

            if ($driver === 'pgsql') {
                $versionRow = DB::selectOne('SELECT version()');
                if ($versionRow && isset($versionRow->version)) {
                    $version = (string) $versionRow->version;
                }

                $sizeRow = DB::selectOne("SELECT pg_database_size(current_database()) as size_bytes");
                if ($sizeRow && isset($sizeRow->size_bytes)) {
                    $dbSizeMb = round(((int) $sizeRow->size_bytes) / 1024 / 1024, 2);
                }

                $tablesRow = DB::selectOne("SELECT count(*) as total FROM information_schema.tables WHERE table_schema = 'public'");
                if ($tablesRow && isset($tablesRow->total)) {
                    $tableCount = (int) $tablesRow->total;
                }
            } elseif ($driver === 'sqlite') {
                $version = 'SQLite '.DB::selectOne('SELECT sqlite_version() as v')->v;
                $tableCount = count(DB::select("SELECT name FROM sqlite_master WHERE type='table'"));
            }

            return [
                'status' => 'ok',
                'driver' => $driver,
                'latency_ms' => $latencyMs,
                'version' => $version,
                'database' => DB::connection()->getDatabaseName(),
                'size_mb' => $dbSizeMb,
                'table_count' => $tableCount,
                'error' => null,
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'error',
                'driver' => $driver,
                'latency_ms' => null,
                'version' => 'N/A',
                'database' => config("database.connections.{$driver}.database"),
                'size_mb' => null,
                'table_count' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Diagnóstico de APIs externas e conectividade de rede.
     */
    public function getApisHealth(): array
    {
        return [
            'ouro_moderno' => $this->checkOuroModernoApi(),
            'tailscale' => $this->checkTailscale(),
            'smtp' => $this->checkSmtpConnection(),
            'internet' => $this->checkInternetConnectivity(),
            'backup' => $this->getBackupHealth(),
        ];
    }

    /**
     * Diagnóstico da API Ouro Moderno LMS (compatível com múltiplas escolas e unidade isolada).
     */
    public function checkOuroModernoApi(): array
    {
        $items = $this->checkOuroModernoApis();
        $first = $items[0] ?? [
            'configured' => false,
            'url' => 'Não configurada',
            'status' => 'unconfigured',
            'latency_ms' => null,
            'message' => 'Nenhuma API Ouro Moderno configurada.',
        ];

        $first['items'] = $items;
        $first['total_schools'] = count($items);

        return $first;
    }

    /**
     * Testa individualmente a API da Ouro Moderno para todas as escolas cadastradas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function checkOuroModernoApis(): array
    {
        $results = [];

        try {
            $schools = \App\Models\School::all();
            $settings = IntegrationSetting::query()->where('provider', 'ouro_moderno')->get()->keyBy('school_id');

            if ($schools->isNotEmpty()) {
                foreach ($schools as $school) {
                    $setting = $settings->get($school->id) ?? $settings->get(null);
                    $baseUrl = $setting?->base_url ?: config('services.ouro_moderno.base_url', env('OURO_MODERNO_URL'));
                    $token = $setting?->token ?: config('services.ouro_moderno.token', env('OURO_MODERNO_TOKEN'));

                    $results[] = $this->testSingleOuroModernoInstance(
                        schoolName: $school->trade_name ?: $school->name ?: "Escola #{$school->id}",
                        schoolId: $school->id,
                        baseUrl: $baseUrl,
                        token: $token,
                        isGlobal: false
                    );
                }
            } else {
                $globalSetting = $settings->get(null) ?? IntegrationSetting::query()->where('provider', 'ouro_moderno')->first();
                $baseUrl = $globalSetting?->base_url ?: config('services.ouro_moderno.base_url', env('OURO_MODERNO_URL'));
                $token = $globalSetting?->token ?: config('services.ouro_moderno.token', env('OURO_MODERNO_TOKEN'));

                $results[] = $this->testSingleOuroModernoInstance(
                    schoolName: 'Servidor Principal (Global)',
                    schoolId: null,
                    baseUrl: $baseUrl,
                    token: $token,
                    isGlobal: true
                );
            }
        } catch (\Throwable $e) {
            $results[] = [
                'school_id' => null,
                'school_name' => 'Geral',
                'configured' => false,
                'url' => 'Erro ao consultar banco',
                'status' => 'error',
                'latency_ms' => null,
                'message' => $e->getMessage(),
            ];
        }

        return $results;
    }

    /**
     * Executa o teste de conexão real contra um servidor específico da Ouro Moderno.
     *
     * @return array<string, mixed>
     */
    public function testSingleOuroModernoInstance(
        string $schoolName,
        ?int $schoolId,
        ?string $baseUrl,
        ?string $token,
        bool $isGlobal = false
    ): array {
        if (empty($baseUrl)) {
            return [
                'school_id' => $schoolId,
                'school_name' => $schoolName,
                'configured' => false,
                'url' => 'Não configurada',
                'status' => 'unconfigured',
                'latency_ms' => null,
                'message' => 'URL não configurada para esta unidade.',
            ];
        }

        $cleanUrl = rtrim((string) $baseUrl, '/');
        $startTime = microtime(true);

        try {
            // 1. Testa autenticação formal via POST /api/token com form param 'token' (padrão oficial Ouro Moderno)
            $response = Http::asForm()
                ->acceptJson()
                ->timeout(5)
                ->post($cleanUrl . '/api/token', ['token' => $token]);

            // 2. Se responder 404, testa lista de cursos /api/cursos/lista (versões legadas)
            if ($response->status() === 404) {
                $response = Http::asForm()
                    ->acceptJson()
                    ->timeout(5)
                    ->post($cleanUrl . '/api/cursos/lista', ['token' => $token]);
            }

            // 3. Se ainda for 404, testa a raiz do host web Ouro Moderno no Tailscale
            if ($response->status() === 404) {
                $response = Http::timeout(5)->get($cleanUrl);
            }

            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            $statusCode = $response->status();
            $isOk = $response->successful() || in_array($statusCode, [200, 302], true);

            if ($isOk) {
                $message = "Online: Servidor LMS respondeu com HTTP {$statusCode} ({$latencyMs} ms)";
            } elseif ($statusCode === 401 || $statusCode === 403) {
                $message = "Servidor acessível ({$latencyMs} ms), mas o token de integração foi rejeitado (HTTP {$statusCode}).";
            } else {
                $message = "Resposta HTTP: {$statusCode} ({$latencyMs} ms)";
            }

            return [
                'school_id' => $schoolId,
                'school_name' => $schoolName,
                'configured' => true,
                'url' => $cleanUrl,
                'status' => $isOk ? 'ok' : 'warning',
                'status_code' => $statusCode,
                'latency_ms' => $latencyMs,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'school_id' => $schoolId,
                'school_name' => $schoolName,
                'configured' => true,
                'url' => $cleanUrl,
                'status' => 'error',
                'status_code' => null,
                'latency_ms' => $latencyMs,
                'message' => 'Falha de conexão: ' . $e->getMessage(),
            ];
        }
    }

    public function checkTailscale(): array
    {
        $result = [
            'installed' => false,
            'running' => false,
            'ip' => null,
            'domain' => null,
            'funnel_status' => 'Desconhecido',
            'message' => 'Tailscale não detectado',
        ];

        try {
            $process = new Process(['tailscale', 'status', '--json']);
            $process->setTimeout(3);
            $process->run();

            if ($process->isSuccessful()) {
                $data = json_decode($process->getOutput(), true);
                if (is_array($data)) {
                    $self = $data['Self'] ?? [];
                    $ips = $self['TailscaleIPs'] ?? [];
                    $dnsName = rtrim((string) ($self['DNSName'] ?? ''), '.');

                    return [
                        'installed' => true,
                        'running' => ($self['Online'] ?? true) === true,
                        'ip' => $ips[0] ?? null,
                        'domain' => $dnsName ?: null,
                        'funnel_status' => 'Conectado via Tailscale Mesh',
                        'message' => 'Conexão ativa: '.($dnsName ?: ($ips[0] ?? 'Online')),
                    ];
                }
            }
        } catch (Throwable) {
            // Se falhou ao rodar comando direto, tenta verificar via cabeçalho do request atual
        }

        // Fallback: verificar se o host atual é um domínio .ts.net
        $currentHost = request()->header('host', '');
        if (str_contains($currentHost, '.ts.net')) {
            return [
                'installed' => true,
                'running' => true,
                'ip' => null,
                'domain' => $currentHost,
                'funnel_status' => 'Conectado via Tailscale Mesh',
                'message' => "Rede privada ativa em: {$currentHost}",
            ];
        }

        return $result;
    }

    /**
     * Retorna a saúde e status de regularidade dos backups da escola.
     *
     * @return array<string, mixed>
     */
    public function getBackupHealth(): array
    {
        $localStatus = app(\App\Services\LocalBackupManager::class)->status();

        $driveLastDate = null;
        $driveStatus = 'Não configurado';

        try {
            $school = \App\Models\School::query()
                ->whereNotNull('google_drive_last_backup_at')
                ->latest('google_drive_last_backup_at')
                ->first();

            if ($school && $school->google_drive_last_backup_at) {
                $driveLastDate = Carbon::parse($school->google_drive_last_backup_at)->format('d/m/Y H:i');
                $driveStatus = $school->google_drive_last_backup_status ?: 'Sincronizado';
            } elseif (config('services.google.drive_folder_id') || env('GOOGLE_DRIVE_FOLDER_ID')) {
                $driveStatus = 'Configurado (Aguardando primeiro envio)';
            }
        } catch (Throwable) {
            // Silencioso
        }

        return [
            'total_local' => $localStatus['total_backups'] ?? 0,
            'last_local_date' => $localStatus['last_backup_date'] ?? null,
            'last_local_age_hours' => $localStatus['last_backup_age_hours'] ?? null,
            'is_healthy' => $localStatus['is_healthy'] ?? false,
            'badge_color' => $localStatus['badge_color'] ?? 'danger',
            'message' => $localStatus['message'] ?? 'Nenhum backup local encontrado.',
            'drive_last_date' => $driveLastDate,
            'drive_status' => $driveStatus,
        ];
    }

    public function checkSmtpConnection(): array
    {
        $host = (string) config('mail.mailers.smtp.host', env('MAIL_HOST', '127.0.0.1'));
        $port = (int) config('mail.mailers.smtp.port', env('MAIL_PORT', 587));

        if (empty($host) || $host === '127.0.0.1' || $host === 'localhost') {
            return [
                'host' => $host,
                'port' => $port,
                'status' => 'local_or_disabled',
                'latency_ms' => null,
                'message' => "SMTP local ou não configurado ({$host}:{$port})",
            ];
        }

        $startTime = microtime(true);
        $errno = 0;
        $errstr = '';

        $fp = @fsockopen($host, $port, $errno, $errstr, 3);
        $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

        if ($fp) {
            fclose($fp);

            return [
                'host' => $host,
                'port' => $port,
                'status' => 'ok',
                'latency_ms' => $latencyMs,
                'message' => "Servidor SMTP respondeu com sucesso em {$latencyMs} ms",
            ];
        }

        return [
            'host' => $host,
            'port' => $port,
            'status' => 'error',
            'latency_ms' => $latencyMs,
            'message' => "Não foi possível conectar ao host SMTP ({$errno}: {$errstr})",
        ];
    }

    public function checkInternetConnectivity(): array
    {
        $startTime = microtime(true);
        $errno = 0;
        $errstr = '';

        $fp = @fsockopen('8.8.8.8', 53, $errno, $errstr, 2);
        $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

        if ($fp) {
            fclose($fp);

            return [
                'status' => 'online',
                'latency_ms' => $latencyMs,
                'message' => "Conexão com a internet ativa ({$latencyMs} ms)",
            ];
        }

        return [
            'status' => 'offline',
            'latency_ms' => null,
            'message' => 'Sem conexão com a internet externa',
        ];
    }

    /**
     * Retorna a saúde e status detalhado da licença do sistema.
     */
    public function getLicenseHealth(bool $forceRefresh = false): array
    {
        $licenseData = $this->licenseService->checkLicense($forceRefresh);
        $rawKey = $this->licenseService->getKey();

        $maskedKey = 'NÃO CONFIGURADA';
        if (! empty($rawKey)) {
            $len = strlen($rawKey);
            $maskedKey = $len > 8
                ? substr($rawKey, 0, 4).'-****-****-'.substr($rawKey, -4)
                : substr($rawKey, 0, 2).'****';
        }

        return [
            'enabled' => $this->licenseService->isEnabled(),
            'status' => $licenseData['status'] ?? 'unknown',
            'active' => (bool) ($licenseData['active'] ?? false),
            'message' => $licenseData['message'] ?? '',
            'school_name' => $licenseData['school_name'] ?? null,
            'due_date' => $licenseData['due_date'] ?? null,
            'support_phone' => $licenseData['support_phone'] ?? null,
            'last_checked_at' => $licenseData['last_checked_at'] ?? null,
            'webhook_url' => $this->licenseService->getWebhookUrl(),
            'validated_via' => $licenseData['validated_via'] ?? null,
            'verification_servers' => $this->licenseService->getVerificationEndpoints(),
            'masked_key' => $maskedKey,
            'grace_days' => $this->licenseService->getGraceDays(),
        ];
    }

    /**
     * Verifica a integridade do código fonte local (Git Status, Alterações, Tampering).
     */
    public function getCodeIntegrity(): array
    {
        $gitAvailable = false;
        $branch = 'desconhecida';
        $commitHash = 'N/A';
        $commitMessage = 'N/A';
        $commitDate = 'N/A';
        $isClean = true;
        $modifiedFiles = [];
        $untrackedFiles = [];
        $diffStat = '';
        $suspiciousChanges = [];

        try {
            $branch = $this->runGit(['rev-parse', '--abbrev-ref', 'HEAD']);
            $commitHash = $this->runGit(['rev-parse', '--short', 'HEAD']);
            $commitMessage = $this->runGit(['log', '-1', '--pretty=format:%s']);
            $commitDate = $this->runGit(['log', '-1', '--pretty=format:%cd', '--date=iso']);
            $gitAvailable = true;

            $statusOutput = $this->runGit(['status', '--porcelain']);

            if (! empty($statusOutput)) {
                $lines = explode("\n", $statusOutput);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) {
                        continue;
                    }

                    $statusCode = substr($line, 0, 2);
                    $file = trim(substr($line, 2));

                    if (str_starts_with($statusCode, '??')) {
                        $untrackedFiles[] = $file;
                    } else {
                        $modifiedFiles[] = [
                            'code' => $statusCode,
                            'file' => $file,
                        ];
                    }

                    // Detector de adulteração em arquivos críticos
                    $sensitivePaths = [
                        'app/Http/Middleware/CheckLicenseStatus.php',
                        'app/Services/LicenseService.php',
                        'app/Http/Controllers/LicenseController.php',
                        'config/license.php',
                        'routes/web.php',
                        'bootstrap/app.php',
                    ];

                    foreach ($sensitivePaths as $sensitive) {
                        if (str_contains($file, $sensitive)) {
                            $suspiciousChanges[] = $file;
                        }
                    }
                }

                $isClean = count($modifiedFiles) === 0 && count($untrackedFiles) === 0;
            }

            if (! $isClean) {
                try {
                    $diffStat = $this->runGit(['diff', '--stat']);
                } catch (Throwable) {
                    $diffStat = '';
                }
            }

            // Histórico recente dos últimos 10 commits
            $recentCommitsRaw = $this->runGit(['log', '-10', '--pretty=format:%h|%cd|%s', '--date=short']);
            $recentCommits = [];
            if (! empty($recentCommitsRaw)) {
                foreach (explode("\n", $recentCommitsRaw) as $cLine) {
                    $parts = explode('|', $cLine, 3);
                    if (count($parts) === 3) {
                        $recentCommits[] = [
                            'hash' => $parts[0],
                            'date' => $parts[1],
                            'message' => $parts[2],
                        ];
                    }
                }
            }
        } catch (Throwable $e) {
            $isClean = false;
            $recentCommits = [];
            $modifiedFiles[] = [
                'code' => 'ERR',
                'file' => 'Erro ao verificar Git: '.$e->getMessage(),
            ];
        }

        return [
            'git_available' => $gitAvailable,
            'branch' => $branch,
            'commit_hash' => $commitHash,
            'commit_message' => $commitMessage,
            'commit_date' => $commitDate !== 'N/A' ? Carbon::parse($commitDate)->format('d/m/Y H:i:s') : 'N/A',
            'is_clean' => $isClean,
            'modified_files' => $modifiedFiles,
            'untracked_files' => $untrackedFiles,
            'diff_stat' => $diffStat,
            'suspicious_changes' => array_unique($suspiciousChanges),
            'recent_commits' => $recentCommits,
        ];
    }

    /**
     * Retorna os últimos registros de log da aplicação em storage/logs.
     *
     * @return array<int, array{timestamp: string, level: string, message: string, trace: string}>
     */
    public function getRecentLogs(int $limit = 50, ?string $level = null): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (! File::exists($logPath)) {
            // Tenta achar o log mais recente em storage/logs/
            $logFiles = glob(storage_path('logs/laravel-*.log'));
            if (! empty($logFiles)) {
                rsort($logFiles);
                $logPath = $logFiles[0];
            } else {
                return [];
            }
        }

        $lines = @file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false || empty($lines)) {
            return [];
        }

        $entries = [];
        $currentEntry = null;

        // Itera de trás para frente para pegar os logs mais recentes
        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $line = $lines[$i];

            // Padrão de linha de log Laravel: [2026-10-31 18:20:00] local.ERROR: mensagem {"exception":"..."}
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[\+-]\d{2}:\d{2})?)\]\s+([\w\.\-]+)\.([A-Z]+):\s+(.*)$/', $line, $matches)) {
                if ($currentEntry !== null) {
                    $entries[] = $currentEntry;
                    if (count($entries) >= $limit) {
                        break;
                    }
                }

                $logLevel = strtoupper($matches[3]);
                $message = $matches[4];

                $currentEntry = [
                    'timestamp' => $matches[1],
                    'environment' => $matches[2],
                    'level' => $logLevel,
                    'message' => $message,
                    'trace' => '',
                ];
            } elseif ($currentEntry !== null) {
                // Linha de continuação / stack trace
                $currentEntry['trace'] = $line."\n".$currentEntry['trace'];
            }
        }

        if ($currentEntry !== null && count($entries) < $limit) {
            $entries[] = $currentEntry;
        }

        if ($level !== null && $level !== '') {
            $level = strtoupper($level);
            $entries = array_filter($entries, fn (array $e) => $e['level'] === $level);
        }

        return array_values($entries);
    }

    /**
     * Limpa o arquivo de log principal com segurança.
     */
    public function clearLogs(): bool
    {
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            File::put($logPath, '');
            Log::info('Arquivo de logs limpo pelo console de desenvolvedor.');

            return true;
        }

        return false;
    }

    /**
     * Executa comandos Git de forma segura.
     */
    protected function runGit(array $args, int $timeout = 30): string
    {
        $tempDir = storage_path('app/private/tmp');
        File::ensureDirectoryExists($tempDir);
        putenv('TMP='.$tempDir);
        putenv('TEMP='.$tempDir);

        $command = array_merge(['git'], $args);
        $process = new Process($command, base_path(), [
            'TMP' => $tempDir,
            'TEMP' => $tempDir,
            'GIT_TERMINAL_PROMPT' => '0',
        ]);
        $process->setTimeout($timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            $error = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new \RuntimeException($error !== '' ? $error : 'Falha ao executar comando Git.');
        }

        return trim($process->getOutput());
    }
}
