<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LmsContentBackupService;
use Illuminate\Console\Command;

class BackupLmsContentCommand extends Command
{
    protected $signature = 'lms:backup {--path= : Caminho customizado para salvar o arquivo JSON}';

    protected $description = 'Gera um backup completo em JSON de todos os cursos, módulos, lições, passos multimídia e treinos de digitação do LMS';

    public function handle(LmsContentBackupService $backupService): int
    {
        $this->info('Iniciando exportação de conteúdo LMS do Aurosoft...');

        try {
            $customPath = $this->option('path');
            $savedPath = $backupService->saveBackupToFile($customPath ? (string) $customPath : null);

            $this->info("Backup do LMS gerado com sucesso em: {$savedPath}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Erro ao gerar backup LMS: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
