<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\BarberDoctorService;
use Illuminate\Console\Command;

class BarberDoctorCommand extends Command
{
    protected $signature = 'aurobarber:doctor';

    protected $description = 'Executa o BarberDoctor: robô clínico de diagnóstico e auditoria de saúde do Aurobarber';

    public function handle(BarberDoctorService $doctor): int
    {
        $this->newLine();
        $this->line(' <fg=black;bg=yellow;options=bold> BARBERDOCTOR • DIAGNÓSTICO & SAÚDE DO SISTEMA </>');
        $this->line(' <fg=gray>Iniciando exames clínicos de infraestrutura, segurança, backups e filiais...</>');
        $this->newLine();

        $report = $doctor->diagnose();

        $rows = [];
        foreach ($report['checks'] as $check) {
            $badge = match ($check['status'] ?? 'ok') {
                'ok' => '<fg=green;options=bold>✓ OK</>',
                'warning' => '<fg=yellow;options=bold>⚠ ALERTA</>',
                default => '<fg=red;options=bold>✗ CRÍTICO</>',
            };

            $rows[] = [
                $check['category'],
                $check['name'],
                $badge,
                $check['details'],
            ];
        }

        $this->table(['Categoria', 'Exame Clínico', 'Status', 'Detalhes'], $rows);

        $this->newLine();
        $score = $report['score'] ?? 0;
        $scoreColor = $score >= 90 ? 'green' : ($score >= 70 ? 'yellow' : 'red');

        $this->line(" <fg=white;options=bold>Score Clínico Global:</> <fg={$scoreColor};options=bold>{$score}%</> ({$report['status_label']})");
        $this->line(" <fg=white>Checagens:</> {$report['summary']['total']} total | <fg=green>{$report['summary']['passed']} aprovadas</> | <fg=yellow>{$report['summary']['warning']} alertas</> | <fg=red>{$report['summary']['failed']} críticas</>");
        $this->line(" <fg=gray>Tempo de varredura: {$report['execution_time_ms']}ms</>");
        $this->newLine();

        if (! empty($report['recommendations'])) {
            $this->warn('Recomendações Clínicas:');
            foreach ($report['recommendations'] as $i => $rec) {
                $num = $i + 1;
                $this->line("  <fg=yellow>[{$num}]</> <fg=white;options=bold>{$rec['title']}:</> {$rec['action']}");
            }
            $this->newLine();
        }

        return $score >= 70 ? Command::SUCCESS : Command::FAILURE;
    }
}
