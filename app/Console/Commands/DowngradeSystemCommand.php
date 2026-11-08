<?php

namespace App\Console\Commands;

use App\Services\SystemUpdateService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('system:downgrade {--commit= : Hash específico do commit para reverter (padrão: commit anterior)} {--with-db : Restaura o backup do banco gerado antes da atualização} {--force : Executa sem solicitar confirmação manual} {--key= : Chave do .env.encrypted}')]
#[Description('Reverte o sistema Aurosoft para a versão/commit anterior com backup de segurança')]
class DowngradeSystemCommand extends Command
{
    public function handle(SystemUpdateService $service): int
    {
        $commit = $this->option('commit');
        $prev = $service->getPreviousVersionInfo();

        if (! $commit) {
            if (! $prev) {
                $this->error('Não foi possível identificar a versão anterior no histórico do Git.');

                return self::FAILURE;
            }
            $commit = $prev['full_hash'] ?? $prev['short_hash'];
        }

        $this->warn("⚠️  Atenção: Você está prestes a reverter o Aurosoft para a versão: {$commit}");
        if ($prev) {
            $this->line("   Mensagem: {$prev['message']}");
            $this->line("   Data: {$prev['date']}");
        }

        if ($this->option('with-db')) {
            $this->warn('   📦 O banco de dados também será restaurado a partir do backup pré-atualização.');
        }

        if (! $this->option('force') && ! $this->confirm('Deseja realmente reverter o sistema para esta versão?', true)) {
            $this->line('Operação de downgrade cancelada.');

            return self::SUCCESS;
        }

        $key = $this->option('key');
        if (! $key && $service->isEncryptedEnvDeployment() && ! $service->hasEnvEncryptionKey()) {
            if ($this->input->isInteractive()) {
                $key = $this->secret('O ambiente utiliza .env.encrypted. Informe a chave de descriptografia:');
            }
        }

        $this->info('Iniciando o processo de reversão segura...');

        try {
            $result = $service->downgradeToVersion(
                $commit,
                (bool) $this->option('with-db'),
                $key
            );

            $this->info($result['message']);
            foreach ($result['logs'] as $log) {
                $this->line("  ✓ {$log}");
            }
            $this->info("Commit ativo agora: {$result['current_commit']['short_hash']} - {$result['current_commit']['message']}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}

