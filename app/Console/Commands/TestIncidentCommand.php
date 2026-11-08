<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\SupportTicket;
use App\Services\SystemIncidentReporter;
use Illuminate\Console\Command;
use RuntimeException;

class TestIncidentCommand extends Command
{
    protected $signature = 'aurosoft:trigger-test-incident {--resolve : Marca o incidente de teste como resolvido}';

    protected $description = 'Dispara uma simulação de erro 500 para validar o AuroDoctor e os alertas em tempo real.';

    public function handle(SystemIncidentReporter $reporter): int
    {
        if ($this->option('resolve')) {
            $count = SupportTicket::query()
                ->withoutGlobalScopes()
                ->where('type', 'system_incident')
                ->where('error_message', 'like', '%Teste de Telemetria%')
                ->whereIn('status', ['open', 'in_progress'])
                ->update([
                    'status' => 'resolved',
                    'resolved_at' => now(),
                    'resolution_notes' => 'Incidente de teste finalizado com sucesso.',
                ]);

            $this->info("✔ {$count} incidente(s) de teste foram marcados como resolvidos!");
            return self::SUCCESS;
        }

        $this->warn('⚡ Simulando um erro crítico de sistema para validação de telemetria...');

        $dummyException = new RuntimeException(
            '[Teste de Telemetria AuroDoctor] Simulação de falha crítica de processamento no servidor para validar captura autônoma e notificações em tempo real.'
        );

        $ticket = $reporter->report($dummyException);

        if (! $ticket) {
            $this->error('✖ Falha ao capturar e registrar o incidente.');
            return self::FAILURE;
        }

        $this->info("✔ Incidente capturado com sucesso!");
        $this->line(" - Protocolo: <fg=yellow>{$ticket->protocol}</>");
        $this->line(" - Assunto: {$ticket->subject}");
        $this->line(" - Status: <fg=red>{$ticket->status}</>");
        $this->line("");
        $this->info("🔔 A notificação foi enviada diretamente para o sino dos Super Admins (Anderson).");
        $this->info("🚨 O menu lateral 'Central de Incidentes & Chamados' agora exibe um badge vermelho.");
        $this->info("🩺 Execute 'php artisan aurosoft:doctor' ou recarregue '/doctor' para ver o AuroDoctor alertar o incidente!");
        $this->line("");
        $this->line("Para resolver este teste depois, execute: <fg=cyan>php artisan aurosoft:trigger-test-incident --resolve</>");

        return self::SUCCESS;
    }
}
