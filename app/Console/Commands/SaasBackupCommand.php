<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LocalBackupManager;
use App\Services\SaasGoogleDriveBackupService;
use Illuminate\Console\Command;

class SaasBackupCommand extends Command
{
    protected $signature = 'aurobarber:backup {--cloud : Força o envio e sincronização com o Google Drive} {--prune=14 : Dias de retenção no Google Drive}';

    protected $description = 'Gera backup completo de segurança do sistema e envia para o Google Drive se habilitado';

    public function handle(LocalBackupManager $backupManager, SaasGoogleDriveBackupService $driveService): int
    {
        $this->info('Iniciando geração de backup do AuroBarber...');

        try {
            $filename = $backupManager->create();
            $this->info("Backup local gerado com sucesso: {$filename}");

            if ($this->option('cloud') || $driveService->isEnabled()) {
                $this->info('Criptografando com Libsodium e enviando para o Google Drive...');
                $archivePath = storage_path('app/private/backups/'.$filename);
                $uploadResult = $driveService->uploadIfEnabled($archivePath, requireSuccess: true);

                if ($uploadResult) {
                    $this->info("Backup enviado com sucesso para o Google Drive! (ID: {$uploadResult['id']})");
                } else {
                    $this->warn('O envio para o Google Drive está desativado nas configurações SaaS.');
                }

                $pruneDays = (int) $this->option('prune');
                $deletedCount = $driveService->pruneOldBackups($pruneDays);
                if ($deletedCount > 0) {
                    $this->info("Políticas de retenção aplicadas: {$deletedCount} backup(s) antigo(s) removido(s) do Drive.");
                }
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Falha na rotina de backup: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
