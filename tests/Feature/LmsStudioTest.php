<?php

use App\Filament\Studio\Pages\LmsStudio;
use App\Models\Course;
use App\Models\LmsLesson;
use App\Models\LmsLessonStep;
use App\Models\LmsModule;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->person = Person::create([
        'name' => 'Professor Coordenador',
        'email' => 'coordenador@aurosoft.com',
    ]);

    $this->user = User::create([
        'person_id' => $this->person->id,
        'email' => 'coordenador@aurosoft.com',
        'password' => bcrypt('secret'),
    ]);

    $this->user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('View:LmsStudio', 'web'));
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('studio'));

    $this->course = Course::create([
        'name' => 'Excel para Negócios',
        'workload' => 40,
        'active' => true,
        'ouro_moderno_id' => null,
    ]);
});

it('allows authorized educators to access the lms studio', function () {
    $this->actingAs($this->user)
        ->get(LmsStudio::getUrl(panel: 'studio'))
        ->assertSuccessful()
        ->assertSee('Estúdio de Criação de Aulas Interativas')
        ->assertSee('Excel para Negócios');
});

it('allows creating modules, lessons, and jesuit steps interactively', function () {
    Livewire::actingAs($this->user)
        ->test(LmsStudio::class, ['course_id' => $this->course->id])
        ->set('selectedCourseId', $this->course->id)
        ->set('moduleTitle', 'Módulo 1: Primeiros Passos')
        ->call('saveModule')
        ->assertNotified('Seção criada com sucesso!');

    $module = LmsModule::where('title', 'Módulo 1: Primeiros Passos')->first();
    expect($module)->not->toBeNull()
        ->and($module->course_id)->toBe($this->course->id);

    // Cria aula
    Livewire::actingAs($this->user)
        ->test(LmsStudio::class, ['course_id' => $this->course->id])
        ->set('selectedModuleId', $module->id)
        ->set('lessonTitle', 'Aula 01: Fórmulas Básicas')
        ->set('lessonDuration', 90)
        ->set('lessonPassingScore', 80)
        ->call('saveLesson')
        ->assertNotified('Aula criada com sucesso!');

    $lesson = LmsLesson::where('title', 'Aula 01: Fórmulas Básicas')->first();
    expect($lesson)->not->toBeNull()
        ->and($lesson->estimated_duration_minutes)->toBe(90)
        ->and($lesson->passing_score)->toBe('80.00');

    // Cria passo jesuítico
    Livewire::actingAs($this->user)
        ->test(LmsStudio::class, ['course_id' => $this->course->id])
        ->set('selectedLessonId', $lesson->id)
        ->set('stepPhase', 'praelectio')
        ->set('stepType', 'theory')
        ->set('stepTitle', 'Conceito de Células')
        ->set('stepBody', 'Uma célula é a interseção entre uma coluna e uma linha.')
        ->call('saveStep')
        ->assertNotified('Passo interativo salvo com sucesso!');

    $step = LmsLessonStep::where('title', 'Conceito de Células')->first();
    expect($step)->not->toBeNull()
        ->and($step->phase)->toBe('praelectio')
        ->and($step->step_type)->toBe('theory');
});

it('generates a complete 90-minute 5-phase jesuit lesson with the ai assistant', function () {
    $module = LmsModule::create([
        'course_id' => $this->course->id,
        'title' => 'Módulo Avançado',
    ]);

    Livewire::actingAs($this->user)
        ->test(LmsStudio::class, ['course_id' => $this->course->id])
        ->set('selectedModuleId', $module->id)
        ->set('aiTopic', 'Tabelas Dinâmicas no Varejo')
        ->call('generateWithAi')
        ->assertNotified('✨ Aula de 90 minutos gerada com sucesso!');

    $lesson = LmsLesson::where('title', 'Tabelas Dinâmicas no Varejo')->first();
    expect($lesson)->not->toBeNull()
        ->and($lesson->estimated_duration_minutes)->toBe(90)
        ->and($lesson->passing_score)->toBe('80.00');

    // Deve ter gerado os 5 blocos do Método Jesuítico:
    // 1. Praelectio, 2. Practice, 3. Repetitio, 4. Concertatio, 5. Examen
    $steps = $lesson->steps()->orderBy('sort_order')->get();
    expect($steps)->toHaveCount(5)
        ->and($steps[0]->phase)->toBe('praelectio')
        ->and($steps[1]->phase)->toBe('practice')
        ->and($steps[2]->phase)->toBe('repetitio')
        ->and($steps[3]->phase)->toBe('concertatio')
        ->and($steps[4]->phase)->toBe('examen');
});

it('supports uploading media files directly from computer and editing existing steps', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    $module = LmsModule::create([
        'course_id' => $this->course->id,
        'title' => 'Módulo Multimídia',
    ]);

    $lesson = LmsLesson::create([
        'module_id' => $module->id,
        'title' => 'Aula com Upload de Mídia',
    ]);

    $uploadedFile = \Illuminate\Http\UploadedFile::fake()->image('dashboard-screenshot.png');

    Livewire::actingAs($this->user)
        ->test(LmsStudio::class, ['course_id' => $this->course->id])
        ->set('selectedLessonId', $lesson->id)
        ->set('stepPhase', 'praelectio')
        ->set('stepType', 'theory')
        ->set('stepTitle', 'Visão Geral com Imagem do Computador')
        ->set('stepBody', 'Observe o layout da imagem anexada.')
        ->set('mediaSourceType', 'upload')
        ->set('mediaUpload', $uploadedFile)
        ->call('saveStep')
        ->assertNotified('Passo interativo salvo com sucesso!');

    $step = LmsLessonStep::where('title', 'Visão Geral com Imagem do Computador')->first();
    expect($step)->not->toBeNull()
        ->and($step->content['media_type'])->toBe('image')
        ->and($step->content['media_url'])->toContain('/storage/lms-media/');

    // Agora testa edição do passo
    Livewire::actingAs($this->user)
        ->test(LmsStudio::class, ['course_id' => $this->course->id])
        ->call('openEditStepModal', $step->id)
        ->assertSet('editingStepId', $step->id)
        ->assertSet('stepTitle', 'Visão Geral com Imagem do Computador')
        ->set('stepTitle', 'Visão Geral Atualizada pelo Professor')
        ->call('saveStep')
        ->assertNotified('Passo interativo salvo com sucesso!');

    expect($step->fresh()->title)->toBe('Visão Geral Atualizada pelo Professor');
});

it('allows editing sections, lessons, and adding lessons with section selection', function () {
    $module1 = LmsModule::create([
        'course_id' => $this->course->id,
        'title' => 'Seção Original',
        'sort_order' => 1,
    ]);

    $module2 = LmsModule::create([
        'course_id' => $this->course->id,
        'title' => 'Segunda Seção',
        'sort_order' => 2,
    ]);

    // Edita a primeira seção
    Livewire::actingAs($this->user)
        ->test(LmsStudio::class, ['course_id' => $this->course->id])
        ->call('openEditModuleModal', $module1->id)
        ->assertSet('editingModuleId', $module1->id)
        ->set('moduleTitle', 'Seção 1: Fundamentos Renomeados')
        ->call('saveModule')
        ->assertNotified('Seção atualizada com sucesso!');

    expect($module1->fresh()->title)->toBe('Seção 1: Fundamentos Renomeados');

    // Cria aula especificando a segunda seção
    Livewire::actingAs($this->user)
        ->test(LmsStudio::class, ['course_id' => $this->course->id])
        ->call('openCreateLessonModal', $module2->id)
        ->assertSet('selectedModuleId', $module2->id)
        ->set('lessonTitle', 'Aula na Segunda Seção')
        ->set('lessonDuration', 90)
        ->call('saveLesson')
        ->assertNotified('Aula criada com sucesso!');

    $lesson = LmsLesson::where('title', 'Aula na Segunda Seção')->first();
    expect($lesson)->not->toBeNull()
        ->and($lesson->module_id)->toBe($module2->id);

    // Edita a aula alterando seu título
    Livewire::actingAs($this->user)
        ->test(LmsStudio::class, ['course_id' => $this->course->id])
        ->call('openEditLessonModal', $lesson->id)
        ->assertSet('editingLessonId', $lesson->id)
        ->set('lessonTitle', 'Aula 02: Título Atualizado')
        ->call('saveLesson')
        ->assertNotified('Aula atualizada com sucesso!');

    expect($lesson->fresh()->title)->toBe('Aula 02: Título Atualizado');
});

it('validates upload media format and rejects files with unsupported extensions', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    $module = LmsModule::create([
        'course_id' => $this->course->id,
        'title' => 'Seção Validação',
    ]);

    $lesson = LmsLesson::create([
        'module_id' => $module->id,
        'title' => 'Aula com Validação',
    ]);

    // Tenta subir arquivo executável ou inválido para vídeo
    $invalidFile = \Illuminate\Http\UploadedFile::fake()->create('malicious.exe', 1000);

    Livewire::actingAs($this->user)
        ->test(LmsStudio::class, ['course_id' => $this->course->id])
        ->set('selectedLessonId', $lesson->id)
        ->set('stepPhase', 'praelectio')
        ->set('stepType', 'theory')
        ->set('stepTitle', 'Teste de Validação de Mídia')
        ->set('stepMediaType', 'video')
        ->set('mediaSourceType', 'upload')
        ->set('mediaUpload', $invalidFile)
        ->call('saveStep')
        ->assertHasErrors(['mediaUpload']);
});

it('supports paginated and filtered student ratings in lms studio', function () {
    $module = LmsModule::create([
        'course_id' => $this->course->id,
        'title' => 'Módulo com Avaliações',
    ]);

    $lesson = LmsLesson::create([
        'module_id' => $module->id,
        'title' => 'Aula Avaliada',
    ]);

    $studentPerson = Person::create([
        'name' => 'Aluno Teste',
        'email' => 'aluno@teste.com',
    ]);

    $resp = \App\Models\Responsible::create([
        'person_id' => $studentPerson->id,
        'relationship' => \App\Enums\RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = \App\Models\Contract::create([
        'contract_number' => 'CTR-TEST-001',
        'student_id' => $studentPerson->id,
        'responsible_id' => $resp->id,
        'start_date' => today(),
        'total_value' => 1000,
        'status' => 'active',
    ]);

    // Create 15 feedbacks across lessons (ratings from 1 to 5)
    for ($i = 1; $i <= 15; $i++) {
        $l = LmsLesson::create([
            'module_id' => $module->id,
            'title' => "Aula Avaliada $i",
        ]);

        \App\Models\LmsStudentLessonProgress::create([
            'contract_id' => $contract->id,
            'student_id' => $studentPerson->id,
            'lesson_id' => $l->id,
            'status' => 'completed',
            'student_rating' => ($i % 2 === 0) ? 5 : 2,
            'student_rating_comment' => "Comentário avaliação $i",
            'student_rated_at' => now()->subMinutes($i),
        ]);
    }

    $component = Livewire::actingAs($this->user)
        ->test(LmsStudio::class, ['course_id' => $this->course->id])
        ->set('selectedCourseId', $this->course->id)
        ->call('openRatingsModal')
        ->assertSet('showRatingsModal', true);

    $stats = $component->get('ratingsStats');
    expect($stats['total'])->toBe(15)
        ->and($stats['alerts'])->toBe(8)
        ->and($stats['positive'])->toBe(7);

    // Test pagination (10 per page)
    $feedbacks = $component->get('courseFeedbacks');
    expect($feedbacks->count())->toBe(10)
        ->and($feedbacks->total())->toBe(15);

    // Test filter alerts (<= 3 stars)
    $component->call('setRatingsFilter', 'alerts');
    $alertFeedbacks = $component->get('courseFeedbacks');
    expect($alertFeedbacks->total())->toBe(8);

    // Test search filter
    $component->set('ratingsFilter', 'all')
        ->set('ratingsSearch', 'avaliação 12');
    $searchedFeedbacks = $component->get('courseFeedbacks');
    expect($searchedFeedbacks->total())->toBe(1);
});



