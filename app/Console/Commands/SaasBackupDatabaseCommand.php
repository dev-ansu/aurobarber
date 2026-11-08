<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SaasBackupManager;
use Illuminate\Console\Command;

class SaasBackupDatabaseCommand extends Command
{
    protected $signature = 'saas:backup-database 
                            {--scope=all : Escopo do backup: all (global + todos os tenants), global (apenas base completa) ou nome do schema (ex: tenant_itep)}
                            {--keep=7 : Dias de retenção antes de purgar backups antigos}';

    protected $description = 'Executa a rotina de backup automatizado da base PostgreSQL no SaaS Cloud com retenção configurável.';

    public function handle(SaasBackupManager $backupManager): int
    {
        $scope = (string) $this->option('scope');
        $keepDays = (int) $this->option('keep');

        $this->info("Iniciando rotina de backup do banco de dados (Escopo: {$scope}, Retenção: {$keepDays} dias)...");

        try {
            if ($scope === 'all') {
                $generated = $backupManager->createAllBackups();
                $this->info("Sucesso! " . count($generated) . " arquivo(s) de backup gerados:");
                foreach ($generated as $key => $file) {
                    $this->line(" - [{$key}]: {$file}");
                }
            } elseif (strtoupper($scope) === 'GLOBAL') {
                $file = $backupManager->createGlobalBackup();
                $this->info("Backup Global gerado com sucesso: {$file}");
            } else {
                $file = $backupManager->createTenantBackup($scope);
                $this->info("Backup do Tenant '{$scope}' gerado com sucesso: {$file}");
            }

            // Aplica retenção
            $pruned = $backupManager->applyRetention($keepDays);
            if ($pruned > 0) {
                $this->warn("Política de retenção: {$pruned} backup(s) com mais de {$keepDays} dias foram purgados.");
            }

            $this->info("Rotina de backup concluída com sucesso!");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Erro durante execução do backup: " . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
