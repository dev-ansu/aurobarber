<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveBackupService;
use App\Services\LocalBackupManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('system:backup {--if-needed : Executa apenas se o último backup tiver mais de 24 horas} {--no-cloud : Não envia cópia para o Google Drive}')]
#[Description('Cria um backup local do banco de dados e dos arquivos enviados com upload para o Google Drive')]
class CreateSystemBackup extends Command
{
    public function handle(LocalBackupManager $backups, GoogleDriveBackupService $driveService): int
    {
        try {
            if ($this->option('if-needed')) {
                $filename = $backups->ensurePeriodicBackup(24);
                if ($filename === null) {
                    $this->info('O sistema já possui um backup recente (menos de 24 horas). Nenhum backup necessário no momento.');

                    return self::SUCCESS;
                }
            } else {
                $filename = $backups->create();
            }

            $this->info("Backup local criado com sucesso: {$filename}");

            // Upload para o Google Drive se configurado e habilitado
            if (! $this->option('no-cloud') && $driveService->isConfigured() && $driveService->isEnabled()) {
                $this->line('Iniciando sincronização com o Google Drive (Nuvem de 2TB)...');
                $filePath = storage_path('app/private/backups/' . $filename);
                $uploadResult = $driveService->uploadFile($filePath, $filename);
                $this->info("✓ Cópia de segurança enviada para o Google Drive com sucesso! (ID: {$uploadResult['file_id']})");

                // Registra o timestamp em todas as unidades escolares da rede
                \App\Models\School::query()->update([
                    'google_drive_last_backup_at' => now(),
                    'google_drive_last_backup_status' => 'Concluído com sucesso (ID: ' . $uploadResult['file_id'] . ')',
                ]);
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
