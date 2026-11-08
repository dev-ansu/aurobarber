<?php

namespace App\Console\Commands;

use App\Services\SystemUpdateService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('system:update {--force : Atualiza sem solicitar confirmação manual} {--key= : Chave de descriptografia/criptografia do .env.encrypted}')]
#[Description('Aplica as atualizações da branch principal com backup de segurança prévio')]
class ApplySystemUpdateCommand extends Command
{
    public function handle(SystemUpdateService $service): int
    {
        $status = $service->checkForUpdates(force: true);

        if (! empty($status['error'])) {
            $this->error($status['error']);

            return self::FAILURE;
        }

        if (! $status['has_update']) {
            $this->info("O sistema já está na versão mais recente da branch {$status['target_branch']}.");
            if (! $this->option('force') && ! $this->confirm('Deseja forçar a reinstalação / pull mesmo assim?', false)) {
                return self::SUCCESS;
            }
        } else {
            $this->warn("🚀 Há {$status['commits_behind']} novo(s) commit(s) na branch {$status['target_branch']}.");
        }

        if (! $this->option('force') && ! $this->confirm('Deseja continuar com a atualização? Um backup será gerado automaticamente antes do processo.', true)) {
            $this->line('Atualização cancelada pelo usuário.');

            return self::SUCCESS;
        }

        $key = $this->option('key');
        if (! $key && $service->isEncryptedEnvDeployment() && ! $service->hasEnvEncryptionKey()) {
            if ($this->input->isInteractive()) {
                $key = $this->secret('O ambiente utiliza .env.encrypted. Informe a chave de descriptografia (LARAVEL_ENV_ENCRYPTION_KEY):');
            }
        }

        $this->info('Iniciando o processo de atualização segura...');

        try {
            $result = $service->applyUpdate($key);

            $this->info($result['message']);
            foreach ($result['logs'] as $log) {
                $this->line("  ✓ {$log}");
            }
            $this->info("Novo commit ativo: {$result['new_commit']['short_hash']} - {$result['new_commit']['message']}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}

