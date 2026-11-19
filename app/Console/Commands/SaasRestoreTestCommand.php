<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LocalBackupManager;
use App\Services\SaasRestoreTestService;
use Illuminate\Console\Command;

class SaasRestoreTestCommand extends Command
{
    protected $signature = 'aurobarber:restore-test {backup_file? : Nome do arquivo de backup}';

    protected $description = 'Executa teste prático de restauração em sandbox isolada no PostgreSQL';

    public function handle(SaasRestoreTestService $service, LocalBackupManager $backupManager): int
    {
        $backupFile = $this->argument('backup_file');

        if (! $backupFile) {
            $list = $backupManager->list();
            if (empty($list)) {
                $this->error('Nenhum backup disponível para teste.');

                return Command::FAILURE;
            }
            $backupFile = $list[0]['filename'] ?? $list[0]['name'] ?? null;
        }

        $this->info("Iniciando sandbox de restauração para: {$backupFile}...");

        $run = $service->createSandbox($backupFile);

        $results = $run->automated_check_results ?? [];
        $tableCount = $results['table_count'] ?? 0;
        $entities = $results['entities'] ?? [];

        $this->info("✓ Sandbox provisionada com sucesso no schema: {$run->target_schema}");
        $this->line("  Tabelas restauradas: <fg=green;options=bold>{$tableCount}</>");
        $this->line("  Barbearias: {$entities['barbershops']} | Usuários: {$entities['users']} | Agendamentos: {$entities['appointments']}");
        $this->line("  Resumo: {$results['summary']}");

        return Command::SUCCESS;
    }
}
