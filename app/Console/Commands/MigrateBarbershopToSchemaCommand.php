<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Barbershop;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class MigrateBarbershopToSchemaCommand extends Command
{
    protected $signature = 'aurobarber:migrate-to-schema 
                            {slug : O slug da barbearia a ser migrada (ex: marinho)}
                            {--dry-run : Apenas simula a migração sem gravar alterações}';

    protected $description = 'Migra uma barbearia do schema public compartilhado para seu schema PostgreSQL dedicado';

    public function handle(TenantService $tenantService): int
    {
        $slug = (string) $this->argument('slug');
        $isDryRun = (bool) $this->option('dry-run');

        $barbershop = Barbershop::where('slug', $slug)->first();
        if (! $barbershop) {
            $this->error("Barbearia com slug '{$slug}' não foi encontrada.");
            return self::FAILURE;
        }

        $targetSchema = $tenantService->sanitizeSchemaName('tenant_' . $barbershop->slug);
        $this->info("Iniciando migração da barbearia '{$barbershop->name}' (ID #{$barbershop->id}) para o schema: \"{$targetSchema}\"...");

        if ($isDryRun) {
            $this->warn('[DRY-RUN] Modo de simulação ativado. Nenhuma alteração real será gravada.');
        }

        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->error('Esta operação requer conexão com PostgreSQL.');
            return self::FAILURE;
        }

        $tablesToMigrate = [
            'barbershops' => "id = {$barbershop->id}",
            'users' => ($barbershop->owner_id ? "id = {$barbershop->owner_id} OR " : '') . "barbershop_id = {$barbershop->id} OR id IN (SELECT user_id FROM public.barbershop_user WHERE barbershop_id = {$barbershop->id}) OR id IN (SELECT user_id FROM public.professionals WHERE barbershop_id = {$barbershop->id} AND user_id IS NOT NULL)",
            'professionals' => "barbershop_id = {$barbershop->id}",
            'services' => "barbershop_id = {$barbershop->id}",
            'appointments' => "barbershop_id = {$barbershop->id}",
            'appointment_services' => "appointment_id IN (SELECT id FROM public.appointments WHERE barbershop_id = {$barbershop->id})",
            'appointment_products' => "appointment_id IN (SELECT id FROM public.appointments WHERE barbershop_id = {$barbershop->id})",
            'products' => "barbershop_id = {$barbershop->id}",
            'product_sales' => "barbershop_id = {$barbershop->id}",
            'payables' => "barbershop_id = {$barbershop->id}",
            'cash_movements' => "barbershop_id = {$barbershop->id}",
            'barbershop_clients' => "barbershop_id = {$barbershop->id}",
            'commission_payouts' => "barbershop_id = {$barbershop->id}",
            'barbershop_user' => "barbershop_id = {$barbershop->id}",
        ];

        // 1. Diagnóstico prévio de contagem
        $this->line('');
        $this->info('--- CONTAGEM DE DADOS ATUAIS NO SCHEMA PUBLIC ---');
        $counts = [];
        foreach ($tablesToMigrate as $table => $condition) {
            $hasTable = DB::selectOne("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?", [$table]) !== null;
            if ($hasTable) {
                $c = (int) DB::selectOne("SELECT count(*) as c FROM public.{$table} WHERE {$condition}")->c;
                $counts[$table] = $c;
                $this->line("  • {$table}: {$c} registro(s)");
            }
        }

        if ($isDryRun) {
            $this->info('[DRY-RUN] Simulação concluída com sucesso. Todos os registros foram mapeados.');
            return self::SUCCESS;
        }

        try {
            DB::beginTransaction();

            // 2. Criar e migrar o novo schema
            $this->line('');
            $this->info("Criando schema \"{$targetSchema}\" e rodando migrações isoladas...");
            DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$targetSchema}\"");
            
            $tenantService->migrateSchema($targetSchema);

            // 3. Copiar os dados com integridade referencial mantida
            $this->info("Copiando registros de dados para \"{$targetSchema}\"...");
            DB::statement("SET session_replication_role = 'replica'");

            try {
                foreach ($tablesToMigrate as $table => $condition) {
                    $hasInPublic = DB::selectOne("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?", [$table]) !== null;
                    $hasInTenant = DB::selectOne("SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ?", [$targetSchema, $table]) !== null;

                    if ($hasInPublic && $hasInTenant) {
                        $originalCount = $counts[$table] ?? 0;
                        if ($originalCount > 0) {
                            // Copia os dados
                            DB::statement("INSERT INTO \"{$targetSchema}\".{$table} SELECT * FROM public.{$table} WHERE {$condition}");

                            // Atualiza a sequence caso exista coluna 'id' serial
                            $hasIdCol = DB::selectOne(
                                "SELECT 1 FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = 'id'",
                                [$targetSchema, $table]
                            ) !== null;

                            if ($hasIdCol) {
                                DB::statement("
                                    SELECT setval(
                                        pg_get_serial_sequence('\"{$targetSchema}\".{$table}', 'id'),
                                        COALESCE(max(id), 1),
                                        max(id) IS NOT NULL
                                    ) FROM \"{$targetSchema}\".{$table}
                                ");
                            }

                            // Valida contagem
                            $migratedCount = (int) DB::selectOne("SELECT count(*) as c FROM \"{$targetSchema}\".{$table}")->c;
                            if ($migratedCount !== $originalCount) {
                                throw new \RuntimeException("Divergência na tabela {$table}: esperado {$originalCount}, migrado {$migratedCount}.");
                            }

                            $this->line("  ✓ {$table}: {$migratedCount}/{$originalCount} registros migrados e validados.");
                        }
                    }
                }
            } finally {
                DB::statement("SET session_replication_role = 'origin'");
            }

            // 4. Atribui o schema_name à barbearia
            $barbershop->schema_name = $targetSchema;
            $barbershop->save();

            DB::commit();

            $this->line('');
            $this->info("=================================================================");
            $this->info(" SUCESSO! A barbearia '{$barbershop->name}' agora opera no schema: \"{$targetSchema}\"");
            $this->info(" Os dados no schema 'public' continuam preservados como cópia de segurança.");
            $this->info("=================================================================");

            return self::SUCCESS;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error('Erro durante a migração: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
