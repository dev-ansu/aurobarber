<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Course;
use App\Models\LmsLesson;
use App\Models\LmsLessonStep;
use App\Models\LmsModule;
use App\Models\TypingExercise;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class LmsContentBackupService
{
    public const DEFAULT_BACKUP_PATH = 'database/seeders/data/lms_courses_backup.json';

    /**
     * Exporta todos os cursos com módulos, lições, passos e exercícios de digitação.
     *
     * @return array<string, mixed>
     */
    public function exportAll(): array
    {
        $courses = Course::query()
            ->with([
                'lmsModules' => function ($mQuery): void {
                    $mQuery->orderBy('sort_order')
                        ->with([
                            'lessons' => function ($lQuery): void {
                                $lQuery->orderBy('sort_order')
                                    ->with([
                                        'steps' => fn ($sQuery) => $sQuery->orderBy('sort_order'),
                                    ]);
                            },
                        ]);
                },
            ])
            ->orderBy('name')
            ->get();

        $typingExercises = TypingExercise::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return [
            'application' => 'Aurosoft LMS',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'counts' => [
                'courses' => $courses->count(),
                'modules' => $courses->flatMap->lmsModules->count(),
                'lessons' => $courses->flatMap->lmsModules->flatMap->lessons->count(),
                'steps' => $courses->flatMap->lmsModules->flatMap->lessons->flatMap->steps->count(),
                'typing_exercises' => $typingExercises->count(),
            ],
            'courses' => $courses->map(fn (Course $course) => $this->serializeCourse($course))->all(),
            'typing_exercises' => $typingExercises->map(fn (TypingExercise $te) => [
                'title' => $te->title,
                'target_text' => $te->target_text,
                'level' => $te->level,
                'category' => $te->category,
                'instructions' => $te->instructions,
                'keyboard_layout' => $te->keyboard_layout,
                'min_wpm' => $te->min_wpm,
                'min_accuracy' => $te->min_accuracy,
                'sort_order' => $te->sort_order,
                'is_active' => (bool) $te->is_active,
            ])->all(),
        ];
    }

    /**
     * Exporta um único curso com seus módulos e aulas.
     *
     * @return array<string, mixed>
     */
    public function exportCourse(Course $course): array
    {
        $course->loadMissing([
            'lmsModules' => function ($mQuery): void {
                $mQuery->orderBy('sort_order')
                    ->with([
                        'lessons' => function ($lQuery): void {
                            $lQuery->orderBy('sort_order')
                                ->with([
                                    'steps' => fn ($sQuery) => $sQuery->orderBy('sort_order'),
                                ]);
                        },
                    ]);
            },
        ]);

        return [
            'application' => 'Aurosoft LMS Course Package',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'course' => $this->serializeCourse($course),
        ];
    }

    /**
     * Exporta um único curso em um pacote ZIP completo contendo dados e todos os arquivos de mídia.
     */
    public function exportCourseToZip(Course $course): string
    {
        $exportDir = storage_path('app/private/lms-exports');
        File::ensureDirectoryExists($exportDir);

        $slug = Str::slug($course->name) ?: 'curso';
        $filename = "pacote-curso-{$slug}-" . now()->format('Ymd-His') . '.zip';
        $targetZip = $exportDir . '/' . $filename;

        $zip = new ZipArchive();
        if ($zip->open($targetZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Falha ao criar arquivo ZIP do curso em {$targetZip}");
        }

        $data = $this->exportCourse($course);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $zip->addFromString('package.json', $json);

        $mediaFiles = $this->collectMediaFilesFromExportData($data);
        foreach ($mediaFiles as $mediaFilename => $realPath) {
            if (File::exists($realPath)) {
                $zip->addFile($realPath, 'media/' . $mediaFilename);
            }
        }

        $zip->close();

        return $targetZip;
    }

    /**
     * Exporta todos os cursos e catálogo em um pacote ZIP completo com todas as mídias do sistema.
     */
    public function exportAllToZip(): string
    {
        $exportDir = storage_path('app/private/lms-exports');
        File::ensureDirectoryExists($exportDir);

        $filename = 'pacote-completo-aurosoft-lms-' . now()->format('Ymd-His') . '.zip';
        $targetZip = $exportDir . '/' . $filename;

        $zip = new ZipArchive();
        if ($zip->open($targetZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Falha ao criar arquivo ZIP completo em {$targetZip}");
        }

        $data = $this->exportAll();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $zip->addFromString('package.json', $json);

        // Coleta mídias referenciadas e todos os arquivos presentes no diretório público de mídias LMS
        $mediaDir = storage_path('app/public/lms-media');
        $mediaFiles = $this->collectMediaFilesFromExportData($data);

        if (File::isDirectory($mediaDir)) {
            foreach (File::files($mediaDir) as $file) {
                $mediaFiles[$file->getFilename()] = $file->getRealPath();
            }
        }

        foreach ($mediaFiles as $mediaFilename => $realPath) {
            if (File::exists($realPath)) {
                $zip->addFile($realPath, 'media/' . $mediaFilename);
            }
        }

        $zip->close();

        return $targetZip;
    }

    /**
     * Coleta caminhos reais de mídias referenciadas nos dados do pacote.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>  [filename => absolute_path]
     */
    public function collectMediaFilesFromExportData(array $data): array
    {
        $files = [];
        $mediaDir = storage_path('app/public/lms-media');

        $recordFile = function ($value) use (&$files, $mediaDir): void {
            if (! is_string($value) || empty($value)) {
                return;
            }

            if (str_contains($value, 'lms-media') || str_contains($value, '/storage/')) {
                $basename = basename(parse_url($value, PHP_URL_PATH));
                if (! empty($basename)) {
                    $fullPath = $mediaDir . '/' . $basename;
                    if (File::exists($fullPath)) {
                        $files[$basename] = $fullPath;
                    }
                }
            }
        };

        $walk = function (array $items) use (&$walk, $recordFile): void {
            foreach ($items as $val) {
                if (is_array($val)) {
                    $walk($val);
                } elseif (is_string($val)) {
                    $recordFile($val);
                }
            }
        };

        $walk($data);

        return $files;
    }

    /**
     * Importa um pacote ZIP completo, restaurando mídias para storage público e importando os dados.
     *
     * @return array{courses: int, modules: int, lessons: int, steps: int, typing_exercises: int, media_files: int}
     */
    public function importFromZip(string $zipFilePath): array
    {
        if (! File::exists($zipFilePath)) {
            throw new RuntimeException("Arquivo ZIP não encontrado em {$zipFilePath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath) !== true) {
            throw new RuntimeException("Falha ao abrir arquivo ZIP: {$zipFilePath}");
        }

        $jsonContent = null;
        if ($zip->locateName('package.json') !== false) {
            $jsonContent = $zip->getFromName('package.json');
        } else {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (str_ends_with(strtolower((string) $entry), '.json') && ! str_contains((string) $entry, '/')) {
                    $jsonContent = $zip->getFromName($entry);
                    break;
                }
            }
        }

        if (empty($jsonContent)) {
            $zip->close();
            throw new RuntimeException("O pacote ZIP não contém o arquivo 'package.json' do LMS.");
        }

        $mediaDir = storage_path('app/public/lms-media');
        File::ensureDirectoryExists($mediaDir);
        $restoredMediaCount = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = (string) $zip->getNameIndex($i);
            if (str_contains($entry, '..') || str_starts_with($entry, '/')) {
                continue;
            }

            if (str_starts_with($entry, 'media/') || str_starts_with($entry, 'storage/lms-media/')) {
                $filename = basename($entry);
                if (! empty($filename)) {
                    $targetPath = $mediaDir . '/' . $filename;
                    $stream = $zip->getStream($entry);
                    if ($stream) {
                        file_put_contents($targetPath, stream_get_contents($stream));
                        fclose($stream);
                        $restoredMediaCount++;
                    }
                }
            }
        }

        $zip->close();

        $data = json_decode((string) $jsonContent, true, 512, JSON_THROW_ON_ERROR);
        $stats = $this->importAll($data);
        $stats['media_files'] = $restoredMediaCount;

        return $stats;
    }

    /**
     * Importa um pacote universal (.zip com mídias ou .json legadado).
     *
     * @return array{courses: int, modules: int, lessons: int, steps: int, typing_exercises: int, media_files: int}
     */
    public function importPackageFile(string $filePath): array
    {
        $handle = @fopen($filePath, 'rb');
        $isZip = false;
        if ($handle) {
            $header = fread($handle, 4);
            fclose($handle);
            $isZip = ($header === "PK\x03\x04");
        }

        if ($isZip || str_ends_with(strtolower($filePath), '.zip')) {
            return $this->importFromZip($filePath);
        }

        $stats = $this->restoreFromFile($filePath);
        $stats['media_files'] = 0;

        return $stats;
    }

    /**
     * Salva o backup completo de conteúdo LMS em disco.
     */
    public function saveBackupToFile(?string $path = null): string
    {
        $targetPath = $path ? base_path($path) : base_path(self::DEFAULT_BACKUP_PATH);
        File::ensureDirectoryExists(dirname($targetPath));

        $data = $this->exportAll();
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        if (File::put($targetPath, $json) === false) {
            throw new RuntimeException("Falha ao gravar arquivo de backup LMS em {$targetPath}");
        }

        // Também salva uma cópia rotacionada em storage/app/private/lms-backups/
        $storageDir = storage_path('app/private/lms-backups');
        File::ensureDirectoryExists($storageDir);
        $snapshotName = 'lms-backup-' . now()->format('Ymd-His') . '.json';
        File::put($storageDir . '/' . $snapshotName, $json);

        return $targetPath;
    }

    /**
     * Restaura todo o conteúdo LMS a partir de um arquivo JSON.
     *
     * @return array{courses: int, modules: int, lessons: int, steps: int, typing_exercises: int}
     */
    public function restoreFromFile(?string $path = null): array
    {
        $targetPath = $path ? (File::exists($path) ? $path : base_path($path)) : base_path(self::DEFAULT_BACKUP_PATH);

        if (! File::exists($targetPath)) {
            throw new RuntimeException("Arquivo de backup LMS não encontrado em {$targetPath}");
        }

        $json = File::get($targetPath);
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $this->importAll($data);
    }

    /**
     * Importa todos os dados de um array de backup.
     *
     * @param  array<string, mixed>  $data
     * @return array{courses: int, modules: int, lessons: int, steps: int, typing_exercises: int}
     */
    public function importAll(array $data): array
    {
        $stats = [
            'courses' => 0,
            'modules' => 0,
            'lessons' => 0,
            'steps' => 0,
            'typing_exercises' => 0,
            'media_files' => 0,
        ];

        DB::transaction(function () use ($data, &$stats): void {
            // 1. Cursos, Módulos, Lições e Passos (suporta tanto pacote múltiplo 'courses' quanto pacote de curso individual 'course')
            $coursesData = $data['courses'] ?? (isset($data['course']) ? [$data['course']] : []);
            if (empty($coursesData) && isset($data['name']) && isset($data['modules'])) {
                // Suporta o próprio objeto do curso passado diretamente
                $coursesData = [$data];
            }

            foreach ($coursesData as $cData) {
                $course = Course::updateOrCreate(
                    ['name' => $cData['name']],
                    [
                        'description' => $cData['description'] ?? null,
                        'workload' => $cData['workload'] ?? null,
                        'active' => $cData['active'] ?? true,
                        'ouro_moderno_id' => $cData['ouro_moderno_id'] ?? $cData['ouro_moderno_course_id'] ?? null,
                    ]
                );
                $stats['courses']++;

                foreach ($cData['modules'] ?? [] as $mIndex => $mData) {
                    $module = LmsModule::updateOrCreate(
                        [
                            'course_id' => $course->id,
                            'title' => $mData['title'],
                        ],
                        [
                            'sort_order' => $mData['sort_order'] ?? ($mIndex + 1),
                            'description' => $mData['description'] ?? null,
                            'is_active' => $mData['is_active'] ?? true,
                        ]
                    );
                    $stats['modules']++;

                    foreach ($mData['lessons'] ?? [] as $lIndex => $lData) {
                        $lesson = LmsLesson::updateOrCreate(
                            [
                                'module_id' => $module->id,
                                'title' => $lData['title'],
                            ],
                            [
                                'sort_order' => $lData['sort_order'] ?? ($lIndex + 1),
                                'passing_score' => $lData['passing_score'] ?? 80,
                                'estimated_duration_minutes' => $lData['estimated_duration_minutes'] ?? $lData['estimated_minutes'] ?? 90,
                                'description' => $lData['description'] ?? null,
                                'is_active' => $lData['is_active'] ?? true,
                            ]
                        );
                        $stats['lessons']++;

                        // Substitui passos para garantir sincronização perfeita
                        $lesson->steps()->delete();
                        foreach ($lData['steps'] ?? [] as $sIndex => $sData) {
                            LmsLessonStep::create([
                                'lesson_id' => $lesson->id,
                                'phase' => $sData['phase'] ?? 'exposition',
                                'step_type' => $sData['step_type'] ?? 'slide',
                                'title' => $sData['title'] ?? 'Passo ' . ($sIndex + 1),
                                'content' => $sData['content'] ?? [],
                                'sort_order' => $sData['sort_order'] ?? ($sIndex + 1),
                            ]);
                            $stats['steps']++;
                        }
                    }
                }
            }

            // 2. Exercícios de Digitação
            foreach ($data['typing_exercises'] ?? [] as $tIndex => $tData) {
                TypingExercise::updateOrCreate(
                    ['title' => $tData['title']],
                    [
                        'target_text' => $tData['target_text'] ?? $tData['text_content'] ?? 'ffff jjjj ff jj',
                        'level' => $tData['level'] ?? 1,
                        'category' => $tData['category'] ?? 'home_row',
                        'instructions' => $tData['instructions'] ?? 'Posicione os dedos nas teclas guia',
                        'keyboard_layout' => $tData['keyboard_layout'] ?? 'abnt2',
                        'min_wpm' => $tData['min_wpm'] ?? $tData['target_wpm'] ?? 15,
                        'min_accuracy' => $tData['min_accuracy'] ?? 80.00,
                        'sort_order' => $tData['sort_order'] ?? ($tIndex + 1),
                        'is_active' => $tData['is_active'] ?? true,
                    ]
                );
                $stats['typing_exercises']++;
            }
        });

        return $stats;
    }

    /**
     * Serializa um curso para formato portátil.
     *
     * @return array<string, mixed>
     */
    protected function serializeCourse(Course $course): array
    {
        return [
            'name' => $course->name,
            'description' => $course->description,
            'workload' => $course->workload,
            'active' => (bool) $course->active,
            'ouro_moderno_id' => $course->ouro_moderno_id,
            'modules' => $course->lmsModules->map(fn (LmsModule $m) => [
                'title' => $m->title,
                'description' => $m->description,
                'sort_order' => $m->sort_order,
                'is_active' => (bool) $m->is_active,
                'lessons' => $m->lessons->map(fn (LmsLesson $l) => [
                    'title' => $l->title,
                    'description' => $l->description,
                    'sort_order' => $l->sort_order,
                    'passing_score' => $l->passing_score,
                    'estimated_duration_minutes' => $l->estimated_duration_minutes,
                    'is_active' => (bool) $l->is_active,
                    'steps' => $l->steps->map(fn (LmsLessonStep $s) => [
                        'phase' => $s->phase,
                        'step_type' => $s->step_type,
                        'title' => $s->title,
                        'sort_order' => $s->sort_order,
                        'content' => $s->content,
                    ])->all(),
                ])->all(),
            ])->all(),
        ];
    }
}
