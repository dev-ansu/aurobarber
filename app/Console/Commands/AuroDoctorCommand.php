<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AuroDoctorService;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;

class AuroDoctorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aurosoft:doctor 
                            {--tenant=global : Nome do tenant específico para examinar, ou global para a VPS}
                            {--notify : Envia notificação em tempo real no sino do Master se houver falhas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa o AuroDoctor: robô clínico de diagnóstico e varredura do sistema';

    /**
     * Execute the console command.
     */
    public function handle(AuroDoctorService $doctorService): int
    {
        $tenant = (string) ($this->option('tenant') ?? 'global');

        $this->info('');
        $this->line(" <fg=black;bg=yellow;options=bold> AURODOCTOR • ROBÔ CLÍNICO DO SISTEMA (Alvo: {$tenant}) </>");
        $this->line(' <fg=gray>Iniciando bateria de exames e varredura completa...</>');
        $this->info('');

        $report = $doctorService->diagnose($tenant);

        $rows = [];
        foreach ($report['checks'] as $check) {
            $statusBadge = match ($check['status']) {
                'passed' => '<fg=green;options=bold>✓ APROVADO</>',
                'warning' => '<fg=yellow;options=bold>⚠ ALERTA</>',
                default => '<fg=red;options=bold>✗ FALHOU</>',
            };

            $rows[] = [
                $check['name'],
                $check['category'],
                $statusBadge,
                "{$check['duration_ms']} ms",
                $check['message'],
            ];
        }

        $this->table(['Exame / Teste', 'Categoria', 'Status', 'Tempo', 'Resultado'], $rows);

        $this->info('');
        $scoreColor = $report['health_score'] >= 90 ? 'green' : ($report['health_score'] >= 70 ? 'yellow' : 'red');
        $this->line(" <fg=white;options=bold>Score de Saúde do Sistema:</> <fg={$scoreColor};options=bold>{$report['health_score']}%</>");
        $this->line(" <fg=white>Checagens:</> {$report['total_checks']} total | <fg=green>{$report['passed']} OK</> | <fg=yellow>{$report['warnings']} alertas</> | <fg=red>{$report['failed']} falhas</>");
        $this->line(" <fg=gray>Tempo de varredura: {$report['duration_seconds']}s</>");
        $this->info('');

        // Se houver falhas críticas e o flag --notify estiver ativo, notifica no sino do Master
        if ($this->option('notify') && $report['failed'] > 0) {
            try {
                $superAdmins = User::withoutGlobalScopes()
                    ->where(function ($q) {
                        $q->where('is_super_admin', true)->orWhere('is_admin', true);
                    })
                    ->get();

                foreach ($superAdmins as $admin) {
                    Notification::make()
                        ->title("🚨 AuroDoctor detectou {$report['failed']} falha(s) crítica(s)!")
                        ->body("Score de saúde caiu para {$report['health_score']}%. Verifique o relatório no painel Master.")
                        ->danger()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->label('Abrir AuroDoctor')
                                ->url(url('/doctor')),
                        ])
                        ->sendToDatabase($admin);
                }
            } catch (\Throwable) {
                // Silencioso se notificações de banco não estiverem ativas
            }
        }

        if ($report['status'] === 'healthy') {
            $this->info(' 🎉 O Aurosoft está 100% saudável e operacional!');
            return Command::SUCCESS;
        }

        if ($report['failed'] > 0) {
            $this->error(" 🚨 Foram encontradas {$report['failed']} falha(s). Verifique os logs acima ou acesse o SaaS Master em /doctor.");
            return Command::FAILURE;
        }

        $this->warn(' ⚠ Sistema operacional, mas com alertas que requerem atenção.');
        return Command::SUCCESS;
    }
}
