<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LmsLesson;
use App\Models\LmsLessonStep;
use App\Models\LmsModule;
use App\Services\LmsContentBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
use ZipArchive;

class LmsContentBackupPackageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $mediaDir = storage_path('app/public/lms-media');
        if (File::exists($mediaDir . '/package-test-img.png')) {
            File::delete($mediaDir . '/package-test-img.png');
        }
        $exportDir = storage_path('app/private/lms-exports');
        if (File::isDirectory($exportDir)) {
            File::cleanDirectory($exportDir);
        }

        parent::tearDown();
    }

    public function test_can_export_and_import_complete_package_with_media_in_zip(): void
    {
        $mediaDir = storage_path('app/public/lms-media');
        File::ensureDirectoryExists($mediaDir);
        $dummyImage = $mediaDir . '/package-test-img.png';
        File::put($dummyImage, 'DUMMY_IMAGE_BINARY_CONTENT_FOR_TEST');

        $course = Course::create([
            'name' => 'Curso de Teste com Mídia',
            'description' => 'Descrição do curso',
            'workload' => 40,
            'active' => true,
        ]);

        $module = LmsModule::create([
            'course_id' => $course->id,
            'title' => 'Módulo 1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $lesson = LmsLesson::create([
            'module_id' => $module->id,
            'title' => 'Aula 1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        LmsLessonStep::create([
            'lesson_id' => $lesson->id,
            'phase' => 'praelectio',
            'step_type' => 'slide',
            'title' => 'Passo 1 com Imagem',
            'content' => [
                'media_type' => 'image',
                'media_url' => '/storage/lms-media/package-test-img.png',
                'body' => 'Conteúdo de teste',
                'min_stay_seconds' => 30,
            ],
            'sort_order' => 1,
        ]);

        $service = app(LmsContentBackupService::class);

        // 1. Exporta para ZIP
        $zipPath = $service->exportCourseToZip($course);

        $this->assertFileExists($zipPath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipPath));
        $this->assertNotFalse($zip->locateName('package.json'));
        $this->assertNotFalse($zip->locateName('media/package-test-img.png'));
        $zip->close();

        // 2. Apaga o arquivo original de mídia e os registros do banco
        File::delete($dummyImage);
        $this->assertFileDoesNotExist($dummyImage);

        LmsLessonStep::truncate();
        LmsLesson::truncate();
        LmsModule::truncate();
        Course::truncate();

        // 3. Importa o ZIP em um único lance
        $stats = $service->importFromZip($zipPath);

        $this->assertEquals(1, $stats['courses']);
        $this->assertEquals(1, $stats['modules']);
        $this->assertEquals(1, $stats['lessons']);
        $this->assertEquals(1, $stats['steps']);
        $this->assertGreaterThanOrEqual(1, $stats['media_files']);

        // 4. Verifica se o arquivo de mídia foi restaurado no disco público
        $this->assertFileExists($dummyImage);
        $this->assertEquals('DUMMY_IMAGE_BINARY_CONTENT_FOR_TEST', File::get($dummyImage));

        // 5. Verifica se o curso foi restaurado no banco
        $restoredCourse = Course::where('name', 'Curso de Teste com Mídia')->first();
        $this->assertNotNull($restoredCourse);
        $restoredStep = LmsLessonStep::first();
        $this->assertNotNull($restoredStep);
        $this->assertEquals('/storage/lms-media/package-test-img.png', $restoredStep->content['media_url']);
        $this->assertEquals(30, $restoredStep->content['min_stay_seconds']);
    }
}

