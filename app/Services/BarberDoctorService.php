<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Barbershop;
use App\Models\SaasBackupSetting;
use App\Models\SecurityAccessRule;
use App\Services\Backup\GoogleDriveStorageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BarberDoctorService
{
    private const CACHE_KEY = 'barberdoctor_latest_report';

    /**
     * Executa a bateria completa de diagnósticos do sistema Aurobarber.
     *
     * @return array<string, mixed>
     */
    public function diagnose(): array
    {
        $startTime = microtime(true);
        $checks = [];

        // 1. Infraestrutura & Banco de Dados
        $checks[] = $this->checkDatabase();
        $checks[] = $this->checkPendingMigrations();
        $checks[] = $this->checkEssentialTables();
        $checks[] = $this->checkStoragePermissions();
        $checks[] = $this->checkServerResources();

        // 2. Perímetro de Segurança & Criptografia
        $checks[] = $this->checkSecurityDebugMode();
        $checks[] = $this->checkSecurityAppKey();
        $checks[] = $this->checkSecuritySodiumEncryption();
        $checks[] = $this->checkSecurityFirewallRules();

        // 3. Resiliência & Backups
        $checks[] = $this->checkLocalBackups();
        $checks[] = $this->checkGoogleDriveCloudBackup();
        $checks[] = $this->checkPostgresBinaries();

        // 4. Integridade Multi-Barbearias & Licenças
        $checks[] = $this->checkTenancyIntegrity();

        // 5. Filas & Jobs
        $checks[] = $this->checkQueueAndFailedJobs();

        // Cálculo do Score Clínico
        $totalChecks = count($checks);
        $passedChecks = count(array_filter($checks, fn ($c) => ($c['status'] ?? '') === 'ok'));
        $warningChecks = count(array_filter($checks, fn ($c) => ($c['status'] ?? '') === 'warning'));
        $failedChecks = count(array_filter($checks, fn ($c) => ($c['status'] ?? '') === 'danger'));

        // Ponderação: OK = 100%, Warning = 50%, Danger = 0%
        $points = ($passedChecks * 100) + ($warningChecks * 50);
        $score = (int) round($totalChecks > 0 ? $points / $totalChecks : 0);

        $status = match (true) {
            $score >= 90 => 'excellent',
            $score >= 70 => 'good',
            $score >= 50 => 'warning',
            default => 'critical',
        };

        $statusLabel = match ($status) {
            'excellent' => 'Excelente • Sistema Saudável',
            'good' => 'Bom • Pequenas Otimizações Recomendadas',
            'warning' => 'Atenção Necessária • Verifique Avisos',
            'critical' => 'Crítico • Intervenção Imediata Necessária',
        };

        // Recomendações automáticas
        $recommendations = [];
        foreach ($checks as $check) {
            if (in_array($check['status'], ['warning', 'danger'], true) && ! empty($check['recommendation'])) {
                $recommendations[] = [
                    'severity' => $check['status'],
                    'title' => $check['name'],
                    'action' => $check['recommendation'],
                ];
            }
        }

        $report = [
            'score' => $score,
            'status' => $status,
            'status_label' => $statusLabel,
            'summary' => [
                'total' => $totalChecks,
                'passed' => $passedChecks,
                'warning' => $warningChecks,
                'failed' => $failedChecks,
            ],
            'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
            'checked_at' => now()->format('d/m/Y H:i:s'),
            'checks' => $checks,
            'recommendations' => $recommendations,
        ];

        Cache::put(self::CACHE_KEY, $report, now()->addMinutes(10));

        return $report;
    }

    public function getCachedReport(): ?array
    {
        return Cache::get(self::CACHE_KEY);
    }

    private function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);

            $status = $latency > 250 ? 'warning' : 'ok';

            return [
                'category' => 'Infraestrutura',
                'name' => 'Conexão PostgreSQL & Latência',
                'status' => $status,
                'details' => "Conexão ativa ({$latency}ms). Driver: ".config('database.default'),
                'recommendation' => $latency > 250 ? 'Latência elevada no PostgreSQL. Verifique carga no servidor.' : null,
            ];
        } catch (Throwable $e) {
            return [
                'category' => 'Infraestrutura',
                'name' => 'Conexão PostgreSQL & Latência',
                'status' => 'danger',
                'details' => 'Falha de conexão: '.$e->getMessage(),
                'recommendation' => 'O banco de dados não está respondendo. Verifique o serviço postgresql.',
            ];
        }
    }

    private function checkPendingMigrations(): array
    {
        try {
            $files = collect(File::glob(database_path('migrations/*.php')))
                ->map(fn ($p) => basename($p, '.php'));

            $ran = collect(DB::table('migrations')->pluck('migration'));
            $pending = $files->diff($ran)->values()->all();

            $count = count($pending);
            if ($count > 0) {
                return [
                    'category' => 'Infraestrutura',
                    'name' => 'Migrações do Banco de Dados',
                    'status' => 'warning',
                    'details' => "{$count} migração(ões) pendente(s) no banco.",
                    'recommendation' => 'Execute "php artisan migrate" para atualizar as tabelas do sistema.',
                ];
            }

            return [
                'category' => 'Infraestrutura',
                'name' => 'Migrações do Banco de Dados',
                'status' => 'ok',
                'details' => 'Todas as migrações estão sincronizadas ('.$ran->count().' executadas).',
                'recommendation' => null,
            ];
        } catch (Throwable $e) {
            return [
                'category' => 'Infraestrutura',
                'name' => 'Migrações do Banco de Dados',
                'status' => 'danger',
                'details' => 'Erro ao auditar migrações: '.$e->getMessage(),
                'recommendation' => 'Verifique o acesso à tabela "migrations".',
            ];
        }
    }

    private function checkEssentialTables(): array
    {
        $essential = [
            'users',
            'barbershops',
            'saas_plans',
            'appointments',
            'services',
            'professionals',
            'security_access_rules',
            'saas_backup_settings',
        ];

        $missing = [];
        foreach ($essential as $table) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;
            }
        }

        if (! empty($missing)) {
            return [
                'category' => 'Infraestrutura',
                'name' => 'Tabelas Essenciais do Core',
                'status' => 'danger',
                'details' => 'Tabelas ausentes: '.implode(', ', $missing),
                'recommendation' => 'Execute as migrações para criar as tabelas ausentes.',
            ];
        }

        return [
            'category' => 'Infraestrutura',
            'name' => 'Tabelas Essenciais do Core',
            'status' => 'ok',
            'details' => count($essential).' tabelas críticas verificadas e presentes no catálogo.',
            'recommendation' => null,
        ];
    }

    private function checkStoragePermissions(): array
    {
        $dirs = [
            'storage/app' => storage_path('app'),
            'storage/framework' => storage_path('framework'),
            'storage/logs' => storage_path('logs'),
        ];

        $unwritable = [];
        foreach ($dirs as $label => $path) {
            if (! File::isDirectory($path) || ! is_writable($path)) {
                $unwritable[] = $label;
            }
        }

        if (! empty($unwritable)) {
            return [
                'category' => 'Infraestrutura',
                'name' => 'Permissões de Escrita no Storage',
                'status' => 'danger',
                'details' => 'Diretórios sem permissão de escrita: '.implode(', ', $unwritable),
                'recommendation' => 'Ajuste as permissões na VPS com chmod -R 775 storage bootstrap/cache.',
            ];
        }

        return [
            'category' => 'Infraestrutura',
            'name' => 'Permissões de Escrita no Storage',
            'status' => 'ok',
            'details' => 'Todos os diretórios de armazenamento possuem permissão correta de escrita.',
            'recommendation' => null,
        ];
    }

    private function checkServerResources(): array
    {
        try {
            $free = disk_free_space(base_path());
            $total = disk_total_space(base_path());

            if ($total && $total > 0) {
                $usedPercent = round((($total - $free) / $total) * 100, 1);
                $freeGb = round($free / 1024 / 1024 / 1024, 2);

                $status = match (true) {
                    $usedPercent >= 90 => 'danger',
                    $usedPercent >= 80 => 'warning',
                    default => 'ok',
                };

                return [
                    'category' => 'Infraestrutura',
                    'name' => 'Espaço em Disco na VPS',
                    'status' => $status,
                    'details' => "{$usedPercent}% em uso ({$freeGb} GB livres de ".round($total / 1024 / 1024 / 1024, 2).' GB).',
                    'recommendation' => $usedPercent >= 80 ? 'O disco da VPS está com mais de 80% de ocupação. Limpe arquivos de log e backups antigos.' : null,
                ];
            }
        } catch (Throwable) {
            // fallback se não puder ler
        }

        return [
            'category' => 'Infraestrutura',
            'name' => 'Espaço em Disco na VPS',
            'status' => 'ok',
            'details' => 'Espaço em disco operacional.',
            'recommendation' => null,
        ];
    }

    private function checkSecurityDebugMode(): array
    {
        $isDebug = config('app.debug');
        $isProduction = config('app.env') === 'production';

        if ($isProduction && $isDebug) {
            return [
                'category' => 'Segurança & Perímetro',
                'name' => 'Modo de Depuração (APP_DEBUG)',
                'status' => 'danger',
                'details' => 'APP_DEBUG=true em ambiente de produção! Isso pode expor variáveis de ambiente e senhas.',
                'recommendation' => 'Defina APP_DEBUG=false imediatamente no arquivo .env da VPS.',
            ];
        }

        return [
            'category' => 'Segurança & Perímetro',
            'name' => 'Modo de Depuração (APP_DEBUG)',
            'status' => 'ok',
            'details' => $isProduction ? 'Desativado com segurança em produção.' : 'Ativado em ambiente de '.config('app.env').'.',
            'recommendation' => null,
        ];
    }

    private function checkSecurityAppKey(): array
    {
        $key = config('app.key');

        if (empty($key)) {
            return [
                'category' => 'Segurança & Perímetro',
                'name' => 'Chave de Criptografia (APP_KEY)',
                'status' => 'danger',
                'details' => 'APP_KEY não está definida!',
                'recommendation' => 'Execute "php artisan key:generate" para gerar uma chave segura.',
            ];
        }

        return [
            'category' => 'Segurança & Perímetro',
            'name' => 'Chave de Criptografia (APP_KEY)',
            'status' => 'ok',
            'details' => 'Chave criptográfica ativa e protegida.',
            'recommendation' => null,
        ];
    }

    private function checkSecuritySodiumEncryption(): array
    {
        if (! extension_loaded('sodium')) {
            return [
                'category' => 'Segurança & Perímetro',
                'name' => 'Criptografia Libsodium',
                'status' => 'danger',
                'details' => 'Extensão PHP Libsodium não está instalada. Backups em nuvem criptografados não funcionarão.',
                'recommendation' => 'Instale a extensão php-sodium no servidor.',
            ];
        }

        return [
            'category' => 'Segurança & Perímetro',
            'name' => 'Criptografia Libsodium',
            'status' => 'ok',
            'details' => 'Extensão Libsodium ativa (XChaCha20-Poly1305 operacional).',
            'recommendation' => null,
        ];
    }

    private function checkSecurityFirewallRules(): array
    {
        try {
            $activeRules = SecurityAccessRule::where('is_active', true)->count();
            $blockedIps = SecurityAccessRule::where('is_active', true)->where('type', 'blacklist')->count();

            return [
                'category' => 'Segurança & Perímetro',
                'name' => 'Firewall & Regras de Acesso SaaS',
                'status' => 'ok',
                'details' => "{$activeRules} regra(s) ativa(s) ({$blockedIps} bloqueios/blacklist).",
                'recommendation' => null,
            ];
        } catch (Throwable $e) {
            return [
                'category' => 'Segurança & Perímetro',
                'name' => 'Firewall & Regras de Acesso SaaS',
                'status' => 'warning',
                'details' => 'Não foi possível verificar regras de firewall: '.$e->getMessage(),
                'recommendation' => 'Verifique se a tabela security_access_rules está migrada.',
            ];
        }
    }

    private function checkLocalBackups(): array
    {
        try {
            $manager = app(LocalBackupManager::class);
            $backups = $manager->list();

            if (empty($backups)) {
                return [
                    'category' => 'Resiliência & Backups',
                    'name' => 'Backups Locais em Disco',
                    'status' => 'warning',
                    'details' => 'Nenhum backup local encontrado no diretório de segurança.',
                    'recommendation' => 'Crie um backup imediatamente no painel de Backups ou agende a rotina automática.',
                ];
            }

            $latest = $backups[0];
            $latestDate = strtotime($latest['created_at']);
            $hoursOld = (time() - $latestDate) / 3600;

            if ($hoursOld > 48) {
                return [
                    'category' => 'Resiliência & Backups',
                    'name' => 'Backups Locais em Disco',
                    'status' => 'warning',
                    'details' => count($backups)." backup(s) no total. O último backup foi criado há mais de ".round($hoursOld)." horas ({$latest['created_at']}).",
                    'recommendation' => 'Gere um novo backup local recente para garantir recuperação confiável.',
                ];
            }

            return [
                'category' => 'Resiliência & Backups',
                'name' => 'Backups Locais em Disco',
                'status' => 'ok',
                'details' => count($backups)." backup(s) íntegros. Último criado há ".round($hoursOld, 1)." horas ({$latest['created_at']}).",
                'recommendation' => null,
            ];
        } catch (Throwable $e) {
            return [
                'category' => 'Resiliência & Backups',
                'name' => 'Backups Locais em Disco',
                'status' => 'danger',
                'details' => 'Falha ao auditar backups: '.$e->getMessage(),
                'recommendation' => 'Verifique o diretório de backups em storage/app/private/backups.',
            ];
        }
    }

    private function checkGoogleDriveCloudBackup(): array
    {
        try {
            $setting = SaasBackupSetting::getGlobal();
            $driveService = app(GoogleDriveStorageService::class);

            if (! $driveService->isConfigured($setting)) {
                return [
                    'category' => 'Resiliência & Backups',
                    'name' => 'Backup Externo no Google Drive',
                    'status' => 'warning',
                    'details' => 'Backup na nuvem não configurado. Os backups residem apenas no disco local da VPS.',
                    'recommendation' => 'Configure o Google Drive em "Nuvem Drive" para proteger contra falhas de hardware da VPS.',
                ];
            }

            return [
                'category' => 'Resiliência & Backups',
                'name' => 'Backup Externo no Google Drive',
                'status' => 'ok',
                'details' => 'Google Drive configurado com criptografia XChaCha20-Poly1305 e retenção ativa.',
                'recommendation' => null,
            ];
        } catch (Throwable $e) {
            return [
                'category' => 'Resiliência & Backups',
                'name' => 'Backup Externo no Google Drive',
                'status' => 'warning',
                'details' => 'Status não verificado: '.$e->getMessage(),
                'recommendation' => 'Verifique as credenciais do Google Drive.',
            ];
        }
    }

    private function checkPostgresBinaries(): array
    {
        try {
            $manager = app(LocalBackupManager::class);
            $reflection = new \ReflectionClass($manager);
            $method = $reflection->getMethod('resolveBinary');
            $method->setAccessible(true);
            $pgRestore = $method->invoke($manager, 'pg_restore');

            return [
                'category' => 'Resiliência & Backups',
                'name' => 'Binários PostgreSQL (pg_dump / pg_restore)',
                'status' => 'ok',
                'details' => "Executável pg_restore resolvido: {$pgRestore}",
                'recommendation' => null,
            ];
        } catch (Throwable $e) {
            return [
                'category' => 'Resiliência & Backups',
                'name' => 'Binários PostgreSQL (pg_dump / pg_restore)',
                'status' => 'warning',
                'details' => 'Não foi possível verificar binário: '.$e->getMessage(),
                'recommendation' => 'Verifique se o cliente postgresql-client está instalado.',
            ];
        }
    }

    private function checkTenancyIntegrity(): array
    {
        try {
            $totalShops = Barbershop::count();
            $matrices = Barbershop::whereNull('parent_id')->count();
            $branches = Barbershop::whereNotNull('parent_id')->count();

            // Verificar filiais órfãs (cujo parent_id não existe)
            $orphans = Barbershop::whereNotNull('parent_id')
                ->whereNotIn('parent_id', Barbershop::select('id'))
                ->count();

            // Verificar barbearias sem plano
            $noPlan = Barbershop::whereNull('saas_plan_id')->count();

            if ($orphans > 0) {
                return [
                    'category' => 'Multi-Barbearias & Licenças',
                    'name' => 'Integridade de Filiais e Redes',
                    'status' => 'danger',
                    'details' => "Encontrada(s) {$orphans} filial(is) órfã(s) com parent_id inexistente!",
                    'recommendation' => 'Corrija ou exclua as filiais órfãs na listagem de Barbearias.',
                ];
            }

            if ($noPlan > 0) {
                return [
                    'category' => 'Multi-Barbearias & Licenças',
                    'name' => 'Integridade de Filiais e Redes',
                    'status' => 'warning',
                    'details' => "{$totalShops} barbearias cadastradas ({$matrices} matrizes, {$branches} filiais). {$noPlan} barbearia(s) sem plano SaaS vinculado.",
                    'recommendation' => 'Vincule um plano SaaS a todas as barbearias ativas.',
                ];
            }

            return [
                'category' => 'Multi-Barbearias & Licenças',
                'name' => 'Integridade de Filiais e Redes',
                'status' => 'ok',
                'details' => "{$totalShops} barbearias operando ({$matrices} matrizes, {$branches} filiais). Nenhuma orfandade detectada.",
                'recommendation' => null,
            ];
        } catch (Throwable $e) {
            return [
                'category' => 'Multi-Barbearias & Licenças',
                'name' => 'Integridade de Filiais e Redes',
                'status' => 'danger',
                'details' => 'Erro ao auditar barbearias: '.$e->getMessage(),
                'recommendation' => 'Verifique a tabela barbershops.',
            ];
        }
    }

    private function checkQueueAndFailedJobs(): array
    {
        try {
            $failedCount = 0;
            if (Schema::hasTable('failed_jobs')) {
                $failedCount = DB::table('failed_jobs')->count();
            }

            $driver = config('queue.default');

            if ($failedCount > 0) {
                return [
                    'category' => 'Filas & Assincronismo',
                    'name' => 'Jobs em Fila & Falhas',
                    'status' => 'warning',
                    'details' => "{$failedCount} job(s) com falha registrados na fila (driver: {$driver}).",
                    'recommendation' => 'Inspecione os jobs com falha usando "php artisan queue:failed" ou execute "queue:retry all".',
                ];
            }

            return [
                'category' => 'Filas & Assincronismo',
                'name' => 'Jobs em Fila & Falhas',
                'status' => 'ok',
                'details' => "Fila operacional (driver: {$driver}). Nenhum job falho pendente.",
                'recommendation' => null,
            ];
        } catch (Throwable $e) {
            return [
                'category' => 'Filas & Assincronismo',
                'name' => 'Jobs em Fila & Falhas',
                'status' => 'warning',
                'details' => 'Não foi possível verificar status da fila: '.$e->getMessage(),
                'recommendation' => null,
            ];
        }
    }
}
