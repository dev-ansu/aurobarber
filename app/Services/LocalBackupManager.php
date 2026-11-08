<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class LocalBackupManager
{
    public function list(): array
    {
        $directory = $this->backupDirectory();
        File::ensureDirectoryExists($directory);

        return collect(File::files($directory))
            ->filter(fn ($file): bool => $this->isAllowedFilename($file->getFilename()))
            ->sortByDesc(fn ($file): int => $file->getMTime())
            ->map(fn ($file): array => [
                'name' => $file->getFilename(),
                'size' => $file->getSize(),
                'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
            ])->values()->all();
    }

    public function create(bool $safetyCopy = false): string
    {
        $connection = $this->pgsqlConnection();
        $base = storage_path('app/private/backups');
        $work = $base.'/.tmp-'.Str::uuid();
        File::ensureDirectoryExists($work);
        $archivePath = null;

        try {
            $dump = $work.'/database.dump';
            $this->runPgDump($connection, $dump);

            $filename = 'aurosoft-'.($safetyCopy ? 'pre-restore-' : 'backup-').now()->format('Ymd-His').'.zip';
            $archivePath = $base.'/'.$filename;
            $zip = new ZipArchive;

            if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Não foi possível criar o arquivo ZIP do backup.');
            }

            $schoolsManifest = [];
            try {
                $schoolsManifest = DB::table('schools')
                    ->orderBy('id')
                    ->get(['id', 'trade_name', 'legal_name'])
                    ->map(fn ($s): array => [
                        'id' => $s->id,
                        'name' => $s->trade_name ?: $s->legal_name,
                    ])
                    ->all();
            } catch (\Throwable) {
            }

            if (! $zip->addFile($dump, 'database.dump') || ! $zip->addFromString('manifest.json', json_encode([
                'application' => config('app.name'),
                'created_at' => now()->toIso8601String(),
                'database_driver' => 'pgsql',
                'scope' => 'all_schools',
                'schools_count' => count($schoolsManifest),
                'schools' => $schoolsManifest,
                'format' => 1,
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR))) {
                $zip->close();
                throw new RuntimeException('Não foi possível adicionar os dados ao arquivo ZIP do backup.');
            }

            $uploads = storage_path('app/public');
            if (File::isDirectory($uploads)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($uploads, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY,
                );

                foreach ($iterator as $item) {
                    if ($item->isFile() && ! $item->isLink()) {
                        $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($uploads) + 1));
                        if (! $zip->addFile($item->getPathname(), 'storage/'.$relative)) {
                            $zip->close();
                            throw new RuntimeException('Não foi possível adicionar um arquivo enviado ao backup.');
                        }
                    }
                }
            }

            if (! $zip->close()) {
                throw new RuntimeException('Não foi possível finalizar o arquivo ZIP do backup.');
            }

            $this->applyRetention(10);

            return $filename;
        } catch (\Throwable $exception) {
            if (is_string($archivePath)) {
                File::delete($archivePath);
            }
            throw $exception;
        } finally {
            File::deleteDirectory($work);
        }
    }

    public function restore(string $filename): void
    {
        $archivePath = $this->archivePath($filename);
        $this->restoreFromPath($archivePath);
    }

    public function restoreFromPath(string $archivePath): void
    {
        if (! File::isFile($archivePath)) {
            throw new RuntimeException("O arquivo de backup '{$archivePath}' não foi encontrado.");
        }

        $connection = $this->pgsqlConnection();
        $work = storage_path('app/private/backups/.restore-'.Str::uuid());
        File::ensureDirectoryExists($work);
        $zip = new ZipArchive;
        $previousUploads = storage_path('app/public.previous-'.Str::uuid());
        $uploads = storage_path('app/public');
        $filesSwapped = false;

        try {
            if ($zip->open($archivePath) !== true) {
                throw new RuntimeException('O arquivo selecionado não é um ZIP válido.');
            }

            $manifestContents = $zip->getFromName('manifest.json');
            $manifest = is_string($manifestContents) ? json_decode($manifestContents, true) : null;
            if (! is_array($manifest) || ($manifest['database_driver'] ?? null) !== 'pgsql' || $zip->locateName('database.dump') === false) {
                throw new RuntimeException('O arquivo não contém um backup PostgreSQL válido do sistema.');
            }

            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->getNameIndex($index);
                if (! is_string($entry) || str_contains($entry, "\0") || str_starts_with($entry, '/') || str_contains($entry, '\\')) {
                    throw new RuntimeException('O backup contém um caminho inválido.');
                }

                $segments = explode('/', $entry);
                if (in_array('..', $segments, true) || (! in_array($entry, ['manifest.json', 'database.dump'], true) && ! str_starts_with($entry, 'storage/'))) {
                    throw new RuntimeException('O backup contém um caminho não permitido.');
                }
            }

            if (! $zip->extractTo($work)) {
                throw new RuntimeException('Não foi possível preparar os arquivos do backup.');
            }
            $zip->close();

            // Create a local safety copy before changing either the database or uploads.
            $this->create(safetyCopy: true);

            $stagedUploads = $work.'/storage';
            if (File::isDirectory($stagedUploads)) {
                File::ensureDirectoryExists($previousUploads);
                if (File::isDirectory($uploads)) {
                    File::copyDirectory($uploads, $previousUploads);
                    File::cleanDirectory($uploads);
                } else {
                    File::ensureDirectoryExists($uploads);
                }
                File::copyDirectory($stagedUploads, $uploads);
                $filesSwapped = true;
            }

            $this->runPgRestore($connection, $work.'/database.dump');

            if ($filesSwapped && File::isDirectory($previousUploads)) {
                File::deleteDirectory($previousUploads);
            }
        } catch (\Throwable $exception) {
            if ($filesSwapped && File::isDirectory($previousUploads)) {
                File::cleanDirectory($uploads);
                File::copyDirectory($previousUploads, $uploads);
            }
            throw $exception;
        } finally {
            if (isset($zip) && $zip instanceof ZipArchive && $zip->status !== ZipArchive::ER_OK) {
                @$zip->close();
            }
            if (File::isDirectory($previousUploads)) {
                File::deleteDirectory($previousUploads);
            }
            File::deleteDirectory($work);
        }
    }

    public function importFromExternalPath(string $sourcePath): string
    {
        if (! File::isFile($sourcePath)) {
            throw new RuntimeException("O arquivo de backup '{$sourcePath}' não foi encontrado.");
        }

        $zip = new ZipArchive;
        if ($zip->open($sourcePath) !== true) {
            throw new RuntimeException('O arquivo selecionado não é um ZIP válido.');
        }

        $manifestContents = $zip->getFromName('manifest.json');
        $manifest = is_string($manifestContents) ? json_decode($manifestContents, true) : null;
        if (! is_array($manifest) || ($manifest['database_driver'] ?? null) !== 'pgsql' || $zip->locateName('database.dump') === false) {
            $zip->close();
            throw new RuntimeException('O arquivo não contém um backup PostgreSQL válido do Aurosoft.');
        }
        $zip->close();

        $base = $this->backupDirectory();
        File::ensureDirectoryExists($base);

        $filename = basename($sourcePath);
        if (! $this->isAllowedFilename($filename)) {
            $filename = 'aurosoft-backup-'.now()->format('Ymd-His').'.zip';
        }

        $targetPath = $base.'/'.$filename;
        if (! copy($sourcePath, $targetPath)) {
            throw new RuntimeException('Não foi possível copiar o backup para a pasta local de backups.');
        }

        return $filename;
    }

    public function scanExternalDrives(): array
    {
        $found = [];

        if (PHP_OS_FAMILY === 'Windows') {
            foreach (range('D', 'Z') as $letter) {
                $root = $letter.':\\';
                if (@is_dir($root)) {
                    $patterns = [
                        $root.'aurosoft-*.zip',
                        $root.'*.zip',
                        $root.'*\\aurosoft-*.zip',
                    ];
                    foreach ($patterns as $pattern) {
                        $files = @glob($pattern) ?: [];
                        foreach ($files as $file) {
                            if (is_file($file) && ! isset($found[$file])) {
                                $name = basename($file);
                                $size = number_format(filesize($file) / 1048576, 2, ',', '.');
                                $found[$file] = "{$letter}:\\{$name} ({$size} MB)";
                            }
                        }
                    }
                }
            }
        } else {
            $linuxRoots = ['/media', '/mnt'];
            foreach ($linuxRoots as $root) {
                if (@is_dir($root)) {
                    $files = @glob($root.'/*/*.zip') ?: [];
                    foreach ($files as $file) {
                        if (is_file($file) && ! isset($found[$file])) {
                            $name = basename($file);
                            $size = number_format(filesize($file) / 1048576, 2, ',', '.');
                            $found[$file] = "{$file} ({$size} MB)";
                        }
                    }
                }
            }
        }

        return $found;
    }

    public function pathForDownload(string $filename): string
    {
        $path = $this->archivePath($filename);
        abort_unless(File::isFile($path), 404);

        return $path;
    }

    private function pgsqlConnection(): array
    {
        $name = config('database.default');
        $connection = config("database.connections.{$name}");
        if (($connection['driver'] ?? null) !== 'pgsql') {
            if (! File::exists(base_path('.env')) && File::exists(base_path('.env.encrypted'))) {
                throw new RuntimeException('O arquivo .env não foi localizado. O ambiente utiliza .env.encrypted. É necessário descriptografá-lo ("php artisan env:decrypt") para carregar a conexão com o PostgreSQL.');
            }

            throw new RuntimeException('O backup local atualmente requer PostgreSQL como banco padrão.');
        }

        $database = $connection['database'] ?? null;
        $username = $connection['username'] ?? null;
        if (! is_string($database) || $database === '' || ! is_string($username) || $username === '') {
            throw new RuntimeException('A configuração PostgreSQL do sistema está incompleta.');
        }

        return [
            'host' => (string) ($connection['host'] ?? '127.0.0.1'),
            'port' => (string) ($connection['port'] ?? '5432'),
            'database' => $database,
            'username' => $username,
            'password' => (string) ($connection['password'] ?? ''),
        ];
    }

    private function runPgDump(array $connection, string $output): void
    {
        $command = [$this->resolveBinary('pg_dump'), '--format=custom', '--no-owner', '--no-privileges', '--host='.$connection['host'], '--port='.$connection['port'], '--username='.$connection['username'], '--file='.$output, $connection['database']];
        $this->runProcess($command, $connection['password']);
    }

    private function runPgRestore(array $connection, string $dump): void
    {
        $command = [$this->resolveBinary('pg_restore'), '--clean', '--if-exists', '--no-owner', '--no-privileges', '--single-transaction', '--exit-on-error', '--host='.$connection['host'], '--port='.$connection['port'], '--username='.$connection['username'], '--dbname='.$connection['database'], $dump];
        $this->runProcess($command, $connection['password']);
    }

    private function resolveBinary(string $binary): string
    {
        $customPath = config("database.connections.pgsql.{$binary}_path") ?? env(strtoupper($binary).'_PATH');
        if (is_string($customPath) && File::isFile($customPath)) {
            return $customPath;
        }

        $binDir = env('POSTGRES_BIN_PATH');
        if (is_string($binDir) && File::isDirectory($binDir)) {
            $candidate = rtrim($binDir, '/\\').DIRECTORY_SEPARATOR.$binary.(PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
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
                    $candidate = $dir.DIRECTORY_SEPARATOR.$binary.'.exe';
                    if (File::isFile($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        return $binary;
    }

    private function runProcess(array $command, string $password): void
    {
        $tempDir = storage_path('app/private/tmp');
        File::ensureDirectoryExists($tempDir);
        putenv('TMP='.$tempDir);
        putenv('TEMP='.$tempDir);
        putenv('TMPDIR='.$tempDir);
        $_ENV['TMP'] = $tempDir;
        $_ENV['TEMP'] = $tempDir;
        $_ENV['TMPDIR'] = $tempDir;

        $process = new Process($command, null, [
            'PGPASSWORD' => $password,
            'TMP' => $tempDir,
            'TEMP' => $tempDir,
            'TMPDIR' => $tempDir,
        ]);
        $process->setTimeout(1800);
        $process->run();

        if (! $process->isSuccessful()) {
            $message = trim($process->getErrorOutput());
            throw new RuntimeException($message !== '' ? $message : 'O comando PostgreSQL terminou com erro.');
        }
    }

    public function ensurePeriodicBackup(int $maxAgeHours = 24): ?string
    {
        $status = $this->status();
        $ageHours = $status['last_backup_age_hours'] ?? null;

        if ($status['total_backups'] === 0 || ($ageHours !== null && $ageHours >= $maxAgeHours)) {
            return $this->create();
        }

        return null;
    }

    public function status(): array
    {
        $list = $this->list();
        $total = count($list);

        if ($total === 0) {
            return [
                'total_backups' => 0,
                'last_backup_date' => null,
                'last_backup_age_hours' => null,
                'is_healthy' => false,
                'badge_color' => 'danger',
                'message' => 'Nenhum backup local encontrado. Crie um backup imediatamente para proteger seus dados!',
            ];
        }

        $last = $list[0];
        $lastDate = Carbon::parse($last['created_at']);
        $ageHours = (int) $lastDate->diffInHours(now());
        $isHealthy = $ageHours <= 48;

        return [
            'total_backups' => $total,
            'last_backup_date' => $lastDate->format('d/m/Y H:i'),
            'last_backup_age_hours' => $ageHours,
            'is_healthy' => $isHealthy,
            'badge_color' => $isHealthy ? 'success' : 'warning',
            'message' => $isHealthy
                ? "Sistema protegido: último backup gerado em {$lastDate->format('d/m/Y H:i')} (há {$ageHours} horas)."
                : "Atenção: o último backup foi gerado há {$ageHours} horas ({$lastDate->format('d/m/Y')}). Recomendamos gerar um backup recente e copiar para o Pen Drive!",
        ];
    }

    public function applyRetention(int $keepLast = 10): int
    {
        $directory = $this->backupDirectory();
        if (! File::isDirectory($directory)) {
            return 0;
        }

        $files = collect(File::files($directory))
            ->filter(fn ($file): bool => $this->isAllowedFilename($file->getFilename()))
            ->sortByDesc(fn ($file): int => $file->getMTime())
            ->values();

        $deletedCount = 0;
        if ($files->count() > $keepLast) {
            $toDelete = $files->slice($keepLast);
            foreach ($toDelete as $file) {
                File::delete($file->getPathname());
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    public function delete(string $filename): void
    {
        $path = $this->archivePath($filename);
        if (File::isFile($path)) {
            File::delete($path);
        }
    }

    public function copyToExternalPath(string $filename, string $destinationDir): string
    {
        $sourcePath = $this->archivePath($filename);
        if (! File::isFile($sourcePath)) {
            throw new RuntimeException('O arquivo de backup não foi encontrado.');
        }

        $dest = rtrim($destinationDir, '/\\');
        if (! File::isDirectory($dest)) {
            throw new RuntimeException("O diretório de destino '{$destinationDir}' não existe ou o Pen Drive não está conectado.");
        }

        if (! is_writable($dest)) {
            throw new RuntimeException("O diretório de destino '{$destinationDir}' não tem permissão de escrita.");
        }

        $targetFile = $dest.DIRECTORY_SEPARATOR.$filename;
        if (! copy($sourcePath, $targetFile)) {
            throw new RuntimeException("Falha ao copiar o backup para '{$targetFile}'.");
        }

        if (filesize($sourcePath) !== filesize($targetFile)) {
            File::delete($targetFile);
            throw new RuntimeException('A cópia do backup falhou: os tamanhos dos arquivos não coincidem.');
        }

        return $targetFile;
    }

    private function backupDirectory(): string
    {
        return storage_path('app/private/backups');
    }

    private function isAllowedFilename(string $filename): bool
    {
        return preg_match('/^aurosoft-(?:backup|pre-restore)-\d{8}-\d{6}\.zip$/', $filename) === 1;
    }

    private function archivePath(string $filename): string
    {
        abort_unless($this->isAllowedFilename($filename) && basename($filename) === $filename, 404);

        return $this->backupDirectory().'/'.$filename;
    }
}
