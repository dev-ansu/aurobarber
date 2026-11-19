<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Backup\BackupEncryptionService;
use App\Services\Backup\GoogleDriveStorageService;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SaasGoogleDriveBackupService
{
    public function __construct(
        protected GoogleDriveStorageService $storage,
        protected BackupEncryptionService $encryption,
    ) {}

    /**
     * @return array<int, array{id: string, name: string, size: int, created_at: string, scope: string, is_global: bool}>
     */
    public function listEncryptedBackups(): array
    {
        return $this->storage->listDatabaseBackups();
    }

    public function isEnabled(): bool
    {
        return $this->storage->isEnabled();
    }

    public function isConfigured(): bool
    {
        return $this->storage->isConfigured();
    }

    public function testConnection(): void
    {
        $this->storage->testConnection();
    }

    public function createBackupFolder(): string
    {
        return $this->storage->createBackupFolder();
    }

    /**
     * Faz download de um backup criptografado do Google Drive e o descriptografa na pasta local de backups.
     */
    public function downloadAndDecryptBackup(string $fileId, string $filename): string
    {
        $filename = basename($filename);
        if (! str_ends_with($filename, '.enc')) {
            throw new RuntimeException('O arquivo selecionado deve possuir a extensão .enc.');
        }

        $tmpDir = storage_path('app/private/tmp');
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0700, true);
        }

        $encryptedPath = tempnam($tmpDir, '.drive-dl-');
        $decryptedFilename = substr($filename, 0, -4); // Remove .enc
        $targetDirectory = storage_path('app/private/backups');
        if (! is_dir($targetDirectory)) {
            @mkdir($targetDirectory, 0700, true);
        }

        $targetPath = $targetDirectory.'/'.$decryptedFilename;
        if (is_file($targetPath)) {
            $targetPath = $targetDirectory.'/restored-'.now()->format('His').'-'.$decryptedFilename;
        }

        try {
            $this->storage->downloadToFile($fileId, $encryptedPath);
            $this->encryption->decryptFile($encryptedPath, $targetPath);

            return $targetPath;
        } finally {
            if (is_string($encryptedPath)) {
                @unlink($encryptedPath);
            }
        }
    }

    /**
     * Envia um arquivo local para o Google Drive criptografado em fluxo, se o Drive estiver habilitado.
     */
    public function uploadIfEnabled(string $filePath, bool $requireSuccess = false): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        if (! is_file($filePath)) {
            throw new RuntimeException("Arquivo não encontrado para envio ao Google Drive: {$filePath}");
        }

        try {
            $encrypted = $this->encryption->encryptFile($filePath);
            $driveName = basename($filePath).'.enc';

            try {
                $result = $this->storage->uploadResumable(
                    $encrypted['encrypted_path'],
                    $driveName,
                    'Backup AuroBarber SaaS criptografado (Libsodium SecretStream)'
                );

                // Expurgar backups antigos conforme política de retenção (14 dias)
                $this->pruneOldBackups(14);

                return $result;
            } finally {
                @unlink($encrypted['encrypted_path']);
            }
        } catch (\Throwable $exception) {
            Log::error('[SaasGoogleDriveBackupService] Falha ao enviar backup para o Google Drive', [
                'file' => basename($filePath),
                'error' => $exception->getMessage(),
            ]);

            if ($requireSuccess) {
                throw $exception;
            }

            return null;
        }
    }

    public function pruneOldBackups(int $days = 14): int
    {
        return $this->storage->pruneDatabaseBackups($days);
    }
}
