<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\License;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;

class SaasBackupManager
{
    public function directory(): string
    {
        $dir = storage_path('app/private/saas_backups');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    /**
     * Gera o backup completo da instância inteira do banco PostgreSQL (Global).
     */
    public function createGlobalBackup(): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $filename = "backup_GLOBAL_{$timestamp}.dump";
        $filepath = $this->directory() . '/' . $filename;

        $connection = $this->getConnectionConfig();
        $binary = $this->resolveBinary('pg_dump');

        $command = [
            $binary,
            '--format=custom',
            '--no-owner',
            '--no-privileges',
            '--host=' . $connection['host'],
            '--port=' . $connection['port'],
            '--username=' . $connection['username'],
            '--file=' . $filepath,
            $connection['database'],
        ];

        $this->runProcess($command, $connection['password']);
        $this->applyRetention(7);

        return $filename;
    }

    /**
     * Gera o backup isolado de um schema de tenant específico.
     */
    public function createTenantBackup(string $schema): string
    {
        $schema = trim($schema);
        if (blank($schema)) {
            throw new RuntimeException('Nome do schema não informado.');
        }

        $timestamp = now()->format('Y-m-d_His');
        $filename = "backup_{$schema}_{$timestamp}.dump";
        $filepath = $this->directory() . '/' . $filename;

        $connection = $this->getConnectionConfig();
        $binary = $this->resolveBinary('pg_dump');

        $command = [
            $binary,
            '--format=custom',
            '--schema=' . $schema,
            '--no-owner',
            '--no-privileges',
            '--host=' . $connection['host'],
            '--port=' . $connection['port'],
            '--username=' . $connection['username'],
            '--file=' . $filepath,
            $connection['database'],
        ];

        $this->runProcess($command, $connection['password']);
        $this->applyRetention(7);

        return $filename;
    }

    /**
     * Gera o backup unificado (Global) e os backups individuais de cada tenant ativo.
     *
     * @return array<string, string> Lista de arquivos gerados
     */
    public function createAllBackups(): array
    {
        $generated = [];

        // 1. Gera backup global da base completa
        $generated['GLOBAL'] = $this->createGlobalBackup();

        // 2. Descobre todos os tenants e gera backup de cada um
        $tenantService = app(TenantService::class);
        $tenants = $tenantService->listTenants();

        foreach ($tenants as $tenant) {
            $schema = $tenant['name'];
            $generated[$schema] = $this->createTenantBackup($schema);
        }

        return $generated;
    }

    /**
     * Lista todos os backups armazenados com metadados detalhados de retenção e cliente.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function list(): Collection
    {
        $dir = $this->directory();
        $files = File::files($dir);

        // Mapeia schemas para nomes legíveis de clientes a partir de licenses
        $licensesMap = [];
        try {
            $licensesMap = License::query()
                ->whereNotNull('schema_name')
                ->get()
                ->keyBy('schema_name')
                ->map(fn (License $l): string => (string) ($l->trade_name ?: $l->legal_name ?: $l->schema_name))
                ->all();
        } catch (\Throwable) {
        }

        return collect($files)
            ->filter(fn ($f): bool => str_starts_with($f->getFilename(), 'backup_') && str_ends_with($f->getFilename(), '.dump'))
            ->map(function ($file) use ($licensesMap): array {
                $filename = $file->getFilename();
                $sizeBytes = $file->getSize();
                $mtime = $file->getMTime();
                $createdAt = Carbon::createFromTimestamp($mtime);
                $expiresAt = (clone $createdAt)->addDays(7);
                $daysRemaining = max(0, (int) now()->diffInDays($expiresAt, false));
                $isExpired = now()->greaterThan($expiresAt);

                // Formato padrão: backup_{scope}_{Y-m-d_His}.dump
                $parts = explode('_', $filename);
                $scope = 'OUTRO';

                if (count($parts) >= 3) {
                    // Remove "backup" do início e a data/hora do final
                    array_shift($parts); // remove "backup"
                    array_pop($parts);   // remove "His.dump"
                    array_pop($parts);   // remove "Y-m-d"
                    $scope = implode('_', $parts);
                }

                $isGlobal = strtoupper($scope) === 'GLOBAL';
                $clientName = match (true) {
                    $isGlobal => 'Base Completa (Todos os Clientes / Global)',
                    $scope === 'demo' => 'Ambiente Demo (Escola Demonstrativa)',
                    $scope === 'public' => 'Painel Master (Public)',
                    isset($licensesMap[$scope]) => $licensesMap[$scope],
                    default => 'Tenant: ' . $scope,
                };

                return [
                    'filename' => $filename,
                    'path' => $file->getPathname(),
                    'scope' => $scope,
                    'is_global' => $isGlobal,
                    'client_name' => $clientName,
                    'size_bytes' => $sizeBytes,
                    'size_formatted' => $this->formatBytes($sizeBytes),
                    'created_at' => $createdAt,
                    'created_at_formatted' => $createdAt->format('d/m/Y H:i:s'),
                    'expires_at' => $expiresAt,
                    'expires_at_formatted' => $expiresAt->format('d/m/Y H:i'),
                    'days_remaining' => $daysRemaining,
                    'is_expired' => $isExpired,
                ];
            })
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Aplica a política de retenção automática, removendo arquivos com mais de $days dias.
     */
    public function applyRetention(int $days = 7): int
    {
        $dir = $this->directory();
        $files = File::files($dir);
        $threshold = now()->subDays($days)->timestamp;
        $deletedCount = 0;

        foreach ($files as $file) {
            if (str_starts_with($file->getFilename(), 'backup_') && $file->getMTime() < $threshold) {
                @unlink($file->getPathname());
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Exclui um arquivo de backup com segurança.
     */
    public function delete(string $filename): bool
    {
        $cleanName = basename($filename);
        $path = $this->directory() . '/' . $cleanName;

        if (file_exists($path)) {
            return @unlink($path);
        }

        return false;
    }

    /**
     * Retorna o caminho absoluto para download do backup se válido.
     */
    public function getDownloadPath(string $filename): ?string
    {
        $cleanName = basename($filename);
        $path = $this->directory() . '/' . $cleanName;

        return file_exists($path) ? $path : null;
    }

    protected function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        return round($bytes / 1073741824, 2) . ' GB';
    }

    protected function getConnectionConfig(): array
    {
        $connection = config('database.connections.pgsql', []);
        $database = (string) ($connection['database'] ?? 'aurosoft_prod');
        $username = (string) ($connection['username'] ?? 'postgres');

        return [
            'host' => (string) ($connection['host'] ?? '127.0.0.1'),
            'port' => (string) ($connection['port'] ?? '5432'),
            'database' => $database,
            'username' => $username,
            'password' => (string) ($connection['password'] ?? ''),
        ];
    }

    protected function runProcess(array $command, string $password): void
    {
        $tempDir = storage_path('app/private/tmp');
        File::ensureDirectoryExists($tempDir);

        $process = new Process($command, null, [
            'PGPASSWORD' => $password,
            'TMP' => $tempDir,
            'TEMP' => $tempDir,
            'TMPDIR' => $tempDir,
        ]);
        $process->setTimeout(1800);
        $process->run();

        if (! $process->isSuccessful()) {
            $error = trim($process->getErrorOutput());
            throw new RuntimeException($error !== '' ? $error : 'Falha ao executar dump do PostgreSQL.');
        }
    }

    protected function resolveBinary(string $binary): string
    {
        $customPath = config("database.connections.pgsql.{$binary}_path") ?? env(strtoupper($binary) . '_PATH');
        if (is_string($customPath) && File::isFile($customPath)) {
            return $customPath;
        }

        $binDir = env('POSTGRES_BIN_PATH');
        if (is_string($binDir) && File::isDirectory($binDir)) {
            $candidate = rtrim($binDir, '/\\') . DIRECTORY_SEPARATOR . $binary . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
            if (File::isFile($candidate)) {
                return $candidate;
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $searchPaths = [
                'C:/Program Files/PostgreSQL/*/bin',
                'C:/Program Files (x86)/PostgreSQL/*/bin',
                'C:/PostgreSQL/*/bin',
                'C:/laragon/bin/postgresql/*/bin',
            ];

            foreach ($searchPaths as $pattern) {
                $dirs = glob($pattern, GLOB_ONLYDIR) ?: [];
                rsort($dirs);
                foreach ($dirs as $dir) {
                    $candidate = $dir . DIRECTORY_SEPARATOR . $binary . '.exe';
                    if (File::isFile($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        return $binary;
    }
}
