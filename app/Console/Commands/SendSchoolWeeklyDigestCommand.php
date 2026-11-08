<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\SchoolWeeklyDigestMail;
use App\Models\School;
use App\Services\SchoolWeeklyDigestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendSchoolWeeklyDigestCommand extends Command
{
    protected $signature = 'school:send-weekly-digest 
                            {--school-id= : ID da escola que deseja gerar o resumo}
                            {--email= : E-mail de destino para envio do teste}
                            {--preview : Exibe os números apenas no console sem enviar e-mail}';

    protected $description = 'Gera e dispara o Boletim Executivo Semanal de Diretoria da escola via Resend';

    public function handle(SchoolWeeklyDigestService $digestService): int
    {
        $this->info('🔍 Localizando instituição e consolidando métricas da semana...');

        $schoolId = $this->option('school-id');
        $school = $schoolId 
            ? School::withoutGlobalScopes()->find($schoolId)
            : (School::getHeadquarters() ?? School::withoutGlobalScopes()->first());

        if (! $school) {
            // Cria uma instância fictícia/modelo caso ainda não haja escolas cadastradas
            $school = new School([
                'trade_name' => 'Aurosoft Academy',
                'legal_name' => 'Aurosoft Educação e Tecnologia',
                'email' => 'diretoria@aurosoft.com.br',
            ]);
        }

        $metrics = $digestService->collectMetrics($school);

        $this->table(
            ['Indicador Semanal', 'Valor Consolidado'],
            [
                ['Período', $metrics['period_label']],
                ['Receita Realizada (Caixa/Pix)', 'R$ ' . number_format($metrics['total_received'], 2, ',', '.') . " ({$metrics['received_count']} baixas)"],
                ['Novas Matrículas (VGV)', "{$metrics['new_contracts_count']} alunos (R$ " . number_format($metrics['new_contracts_value'], 2, ',', '.') . ")"],
                ['Previsão a Receber (Próx. 7 dias)', "{$metrics['upcoming_due_count']} parcelas (R$ " . number_format($metrics['upcoming_due_value'], 2, ',', '.') . ")"],
                ['Retornos de Trancamento', "{$metrics['pending_suspensions_count']} alunos"],
            ]
        );

        if ($this->option('preview')) {
            $this->warn('Modo preview ativado. E-mail não foi disparado.');
            return self::SUCCESS;
        }

        $targetEmail = $this->option('email') 
            ?: $school->email 
            ?: env('ADMIN_NOTIFICATION_EMAIL', 'dev.andersondesouzafeitosa@gmail.com');

        $this->info("🚀 Disparando Boletim Executivo via Resend para: {$targetEmail}...");

        try {
            Mail::to($targetEmail)->send(new SchoolWeeklyDigestMail(
                school: $school,
                metrics: $metrics,
                recipientName: $school->trade_name ? 'Diretoria ' . $school->trade_name : 'Diretoria'
            ));

            $this->info('✔ BOLETIM EXECUTIVO SEMANAL DISPARADO COM SUCESSO VIA RESEND!');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✖ Falha ao enviar boletim: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
