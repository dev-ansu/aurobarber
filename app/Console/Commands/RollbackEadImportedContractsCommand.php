<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\Person;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RollbackEadImportedContractsCommand extends Command
{
    protected $signature = 'ead:rollback-imported-contracts 
                            {--force : Executar a exclusão sem pedir confirmação interativa}
                            {--dry-run : Apenas simular a reversão sem alterar o banco de dados}';

    protected $description = 'Remove com segurança contratos e pessoas gerados automaticamente pela importação em lote do EAD Ouro Moderno, preservando 100% dos dados legítimos da escola.';

    public function handle(): int
    {
        $this->info('================================================================');
        $this->info('  REVERSÃO DE IMPORTAÇÃO AUTOMÁTICA DO EAD OURO MODERNO');
        $this->info('================================================================');

        // 1. Contratos legítimos que serão 100% preservados
        $legitimateContractsCount = Contract::withoutGlobalScopes()
            ->where(function ($q) {
                $q->where('contract_number', 'not like', 'EAD-%')
                  ->orWhereNull('contract_number');
            })
            ->count();

        // 2. Contratos gerados pelo EAD (prefixo EAD- e flag is_ead)
        $queryContracts = Contract::withoutGlobalScopes()
            ->where('contract_number', 'like', 'EAD-%')
            ->where('is_ead', true);

        $totalContracts = $queryContracts->count();

        $this->line("Contratos legítimos no sistema (100% intocados): <info>{$legitimateContractsCount}</info>");
        $this->line("Contratos gerados pelo EAD a serem revertidos: <comment>{$totalContracts}</comment>");

        if ($totalContracts === 0) {
            $this->info('Nenhum contrato automático com prefixo EAD- encontrado.');

            return Command::SUCCESS;
        }

        // Amostra de contratos
        $sample = (clone $queryContracts)->limit(5)->pluck('contract_number')->implode(', ');
        $this->line("Amostra identificada: {$sample}...");

        // Identifica IDs de pessoas vinculadas a esses contratos
        $studentIds = (clone $queryContracts)->pluck('student_id')->unique()->filter()->all();

        // 3. Localiza pessoas que foram criadas exclusivamente pela importação em lote:
        // Pessoas que NÃO possuem nenhum outro contrato legítimo, não são colaboradores,
        // não têm login de usuário, não são leads e não têm contratos como responsáveis.
        $orphansQuery = Person::withoutGlobalScopes()
            ->whereIn('id', $studentIds)
            ->whereDoesntHave('contracts', function ($q) {
                $q->where('contract_number', 'not like', 'EAD-%');
            })
            ->whereDoesntHave('collaborators')
            ->whereDoesntHave('users')
            ->whereDoesntHave('commercialLeads')
            ->whereDoesntHave('responsibles.contracts');

        $orphanPersonCount = $orphansQuery->count();
        $this->line("Pessoas criadas exclusivamente nessa importação a serem removidas: <comment>{$orphanPersonCount}</comment>");

        if ($this->option('dry-run')) {
            $this->warn('[DRY-RUN] Modo de simulação ativado. Nenhuma alteração foi realizada no banco.');

            return Command::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Deseja realmente remover os {$totalContracts} contratos EAD e {$orphanPersonCount} cadastros de pessoas órfãs, mantendo os {$legitimateContractsCount} contratos legítimos intactos?", false)) {
            $this->warn('Operação cancelada pelo operador.');

            return Command::SUCCESS;
        }

        DB::transaction(function () use ($queryContracts, $orphansQuery, $totalContracts, $orphanPersonCount) {
            // Remove permanentemente os contratos gerados pelo EAD
            $queryContracts->forceDelete();
            $this->info("✓ {$totalContracts} contratos com prefixo EAD- removidos com sucesso.");

            // Remove as pessoas órfãs e suas relações vinculadas
            $deletedPersons = 0;
            foreach ($orphansQuery->get() as $person) {
                $person->contacts()->delete();
                $person->documents()->delete();
                $person->schools()->detach();
                $person->delete();
                $deletedPersons++;
            }

            $this->info("✓ {$deletedPersons} pessoas geradas exclusivamente na importação EAD foram removidas.");
        });

        $finalContracts = Contract::withoutGlobalScopes()->count();
        $finalPersons = Person::withoutGlobalScopes()->count();

        $this->info('================================================================');
        $this->info('Limpeza concluída com sucesso!');
        $this->info("Contratos legítimos ativos no banco: {$finalContracts}");
        $this->info("Total de pessoas no banco: {$finalPersons}");
        $this->info('================================================================');

        return Command::SUCCESS;
    }
}
