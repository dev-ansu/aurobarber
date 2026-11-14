<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Barbershop;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class TenantService
{
    /**
     * Schemas protegidos do sistema que nunca podem ser alterados ou apagados.
     */
    protected const PROTECTED_SCHEMAS = [
        'public',
        'pg_catalog',
        'pg_toast',
        'information_schema',
    ];

    /**
     * Cria e migra um novo schema isolado no PostgreSQL para uma barbearia.
     */
    public function createTenant(string $name): string
    {
        $schema = $this->sanitizeSchemaName($name);

        if (in_array($schema, self::PROTECTED_SCHEMAS, true)) {
            throw new InvalidArgumentException("O nome de schema '{$schema}' é reservado do sistema.");
        }

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$schema}\"");
        }

        $this->migrateSchema($schema);

        return $schema;
    }

    /**
     * Remove completamente um schema de tenant e seus arquivos no storage.
     */
    public function dropTenant(string $name): void
    {
        $schema = $this->sanitizeSchemaName($name);

        if (in_array($schema, self::PROTECTED_SCHEMAS, true)) {
            throw new InvalidArgumentException("Não é permitido excluir schemas protegidos como '{$schema}'.");
        }

        $this->switchToPublic();

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("DROP SCHEMA IF EXISTS \"{$schema}\" CASCADE");
        }

        $slug = str_starts_with($schema, 'tenant_') ? substr($schema, 7) : $schema;
        $tenantStoragePath = storage_path("app/public/tenants/{$slug}");
        if (is_dir($tenantStoragePath)) {
            File::deleteDirectory($tenantStoragePath);
        }
    }

    /**
     * Executa as migrations do Aurobarber isoladamente dentro do schema informado.
     */
    public function migrateSchema(string $schema): void
    {
        $schema = $this->sanitizeSchemaName($schema);

        $this->switchToTenant($schema);

        try {
            $exitCode = Artisan::call('migrate', [
                '--force' => true,
            ]);

            if ($exitCode !== 0) {
                $output = trim(Artisan::output());
                throw new RuntimeException(
                    "Falha nas migrations do schema '{$schema}': " . ($output !== '' ? $output : "código de saída {$exitCode}.")
                );
            }
        } finally {
            $this->switchToPublic();
        }
    }

    /**
     * Alterna o search_path do PostgreSQL para o schema do tenant
     * e isola dinamicamente a pasta de arquivos no Storage.
     */
    public function switchToTenant(string $name): void
    {
        $schema = $this->sanitizeSchemaName($name);

        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("SET search_path TO \"{$schema}\", public");
            Config::set('database.connections.pgsql.search_path', "{$schema},public");
        }

        $this->configureTenantStorage($schema);
    }

    /**
     * Retorna a conexão para o schema 'public' padrão.
     */
    public function switchToPublic(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('SET search_path TO public');
            Config::set('database.connections.pgsql.search_path', 'public');
        }

        $this->resetPublicStorage();
    }

    /**
     * Isola dinamicamente as raízes dos discos 'public' e 'local' para a subpasta da barbearia.
     */
    public function configureTenantStorage(string $schema): void
    {
        $slug = str_starts_with($schema, 'tenant_') ? substr($schema, 7) : $schema;
        $tenantPublicPath = storage_path("app/public/tenants/{$slug}");
        $tenantPrivatePath = storage_path("app/private/tenants/{$slug}");

        if (! is_dir($tenantPublicPath)) {
            @mkdir($tenantPublicPath, 0755, true);
        }

        if (! is_dir($tenantPrivatePath)) {
            @mkdir($tenantPrivatePath, 0755, true);
        }

        Config::set('filesystems.disks.public.root', $tenantPublicPath);
        Config::set('filesystems.disks.public.url', "/storage/tenants/{$slug}");

        Config::set('filesystems.disks.local.root', $tenantPrivatePath);

        if (app()->bound('filesystem')) {
            app('filesystem')->forgetDisk('public');
            app('filesystem')->forgetDisk('local');
        }
    }

    /**
     * Restaura os discos de arquivos para a raiz padrão compartilhada.
     */
    public function resetPublicStorage(): void
    {
        Config::set('filesystems.disks.public.root', storage_path('app/public'));
        Config::set('filesystems.disks.public.url', '/storage');

        Config::set('filesystems.disks.local.root', storage_path('app/private'));

        if (app()->bound('filesystem')) {
            app('filesystem')->forgetDisk('public');
            app('filesystem')->forgetDisk('local');
        }
    }

    /**
     * Retorna o nome do schema ativo no momento.
     */
    public function getCurrentSchema(): string
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return 'public';
        }

        $row = DB::selectOne('SELECT current_schema() as schema');

        return (string) ($row->schema ?? 'public');
    }

    /**
     * Retorna a lista de todos os schemas de barbearias criados no banco.
     *
     * @return array<int, array{name: string, tables_count: int, size: string, size_bytes: int}>
     */
    public function listTenants(): array
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return [];
        }

        $sql = "SELECT schema_name FROM information_schema.schemata 
                WHERE schema_name NOT IN ('public', 'information_schema')
                  AND schema_name NOT LIKE 'pg_%' 
                ORDER BY schema_name ASC";

        $rows = DB::select($sql);
        $tenants = [];

        foreach ($rows as $row) {
            $schemaName = (string) $row->schema_name;

            $tablesCountRow = DB::selectOne(
                'SELECT count(*) as total FROM information_schema.tables WHERE table_schema = ?',
                [$schemaName]
            );

            $tablesCount = (int) ($tablesCountRow->total ?? 0);

            $sizeRow = DB::selectOne(
                "SELECT pg_size_pretty(COALESCE(sum(pg_total_relation_size(quote_ident(schemaname) || '.' || quote_ident(tablename))), 0)) as size,
                        COALESCE(sum(pg_total_relation_size(quote_ident(schemaname) || '.' || quote_ident(tablename))), 0)::bigint as size_bytes
                 FROM pg_tables WHERE schemaname = ?",
                [$schemaName]
            );

            $tenants[] = [
                'name' => $schemaName,
                'tables_count' => $tablesCount,
                'size' => (string) ($sizeRow->size ?? '0 MB'),
                'size_bytes' => (int) ($sizeRow->size_bytes ?? 0),
            ];
        }

        return $tenants;
    }

    /**
     * Valida e sanitiza o nome de um schema (apenas letras minúsculas, números e sublinhados).
     */
    public function sanitizeSchemaName(string $name): string
    {
        $sanitized = Str::lower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($name)));
        $sanitized = trim($sanitized, '_');

        if (empty($sanitized)) {
            throw new InvalidArgumentException('Nome de schema inválido.');
        }

        if (is_numeric($sanitized[0])) {
            $sanitized = 'tenant_' . $sanitized;
        }

        return $sanitized;
    }

    /**
     * Resolve a barbearia correspondente a partir do host/domínio da requisição HTTP.
     */
    public function resolveTenantFromHost(string $host): ?Barbershop
    {
        $cleanHost = strtolower(trim(explode(':', $host)[0]));

        if (empty($cleanHost)) {
            return null;
        }

        try {
            // 1. Domínio Próprio / Custom Domain
            $customDomainShop = Barbershop::where('custom_domain', $cleanHost)->where('is_active', true)->first();
            if ($customDomainShop) {
                return $customDomainShop;
            }

            // 2. Subdomínio em ambiente local (*.localhost)
            if (str_ends_with($cleanHost, '.localhost')) {
                $parts = explode('.', $cleanHost);
                $slug = $parts[0] ?? null;
                if ($slug && ! in_array($slug, ['www', 'saas', 'admin', 'api'], true)) {
                    return Barbershop::where('slug', $slug)->first();
                }

                return null;
            }

            // 3. Subdomínio em produção na nuvem (*.aurobarber.com.br)
            $rootDomain = strtolower((string) config('app.saas_domain', env('SAAS_DOMAIN', 'aurobarber.com.br')));
            if ($rootDomain && str_ends_with($cleanHost, '.' . $rootDomain)) {
                $subdomain = substr($cleanHost, 0, -(strlen($rootDomain) + 1));
                $parts = explode('.', $subdomain);
                $slug = end($parts);
                if ($slug && ! in_array($slug, ['www', 'saas', 'admin', 'api', 'mail'], true)) {
                    return Barbershop::where('slug', $slug)->first();
                }

                return null;
            }

            // 4. Fallback genérico para hosts com subdomínio
            $hostParts = explode('.', $cleanHost);
            if (count($hostParts) >= 3) {
                $candidateSlug = $hostParts[0];
                if (! in_array($candidateSlug, ['www', 'saas', 'admin', 'api'], true)) {
                    return Barbershop::where('slug', $candidateSlug)->first();
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
