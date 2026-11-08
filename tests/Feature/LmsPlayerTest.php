<?php

use App\Livewire\Lms\LmsPlayer;
use App\Models\Contract;
use App\Models\Course;
use App\Models\LmsLesson;
use App\Models\LmsLessonStep;
use App\Models\LmsModule;
use App\Models\LmsStudentLessonProgress;
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

    $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->user->assignRole($superAdmin);

    $this->course = Course::create([
        'name' => 'Excel Avançado',
        'workload' => 40,
        'active' => true,
        'ouro_moderno_id' => null,
    ]);

    $this->module = LmsModule::create([
        'course_id' => $this->course->id,
        'title' => 'Módulo 1: Fórmulas de Alta Performance',
        'sort_order' => 1,
    ]);

    $this->lesson = LmsLesson::create([
        'module_id' => $this->module->id,
        'title' => 'Aula 01: Fórmulas PROCV e ÍNDICE/CORRESP',
        'description' => 'Domínio prático de busca em planilhas empresariais.',
        'estimated_duration_minutes' => 90,
        'passing_score' => 80.00,
        'sort_order' => 1,
        'is_active' => true,
    ]);

    // 5 Passos do Método Jesuítico
    $this->step1 = LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'praelectio',
        'step_type' => 'theory',
        'title' => '1. Praelectio: Fundamentos do PROCV',
        'content' => [
            'body' => 'O PROCV é utilizado para localizar valores em uma tabela de dados.',
            'media_type' => 'image',
            'media_url' => 'https://example.com/procv-diagram.png',
        ],
        'sort_order' => 1,
    ]);

    $this->step2 = LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'practice',
        'step_type' => 'hotspot_simulation',
        'title' => '2. Experientia: Seleção da Célula e Digitação',
        'content' => [
            'instructions' => 'Clique na ferramenta principal para aplicar o cálculo.',
            'explanation_on_error' => 'Atenção ao alvo indicado no simulador.',
        ],
        'sort_order' => 2,
    ]);

    $this->step3 = LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'repetitio',
        'step_type' => 'quiz',
        'title' => '3. Repetitio: Por que o quarto parâmetro é 0 (FALSO)?',
        'content' => [
            'question' => 'Qual a função do parâmetro FALSO ou 0 no PROCV?',
            'options' => [
                ['id' => 'A', 'text' => 'Garante a correspondência exata do valor procurado.', 'is_correct' => true],
                ['id' => 'B', 'text' => 'Faz uma busca aproximada e aleatória.', 'is_correct' => false],
            ],
            'explanation_on_error' => 'O valor FALSO (0) força o Excel a encontrar o valor idêntico.',
        ],
        'sort_order' => 3,
    ]);
});

it('redirects unauthenticated users trying to access lms player', function () {
    $this->get(route('lms.player', ['lesson' => $this->lesson->id]))
        ->assertRedirect(route('login'));
});

it('renders lms player in preview mode for educators with media and steps', function () {
    $this->actingAs($this->user)
        ->get(route('lms.player', ['lesson' => $this->lesson->id, 'preview' => 1]))
        ->assertSuccessful()
        ->assertSee('Excel Avançado')
        ->assertSee('Aula 01: Fórmulas PROCV')
        ->assertSee('Modo Educador')
        ->assertSee('1. Praelectio')
        ->assertSee('https://example.com/procv-diagram.png');
});

it('supports interactive quiz step with jesuit repetitio in player', function () {
    Livewire::actingAs($this->user)
        ->test(LmsPlayer::class, ['lesson' => $this->lesson])
        ->set('currentStepIndex', 2) // Step 3: Quiz
        ->set('selectedOptionId', 'B') // Resposta errada
        ->call('submitQuizAnswer')
        ->assertSet('lastAnswerCorrect', false)
        ->assertSee('Repetitio Inaciana • O erro é o início da compreensão')
        ->assertSee('O valor FALSO (0) força o Excel a encontrar o valor idêntico.')
        // Tenta novamente com a resposta certa
        ->call('resetAttemptForRepetitio')
        ->assertSet('lastAnswerCorrect', null)
        ->set('selectedOptionId', 'A') // Resposta certa
        ->call('submitQuizAnswer')
        ->assertSet('lastAnswerCorrect', true)
        ->assertSee('Excelente! Resposta correta demonstrando domínio do conceito.');
});

it('handles hotspot simulation step interaction correctly', function () {
    Livewire::actingAs($this->user)
        ->test(LmsPlayer::class, ['lesson' => $this->lesson])
        ->set('currentStepIndex', 1) // Step 2: Experientia
        ->call('triggerSimulationHotspot', false)
        ->assertSet('lastAnswerCorrect', false)
        ->assertSee('Atenção ao alvo indicado no simulador.')
        ->call('triggerSimulationHotspot', true)
        ->assertSet('lastAnswerCorrect', true)
        ->assertSet('simulationHotspotClicked', true)
        ->assertSee('Comando executado com precisão no simulador!');
});

it('tracks student linear progress and respects daily pacing limit in player', function () {
    $studentPerson = Person::create([
        'name' => 'Aluno Disciplinado',
        'email' => 'aluno@aurosoft.com',
        'birth_date' => '2005-01-01',
    ]);

    $responsiblePerson = Person::create([
        'name' => 'Pai do Aluno',
        'email' => 'pai@aurosoft.com',
    ]);

    $responsible = \App\Models\Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => \App\Enums\RelationshipEnum::Father,
        'financial_responsible' => true,
    ]);

    $studentUser = User::create([
        'person_id' => $studentPerson->id,
        'email' => 'aluno@aurosoft.com',
        'password' => bcrypt('secret'),
    ]);

    $contract = Contract::create([
        'contract_number' => 'MAT-2026-001',
        'student_id' => $studentPerson->id,
        'responsible_id' => $responsible->id,
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1200,
        'validity_months' => 6,
        'enrollment_fee' => 50,
        'monthly_payment' => 200,
        'installments_count' => 6,
        'payment_due_day' => 10,
    ]);

    // Inicia a aula como aluno
    $component = Livewire::actingAs($studentUser)
        ->test(LmsPlayer::class, ['lesson' => $this->lesson, 'contract_id' => $contract->id])
        ->assertSet('isPreview', false)
        ->assertSet('isLocked', false)
        ->assertSet('currentStepIndex', 0);

    // Passo 1: Teoria -> completa
    $component->call('completeTheoryStep');

    // Verifica que o progresso foi gravado
    $progress = LmsStudentLessonProgress::where('contract_id', $contract->id)->where('lesson_id', $this->lesson->id)->first();
    expect($progress)->not->toBeNull()
        ->and($progress->status)->toBe('in_progress');

    // Simula conclusão da lição hoje
    $progress->update([
        'status' => 'completed',
        'score' => 95.00,
        'completed_at' => now(),
    ]);

    // Cria aula 2 no mesmo módulo
    $lesson2 = LmsLesson::create([
        'module_id' => $this->module->id,
        'title' => 'Aula 02: Tabelas Dinâmicas',
        'sort_order' => 2,
    ]);

    // O aluno tenta abrir a Aula 02 no mesmo dia -> TRAVA DIÁRIA DEVE ACIONAR!
    Livewire::actingAs($studentUser)
        ->test(LmsPlayer::class, ['lesson' => $lesson2, 'contract_id' => $contract->id])
        ->assertSet('isLocked', true)
        ->assertSet('lockReason', 'daily_limit_reached')
        ->assertSee('Meta Diária Concluída! 🎓');
});

it('completes all steps and displays examen somativo mastery card', function () {
    $studentPerson = Person::create([
        'name' => 'Aluno Mestre',
        'email' => 'mestre@aurosoft.com',
        'birth_date' => '2004-02-02',
    ]);

    $responsiblePerson = Person::create([
        'name' => 'Mãe Mestre',
        'email' => 'maemestre@aurosoft.com',
    ]);

    $responsible = \App\Models\Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => \App\Enums\RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    $studentUser = User::create([
        'person_id' => $studentPerson->id,
        'email' => 'mestre@aurosoft.com',
        'password' => bcrypt('secret'),
    ]);

    $contract = Contract::create([
        'contract_number' => 'MAT-2026-002',
        'student_id' => $studentPerson->id,
        'responsible_id' => $responsible->id,
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1200,
        'validity_months' => 6,
        'enrollment_fee' => 50,
        'monthly_payment' => 200,
        'installments_count' => 6,
        'payment_due_day' => 10,
    ]);

    $component = Livewire::actingAs($studentUser)
        ->test(LmsPlayer::class, ['lesson' => $this->lesson, 'contract_id' => $contract->id]);

    // Passo 1: Teoria (Praelectio)
    $component->call('completeTheoryStep');

    // Passo 2: Experientia (Simulação Hotspot)
    $component->call('triggerSimulationHotspot', true);
    $component->call('nextStep');

    // Passo 3: Repetitio (Quiz)
    $component->set('selectedOptionId', 'A');
    $component->call('submitQuizAnswer');

    $component->assertSet('isLessonCompleted', true)
        ->assertSet('passed', true)
        ->assertSee('Aula Concluída com Maestria!')
        ->assertSee('Examen Somativo • Maestria Inaciana');

    $progress = LmsStudentLessonProgress::where('contract_id', $contract->id)->where('lesson_id', $this->lesson->id)->first();
    expect($progress->status)->toBe('completed')
        ->and($progress->score)->toBeGreaterThanOrEqual(80.00);
});

it('allows students to call educator for help and cancel the request in player', function () {
    $studentPerson = Person::create([
        'name' => 'Aluno com Dúvida',
        'email' => 'duvida@aurosoft.com',
    ]);

    $studentUser = User::create([
        'person_id' => $studentPerson->id,
        'email' => 'duvida@aurosoft.com',
        'password' => bcrypt('secret'),
    ]);

    $responsiblePerson = Person::create([
        'name' => 'Pai Dúvida',
        'email' => 'paiduvida@aurosoft.com',
    ]);

    $responsible = \App\Models\Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => \App\Enums\RelationshipEnum::Father,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'MAT-2026-HELP',
        'student_id' => $studentPerson->id,
        'responsible_id' => $responsible->id,
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1000,
        'validity_months' => 6,
        'enrollment_fee' => 0,
        'monthly_payment' => 200,
        'installments_count' => 5,
        'payment_due_day' => 10,
    ]);

    $component = Livewire::actingAs($studentUser)
        ->test(LmsPlayer::class, ['lesson' => $this->lesson, 'contract_id' => $contract->id])
        ->assertSet('helpRequested', false)
        ->call('requestHelp')
        ->assertSet('helpRequested', true);

    $progress = LmsStudentLessonProgress::where('contract_id', $contract->id)->where('lesson_id', $this->lesson->id)->first();
    expect($progress->help_requested)->toBeTrue()
        ->and($progress->help_requested_at)->not->toBeNull();

    // Cancelar chamado de ajuda
    $component->call('cancelHelpRequest')
        ->assertSet('helpRequested', false);

    expect($progress->fresh()->help_requested)->toBeFalse();
});

it('renders anti-skip secure player and presentation slides correctly', function () {
    $mediaLesson = LmsLesson::create([
        'module_id' => $this->module->id,
        'title' => 'Aula com Vídeo e Slides',
        'is_active' => true,
    ]);

    $videoStep = LmsLessonStep::create([
        'lesson_id' => $mediaLesson->id,
        'phase' => 'praelectio',
        'step_type' => 'theory',
        'title' => 'Vídeo Aula Explicativa',
        'content' => [
            'body' => 'Assista atentamente ao vídeo abaixo.',
            'media_type' => 'video',
            'media_url' => '/storage/lms-media/aula-excel.mp4',
        ],
        'sort_order' => 1,
    ]);

    $pdfStep = LmsLessonStep::create([
        'lesson_id' => $mediaLesson->id,
        'phase' => 'praelectio',
        'step_type' => 'theory',
        'title' => 'Apresentação Teórica em Slides',
        'content' => [
            'body' => 'Acompanhe os slides da aula.',
            'media_type' => 'pdf',
            'media_url' => '/storage/lms-media/slides-excel.pdf',
        ],
        'sort_order' => 2,
    ]);

    Livewire::actingAs($this->user)
        ->test(LmsPlayer::class, ['lesson' => $mediaLesson, 'preview' => 1])
        ->assertSet('currentStepIndex', 0)
        ->assertSee('Reprodução contínua (anti-avanço)')
        ->assertSee('-10s')
        ->call('nextStep')
        ->assertSet('currentStepIndex', 1)
        ->assertSee('Apresentação Teórica da Aula (Slides PDF)')
        ->assertSee('Abrir Apresentação em Nova Aba');
});

it('supports challenge concertatio step with local computer mini-player mode', function () {
    $challengeLesson = LmsLesson::create([
        'module_id' => $this->module->id,
        'title' => 'Aula de Desafio Prático',
        'is_active' => true,
    ]);

    $challengeStep = LmsLessonStep::create([
        'lesson_id' => $challengeLesson->id,
        'phase' => 'concertatio',
        'step_type' => 'challenge',
        'title' => 'Desafio: Crie o Relatório da Empresa no Excel Local',
        'content' => [
            'scenario' => 'Você é o assistente administrativo e precisa consolidar a planilha.',
            'instructions' => 'Abra o Excel neste computador, aplique a fórmula PROCV na coluna D e confira.',
        ],
        'sort_order' => 1,
    ]);

    Livewire::actingAs($this->user)
        ->test(LmsPlayer::class, ['lesson' => $challengeLesson, 'preview' => 1])
        ->assertSet('currentStepIndex', 0)
        ->assertSee('Cenário Real do Mercado de Trabalho')
        ->assertSee('Sua Missão Autônoma:')
        ->assertSee('Abrir no Modo Mini-Player')
        ->assertSee('Modo Mini-Player');
});

it('enforces section progression where section 2 requires section 1 completion', function () {
    $studentPerson = Person::create([
        'name' => 'Aluno em Formação',
        'email' => 'formacao@aurosoft.com',
    ]);

    $studentUser = User::create([
        'person_id' => $studentPerson->id,
        'email' => 'formacao@aurosoft.com',
        'password' => bcrypt('secret'),
    ]);

    $responsiblePerson = Person::create([
        'name' => 'Responsável Formação',
        'email' => 'respformacao@aurosoft.com',
    ]);

    $responsible = \App\Models\Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => \App\Enums\RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'MAT-2026-SECTIONS',
        'student_id' => $studentPerson->id,
        'responsible_id' => $responsible->id,
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1000,
        'validity_months' => 6,
        'enrollment_fee' => 0,
        'monthly_payment' => 200,
        'installments_count' => 5,
        'payment_due_day' => 10,
    ]);

    // Cria Seção 2 com Aula 2
    $section2 = LmsModule::create([
        'course_id' => $this->course->id,
        'title' => 'Seção 2: Fórmulas Financeiras',
        'sort_order' => 2,
        'is_active' => true,
    ]);

    $lessonSection2 = LmsLesson::create([
        'module_id' => $section2->id,
        'title' => 'Aula 01 da Seção 2',
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $progressionService = app(\App\Services\LmsProgressionService::class);

    // Como a Aula 1 da Seção 1 ainda não foi concluída, a Seção 2 e a Aula da Seção 2 estão bloqueadas
    $sectionCheck = $progressionService->canAccessSection($contract, $section2);
    expect($sectionCheck['can_access'])->toBeFalse()
        ->and($sectionCheck['reason'])->toBe('previous_section_incomplete');

    $lessonCheck = $progressionService->canAccessLesson($contract, $lessonSection2);
    expect($lessonCheck['can_access'])->toBeFalse()
        ->and($lessonCheck['reason'])->toBe('previous_section_incomplete');

    // Conclui a aula da Seção 1
    LmsStudentLessonProgress::create([
        'contract_id' => $contract->id,
        'student_id' => $studentPerson->id,
        'lesson_id' => $this->lesson->id,
        'status' => 'completed',
        'score' => 90.0,
        'completed_at' => now()->subDay(), // Concluído ontem
    ]);

    // Agora a Seção 2 e a aula da Seção 2 devem estar liberadas!
    expect($progressionService->isSectionCompleted($contract, $this->module))->toBeTrue();
    expect($progressionService->canAccessSection($contract, $section2)['can_access'])->toBeTrue();
    expect($progressionService->canAccessLesson($contract, $lessonSection2)['can_access'])->toBeTrue();
});

test('aluno ao sair da aula registra timestamp de saída e log de acesso', function () {
    $responsiblePerson = Person::create(['name' => 'Responsável Carlos', 'email' => 'resp.carlos@teste.com']);
    $responsible = \App\Models\Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => \App\Enums\RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);
    $studentPerson = Person::create(['name' => 'Carlos Estudante', 'email' => 'carlos@teste.com']);
    $studentUser = User::create(['person_id' => $studentPerson->id, 'email' => 'carlos@teste.com', 'password' => bcrypt('123')]);
    $contract = Contract::create([
        'student_id' => $studentPerson->id,
        'responsible_id' => $responsible->id,
        'contract_number' => 'CTR-2026-LOG',
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1200,
        'validity_months' => 6,
        'enrollment_fee' => 50,
        'monthly_payment' => 200,
        'installments_count' => 6,
        'payment_due_day' => 10,
    ]);

    $component = Livewire::actingAs($studentUser)
        ->test(LmsPlayer::class, ['lesson' => $this->lesson, 'contract_id' => $contract->id]);

    $progress = LmsStudentLessonProgress::where('contract_id', $contract->id)->where('lesson_id', $this->lesson->id)->first();
    expect($progress)->not->toBeNull();
    expect($progress->exited_at)->toBeNull();

    // Aluno aciona o botão de sair da aula
    $component->call('exitLesson')
        ->assertRedirect(route('student.portal'));

    $progress->refresh();
    expect($progress->exited_at)->not->toBeNull();

    // Verifica que o log de acesso foi gerado
    $log = \App\Models\StudentAccessLog::where('contract_id', $contract->id)->where('action', 'lesson_exit')->first();
    expect($log)->not->toBeNull()
        ->and($log->description)->toContain('Aluno saiu da aula');
});

test('fechamento de aba envia beacon de saída e atualiza progresso', function () {
    $responsiblePerson = Person::create(['name' => 'Responsável Ana', 'email' => 'resp.ana@teste.com']);
    $responsible = \App\Models\Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => \App\Enums\RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);
    $studentPerson = Person::create(['name' => 'Ana Aluna', 'email' => 'ana@teste.com']);
    $studentUser = User::create(['person_id' => $studentPerson->id, 'email' => 'ana@teste.com', 'password' => bcrypt('123')]);
    $contract = Contract::create([
        'student_id' => $studentPerson->id,
        'responsible_id' => $responsible->id,
        'contract_number' => 'CTR-2026-BEACON',
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1200,
        'validity_months' => 6,
        'enrollment_fee' => 50,
        'monthly_payment' => 200,
        'installments_count' => 6,
        'payment_due_day' => 10,
    ]);

    $progress = LmsStudentLessonProgress::create([
        'contract_id' => $contract->id,
        'student_id' => $studentPerson->id,
        'lesson_id' => $this->lesson->id,
        'status' => 'in_progress',
        'started_at' => now(),
        'last_heartbeat_at' => now(),
    ]);

    $response = $this->actingAs($studentUser)->post("/lms/player/beacon-exit/{$progress->id}");
    $response->assertNoContent();

    $progress->refresh();
    expect($progress->exited_at)->not->toBeNull();
});

test('exclusão de passo remove arquivo físico da pasta lms-media', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    $fakeFile = 'lms-media/video_aula_teste.mp4';
    \Illuminate\Support\Facades\Storage::disk('public')->put($fakeFile, 'fake video content');

    expect(\Illuminate\Support\Facades\Storage::disk('public')->exists($fakeFile))->toBeTrue();

    $stepWithMedia = LmsLessonStep::create([
        'lesson_id' => $this->lesson->id,
        'phase' => 'praelectio',
        'step_type' => 'theory',
        'title' => 'Vídeo Aula com Mídia Local',
        'content' => [
            'body' => 'Conteúdo explicativo',
            'media_type' => 'video',
            'media_url' => "/storage/{$fakeFile}",
        ],
        'sort_order' => 99,
    ]);

    // Ao deletar o passo, o arquivo físico deve ser excluído do disco
    $stepWithMedia->delete();

    expect(\Illuminate\Support\Facades\Storage::disk('public')->exists($fakeFile))->toBeFalse();
});


