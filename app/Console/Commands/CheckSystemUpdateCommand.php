<?php

namespace App\Console\Commands;

use App\Services\SystemUpdateService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('system:check-update {--force : Força a verificação sem usar cache}')]
#[Description('Verifica se há novas atualizações na branch principal do sistema')]
class CheckSystemUpdateCommand extends Command
{
    public function handle(SystemUpdateService $service): int
    {
        $this->info('Verificando atualizações no repositório remoto...');

        $status = $service->checkForUpdates($this->option('force'));

        if (! empty($status['error'])) {
            $this->error($status['error']);

            return self::FAILURE;
        }

        $this->table(
            ['Parâmetro', 'Valor'],
            [
                ['Branch Atual', $status['current_branch']],
                ['Branch Alvo', $status['target_branch']],
                ['Commit Atual', $status['current_commit']['short_hash'].' - '.$status['current_commit']['message']],
                ['Data do Commit Atual', $status['current_commit']['date']],
                ['Commit Remoto', $status['remote_commit']['short_hash'] ?? 'N/A'],
                ['Atualização Disponível?', $status['has_update'] ? 'SIM' : 'NÃO'],
                ['Commits Atrás', $status['commits_behind']],
            ]
        );

        if ($status['has_update']) {
            $this->warn("🚀 Há {$status['commits_behind']} nova(s) melhoria(s) disponível(is)!");
            if (! empty($status['new_commits'])) {
                $this->line('Novidades:');
                foreach ($status['new_commits'] as $c) {
                    $this->line("  • {$c}");
                }
            }
            $this->info('Execute "php artisan system:update" ou atualize pelo painel administrativo para aplicar.');
        } else {
            $this->info('✅ O sistema já está na versão mais recente da branch '.$status['target_branch']);
        }

        return self::SUCCESS;
    }
}

