<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Services\LmsContentBackupService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CourseSeeder extends Seeder
{
    public function run(?LmsContentBackupService $backupService = null): void
    {
        $backupService ??= app(LmsContentBackupService::class);
        $backupFile = base_path(LmsContentBackupService::DEFAULT_BACKUP_PATH);
        if (! app()->runningUnitTests() && File::exists($backupFile)) {
            $backupService->restoreFromFile($backupFile);
            return;
        }

        $courses = [
            [
                'name' => 'Programação Web',
                'description' => 'Fundamentos de desenvolvimento de aplicações para a web.',
                'workload' => 120,
            ],
            [
                'name' => 'Design Gráfico',
                'description' => 'Fundamentos de design, composição e comunicação visual.',
                'workload' => 96,
            ],
            [
                'name' => 'Gestão Empresarial',
                'description' => 'Princípios de gestão e organização de negócios.',
                'workload' => 80,
            ],
            [
                'name' => 'Marketing Digital',
                'description' => 'Estratégias e ferramentas de marketing digital.',
                'workload' => 72,
            ],
        ];

        foreach ($courses as $course) {
            Course::firstOrCreate(
                ['name' => $course['name']],
                [
                    'description' => $course['description'],
                    'workload' => $course['workload'],
                    'active' => true,
                ],
            );
        }
    }
}
