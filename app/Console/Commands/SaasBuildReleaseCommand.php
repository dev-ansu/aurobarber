<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class SaasBuildReleaseCommand extends Command
{
    protected $signature = 'saas:build-release 
                            {version : A versão da release (ex: 1.6.6 ou 2.0.1)} 
                            {--notes=* : Notas da release (adicione várias vezes --notes="...")} 
                            {--name= : Nome comercial da versão}';

    protected $description = 'Gera o pacote ZIP de distribuição de atualização para o SaaS Master';

    public function handle(): int
    {
        $version = trim((string) $this->argument('version'));
        $notes = (array) $this->option('notes');
        $name = (string) ($this->option('name') ?: "Aurosoft v{$version}");

        if (empty($notes)) {
            $notes = [
                "Atualização cumulativa de estabilidade e recursos da versão {$version}.",
                "Otimizações no banco de dados e performance de carregamento.",
            ];
        }

        $releasesDir = storage_path('app/releases');
        File::ensureDirectoryExists($releasesDir);

        $zipFileName = "aurosoft-v{$version}.zip";
        $zipFilePath = "{$releasesDir}/{$zipFileName}";

        if (File::exists($zipFilePath)) {
            if (! $this->confirm("O arquivo {$zipFileName} já existe. Deseja sobrescrever?", true)) {
                $this->warn('Operação cancelada.');
                return self::SUCCESS;
            }
            File::delete($zipFilePath);
        }

        $this->info("Iniciando empacotamento da versão {$version}...");

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('Não foi possível criar o arquivo ZIP de release.');
            return self::FAILURE;
        }

        $basePath = base_path();
        
        // Pastas e arquivos estritamente excluídos do pacote de atualização
        $excludedPatterns = [
            '^\.git',
            '^\.github',
            '^\.env$',
            '^\.env\.encrypted$',
            '^\.env\.key$',
            '^storage\/app\/releases',
            '^storage\/app\/backups',
            '^storage\/app\/public',
            '^storage\/app\/private',
            '^storage\/framework\/cache',
            '^storage\/framework\/sessions',
            '^storage\/framework\/views',
            '^storage\/logs',
            '^node_modules',
            '^installers',
            '^dist',
            '\.bat$',
            '\.vbs$',
            '\.log$',
            '\.tmp$',
        ];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $bar = $this->output->createProgressBar();
        $bar->start();

        $addedCount = 0;

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $realPath = $file->getRealPath();
            $relativePath = str_replace('\\', '/', substr($realPath, strlen($basePath) + 1));

            // Verifica se está na lista de exclusão
            $shouldExclude = false;
            foreach ($excludedPatterns as $pattern) {
                if (preg_match("#{$pattern}#i", $relativePath)) {
                    $shouldExclude = true;
                    break;
                }
            }

            if ($shouldExclude) {
                continue;
            }

            $zip->addFile($realPath, $relativePath);
            $addedCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $zip->close();

        $sizeBytes = filesize($zipFilePath);
        $sizeMb = round($sizeBytes / 1024 / 1024, 2);
        $sha256 = hash_file('sha256', $zipFilePath);

        $this->info("[+] Pacote ZIP gerado: {$zipFileName} ({$sizeMb} MB, {$addedCount} arquivos)");
        $this->line("    SHA-256: {$sha256}");

        // Grava o manifesto oficial de release
        $manifest = [
            'version' => $version,
            'name' => $name,
            'notes' => $notes,
            'published_at' => now()->format('d/m/Y H:i'),
            'file_name' => $zipFileName,
            'size_bytes' => $sizeBytes,
            'size_mb' => $sizeMb,
            'sha256' => $sha256,
        ];

        File::put("{$releasesDir}/manifest.json", json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $this->info("[+] Manifesto de atualização atualizado com sucesso em storage/app/releases/manifest.json!");

        return self::SUCCESS;
    }
}

