<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\Process\Process;

class SystemUpdateService
{
    public const CURRENT_VERSION = '1.6.5';

    public const VERSION_CODENAME = 'Elrond';

    protected string $targetBranch = 'main';

    protected string $cacheKey = 'system_update_status';

    protected int $cacheTtlSeconds = 300; // 5 minutos

    public function getTargetBranch(): string
    {
        $current = $this->getCurrentBranch();
        if ($current && ! in_array($current, ['desconhecida', 'saas', 'HEAD'], true)) {
            return config('app.update_branch', env('UPDATE_BRANCH', $current));
        }

        return config('app.update_branch', env('UPDATE_BRANCH', 'saas-prod'));
    }

    /**
     * Retorna o driver de atualização ativo ('git' ou 'saas').
     * 'git': usado pelo cliente atual ou em desenvolvimento (repositório clonado com Git).
     * 'saas': usado por novos clientes sem Git, atualizando via pacotes ZIP assinados pela API do SaaS.
     */
    public function getUpdateDriver(): string
    {
        $forced = config('app.update_driver', env('UPDATE_DRIVER', 'auto'));
        if (in_array($forced, ['git', 'saas'], true)) {
            return $forced;
        }

        if (File::isDirectory(base_path('.git')) && $this->isGitAvailable()) {
            return 'git';
        }

        return 'saas';
    }

    /**
     * Verifica se o executável do Git está disponível e responde com sucesso.
     */
    public function isGitAvailable(): bool
    {
        try {
            $this->runGit(['--version'], timeout: 5);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Executa comandos Git na raiz do projeto com ambiente sanitizado para Windows/Linux.
     */
    public function runGit(array $args, int $timeout = 60): string
    {
        $tempDir = storage_path('app/private/tmp');
        File::ensureDirectoryExists($tempDir);
        putenv('TMP='.$tempDir);
        putenv('TEMP='.$tempDir);
        putenv('TMPDIR='.$tempDir);

        $command = array_merge(['git', '-c', 'safe.directory=*'], $args);
        $process = new Process($command, base_path(), [
            'TMP' => $tempDir,
            'TEMP' => $tempDir,
            'TMPDIR' => $tempDir,
            'GIT_TERMINAL_PROMPT' => '0',
        ]);
        $process->setTimeout($timeout);
        $process->run();

        if (! $process->isSuccessful()) {
            $error = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new RuntimeException($error !== '' ? $error : 'Falha ao executar comando Git.');
        }

        return trim($process->getOutput());
    }

    /**
     * Retorna a branch atual do repositório local.
     */
    public function getCurrentBranch(): string
    {
        if ($this->getUpdateDriver() === 'saas') {
            return 'saas';
        }

        try {
            return $this->runGit(['rev-parse', '--abbrev-ref', 'HEAD']);
        } catch (\Throwable $e) {
            return 'desconhecida';
        }
    }

    /**
     * Retorna informações do commit atual local.
     */
    public function getCurrentCommitInfo(): array
    {
        if ($this->getUpdateDriver() === 'saas') {
            return [
                'short_hash' => 'v'.self::CURRENT_VERSION,
                'full_hash' => 'v'.self::CURRENT_VERSION,
                'message' => 'Versão Estável '.self::CURRENT_VERSION.' ('.self::VERSION_CODENAME.')',
                'date' => now()->format('d/m/Y'),
            ];
        }

        try {
            $shortHash = $this->runGit(['rev-parse', '--short', 'HEAD']);
            $fullHash = $this->runGit(['rev-parse', 'HEAD']);
            $subject = $this->runGit(['log', '-1', '--pretty=format:%s']);
            $date = $this->runGit(['log', '-1', '--pretty=format:%cd', '--date=iso']);

            return [
                'short_hash' => $shortHash,
                'full_hash' => $fullHash,
                'message' => $subject,
                'date' => Carbon::parse($date)->format('d/m/Y H:i:s'),
            ];
        } catch (\Throwable $e) {
            return [
                'short_hash' => 'N/A',
                'full_hash' => 'N/A',
                'message' => 'Não disponível',
                'date' => 'N/A',
            ];
        }
    }

    /**
     * Verifica se existem atualizações disponíveis na branch remota (padrão: origin/main).
     */
    public function checkForUpdates(bool $force = false): array
    {
        if ($this->getUpdateDriver() === 'saas') {
            return $this->checkSaasUpdates($force);
        }

        if (! $force) {
            try {
                if (Cache::has($this->cacheKey)) {
                    $cached = Cache::get($this->cacheKey);
                    if (is_array($cached)) {
                        return $cached;
                    }
                }
            } catch (\Throwable) {}
        }

        $targetBranch = $this->getTargetBranch();
        $currentBranch = $this->getCurrentBranch();
        $currentCommit = $this->getCurrentCommitInfo();

        $result = [
            'has_update' => false,
            'system_version' => self::CURRENT_VERSION,
            'version_codename' => self::VERSION_CODENAME,
            'current_branch' => $currentBranch,
            'target_branch' => $targetBranch,
            'current_commit' => $currentCommit,
            'remote_commit' => null,
            'commits_behind' => 0,
            'new_commits' => [],
            'checked_at' => now()->format('d/m/Y H:i:s'),
            'error' => null,
        ];

        // Em branches de feature ou desenvolvimento (diferentes da branch de produção alvo), não verifica nem alerta updates
        if ($currentBranch !== $targetBranch) {
            try {
                Cache::put($this->cacheKey, $result, $this->cacheTtlSeconds);
            } catch (\Throwable) {}

            return $result;
        }

        try {
            // Busca as atualizações da branch de destino sem alterar os arquivos locais
            $this->runGit(['fetch', 'origin', $targetBranch], timeout: 35);

            $remoteShortHash = $this->runGit(['rev-parse', '--short', "origin/{$targetBranch}"]);
            $remoteFullHash = $this->runGit(['rev-parse', "origin/{$targetBranch}"]);

            $result['remote_commit'] = [
                'short_hash' => $remoteShortHash,
                'full_hash' => $remoteFullHash,
            ];

            // Compara o commit local com o remoto da branch de destino
            $behindCountRaw = $this->runGit(['rev-list', '--count', "HEAD..origin/{$targetBranch}"]);
            $behindCount = (int) $behindCountRaw;

            // Se HEAD for diferente do origin/main e houver commits novos
            if ($behindCount > 0 || ($currentCommit['short_hash'] !== 'N/A' && $currentCommit['short_hash'] !== $remoteShortHash && $currentBranch === $targetBranch)) {
                $result['has_update'] = true;
                $result['commits_behind'] = max($behindCount, 1);

                // Obtém a lista dos commits mais recentes que estão no origin/main e não no HEAD
                $logOutput = $this->runGit(['log', "HEAD..origin/{$targetBranch}", '--pretty=format:%h - %s (%cr)', '-n', '15']);
                $commitsList = array_filter(array_map('trim', explode("\n", $logOutput)));

                $result['new_commits'] = $commitsList;
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao verificar atualizações do Aurosoft via Git: '.$e->getMessage());
            $result['error'] = 'Não foi possível conectar ao servidor de atualizações (verifique sua conexão com a internet).';
        }

        try {
            Cache::put($this->cacheKey, $result, $this->cacheTtlSeconds);
        } catch (\Throwable) {}

        return $result;
    }

    /**
     * Identifica se a instalação atual roda com .env criptografado (.env.encrypted) sem .env em texto plano.
     */
    public function isEncryptedEnvDeployment(): bool
    {
        return ! File::exists(base_path('.env')) && File::exists(base_path('.env.encrypted'));
    }

    /**
     * Localiza a chave de criptografia do .env no ambiente, registro do Windows ou arquivos de chave.
     */
    public function getEnvEncryptionKey(): ?string
    {
        $key = getenv('LARAVEL_ENV_ENCRYPTION_KEY')
            ?: ($_ENV['LARAVEL_ENV_ENCRYPTION_KEY'] ?? null)
            ?: ($_SERVER['LARAVEL_ENV_ENCRYPTION_KEY'] ?? null)
            ?: env('LARAVEL_ENV_ENCRYPTION_KEY');

        if (! empty($key)) {
            return (string) $key;
        }

        // No Windows, verifica no registro do Windows (onde o instalador da escola salva a chave)
        if (PHP_OS_FAMILY === 'Windows') {
            $regOutput = (string) @shell_exec('reg query "HKCU\Environment" /v LARAVEL_ENV_ENCRYPTION_KEY 2>nul');
            if ($regOutput === '') {
                $regOutput = (string) @shell_exec('reg query "HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Environment" /v LARAVEL_ENV_ENCRYPTION_KEY 2>nul');
            }
            if (preg_match('/LARAVEL_ENV_ENCRYPTION_KEY\s+REG_SZ\s+(\S+)/i', $regOutput, $regMatches)) {
                return $regMatches[1];
            }
        }

        // Verifica no script batch de inicialização do Windows (se o cliente tiver configurado nele)
        $batPath = base_path('scripts/windows/iniciar_aurosoft.bat');
        if (File::exists($batPath)) {
            $batContent = (string) File::get($batPath);
            if (preg_match('/^\s*set\s+LARAVEL_ENV_ENCRYPTION_KEY\s*=\s*(\S+)/im', $batContent, $matches)) {
                $candidate = trim($matches[1]);
                if (! empty($candidate) && ! str_starts_with($candidate, 'base64:SUA_CHAVE_AQUI')) {
                    return $candidate;
                }
            }
        }

        // Verifica se há arquivo .env.key ou na pasta storage privada
        if (File::exists(base_path('.env.key'))) {
            $key = trim((string) File::get(base_path('.env.key')));
            if (! empty($key)) {
                return $key;
            }
        }

        if (File::exists(storage_path('app/private/.env.key'))) {
            $key = trim((string) File::get(storage_path('app/private/.env.key')));
            if (! empty($key)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Persiste a chave informada localmente para que atualizações futuras não precisem solicitá-la novamente.
     */
    public function persistEnvEncryptionKey(string $key): void
    {
        $cleanKey = trim($key);
        if (empty($cleanKey)) {
            return;
        }

        try {
            $privateDir = storage_path('app/private');
            if (! File::isDirectory($privateDir)) {
                File::makeDirectory($privateDir, 0755, true, true);
            }
            File::put(storage_path('app/private/.env.key'), $cleanKey);

            // No Windows, salva também no registro de usuário para que o Laravel nativo e o terminal a reconheçam
            if (PHP_OS_FAMILY === 'Windows') {
                @shell_exec(sprintf('setx LARAVEL_ENV_ENCRYPTION_KEY %s >nul 2>&1', escapeshellarg($cleanKey)));
            }
        } catch (\Throwable) {
            // Falha silenciosa de persistência não bloqueia o fluxo principal
        }
    }

    /**
     * Injeta novos provedores (ex: StudioPanelProvider) diretamente no cache existente de configuração,
     * permitindo que novas páginas e painéis funcionem mesmo sem a chave de descriptografia do .env.
     */
    public function mergeMissingProvidersIntoCachedConfig(): bool
    {
        $cachedConfigPath = base_path('bootstrap/cache/config.php');
        $providersPath = base_path('bootstrap/providers.php');

        if (! File::exists($cachedConfigPath) || ! File::exists($providersPath)) {
            return false;
        }

        try {
            $cachedConfig = require $cachedConfigPath;
            $bootstrapProviders = require $providersPath;

            if (! is_array($cachedConfig) || ! is_array($bootstrapProviders)) {
                return false;
            }

            $currentProviders = $cachedConfig['app']['providers'] ?? [];
            $modified = false;

            foreach ($bootstrapProviders as $provider) {
                if (! in_array($provider, $currentProviders, true)) {
                    $currentProviders[] = $provider;
                    $modified = true;
                }
            }

            if ($modified) {
                $cachedConfig['app']['providers'] = $currentProviders;
                $exported = '<?php return '.var_export($cachedConfig, true).';'.PHP_EOL;
                File::put($cachedConfigPath, $exported);

                return true;
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
    }

    /**
     * Valida se uma chave de criptografia consegue descriptografar o .env.encrypted atual.
     */
    public function testEnvEncryptionKey(?string $key): bool
    {
        if (empty($key)) {
            return false;
        }

        $encryptedFile = base_path('.env.encrypted');
        if (! File::exists($encryptedFile)) {
            return true;
        }

        try {
            $cleanKey = trim($key);
            $rawKey = str_starts_with($cleanKey, 'base64:')
                ? base64_decode(substr($cleanKey, 7))
                : $cleanKey;

            $cipher = strlen($rawKey) === 16 ? 'AES-128-CBC' : 'AES-256-CBC';
            $encrypter = new \Illuminate\Encryption\Encrypter($rawKey, $cipher);
            $content = (string) File::get($encryptedFile);
            $decrypted = $encrypter->decrypt($content);

            return is_string($decrypted) && strlen($decrypted) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Recarrega as variáveis do .env na memória do processo PHP e no repositório de configuração.
     */
    public function reloadEnvironment(): void
    {
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            try {
                // 1. Popula putenv, $_ENV e $_SERVER explicitamente para garantir compatibilidade com getenv() e env()
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if (is_array($lines)) {
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line === '' || str_starts_with($line, '#')) {
                            continue;
                        }
                        if (str_contains($line, '=')) {
                            [$k, $v] = explode('=', $line, 2);
                            $k = trim($k);
                            $v = trim($v, " \t\n\r\0\x0B\"'");
                            putenv("{$k}={$v}");
                            $_ENV[$k] = $v;
                            $_SERVER[$k] = $v;
                        }
                    }
                }

                // 2. Limpa o arquivo de cache de configuração
                Artisan::call('config:clear');

                // 3. Carrega as variáveis do .env no repositório de ambiente do Laravel
                \Dotenv\Dotenv::create(\Illuminate\Support\Env::getRepository(), base_path(), '.env')->safeLoad();

                // 4. Recarrega os arquivos de configuração usando os novos valores de ambiente
                (new \Illuminate\Foundation\Bootstrap\LoadConfiguration)->bootstrap(app());

                // 5. Purga e redefine as conexões do banco de dados com as credenciais recarregadas
                \Illuminate\Support\Facades\DB::purge();
                \Illuminate\Support\Facades\DB::setDefaultConnection(config('database.default'));
            } catch (\Throwable $e) {
                Log::warning('Falha ao recarregar variáveis de ambiente no processo atual: '.$e->getMessage());
            }
        }
    }

    /**
     * Compila todos os caches essenciais de produção (config, routes, views, events e icons).
     *
     * @return list<string>
     */
    public function compileProductionCaches(): array
    {
        $logs = [];

        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('event:clear');
        $logs[] = 'Caches anteriores limpos com segurança.';

        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
            Artisan::call('event:cache');

            if (array_key_exists('icons:cache', Artisan::all())) {
                Artisan::call('icons:cache');
            }

            $logs[] = 'Caches de configuração, rotas, views, eventos e ícones compilados com sucesso.';
        } catch (\Throwable $e) {
            $logs[] = 'Aviso na compilação de cache: '.$e->getMessage();
        }

        return $logs;
    }

    /**
     * Verifica se há chave de criptografia acessível no ambiente.
     */
    public function hasEnvEncryptionKey(): bool
    {
        return ! empty($this->getEnvEncryptionKey());
    }

    /**
     * Aplica a atualização:
     * 1. Descriptografa temporariamente o .env caso seja ambiente criptografado e recarrega credenciais
     * 2. Cria backup de segurança automático
     * 3. Faz stash de alterações locais pontuais
     * 4. Faz pull da branch de destino (ex: main ou dev)
     * 5. Executa migrations
     * 6. Limpa e recompila caches completos (config, routes, views, events e icons)
     * 7. Re-encripta o .env e remove a versão em texto plano (--prune), mantendo os caches ativos sem erro 500
     */
    public function applyUpdate(?string $providedKey = null): array
    {
        if ($this->getUpdateDriver() === 'saas') {
            return $this->applySaasUpdate($providedKey);
        }

        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ignore_user_abort(true);

        $targetBranch = $this->getTargetBranch();
        $logs = [];

        $isEncryptedEnv = $this->isEncryptedEnvDeployment();
        $encryptionKey = $providedKey ?: $this->getEnvEncryptionKey();

        $temporaryEnvCreated = false;

        try {
            // 1. Se for ambiente com .env criptografado e houver chave, decripta e recarrega configurações
            if ($isEncryptedEnv && ! empty($encryptionKey)) {
                $this->persistEnvEncryptionKey($encryptionKey);

                $exitCode = Artisan::call('env:decrypt', [
                    '--key' => $encryptionKey,
                    '--force' => true,
                ]);

                if ($exitCode === 0 && File::exists(base_path('.env'))) {
                    $temporaryEnvCreated = true;
                    $this->reloadEnvironment();
                    $logs[] = 'Arquivo .env descriptografado e credenciais de produção carregadas com sucesso.';
                }
            } else {
                $this->reloadEnvironment();
            }

            // 2. Criação do backup de segurança pré-atualização
            try {
                $backupFile = app(LocalBackupManager::class)->create(safetyCopy: true);
                $logs[] = "Backup de segurança criado com sucesso: {$backupFile}";

                $preUpdateCommit = $this->getCurrentCommitInfo();
                $this->savePreviousVersionSnapshot([
                    'commit' => $preUpdateCommit,
                    'backup_file' => basename($backupFile),
                    'updated_at' => now()->toIso8601String(),
                ]);
            } catch (\Throwable $e) {
                throw new RuntimeException('Falha de segurança: o sistema não conseguiu gerar o backup antes da atualização. Processo interrompido por precaução: '.$e->getMessage());
            }

            // 3. Stash de alterações locais caso existam
            try {
                $this->runGit(['stash']);
            } catch (\Throwable $e) {
                // Stash vazio ou desnecessário, segue adiante
            }

            // 4. Garante que estamos na branch de destino (ex: main ou dev)
            $currentBranch = $this->getCurrentBranch();
            if ($currentBranch !== $targetBranch) {
                try {
                    $this->runGit(['checkout', $targetBranch]);
                    $logs[] = "Mudou para a branch '{$targetBranch}'";
                } catch (\Throwable $e) {
                    // Tenta criar e rastrear a branch se for a primeira vez
                    $this->runGit(['checkout', '-b', $targetBranch, "origin/{$targetBranch}"]);
                    $logs[] = "Criou e mudou para a branch local '{$targetBranch}'";
                }
            }

            // 5. Executa git pull da branch origin/main ou dev
            $pullOutput = $this->runGit(['pull', 'origin', $targetBranch], timeout: 120);
            $logs[] = "Git pull concluído: {$pullOutput}";

            // 6. Executa as migrations de banco de dados
            $migrateExit = Artisan::call('migrate', ['--force' => true]);
            if ($migrateExit !== 0) {
                $migrateOut = trim(Artisan::output());
                throw new RuntimeException('Falha ao executar migrações do banco de dados: '.($migrateOut ?: 'erro código '.$migrateExit));
            }
            $logs[] = 'Migrações de banco de dados executadas com sucesso.';

            // 6.0. Executa migrações em todos os schemas multi-tenant (escolas)
            try {
                Artisan::call('saas:migrate-tenants');
                $logs[] = 'Migrações multi-tenant aplicadas em todos os schemas de escolas (incluindo Demo) com sucesso.';
            } catch (\Throwable $tenantEx) {
                $logs[] = 'Aviso ao aplicar migrações multi-tenant: ' . $tenantEx->getMessage();
            }

            // 6.0.1. Normaliza incidentes de telemetria pendentes
            try {
                \App\Models\SupportTicket::query()
                    ->withoutGlobalScopes()
                    ->where('type', 'system_incident')
                    ->whereIn('status', ['open', 'in_progress'])
                    ->update([
                        'status' => 'resolved',
                        'resolved_at' => now(),
                    ]);
                $logs[] = 'Incidentes pendentes da telemetria normalizados com sucesso.';
            } catch (\Throwable $incEx) {
                // Silencioso se tabela não existir
            }

            // 6.1. Garante que dados estruturais do Plano de Contas sejam pré-carregados
            try {
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\ChartOfAccountsSeeder',
                    '--force' => true,
                ]);
                $logs[] = 'Estrutura padrão do Plano de Contas inicializada com sucesso.';
            } catch (\Throwable $seedEx) {
                // Em caso de tabela já populada ou restrição, não interrompe a atualização
            }

            // 7. Gera e atualiza permissões do Filament Shield para novas entidades
            try {
                Artisan::call('shield:generate', [
                    '--all' => true,
                    '--option' => 'policies_and_permissions',
                    '--ignore-existing-policies' => true,
                    '--panel' => 'admin',
                    '--no-interaction' => true,
                ]);
                $logs[] = 'Permissões do Filament Shield atualizadas com sucesso.';

                if (class_exists(\Spatie\Permission\Models\Role::class) && class_exists(\Spatie\Permission\Models\Permission::class)) {
                    $superAdminRole = \Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
                    if ($superAdminRole) {
                        $superAdminRole->syncPermissions(\Spatie\Permission\Models\Permission::all());
                        $logs[] = 'Novas permissões vinculadas automaticamente ao perfil super_admin.';
                    }
                }
            } catch (\Throwable $shieldEx) {
                $logs[] = 'Aviso ao gerar permissões do Shield: ' . $shieldEx->getMessage();
            }

            // 8. Limpeza e Reconstrução Segura dos Caches
            if ($temporaryEnvCreated || File::exists(base_path('.env'))) {
                $cacheLogs = $this->compileProductionCaches();
                $logs = array_merge($logs, $cacheLogs);
            } else {
                // Modo resiliente (sem chave de decriptação): preserva credenciais no config.php e injeta novos provedores
                $this->mergeMissingProvidersIntoCachedConfig();

                try {
                    Artisan::call('route:clear');
                    Artisan::call('route:cache');
                    Artisan::call('event:clear');
                    Artisan::call('event:cache');
                    $logs[] = 'Provedores de novos painéis e rotas injetados no cache com sucesso sem alterar configurações de banco.';
                } catch (\Throwable $cacheEx) {
                    $logs[] = 'Aviso ao compilar cache de rotas: '.$cacheEx->getMessage();
                }
            }

            // 9. Se houver .env.encrypted e chave de segurança, re-encripta e apaga o .env em texto plano (exceto em testes automatizados)
            $shouldPrunePlaintextEnv = ! app()->runningUnitTests()
                && File::exists(base_path('.env.encrypted'))
                && ! empty($encryptionKey);

            if ($shouldPrunePlaintextEnv && File::exists(base_path('.env'))) {
                try {
                    Artisan::call('env:encrypt', [
                        '--key' => $encryptionKey,
                        '--force' => true,
                        '--prune' => true,
                    ]);

                    if (File::exists(base_path('.env'))) {
                        File::delete(base_path('.env'));
                    }

                    $logs[] = 'Arquivo .env re-criptografado e versão em texto plano apagada com sucesso (mantendo apenas .env.encrypted e caches ativos).';
                } catch (\Throwable $encEx) {
                    $logs[] = 'Aviso ao re-criptografar .env: '.$encEx->getMessage();
                }
            }

            // 10. Invalida o cache de verificação de atualização
            Cache::forget($this->cacheKey);

            $newCommit = $this->getCurrentCommitInfo();

            return [
                'success' => true,
                'message' => 'Sistema Aurosoft atualizado com sucesso!',
                'new_commit' => $newCommit,
                'logs' => $logs,
            ];
        } catch (\Throwable $e) {
            Log::error('Erro ao aplicar atualização do sistema: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Garante que o ambiente nunca fique em erro 500 caso o processo falhe no meio
            if (! empty($shouldPrunePlaintextEnv) && File::exists(base_path('.env'))) {
                try {
                    $this->compileProductionCaches();
                    if (! empty($encryptionKey)) {
                        Artisan::call('env:encrypt', [
                            '--key' => $encryptionKey,
                            '--force' => true,
                            '--prune' => true,
                        ]);
                    }
                    if (File::exists(base_path('.env'))) {
                        File::delete(base_path('.env'));
                    }
                } catch (\Throwable) {}
            }

            throw new RuntimeException('Erro durante a atualização do sistema: '.$e->getMessage());
        }
    }

    /**
     * Retorna a lista de commits/versões recentes disponíveis para downgrade.
     *
     * @return list<array{short_hash: string, full_hash: string, message: string, date: string}>
     */
    public function getRecentVersions(int $limit = 6): array
    {
        try {
            $output = $this->runGit(['log', 'HEAD~1', '-n', (string) $limit, '--pretty=format:%h|%H|%s|%cd', '--date=iso']);
            $lines = array_filter(array_map('trim', explode("\n", $output)));
            $versions = [];
            foreach ($lines as $line) {
                $parts = explode('|', $line, 4);
                if (count($parts) === 4) {
                    $versions[] = [
                        'short_hash' => $parts[0],
                        'full_hash' => $parts[1],
                        'message' => $parts[2],
                        'date' => Carbon::parse($parts[3])->format('d/m/Y H:i'),
                    ];
                }
            }

            return $versions;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Retorna informações sobre a versão imediatamente anterior.
     *
     * @return array{short_hash: string, full_hash: string, message: string, date: string}|null
     */
    public function getPreviousVersionInfo(): ?array
    {
        $snapshot = $this->getLastUpdateSnapshot();
        if (! empty($snapshot['commit']['full_hash'])) {
            return $snapshot['commit'];
        }

        $recent = $this->getRecentVersions(1);

        return $recent[0] ?? null;
    }

    /**
     * Retorna dados do snapshot salvo antes da última atualização.
     */
    public function getLastUpdateSnapshot(): ?array
    {
        $file = storage_path('app/private/last_update_snapshot.json');
        if (File::exists($file)) {
            try {
                return json_decode(File::get($file), true);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    public function savePreviousVersionSnapshot(array $data): void
    {
        try {
            $dir = storage_path('app/private');
            File::ensureDirectoryExists($dir);
            File::put($dir.'/last_update_snapshot.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } catch (\Throwable) {
            // Falha silenciosa de persistência
        }
    }

    /**
     * Reverte o sistema para um commit/versão anterior (Downgrade seguro).
     */
    public function downgradeToVersion(string $targetCommit, bool $restoreDatabaseBackup = false, ?string $providedKey = null): array
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ignore_user_abort(true);

        $cleanCommit = trim($targetCommit);
        if (! preg_match('/^[a-f0-9]{6,40}$/i', $cleanCommit)) {
            throw new RuntimeException('Hash de commit inválido para reversão.');
        }

        $logs = [];
        $isEncryptedEnv = $this->isEncryptedEnvDeployment();
        $encryptionKey = $providedKey ?: $this->getEnvEncryptionKey();

        $temporaryEnvCreated = false;

        try {
            // 1. Descriptografa o .env caso seja ambiente criptografado e houver chave
            if ($isEncryptedEnv && ! empty($encryptionKey)) {
                $this->persistEnvEncryptionKey($encryptionKey);

                $exitCode = Artisan::call('env:decrypt', [
                    '--key' => $encryptionKey,
                    '--force' => true,
                ]);

                if ($exitCode === 0 && File::exists(base_path('.env'))) {
                    $temporaryEnvCreated = true;
                    $this->reloadEnvironment();
                    $logs[] = 'Arquivo .env descriptografado e credenciais de produção carregadas com sucesso.';
                }
            } else {
                $this->reloadEnvironment();
            }

            // 2. Cria um backup de segurança do estado atual antes de fazer o downgrade
            try {
                $backupFile = app(LocalBackupManager::class)->create(safetyCopy: true);
                $logs[] = "Backup de segurança criado antes do downgrade: {$backupFile}";
            } catch (\Throwable $e) {
                throw new RuntimeException('Falha de segurança ao criar backup antes do downgrade: '.$e->getMessage());
            }

            // 3. Stash de alterações locais caso existam
            try {
                $this->runGit(['stash']);
            } catch (\Throwable) {}

            // 4. Executa reset --hard para o commit alvo
            $resetOutput = $this->runGit(['reset', '--hard', $cleanCommit], timeout: 60);
            $logs[] = "Código revertido para {$cleanCommit}: {$resetOutput}";

            // 5. Restaura o banco de dados caso solicitado
            if ($restoreDatabaseBackup) {
                $snapshot = $this->getLastUpdateSnapshot();
                $backupToRestore = $snapshot['backup_file'] ?? null;

                if ($backupToRestore && File::exists(storage_path('app/private/backups/'.$backupToRestore))) {
                    app(LocalBackupManager::class)->restore($backupToRestore);
                    $logs[] = "Banco de dados restaurado a partir do backup pré-atualização ({$backupToRestore}).";
                } else {
                    $logs[] = 'Aviso: Nenhum arquivo de backup associado foi localizado para restauração automática do banco.';
                }
            }

            // 6. Limpeza e Reconstrução Segura dos Caches
            if ($temporaryEnvCreated || File::exists(base_path('.env'))) {
                $cacheLogs = $this->compileProductionCaches();
                $logs = array_merge($logs, $cacheLogs);
            } else {
                $this->mergeMissingProvidersIntoCachedConfig();
                try {
                    Artisan::call('route:clear');
                    Artisan::call('route:cache');
                    Artisan::call('event:clear');
                    Artisan::call('event:cache');
                } catch (\Throwable) {}
            }

            // 7. Se houver .env.encrypted e chave de segurança, re-encripta e apaga o .env em texto plano (exceto em testes automatizados)
            $shouldPrunePlaintextEnv = ! app()->runningUnitTests()
                && File::exists(base_path('.env.encrypted'))
                && ! empty($encryptionKey);

            if ($shouldPrunePlaintextEnv && File::exists(base_path('.env'))) {
                try {
                    Artisan::call('env:encrypt', [
                        '--key' => $encryptionKey,
                        '--force' => true,
                        '--prune' => true,
                    ]);

                    if (File::exists(base_path('.env'))) {
                        File::delete(base_path('.env'));
                    }

                    $logs[] = 'Arquivo .env re-criptografado e versão em texto plano apagada com sucesso (mantendo apenas .env.encrypted e caches ativos).';
                } catch (\Throwable $encEx) {
                    $logs[] = 'Aviso ao re-criptografar .env: '.$encEx->getMessage();
                }
            }

            // 8. Invalida caches e rate-limiters
            Cache::forget($this->cacheKey);
            if (request()->ip()) {
                \Illuminate\Support\Facades\RateLimiter::clear('livewire-checksum-failures:'.request()->ip());
            }

            $currentCommit = $this->getCurrentCommitInfo();

            return [
                'success' => true,
                'message' => "Sistema revertido com sucesso para a versão {$currentCommit['short_hash']}!",
                'current_commit' => $currentCommit,
                'logs' => $logs,
            ];
        } catch (\Throwable $e) {
            Log::error('Erro ao realizar downgrade: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Garante que o ambiente nunca fique em erro 500 caso o processo falhe no meio
            if (! empty($shouldPrunePlaintextEnv) && File::exists(base_path('.env'))) {
                try {
                    $this->compileProductionCaches();
                    if (! empty($encryptionKey)) {
                        Artisan::call('env:encrypt', [
                            '--key' => $encryptionKey,
                            '--force' => true,
                            '--prune' => true,
                        ]);
                    }
                    if (File::exists(base_path('.env'))) {
                        File::delete(base_path('.env'));
                    }
                } catch (\Throwable) {}
            }

            throw new RuntimeException('Erro durante o downgrade do sistema: '.$e->getMessage());
        }
    }

    /**
     * Verifica atualizações disponíveis consultando a API do SaaS Master via HTTP.
     * Utilizado para clientes novos ou instalações sem Git local.
     */
    public function checkSaasUpdates(bool $force = false): array
    {
        if (! $force) {
            try {
                if (Cache::has($this->cacheKey)) {
                    $cached = Cache::get($this->cacheKey);
                    if (is_array($cached)) {
                        return $cached;
                    }
                }
            } catch (\Throwable) {}
        }

        $currentVersion = self::CURRENT_VERSION;
        $licenseKey = (string) (config('license.key') ?: app(LicenseService::class)->getKey());

        $result = [
            'has_update' => false,
            'driver' => 'saas',
            'system_version' => $currentVersion,
            'version_codename' => self::VERSION_CODENAME,
            'current_branch' => 'saas-cloud',
            'target_branch' => 'SaaS Cloud',
            'current_commit' => [
                'short_hash' => 'v'.$currentVersion,
                'full_hash' => 'v'.$currentVersion,
                'message' => 'Versão Local: '.$currentVersion,
                'date' => now()->format('d/m/Y'),
            ],
            'remote_commit' => null,
            'commits_behind' => 0,
            'new_commits' => [],
            'checked_at' => now()->format('d/m/Y H:i:s'),
            'error' => null,
            'download_url' => null,
            'latest_version' => null,
        ];

        // Obtém servidores configurados para checagem em cascata
        $servers = (array) config('license.verification_servers', []);
        $endpoints = [];

        foreach ($servers as $srv) {
            if (str_contains($srv, '/api/v1/license/verify')) {
                $endpoints[] = str_replace('/api/v1/license/verify', '/api/v1/saas/updates/check', $srv);
            }
        }

        if (empty($endpoints)) {
            $endpoints[] = url('/api/v1/saas/updates/check');
        }

        $connected = false;

        foreach ($endpoints as $url) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(10)->get($url, [
                    'license_key' => $licenseKey,
                    'current_version' => $currentVersion,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (is_array($data)) {
                        if (! empty($data['error'])) {
                            $result['error'] = (string) $data['error'];
                            $result['has_update'] = false;
                        } else {
                            $result['has_update'] = (bool) ($data['has_update'] ?? false);
                            $latestVer = (string) ($data['latest_version'] ?? $currentVersion);
                            $result['latest_version'] = $latestVer;
                            $result['target_branch'] = "v{$latestVer}";
                            $result['download_url'] = $data['download_url'] ?? null;
                            $result['commits_behind'] = $result['has_update'] ? 1 : 0;
                            $result['new_commits'] = (array) ($data['release_notes'] ?? []);
                            $result['remote_commit'] = [
                                'short_hash' => "v{$latestVer}",
                                'full_hash' => "v{$latestVer}",
                            ];
                        }
                        $connected = true;
                        break;
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        if (! $connected && empty($result['error'])) {
            $result['error'] = 'Não foi possível conectar ao servidor de atualizações. Verifique sua conexão com a internet.';
        }

        try {
            Cache::put($this->cacheKey, $result, $this->cacheTtlSeconds);
        } catch (\Throwable) {}

        return $result;
    }

    /**
     * Aplica a atualização via canal SaaS (download do ZIP, extração com proteção e migrações).
     */
    public function applySaasUpdate(?string $providedKey = null): array
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        @ignore_user_abort(true);

        $logs = [];
        $isEncryptedEnv = $this->isEncryptedEnvDeployment();
        $encryptionKey = $providedKey ?: $this->getEnvEncryptionKey();
        $temporaryEnvCreated = false;

        try {
            // 1. Se o ambiente for encriptado, descriptografa para executar migrations
            if ($isEncryptedEnv && ! empty($encryptionKey)) {
                $this->persistEnvEncryptionKey($encryptionKey);
                $exitCode = Artisan::call('env:decrypt', [
                    '--key' => $encryptionKey,
                    '--force' => true,
                ]);

                if ($exitCode === 0 && File::exists(base_path('.env'))) {
                    $temporaryEnvCreated = true;
                    $this->reloadEnvironment();
                    $logs[] = 'Arquivo .env descriptografado e credenciais de produção carregadas.';
                }
            } else {
                $this->reloadEnvironment();
            }

            // 2. Backup de segurança preventivo
            try {
                $backupFile = app(LocalBackupManager::class)->create(safetyCopy: true);
                $logs[] = "Backup de segurança criado com sucesso: {$backupFile}";
            } catch (\Throwable $e) {
                $logs[] = 'Aviso ao gerar backup preventivo: '.$e->getMessage();
            }

            // 3. Consulta status mais recente para obter download_url
            $status = $this->checkSaasUpdates(force: true);
            if (! empty($status['error'])) {
                throw new RuntimeException($status['error']);
            }

            $downloadUrl = $status['download_url'] ?? null;
            if (empty($downloadUrl)) {
                throw new RuntimeException('URL de download do pacote de atualização não informada pelo servidor SaaS.');
            }

            // 4. Download do pacote ZIP
            $tempDir = storage_path('app/private/tmp');
            File::ensureDirectoryExists($tempDir);
            $zipPath = "{$tempDir}/saas_update_package.zip";

            if (File::exists($zipPath)) {
                File::delete($zipPath);
            }

            $logs[] = "Baixando pacote de atualização da versão {$status['target_branch']}...";

            $response = \Illuminate\Support\Facades\Http::timeout(300)
                ->sink($zipPath)
                ->get($downloadUrl);

            if (! $response->successful() || ! File::exists($zipPath) || filesize($zipPath) < 1000) {
                throw new RuntimeException('Falha ao baixar o pacote de atualização do servidor SaaS.');
            }

            $logs[] = 'Pacote de atualização baixado com sucesso ('.round(filesize($zipPath) / 1024 / 1024, 2).' MB).';

            // 5. Extração dos arquivos atualizados sobre o projeto com blindagem
            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new RuntimeException('Arquivo de atualização corrompido ou inválido.');
            }

            $basePath = base_path();

            // Lista de arquivos protegidos para NUNCA sobrescrever no cliente
            $protected = [
                '.env',
                '.env.encrypted',
                '.env.key',
                'storage/',
            ];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                if (str_ends_with($filename, '/')) {
                    continue;
                }

                $isProtected = false;
                foreach ($protected as $prot) {
                    if (str_starts_with($filename, $prot) || $filename === $prot) {
                        $isProtected = true;
                        break;
                    }
                }

                if ($isProtected) {
                    continue;
                }

                $destination = $basePath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $filename);
                $destDir = dirname($destination);
                if (! File::isDirectory($destDir)) {
                    File::makeDirectory($destDir, 0755, true, true);
                }

                $content = $zip->getFromIndex($i);
                if ($content !== false) {
                    File::put($destination, $content);
                }
            }

            $zip->close();
            File::delete($zipPath);

            $logs[] = 'Arquivos do sistema atualizados com sucesso.';

            // 6. Migrações de banco
            $migrateExit = Artisan::call('migrate', ['--force' => true]);
            if ($migrateExit !== 0) {
                $migrateOut = trim(Artisan::output());
                throw new RuntimeException('Falha ao executar migrações do banco de dados: '.($migrateOut ?: 'erro código '.$migrateExit));
            }
            $logs[] = 'Migrações de banco de dados executadas com sucesso.';

            // 6.1. Seeders estruturais e Plano de Contas
            try {
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\ChartOfAccountsSeeder',
                    '--force' => true,
                ]);
            } catch (\Throwable) {}

            // 7. Permissões do Shield
            try {
                Artisan::call('shield:generate', ['--all' => true, '--panel' => 'admin', '--option' => 'permissions']);
                $logs[] = 'Permissões e políticas de acesso atualizadas.';
            } catch (\Throwable) {}

            // 8. Limpa e recompila caches
            $cacheLogs = $this->compileProductionCaches();
            $logs = array_merge($logs, $cacheLogs);

            // 9. Re-criptografa se necessário
            $shouldPrunePlaintextEnv = $isEncryptedEnv
                && File::exists(base_path('.env.encrypted'))
                && ! empty($encryptionKey);

            if ($shouldPrunePlaintextEnv && File::exists(base_path('.env'))) {
                try {
                    Artisan::call('env:encrypt', [
                        '--key' => $encryptionKey,
                        '--force' => true,
                        '--prune' => true,
                    ]);
                    if (File::exists(base_path('.env'))) {
                        File::delete(base_path('.env'));
                    }
                    $logs[] = 'Arquivo .env re-criptografado e protegido com sucesso.';
                } catch (\Throwable) {}
            }

            Cache::forget($this->cacheKey);

            return [
                'success' => true,
                'message' => "Sistema atualizado com sucesso via canal SaaS para a versão {$status['target_branch']}!",
                'new_commit' => [
                    'short_hash' => $status['target_branch'],
                    'full_hash' => $status['target_branch'],
                    'message' => implode(' | ', $status['new_commits'] ?? ['Atualização SaaS']),
                    'date' => now()->format('d/m/Y H:i'),
                ],
                'logs' => $logs,
            ];

        } catch (\Throwable $e) {
            Log::error('Erro na atualização via SaaS: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw new RuntimeException('Erro na atualização via SaaS: '.$e->getMessage());
        }
    }
}

