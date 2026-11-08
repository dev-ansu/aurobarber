<?php

use App\Enums\RelationshipEnum;
use App\Filament\Pages\ClassroomLiveMonitor;
use App\Filament\Studio\Pages\LmsStudio;
use App\Livewire\Lms\LmsPlayer;
use App\Models\Contract;
use App\Models\Course;
use App\Models\LmsLesson;
use App\Models\LmsLessonStep;
use App\Models\LmsModule;
use App\Models\LmsStudentAssignment;
use App\Models\LmsStudentLessonProgress;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

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
    $this->user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('View:LmsLesson', 'web'));
    $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->user->assignRole($superAdmin);

    $this->course = Course::create([
        'name' => 'Excel Profissional',
        'workload' => 40,
        'active' => true,
        'ouro_moderno_id' => null,
    ]);

    $this->module = LmsModule::create([
        'course_id' => $this->course->id,
        'title' => 'Módulo 1: Essencial',
        'sort_order' => 1,
    ]);

    $this->lesson = LmsLesson::create([
        'module_id' => $this->module->id,
        'title' => 'Aula 01: Tabelas e Gráficos',
        'description' => 'Domínio prático de tabelas.',
        'estimated_duration_minutes' => 90,
        'passing_score' => 70.00,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $this->studentPerson = Person::create([
        'name' => 'Aluno Exemplo',
        'email' => 'aluno@aurosoft.com',
        'phone' => '11999998888',
        'birth_date' => '2005-01-01',
    ]);

    $this->studentUser = User::create([
        'person_id' => $this->studentPerson->id,
        'email' => 'aluno@aurosoft.com',
        'password' => bcrypt('secret'),
    ]);

    $responsiblePerson = Person::create([
        'name' => 'Pai do Aluno',
        'email' => 'pai@aurosoft.com',
    ]);

    $this->responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Father,
        'financial_responsible' => true,
    ]);

    $this->contract = Contract::create([
        'contract_number' => 'CTR-9001',
        'student_id' => $this->studentPerson->id,
        'responsible_id' => $this->responsible->id,
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1200,
        'validity_months' => 6,
        'enrollment_fee' => 50,
        'monthly_payment' => 200,
        'installments_count' => 6,
        'payment_due_day' => 10,
    ]);
});

it('uploads large video files in base64 chunks in LmsStudio bypassing PHP upload_max_filesize', function () {
    $videoContent = str_repeat('FAKE_MP4_BINARY_STREAM_DATA_', 500);
    $halfLength = (int) floor(strlen($videoContent) / 2);
    $chunk0 = base64_encode(substr($videoContent, 0, $halfLength));
    $chunk1 = base64_encode(substr($videoContent, $halfLength));

    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('studio'));
    $component = Livewire::actingAs($this->user)
        ->test(LmsStudio::class, ['course_id' => $this->course->id])
        ->set('selectedLessonId', $this->lesson->id)
        ->set('stepPhase', 'praelectio')
        ->set('stepType', 'theory')
        ->set('stepTitle', 'Vídeo Aula de Introdução');

    $component->call('uploadMediaChunk', 'test_upload_123', 'aula-intro.mp4', 0, 2, $chunk0);
    $component->call('uploadMediaChunk', 'test_upload_123', 'aula-intro.mp4', 1, 2, $chunk1)
        ->assertSet('stepMediaType', 'video')
        ->assertSet('uploadedFileName', 'aula-intro.mp4');

    $mediaUrl = $component->get('stepMediaUrl');
    expect($mediaUrl)->toContain('/storage/lms-media/')
        ->and($mediaUrl)->toEndWith('.mp4');

    $component->call('saveStep')
        ->assertNotified('Passo interativo salvo com sucesso!');

    $step = LmsLessonStep::where('title', 'Vídeo Aula de Introdução')->first();
    expect($step)->not->toBeNull()
        ->and($step->content['media_type'])->toBe('video')
        ->and($step->content['media_url'])->toBe($mediaUrl);
});

it('tracks student focus loss and focus gain and surfaces out-of-focus alert in ClassroomLiveMonitor', function () {
    LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'praelectio',
        'step_type' => 'theory',
        'title' => 'Teoria Inicial',
        'content' => ['body' => 'Conteúdo'],
        'sort_order' => 1,
    ]);

    $player = Livewire::actingAs($this->studentUser)
        ->withQueryParams(['contract_id' => $this->contract->id])
        ->test(LmsPlayer::class, ['lesson' => $this->lesson]);

    $player->call('reportFocusLoss');

    $progress = LmsStudentLessonProgress::where('contract_id', $this->contract->id)
        ->where('lesson_id', $this->lesson->id)
        ->first();

    expect($progress)->not->toBeNull()
        ->and($progress->is_in_focus)->toBeFalse()
        ->and($progress->focus_lost_at)->not->toBeNull();

    Livewire::actingAs($this->user)
        ->test(ClassroomLiveMonitor::class)
        ->assertSee('Fora da Aula');

    $player->call('reportFocusGain');
    $progress->refresh();

    expect($progress->is_in_focus)->toBeTrue();
});

it('progresses through multi-step guided hotspot simulation sub_steps', function () {
    LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'practice',
        'step_type' => 'hotspot_simulation',
        'title' => 'Simulador Guiado do Excel',
        'content' => [
            'instruction' => 'Siga os cliques guiados.',
            'sub_steps' => [
                [
                    'instruction' => 'Passo 1: Clique no menu Inserir',
                    'explanation_on_error' => 'Clique no topo esquerdo em Inserir.',
                    'hotspot' => [
                        'x' => 20,
                        'y' => 15,
                        'radius' => 10,
                        'label' => 'Menu Inserir',
                        'interaction_type' => 'click',
                    ],
                ],
                [
                    'instruction' => 'Passo 2: Clique em Tabela Dinâmica',
                    'explanation_on_error' => 'Clique no ícone Tabela Dinâmica.',
                    'hotspot' => [
                        'x' => 45,
                        'y' => 30,
                        'radius' => 10,
                        'label' => 'Tabela Dinâmica',
                        'interaction_type' => 'click',
                    ],
                ],
            ],
        ],
        'sort_order' => 1,
    ]);

    $player = Livewire::actingAs($this->studentUser)
        ->withQueryParams(['contract_id' => $this->contract->id])
        ->test(LmsPlayer::class, ['lesson' => $this->lesson]);

    $player->assertSee('Passo 1: Clique no menu Inserir')
        ->call('clickHotspotCoordinates', 20, 15)
        ->assertSet('currentSubStepIndex', 1)
        ->assertSee('Passo 2: Clique em Tabela Dinâmica')
        ->call('clickHotspotCoordinates', 45, 30)
        ->assertSet('isLessonCompleted', true);

    $progress = LmsStudentLessonProgress::where('contract_id', $this->contract->id)
        ->where('lesson_id', $this->lesson->id)
        ->first();

    expect($progress->status)->toBe('completed');
});

it('draws random questions from question_pool and celebrates 100% completion', function () {
    LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'assessment',
        'step_type' => 'quiz',
        'title' => 'Avaliação Sorteada',
        'content' => [
            'randomize_pool' => true,
            'questions_to_draw' => 2,
            'shuffle_options' => false,
            'question_pool' => [
                [
                    'id' => 'q1',
                    'question' => 'Qual função soma valores?',
                    'options' => [
                        ['id' => 'A', 'text' => 'SOMA', 'is_correct' => true],
                        ['id' => 'B', 'text' => 'MÉDIA', 'is_correct' => false],
                    ],
                ],
                [
                    'id' => 'q2',
                    'question' => 'Qual símbolo inicia fórmulas?',
                    'options' => [
                        ['id' => 'A', 'text' => '=', 'is_correct' => true],
                        ['id' => 'B', 'text' => '@', 'is_correct' => false],
                    ],
                ],
                [
                    'id' => 'q3',
                    'question' => 'Qual atalho salva a planilha?',
                    'options' => [
                        ['id' => 'A', 'text' => 'Ctrl+B', 'is_correct' => true],
                        ['id' => 'B', 'text' => 'Alt+F4', 'is_correct' => false],
                    ],
                ],
            ],
        ],
        'sort_order' => 1,
    ]);

    $player = Livewire::actingAs($this->studentUser)
        ->withQueryParams(['contract_id' => $this->contract->id])
        ->test(LmsPlayer::class, ['lesson' => $this->lesson]);

    $drawn = $player->get('activeQuizQuestions');
    expect($drawn)->toHaveCount(2);

    $player->set('selectedOptionId', 'A')
        ->call('submitQuizAnswer')
        ->assertSet('activeQuizQuestionIndex', 1)
        ->set('selectedOptionId', 'A')
        ->call('submitQuizAnswer')
        ->assertSet('isLessonCompleted', true)
        ->assertSet('showCelebrationModal', true)
        ->assertSee('Parabéns');
});

it('allows students to upload practical assignments and educators to grade them in ClassroomLiveMonitor', function () {
    $step = LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'challenge',
        'step_type' => 'assignment',
        'title' => 'Entrega da Planilha Financeira',
        'content' => [
            'instructions' => 'Envie o arquivo .xlsx finalizado.',
            'allowed_extensions' => 'xlsx,pdf,png',
            'max_size_mb' => 20,
            'require_educator_approval' => true,
        ],
        'sort_order' => 1,
    ]);

    $file = UploadedFile::fake()->create('planilha-final.xlsx', 120);

    Livewire::actingAs($this->studentUser)
        ->withQueryParams(['contract_id' => $this->contract->id])
        ->test(LmsPlayer::class, ['lesson' => $this->lesson])
        ->set('assignmentFile', $file)
        ->set('assignmentNotes', 'Planilha concluída com gráficos.')
        ->call('submitAssignment');

    $assignment = LmsStudentAssignment::where('contract_id', $this->contract->id)
        ->where('step_id', $step->id)
        ->first();

    expect($assignment)->not->toBeNull()
        ->and($assignment->status)->toBe('pending')
        ->and($assignment->file_name)->toBe('planilha-final.xlsx');

    Livewire::actingAs($this->user)
        ->test(ClassroomLiveMonitor::class)
        ->call('openAssignmentsModal', $assignment->id)
        ->set('gradingFeedback', 'Excelente trabalho nos gráficos dinâmicos!')
        ->call('gradeAssignment', $assignment->id, 'approved', 95.0);

    $assignment->refresh();
    expect($assignment->status)->toBe('approved')
        ->and((float) $assignment->grade)->toBe(95.0)
        ->and($assignment->feedback)->toBe('Excelente trabalho nos gráficos dinâmicos!');
});

it('manages the dedicated LmsQuestionBank page and saves anti-cheating question pools', function () {
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('studio'));
    $component = Livewire::actingAs($this->user)
        ->test(\App\Filament\Studio\Pages\LmsQuestionBank::class)
        ->set('selectedCourseId', $this->course->id)
        ->set('selectedLessonId', $this->lesson->id)
        ->call('generateStarterQuestionPack');

    expect($component->get('questions'))->toHaveCount(5);

    $component->call('openCreateQuestionModal')
        ->set('formQuestion', 'Qual tecla recalcula todas as fórmulas no Excel?')
        ->set('formOptionA', 'F9')
        ->set('formOptionB', 'F2')
        ->set('formOptionC', 'Esc')
        ->set('formCorrectOption', 'A')
        ->set('formExplanation', 'A tecla F9 recalcula todas as planilhas abertas.')
        ->call('saveQuestionFromModal');

    expect($component->get('questions'))->toHaveCount(6);

    $step = LmsLessonStep::where('lesson_id', $this->lesson->id)
        ->where('step_type', 'quiz')
        ->first();

    expect($step)->not->toBeNull()
        ->and($step->content['question_pool'])->toHaveCount(6)
        ->and($step->content['randomize'])->toBeTrue();
});

it('sends educator direct messages from ClassroomLiveMonitor to LmsPlayer and records student 1-5 star ratings', function () {
    LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'praelectio',
        'step_type' => 'theory',
        'title' => 'Introdução',
        'content' => ['body' => 'Conteúdo inicial.'],
        'sort_order' => 1,
    ]);

    $player = Livewire::actingAs($this->studentUser)
        ->withQueryParams(['contract_id' => $this->contract->id])
        ->test(LmsPlayer::class, ['lesson' => $this->lesson]);

    $progress = LmsStudentLessonProgress::where('contract_id', $this->contract->id)
        ->where('lesson_id', $this->lesson->id)
        ->first();

    Livewire::actingAs($this->user)
        ->test(ClassroomLiveMonitor::class)
        ->call('openDirectMessageModal', $progress->id, 'Aluno Teste')
        ->set('directMessageText', 'Volte para a planilha do exercício 2!')
        ->call('sendDirectMessageToStudent');

    $player->call('incrementTimeSpent', 15)
        ->assertSet('activeEducatorMessage', 'Volte para a planilha do exercício 2!')
        ->call('acknowledgeEducatorMessage')
        ->assertSet('activeEducatorMessage', '');

    $progress->refresh();
    expect($progress->educator_message_read_at)->not->toBeNull();

    $player->call('setLessonRating', 5)
        ->set('lessonRatingComment', 'Aula excelente e muito clara!')
        ->call('submitLessonRating')
        ->assertSet('lessonRatingSubmitted', true);

    $progress->refresh();
    expect($progress->student_rating)->toBe(5)
        ->and($progress->student_rating_comment)->toBe('Aula excelente e muito clara!');
});

it('displays feedback and advances exercises in StudentTypingWarmup even in Educator Mode without contract', function () {
    $ex1 = \App\Models\TypingExercise::create([
        'title' => 'Lição 01: A S D F',
        'level' => 'beginner',
        'difficulty_order' => 1,
        'target_text' => 'asdf asdf',
        'target_wpm' => 15,
        'min_accuracy' => 90,
        'is_active' => true,
    ]);

    $ex2 = \App\Models\TypingExercise::create([
        'title' => 'Lição 02: J K L Ç',
        'level' => 'beginner',
        'difficulty_order' => 2,
        'target_text' => 'jklç jklç',
        'target_wpm' => 15,
        'min_accuracy' => 90,
        'is_active' => true,
    ]);

    $warmup = Livewire::actingAs($this->user)
        ->test(\App\Livewire\Student\StudentTypingWarmup::class);

    $warmup->set('selectedExerciseId', $ex1->id)
        ->call('submitTypingResult', 32.0, 160.0, 98.5, 50, 49, 1, 25)
        ->assertSet('isFinished', true)
        ->assertSet('passed', true)
        ->assertSet('stars', 3)
        ->call('nextExercise')
        ->assertSet('selectedExerciseId', $ex2->id)
        ->assertSet('isFinished', false);
});

it('executes Filament Global Search across all resources without SQL column errors', function () {
    foreach (\Filament\Facades\Filament::getPanel('admin')->getResources() as $resource) {
        $results = $resource::getGlobalSearchResults('teste');
        expect($results)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    }
});

it('allows creating a ContractHistory from the Contract Infolist (ViewContracts) and unlocks StudentPortal on scheduled makeup class', function () {
    $rm = new \App\Filament\Resources\Contracts\RelationManagers\ContractHistoriesRelationManager;
    expect($rm->isReadOnly())->toBeFalse();

    $formation = \App\Models\Formation::create(['name' => 'Formação Teste', 'workload' => 40, 'active' => true]);
    $item = \App\Models\ContractItem::create([
        'contract_id' => $this->contract->id,
        'formation_id' => $formation->id,
        'value' => 1200,
    ]);
    $otherWeekday = (now()->dayOfWeek + 1) % 7;
    \App\Models\StudentSchedule::create([
        'contract_item_id' => $item->id,
        'classroom_id' => \App\Models\Classroom::first()?->id ?? \App\Models\Classroom::create(['name' => 'Lab 1', 'capacity' => 15])->id,
        'weekday' => $otherWeekday,
        'start_hour' => '08:00:00',
        'end_hour' => '09:00:00',
    ]);

    $makeup = \App\Models\StudentMakeupClass::create([
        'contract_id' => $this->contract->id,
        'makeup_date' => today()->toDateString(),
        'start_hour' => now()->subMinutes(15)->format('H:i:s'),
        'end_hour' => now()->addMinutes(45)->format('H:i:s'),
        'status' => 'scheduled',
        'notes' => 'Reposição de aula prática',
    ]);

    $window = app(\App\Services\LmsProgressionService::class)->checkStudentScheduleWindow($this->contract->fresh());
    expect($window['is_allowed'])->toBeTrue()
        ->and($window['reason'])->toBe('in_makeup_session');

    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('studio'));
    $studio = Livewire::actingAs($this->user)
        ->test(\App\Filament\Studio\Pages\LmsStudio::class)
        ->set('selectedCourseId', $this->course->id)
        ->set('selectedModuleId', $this->module->id)
        ->set('selectedLessonId', $this->lesson->id)
        ->call('duplicateLesson', $this->lesson->id);

    expect(\App\Models\LmsLesson::where('module_id', $this->module->id)->count())->toBe(2);

    $widget = new \App\Filament\Widgets\DropoutRiskRadarWidget;
    expect($widget->getAtRiskStudents())->toBeInstanceOf(\Illuminate\Support\Collection::class);
});




