<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Barbershop;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class SaasMigrateTenantsCommand extends Command
{
    protected $signature = 'saas:migrate-tenants 
                            {--tenant= : Slug ou schema de uma barbearia específica} 
                            {--force : Executa migrations sem confirmação}';

    protected $description = 'Executa as migrations do Aurobarber em todos os schemas de barbearias (SaaS Multi-Tenant)';

    public function handle(TenantService $tenantService): int
    {
        $specificTenant = $this->option('tenant');

        if ($specificTenant) {
            $barbershop = Barbershop::where('slug', $specificTenant)
                ->orWhere('schema_name', $specificTenant)
                ->first();

            $schema = $barbershop?->schema_name ?: $tenantService->sanitizeSchemaName((string) $specificTenant);
            $schemas = [$schema];
        } else {
            $tenants = $tenantService->listTenants();
            $schemas = array_column($tenants, 'name');
        }

        if (empty($schemas)) {
            $this->info('Nenhum schema de barbearia encontrado para migrar.');
            return self::SUCCESS;
        }

        $this->info('Iniciando atualização de ' . count($schemas) . ' schema(s) de barbearia...');

        $successCount = 0;
        $failCount = 0;

        foreach ($schemas as $schema) {
            $this->line('');
            $this->info("» Migrando schema: {$schema}...");

            try {
                $tenantService->switchToTenant($schema);

                $exitCode = Artisan::call('migrate', [
                    '--force' => true,
                ]);

                if ($exitCode === 0) {
                    $this->line("  ✓ Schema '{$schema}' atualizado com sucesso.");
                    $successCount++;
                } else {
                    $this->error("  ✗ Falha ao migrar '{$schema}'. Código de saída: {$exitCode}");
                    $this->line(Artisan::output());
                    $failCount++;
                }
            } catch (Throwable $e) {
                $this->error("  ✗ Exceção no schema '{$schema}': " . $e->getMessage());
                $failCount++;
            } finally {
                $tenantService->switchToPublic();
            }
        }

        $this->line('');
        $this->info("Concluído: {$successCount} schema(s) atualizados com sucesso, {$failCount} falha(s).");

        return $failCount === 0 ? self::SUCCESS : self::FAILURE;
    }
}
