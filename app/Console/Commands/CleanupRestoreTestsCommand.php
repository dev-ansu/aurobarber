<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\SaasRestoreTestService;
use Illuminate\Console\Command;

class CleanupRestoreTestsCommand extends Command
{
    protected $signature = 'aurobarber:cleanup-restore-tests';

    protected $description = 'Remove ambientes sandbox de restauração expirados (> 24h) e schemas temporários';

    public function handle(SaasRestoreTestService $service): int
    {
        $this->info('Iniciando limpeza de sandboxes de restauração expiradas...');

        $cleaned = $service->cleanupExpiredSandboxes();

        $this->info("Concluído: {$cleaned} ambiente(s) sandbox expirado(s) removido(s) com segurança.");

        return Command::SUCCESS;
    }
}
