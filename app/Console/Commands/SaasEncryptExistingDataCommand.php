<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\License;
use App\Models\Person;
use App\Services\TenantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaasEncryptExistingDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saas:encrypt-existing-data 
                            {--tenant= : Nome específico do schema para criptografar} 
                            {--chunk=100 : Tamanho do lote de processamento}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Criptografa com AES-256 os CPFs (people), documentos (documents) e chaves Pix (collaborators) e popula os índices cegos (cpf_hash / value_hash) em todos os schemas.';

    /**
     * Execute the console command.
     */
    public function handle(TenantService $tenantService): int
    {
        $specificTenant = $this->option('tenant');
        $chunkSize = max(10, (int) $this->option('chunk'));

        if ($specificTenant) {
            $schemas = [$tenantService->sanitizeSchemaName($specificTenant)];
        } else {
            $schemas = ['public'];

            $licenses = License::where('type', 'cloud_saas')
                ->whereNotNull('schema_name')
                ->get();

            $tenantSchemas = $licenses->pluck('schema_name')->filter()->unique()->values()->all();
            $schemas = array_merge($schemas, $tenantSchemas);

            $demoTenant = collect($tenantService->listTenants())->firstWhere('name', 'demo');
            if ($demoTenant && ! in_array('demo', $schemas, true)) {
                $schemas[] = 'demo';
            }

            $schemas = array_values(array_unique($schemas));
        }

        $this->info("Iniciando criptografia e geração de blind index para " . count($schemas) . " schema(s)...");

        foreach ($schemas as $schema) {
            $this->line("--------------------------------------------------");
            $this->info("Processando schema: [{$schema}]");

            try {
                if ($schema === 'public') {
                    $tenantService->switchToPublic();
                } else {
                    $tenantService->switchToTenant($schema);
                }

                $this->encryptPeople($chunkSize);
                $this->encryptDocuments($chunkSize);
                $this->encryptCollaborators($chunkSize);

                $this->info("✔ Schema [{$schema}] processado com sucesso!");
            } catch (\Throwable $e) {
                $this->error("✖ Erro no schema [{$schema}]: " . $e->getMessage());
            } finally {
                $tenantService->switchToPublic();
            }
        }

        $this->line("--------------------------------------------------");
        $this->info("Processo de criptografia em repouso e blind index concluído!");

        return self::SUCCESS;
    }

    protected function encryptPeople(int $chunkSize): void
    {
        if (! Schema::hasTable('people') || ! Schema::hasColumn('people', 'cpf')) {
            return;
        }

        $query = DB::table('people')->whereNotNull('cpf')->where('cpf', '!=', '');
        $total = $query->count();

        if ($total === 0) {
            $this->line("  - Pessoas: 0 registros para processar.");
            return;
        }

        $encryptedCount = 0;
        $hashesFixed = 0;

        $query->orderBy('id')->chunk($chunkSize, function ($rows) use (&$encryptedCount, &$hashesFixed) {
            foreach ($rows as $row) {
                $rawCpf = (string) $row->cpf;
                $isEncrypted = false;
                $decrypted = null;

                try {
                    $decrypted = Crypt::decryptString($rawCpf);
                    $isEncrypted = true;
                } catch (\Throwable) {
                    $isEncrypted = false;
                }

                if (! $isEncrypted) {
                    $clean = preg_replace('/\D/', '', $rawCpf);
                    $encrypted = Crypt::encryptString($clean);
                    $hash = Person::hashCpf($clean);

                    DB::table('people')->where('id', $row->id)->update([
                        'cpf' => $encrypted,
                        'cpf_hash' => $hash,
                    ]);
                    $encryptedCount++;
                } elseif (empty($row->cpf_hash) && filled($decrypted)) {
                    $hash = Person::hashCpf($decrypted);
                    DB::table('people')->where('id', $row->id)->update([
                        'cpf_hash' => $hash,
                    ]);
                    $hashesFixed++;
                }
            }
        });

        $this->line("  - Pessoas: {$encryptedCount} criptografados, {$hashesFixed} hashes corrigidos (Total: {$total}).");
    }

    protected function encryptDocuments(int $chunkSize): void
    {
        if (! Schema::hasTable('documents') || ! Schema::hasColumn('documents', 'value')) {
            return;
        }

        $query = DB::table('documents')->whereNotNull('value')->where('value', '!=', '');
        $total = $query->count();

        if ($total === 0) {
            $this->line("  - Documentos: 0 registros para processar.");
            return;
        }

        $encryptedCount = 0;
        $hashesFixed = 0;

        $query->orderBy('id')->chunk($chunkSize, function ($rows) use (&$encryptedCount, &$hashesFixed) {
            foreach ($rows as $row) {
                $rawValue = (string) $row->value;
                $isEncrypted = false;
                $decrypted = null;

                try {
                    $decrypted = Crypt::decryptString($rawValue);
                    $isEncrypted = true;
                } catch (\Throwable) {
                    $isEncrypted = false;
                }

                if (! $isEncrypted) {
                    $trimmed = trim($rawValue);
                    $encrypted = Crypt::encryptString($trimmed);
                    $hash = Document::hashValue($trimmed);

                    DB::table('documents')->where('id', $row->id)->update([
                        'value' => $encrypted,
                        'value_hash' => $hash,
                    ]);
                    $encryptedCount++;
                } elseif (empty($row->value_hash) && filled($decrypted)) {
                    $hash = Document::hashValue($decrypted);
                    DB::table('documents')->where('id', $row->id)->update([
                        'value_hash' => $hash,
                    ]);
                    $hashesFixed++;
                }
            }
        });

        $this->line("  - Documentos: {$encryptedCount} criptografados, {$hashesFixed} hashes corrigidos (Total: {$total}).");
    }

    protected function encryptCollaborators(int $chunkSize): void
    {
        if (! Schema::hasTable('collaborators') || ! Schema::hasColumn('collaborators', 'pix')) {
            return;
        }

        $query = DB::table('collaborators')->whereNotNull('pix')->where('pix', '!=', '');
        $total = $query->count();

        if ($total === 0) {
            $this->line("  - Colaboradores (Pix): 0 registros para processar.");
            return;
        }

        $encryptedCount = 0;

        $query->orderBy('id')->chunk($chunkSize, function ($rows) use (&$encryptedCount) {
            foreach ($rows as $row) {
                $rawPix = (string) $row->pix;
                $isEncrypted = false;

                try {
                    Crypt::decryptString($rawPix);
                    $isEncrypted = true;
                } catch (\Throwable) {
                    $isEncrypted = false;
                }

                if (! $isEncrypted) {
                    $trimmed = trim($rawPix);
                    $encrypted = Crypt::encryptString($trimmed);

                    DB::table('collaborators')->where('id', $row->id)->update([
                        'pix' => $encrypted,
                    ]);
                    $encryptedCount++;
                }
            }
        });

        $this->line("  - Colaboradores (Pix): {$encryptedCount} criptografados (Total: {$total}).");
    }
}
