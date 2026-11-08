<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\License;
use Database\Seeders\DemoEnvironmentSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     * Cria e migra um novo schema isolado no PostgreSQL para um cliente na nuvem (SaaS).
     */
    public function createTenant(string $name): string
    {
        $schema = $this->sanitizeSchemaName($name);

        if (in_array($schema, self::PROTECTED_SCHEMAS, true)) {
            throw new InvalidArgumentException("O nome de schema '{$schema}' é reservado do sistema.");
        }

        DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$schema}\"");

        $this->migrateSchema($schema);

        return $schema;
    }

    /**
     * Remove completamente um schema de tenant e todos os seus dados.
     */
    public function dropTenant(string $name): void
    {
        $schema = $this->sanitizeSchemaName($name);

        if (in_array($schema, self::PROTECTED_SCHEMAS, true)) {
            throw new InvalidArgumentException("Não é permitido excluir schemas protegidos como '{$schema}'.");
        }

        $this->switchToPublic();

        DB::statement("DROP SCHEMA IF EXISTS \"{$schema}\" CASCADE");

        // Remove também a pasta física de arquivos e uploads do tenant
        $slug = str_starts_with($schema, 'tenant_') ? substr($schema, 7) : $schema;
        $tenantStoragePath = storage_path("app/public/tenants/{$slug}");
        if (is_dir($tenantStoragePath)) {
            \Illuminate\Support\Facades\File::deleteDirectory($tenantStoragePath);
        }
    }

    /**
     * Executa as migrations do Aurosoft isoladamente dentro do schema informado.
     */
    public function migrateSchema(string $schema): void
    {
        $schema = $this->sanitizeSchemaName($schema);

        $this->switchToTenant($schema);

        try {
            // Executa as migrations do Laravel criando as tabelas dentro do schema
            Artisan::call('migrate', [
                '--force' => true,
            ]);
        } finally {
            $this->switchToPublic();
        }
    }

    /**
     * Reseta completamente o ambiente Demo: recria o schema 'demo', migra e popula com dados fictícios.
     *
     * @return array{schema: string, duration_ms: float, message: string}
     */
    public function resetDemoTenant(): array
    {
        $startTime = microtime(true);
        $schema = 'demo';

        // 1. Remove schema anterior se existir
        $this->dropTenant($schema);

        // 2. Cria o novo schema
        DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$schema}\"");

        // 3. Executa as migrações isoladas
        $this->migrateSchema($schema);

        // 4. Popula com dados de demonstração
        $this->switchToTenant($schema);

        try {
            $seeder = new DemoEnvironmentSeeder();
            $seeder->run();
        } finally {
            $this->switchToPublic();
        }

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'schema' => $schema,
            'duration_ms' => $durationMs,
            'message' => "Ambiente Demo resetado e populado com sucesso em {$durationMs} ms!",
        ];
    }

    /**
     * Alterna o search_path do PostgreSQL para o schema do tenant
     * e isola dinamicamente a pasta de uploads/arquivos no Storage.
     */
    public function switchToTenant(string $name): void
    {
        $schema = $this->sanitizeSchemaName($name);

        DB::statement("SET search_path TO \"{$schema}\", public");
        Config::set('database.connections.pgsql.search_path', "{$schema},public");

        $this->configureTenantStorage($schema);
    }

    /**
     * Retorna a conexão para o schema 'public' padrão
     * e restaura o disco de storage para a raiz pública.
     */
    public function switchToPublic(): void
    {
        DB::statement("SET search_path TO public");
        Config::set('database.connections.pgsql.search_path', 'public');

        $this->resetPublicStorage();
    }

    /**
     * Isola dinamicamente as raízes dos discos 'public' e 'local' (privado) para a subpasta exclusiva do tenant.
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
        $row = DB::selectOne('SELECT current_schema() as schema');

        return (string) ($row->schema ?? 'public');
    }

    /**
     * Retorna a lista de todos os schemas de tenants criados no banco.
     *
     * @return array<int, array{name: string, tables_count: int, size_mb: float}>
     */
    public function listTenants(): array
    {
        $sql = "SELECT schema_name FROM information_schema.schemata 
                WHERE schema_name NOT IN ('public', 'information_schema') 
                  AND schema_name NOT LIKE 'pg_%' 
                ORDER BY schema_name ASC";

        $rows = DB::select($sql);
        $tenants = [];

        foreach ($rows as $row) {
            $schemaName = (string) $row->schema_name;

            $tablesCountRow = DB::selectOne(
                "SELECT count(*) as total FROM information_schema.tables WHERE table_schema = ?",
                [$schemaName]
            );

            $tablesCount = (int) ($tablesCountRow->total ?? 0);

            $sizeRow = DB::selectOne(
                "SELECT pg_size_pretty(sum(pg_total_relation_size(quote_ident(schemaname) || '.' || quote_ident(tablename)))) as size
                 FROM pg_tables WHERE schemaname = ?",
                [$schemaName]
            );

            $tenants[] = [
                'name' => $schemaName,
                'tables_count' => $tablesCount,
                'size' => (string) ($sizeRow->size ?? '0 MB'),
                'is_demo' => $schemaName === 'demo',
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

        // Não pode começar com números no PostgreSQL sem aspas
        if (is_numeric($sanitized[0])) {
            $sanitized = 'tenant_' . $sanitized;
        }

        return $sanitized;
    }

    /**
     * Provisiona completamente um schema de tenant na nuvem a partir de uma License.
     *
     * @param License $license
     * @param array{admin_name?: string, admin_email?: string, admin_password?: string} $options
     * @return array{schema: string, duration_ms: float, admin_email: string, admin_password: string}
     */
    public function provisionCloudTenant(License $license, array $options = []): array
    {
        $startTime = microtime(true);

        // 1. Garante o slug e o nome do schema
        if (empty($license->slug)) {
            $baseSlug = Str::slug($license->trade_name ?: $license->client_name);
            $license->slug = $baseSlug ?: ('tenant-' . Str::lower(Str::random(6)));
            $license->save();
        }

        $schemaName = $this->sanitizeSchemaName($license->schema_name ?: ('tenant_' . $license->slug));

        if (in_array($schemaName, self::PROTECTED_SCHEMAS, true)) {
            throw new InvalidArgumentException("Não é permitido usar o schema protegido '{$schemaName}'.");
        }

        // 2. Cria o schema no PostgreSQL
        DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$schemaName}\"");

        // 3. Executa as migrações no novo schema
        $this->migrateSchema($schemaName);

        // 4. Executa a carga inicial (seed) das configurações da escola e usuário admin
        $this->switchToTenant($schemaName);

        $adminEmail = $options['admin_email'] ?? $license->contact_email ?? ('admin@' . $license->slug . '.com.br');
        $adminPassword = $options['admin_password'] ?? '12345678';

        try {
            $seeder = new TenantBaselineSeeder($license, [
                'admin_name' => $options['admin_name'] ?? $license->contact_name ?? 'Administrador',
                'admin_email' => $adminEmail,
                'admin_password' => $adminPassword,
            ]);
            $seeder->run();
        } finally {
            $this->switchToPublic();
        }

        // 5. Atualiza a licença com o schema_name definitivo
        $license->update([
            'schema_name' => $schemaName,
            'status' => 'active',
        ]);

        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        return [
            'schema' => $schemaName,
            'duration_ms' => $durationMs,
            'admin_email' => $adminEmail,
            'admin_password' => $adminPassword,
        ];
    }

    /**
     * Resolve a licença do tenant correspondente a partir do host/domínio da requisição HTTP.
     */
    public function resolveTenantFromHost(string $host): ?License
    {
        // 1. Remove qualquer indicação de porta (ex: :8000)
        $cleanHost = strtolower(trim(explode(':', $host)[0]));

        if (empty($cleanHost)) {
            return null;
        }

        // 2. Primeiro checa se bate diretamente com um custom_domain cadastrado
        $customDomainLicense = DB::table('public.licenses')
            ->where('custom_domain', $cleanHost)
            ->first();

        if ($customDomainLicense) {
            return License::find($customDomainLicense->id);
        }

        // 3. Subdomínio em ambiente local (.localhost)
        // Ex: "escola1.localhost" -> slug = "escola1"
        if (str_ends_with($cleanHost, '.localhost')) {
            $parts = explode('.', $cleanHost);
            $slug = $parts[0] ?? null;
            if ($slug && ! in_array($slug, ['www', 'saas', 'admin', 'developer', 'api'], true)) {
                $licenseRecord = DB::table('public.licenses')->where('slug', $slug)->first();
                return $licenseRecord ? License::find($licenseRecord->id) : null;
            }
            return null;
        }

        // 4. Subdomínio em produção na nuvem (ex: *.aurosoft.com.br ou domínio configurado)
        $rootDomain = strtolower(config('app.saas_domain', env('SAAS_DOMAIN', 'aurosoft.com.br')));
        if ($rootDomain && str_ends_with($cleanHost, '.' . $rootDomain)) {
            $subdomain = substr($cleanHost, 0, - (strlen($rootDomain) + 1));
            $parts = explode('.', $subdomain);
            $slug = end($parts);
            if ($slug && ! in_array($slug, ['www', 'saas', 'admin', 'developer', 'api', 'mail'], true)) {
                $licenseRecord = DB::table('public.licenses')->where('slug', $slug)->first();
                return $licenseRecord ? License::find($licenseRecord->id) : null;
            }
            return null;
        }

        // 5. Fallback genérico para hosts com subdomínio
        $hostParts = explode('.', $cleanHost);
        if (count($hostParts) >= 3) {
            $candidateSlug = $hostParts[0];
            if (! in_array($candidateSlug, ['www', 'saas', 'admin', 'developer', 'api', 'mail'], true)) {
                $licenseRecord = DB::table('public.licenses')->where('slug', $candidateSlug)->first();
                if ($licenseRecord) {
                    return License::find($licenseRecord->id);
                }
            }
        }

        return null;
    }
}

