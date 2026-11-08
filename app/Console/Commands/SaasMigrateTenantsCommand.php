<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\License;
use App\Services\TenantService;
use Illuminate\Console\Command;

class SaasMigrateTenantsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saas:migrate-tenants 
                            {--tenant= : Nome específico do schema para migrar} 
                            {--seed : Executa seeders após migrar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executa as migrations do Aurosoft em todos os schemas de tenants na nuvem (ou em um schema específico).';

    /**
     * Execute the console command.
     */
    public function handle(TenantService $tenantService): int
    {
        $specificTenant = $this->option('tenant');

        if ($specificTenant) {
            $schemas = [$tenantService->sanitizeSchemaName($specificTenant)];
        } else {
            $licenses = License::where('type', 'cloud_saas')
                ->whereNotNull('schema_name')
                ->get();

            $schemas = $licenses->pluck('schema_name')->filter()->unique()->values()->all();

            // Inclui o schema 'demo' se existir
            $demoTenant = collect($tenantService->listTenants())->firstWhere('name', 'demo');
            if ($demoTenant && ! in_array('demo', $schemas, true)) {
                $schemas[] = 'demo';
            }
        }

        if (empty($schemas)) {
            $this->warn('Nenhum schema de tenant encontrado para migrar.');
            return self::SUCCESS;
        }

        $this->info("Iniciando migração de " . count($schemas) . " schema(s) de tenants...");

        foreach ($schemas as $schema) {
            $this->line("--------------------------------------------------");
            $this->info("Migrando schema: [{$schema}]...");

            $startTime = microtime(true);

            try {
                $tenantService->migrateSchema($schema);
                $elapsed = round((microtime(true) - $startTime) * 1000, 2);
                $this->info("✔ Schema [{$schema}] migrado com sucesso em {$elapsed} ms!");
            } catch (\Throwable $e) {
                $this->error("✖ Erro ao migrar schema [{$schema}]: " . $e->getMessage());
            }
        }

        $this->line("--------------------------------------------------");
        $this->info("Processo de migrações Multi-Tenant concluído!");

        return self::SUCCESS;
    }
}
