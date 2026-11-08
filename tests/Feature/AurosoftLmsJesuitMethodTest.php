<?php

use App\Enums\RelationshipEnum;
use App\Models\Contract;
use App\Models\Course;
use App\Models\LmsLesson;
use App\Models\LmsLessonStep;
use App\Models\LmsModule;
use App\Models\LmsStudentLessonProgress;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use App\Services\LmsProgressionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->person = Person::create([
        'name' => 'Aluno Interativo',
        'email' => 'aluno.lms@aurosoft.com',
        'birth_date' => '2005-05-10',
    ]);

    $responsiblePerson = Person::create([
        'name' => 'Mãe do Aluno',
        'email' => 'mae.lms@aurosoft.com',
    ]);

    $this->responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    $this->user = User::create([
        'person_id' => $this->person->id,
        'email' => 'aluno.lms@aurosoft.com',
        'password' => bcrypt('secret'),
    ]);

    $this->contract = Contract::create([
        'contract_number' => 'AURO-LMS-001',
        'student_id' => $this->person->id,
        'responsible_id' => $this->responsible->id,
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1800,
        'validity_months' => 12,
        'enrollment_fee' => 100,
        'monthly_payment' => 150,
        'installments_count' => 12,
        'payment_due_day' => 10,
    ]);

    $this->service = app(LmsProgressionService::class);
});

it('distinguishes between Ouro Moderno courses and native Aurosoft LMS courses without collision', function () {
    $ouroCourse = Course::create([
        'name' => 'Operador de Computador OM',
        'workload' => 60,
        'active' => true,
        'ouro_moderno_id' => 9999,
    ]);

    $aurosoftCourse = Course::create([
        'name' => 'Excel Avançado & Negócios Aurosoft',
        'workload' => 40,
        'active' => true,
        'ouro_moderno_id' => null,
    ]);

    expect($ouroCourse->isFromOuroModerno())->toBeTrue()
        ->and($ouroCourse->isAurosoftLms())->toBeFalse()
        ->and($aurosoftCourse->isFromOuroModerno())->toBeFalse()
        ->and($aurosoftCourse->isAurosoftLms())->toBeTrue();
});

it('creates lesson structure with Jesuit methodology phases and 90-minute target', function () {
    $course = Course::create([
        'name' => 'Informática Essencial Jesuítica',
        'workload' => 60,
        'active' => true,
    ]);

    $module = LmsModule::create([
        'course_id' => $course->id,
        'title' => 'Módulo 1: Fundamentos Operacionais',
        'sort_order' => 1,
    ]);

    $lesson = LmsLesson::create([
        'module_id' => $module->id,
        'title' => 'Aula 01: Arquitetura e Área de Trabalho',
        'estimated_duration_minutes' => 90,
        'passing_score' => 80.00,
        'sort_order' => 1,
    ]);

    // 1. Praelectio
    $step1 = LmsLessonStep::create([
        'lesson_id' => $lesson->id,
        'phase' => 'praelectio',
        'step_type' => 'theory',
        'title' => 'Conceito da Interface Windows',
        'content' => ['body' => 'Entenda como o sistema operacional organiza seus arquivos e janelas.'],
        'sort_order' => 1,
    ]);

    // 2. Experientia / Practice
    $step2 = LmsLessonStep::create([
        'lesson_id' => $lesson->id,
        'phase' => 'practice',
        'step_type' => 'hotspot_simulation',
        'title' => 'Prática Guiada: Abrindo o Explorador de Arquivos',
        'content' => [
            'instructions' => 'Pressione a tecla Windows + E',
            'explanation_on_error' => 'A combinação correta é Win + E para abrir o Explorador de Arquivos.',
        ],
        'sort_order' => 2,
    ]);

    // 3. Repetitio
    $step3 = LmsLessonStep::create([
        'lesson_id' => $lesson->id,
        'phase' => 'repetitio',
        'step_type' => 'quiz',
        'title' => 'Fixação: Atalhos Essenciais',
        'content' => [
            'question' => 'Qual atalho cria uma nova pasta?',
            'explanation_on_error' => 'O atalho para nova pasta é Ctrl + Shift + N.',
        ],
        'sort_order' => 3,
    ]);

    expect($lesson->steps)->toHaveCount(3)
        ->and($lesson->estimated_duration_minutes)->toBe(90)
        ->and($course->lmsModules)->toHaveCount(1);
});

it('enforces linear progression, Jesuit repetition on error, and mastery score gating', function () {
    $course = Course::create(['name' => 'Curso Teste LMS', 'workload' => 20]);
    $module = LmsModule::create(['course_id' => $course->id, 'title' => 'Módulo Único']);

    $lesson1 = LmsLesson::create([
        'module_id' => $module->id,
        'title' => 'Aula 1',
        'passing_score' => 80.00,
        'sort_order' => 1,
    ]);

    $lesson2 = LmsLesson::create([
        'module_id' => $module->id,
        'title' => 'Aula 2',
        'passing_score' => 80.00,
        'sort_order' => 2,
    ]);

    $step1 = LmsLessonStep::create([
        'lesson_id' => $lesson1->id,
        'phase' => 'repetitio',
        'step_type' => 'quiz',
        'title' => 'Questão 1',
        'content' => ['explanation_on_error' => 'Revise o atalho Ctrl+C para cópia.'],
        'sort_order' => 1,
    ]);

    // Tentativa 1: Aluno tenta acessar a Aula 2 diretamente -> Bloqueado!
    $accessAula2 = $this->service->canAccessLesson($this->contract, $lesson2);
    expect($accessAula2['can_access'])->toBeFalse()
        ->and($accessAula2['reason'])->toBe('previous_lesson_incomplete');

    // Inicia a Aula 1
    $progress = $this->service->startLesson($this->contract, $lesson1);
    expect($progress->status)->toBe('in_progress')
        ->and($progress->current_step_id)->toBe($step1->id);

    // Aluno erra o passo 1 -> Repetitio acionada, não avança
    $attemptErrada = $this->service->submitStepAttempt($progress, $step1, isCorrect: false, answer: 'Ctrl+V');
    expect($attemptErrada['advances'])->toBeFalse()
        ->and($attemptErrada['requires_repetition'])->toBeTrue()
        ->and($attemptErrada['feedback'])->toContain('Revise o atalho Ctrl+C');

    // Aluno tenta novamente e acerta -> Avança e conclui a aula
    $attemptCerta = $this->service->submitStepAttempt($progress, $step1, isCorrect: true, answer: 'Ctrl+C');
    expect($attemptCerta['advances'])->toBeTrue()
        ->and($attemptCerta['lesson_completed'])->toBeTrue();

    $progress->refresh();
    expect($progress->status)->toBe('completed');
});

it('blocks pacing with daily lesson limit to prevent rapid course rushing', function () {
    $course = Course::create(['name' => 'Pacing Test', 'workload' => 30]);
    $module = LmsModule::create(['course_id' => $course->id, 'title' => 'Módulo 1']);

    $lesson1 = LmsLesson::create(['module_id' => $module->id, 'title' => 'Aula 1', 'sort_order' => 1]);
    $lesson2 = LmsLesson::create(['module_id' => $module->id, 'title' => 'Aula 2', 'sort_order' => 2]);

    // Simula Aula 1 já concluída HOJE pelo aluno
    LmsStudentLessonProgress::create([
        'contract_id' => $this->contract->id,
        'student_id' => $this->person->id,
        'lesson_id' => $lesson1->id,
        'status' => 'completed',
        'score' => 95.00,
        'completed_at' => now(),
        'session_date' => today(),
    ]);

    // Tenta iniciar a Aula 2 no mesmo dia -> Bloqueado pela trava de teto diário!
    $checkHoje = $this->service->canAccessLesson($this->contract, $lesson2, date: today());
    expect($checkHoje['can_access'])->toBeFalse()
        ->and($checkHoje['reason'])->toBe('daily_limit_reached')
        ->and($checkHoje['message'])->toContain('limite de 1 aula concluída por dia');

    // Em outra data (amanhã / próxima aula) -> Liberado!
    $checkAmanha = $this->service->canAccessLesson($this->contract, $lesson2, date: today()->addDay());
    expect($checkAmanha['can_access'])->toBeTrue()
        ->and($checkAmanha['reason'])->toBe('prerequisites_met');
});

it('registers and enforces zero-trust permissions for lms and student portal', function () {
    $permissions = [
        'ViewAny:LmsCourse',
        'View:LmsCourse',
        'Create:LmsCourse',
        'Update:LmsCourse',
        'Delete:LmsCourse',
        'View:LmsLesson',
        'Create:LmsLesson',
        'Update:LmsLesson',
        'Delete:LmsLesson',
        'View:LmsStudentProgress',
        'Reset:LmsStudentProgress',
        'View:LmsPlayer',
        'View:StudentPortal',
    ];

    foreach ($permissions as $permName) {
        expect(Permission::where('name', $permName)->exists())->toBeTrue();
    }
});
