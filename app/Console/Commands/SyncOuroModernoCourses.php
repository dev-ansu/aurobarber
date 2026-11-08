<?php

namespace App\Console\Commands;

use App\Services\CourseSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncOuroModernoCourses extends Command
{
    protected $signature = 'app:sync-ouro-moderno-courses';

    protected $description = 'Sincroniza o catálogo de cursos da Ouro Moderno.';

    public function handle(CourseSyncService $courseSync): int
    {
        $this->info('Sincronizando cursos da Ouro Moderno...');

        try {
            $count = $courseSync->syncCourses();
        } catch (Throwable $exception) {
            $this->error('Não foi possível sincronizar o catálogo: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Sincronização concluída. {$count} curso(s) processado(s).");

        return self::SUCCESS;
    }
}
