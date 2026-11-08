<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LmsContentBackupService;
use Illuminate\Console\Command;

class RestoreLmsContentCommand extends Command
{
    protected $signature = 'lms:restore {--path= : Caminho do arquivo JSON a ser restaurado}';

    protected $description = 'Restaura cursos, módulos, lições, passos multimídia e exercícios de digitação a partir do backup JSON';

    public function handle(LmsContentBackupService $backupService): int
    {
        $this->info('Iniciando restauração de conteúdo LMS...');

        try {
            $customPath = $this->option('path');
            $stats = $backupService->restoreFromFile($customPath ? (string) $customPath : null);

            $this->info('Conteúdo LMS restaurado com sucesso!');
            $this->table(
                ['Entidade', 'Total Restaurado / Sincronizado'],
                [
                    ['Cursos', $stats['courses']],
                    ['Módulos LMS', $stats['modules']],
                    ['Lições / Aulas', $stats['lessons']],
                    ['Passos (Vídeos / Quizzes / Slides)', $stats['steps']],
                    ['Treinos de Digitação', $stats['typing_exercises']],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Erro ao restaurar conteúdo LMS: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
