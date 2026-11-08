<?php

declare(strict_types=1);

use App\Enums\RelationshipEnum;
use App\Filament\Pages\ClassroomLiveMonitor;
use App\Filament\Resources\StudentAttendances\StudentAttendanceResource;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Formation;
use App\Models\LmsLesson;
use App\Models\LmsLessonStep;
use App\Models\LmsLessonUnlock;
use App\Models\LmsModule;
use App\Models\LmsStudentLessonProgress;
use App\Models\LmsStudentStepAttempt;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // 1. Permissões e papéis
    $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'View:LmsStudentProgress', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'ViewAny:StudentAttendance', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'View:Agenda', 'guard_name' => 'web']);

    $educatorPerson = Person::create([
        'name' => 'Professor Carlos',
        'email' => 'carlos@aurosoft.com',
    ]);

    $this->educator = User::create([
        'person_id' => $educatorPerson->id,
        'email' => 'carlos@aurosoft.com',
        'password' => bcrypt('password'),
    ]);
    $this->educator->assignRole($superAdminRole);

    // 2. Aluno e Contrato
    $this->studentPerson = Person::create([
        'name' => 'Maria Silva',
        'email' => 'maria@teste.com',
        'birth_date' => '2006-05-20',
    ]);

    $responsiblePerson = Person::create([
        'name' => 'Pai da Maria',
        'email' => 'pai.maria@teste.com',
    ]);

    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Father,
        'financial_responsible' => true,
    ]);

    $this->contract = Contract::create([
        'contract_number' => 'MAT-2026-LIVE',
        'student_id' => $this->studentPerson->id,
        'responsible_id' => $responsible->id,
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1200,
        'validity_months' => 12,
        'enrollment_fee' => 50,
        'monthly_payment' => 100,
        'installments_count' => 12,
        'payment_due_day' => 10,
    ]);

    // 3. Sala de Aula e Formação
    $this->classroom = Classroom::create([
        'name' => 'Laboratório 01',
        'capacity' => 20,
        'opening_time' => '08:00:00',
        'closing_time' => '22:00:00',
        'active' => true,
    ]);

    $this->formation = Formation::create([
        'name' => 'Formação Office Pro',
        'workload' => 80,
        'active' => true,
    ]);

    $this->contractItem = ContractItem::create([
        'contract_id' => $this->contract->id,
        'formation_id' => $this->formation->id,
    ]);

    // 4. Grade de Cursos e Lições LMS
    $this->course = Course::create([
        'name' => 'Excel Avançado',
        'workload' => 40,
        'active' => true,
    ]);

    $this->formation->courses()->attach($this->course->id, ['order' => 1]);

    ContractCourse::create([
        'contract_item_id' => $this->contractItem->id,
        'course_id' => $this->course->id,
        'status' => 'in_progress',
    ]);

    $this->module = LmsModule::create([
        'course_id' => $this->course->id,
        'title' => 'Módulo 1: Fórmulas',
        'sort_order' => 1,
    ]);

    $this->lesson1 = LmsLesson::create([
        'module_id' => $this->module->id,
        'title' => 'Aula 01: SOMA e MÉDIA',
        'estimated_duration_minutes' => 60,
        'passing_score' => 70.0,
        'sort_order' => 1,
    ]);

    $this->lesson2 = LmsLesson::create([
        'module_id' => $this->module->id,
        'title' => 'Aula 02: PROCV e PROCH',
        'estimated_duration_minutes' => 60,
        'passing_score' => 70.0,
        'sort_order' => 2,
    ]);

    $this->step1 = LmsLessonStep::create([
        'lesson_id' => $this->lesson1->id,
        'phase' => 'praelectio',
        'step_type' => 'theory',
        'title' => 'Conceito de Intervalos',
        'content' => ['body' => 'Explicação de intervalos.'],
        'sort_order' => 1,
    ]);

    $this->step2 = LmsLessonStep::create([
        'lesson_id' => $this->lesson1->id,
        'phase' => 'practice',
        'step_type' => 'hotspot_simulation',
        'title' => 'Executar Fórmula SOMA',
        'content' => ['instructions' => 'Digite =SOMA'],
        'sort_order' => 2,
    ]);
});

it('allows authorized educators to access the classroom live monitor page', function () {
    $this->actingAs($this->educator)
        ->get(ClassroomLiveMonitor::getUrl())
        ->assertSuccessful()
        ->assertSee('Painel da Sala de Aula (Monitor em Tempo Real)')
        ->assertSee('Alunos Monitorados');
});

it('displays students with active LMS progress in the classroom live monitor', function () {
    // Aluno iniciou a aula 1 hoje
    $progress = LmsStudentLessonProgress::create([
        'contract_id' => $this->contract->id,
        'student_id' => $this->studentPerson->id,
        'lesson_id' => $this->lesson1->id,
        'current_step_id' => $this->step1->id,
        'status' => 'in_progress',
        'session_date' => today(),
        'started_at' => now()->subMinutes(10),
        'time_spent_seconds' => 600,
    ]);

    Livewire::actingAs($this->educator)
        ->test(ClassroomLiveMonitor::class)
        ->assertSee('Maria Silva')
        ->assertSee('Excel Avançado')
        ->assertSee('Aula 01: SOMA e MÉDIA')
        ->assertSee('Estudando Agora');
});

it('highlights students needing help when they have 2 or more failed attempts on current step', function () {
    $progress = LmsStudentLessonProgress::create([
        'contract_id' => $this->contract->id,
        'student_id' => $this->studentPerson->id,
        'lesson_id' => $this->lesson1->id,
        'current_step_id' => $this->step2->id,
        'status' => 'in_progress',
        'session_date' => today(),
        'started_at' => now()->subMinutes(15),
    ]);

    // Registra 2 erros no passo prático
    LmsStudentStepAttempt::create([
        'progress_id' => $progress->id,
        'step_id' => $this->step2->id,
        'attempt_number' => 1,
        'is_correct' => false,
        'student_answer' => 'errado1',
    ]);

    LmsStudentStepAttempt::create([
        'progress_id' => $progress->id,
        'step_id' => $this->step2->id,
        'attempt_number' => 2,
        'is_correct' => false,
        'student_answer' => 'errado2',
    ]);

    Livewire::actingAs($this->educator)
        ->test(ClassroomLiveMonitor::class)
        ->assertSee('Maria Silva')
        ->assertSee('Atenção (2 erros)')
        ->assertSee('Aluno com dificuldade no passo');
});

it('allows educators to unlock next lesson and suspend daily limit from live monitor', function () {
    // Aula 1 concluída hoje
    LmsStudentLessonProgress::create([
        'contract_id' => $this->contract->id,
        'student_id' => $this->studentPerson->id,
        'lesson_id' => $this->lesson1->id,
        'current_step_id' => $this->step2->id,
        'status' => 'completed',
        'score' => 95.0,
        'session_date' => today(),
        'started_at' => now()->subHours(1),
        'completed_at' => now()->subMinutes(30),
    ]);

    Livewire::actingAs($this->educator)
        ->test(ClassroomLiveMonitor::class)
        ->call('unlockNextLesson', $this->contract->id)
        ->assertNotified('🔓 Próxima Aula Liberada!');

    // Verifica se os desbloqueios foram registrados
    expect(
        LmsLessonUnlock::query()
            ->where('contract_id', $this->contract->id)
            ->where('lesson_id', $this->lesson2->id)
            ->whereDate('unlocked_date', today())
            ->exists()
    )->toBeTrue();

    expect(
        LmsLessonUnlock::query()
            ->where('contract_id', $this->contract->id)
            ->whereNull('lesson_id')
            ->whereDate('unlocked_date', today())
            ->exists()
    )->toBeTrue();
});

it('allows educators to unlock daily limit only', function () {
    Livewire::actingAs($this->educator)
        ->test(ClassroomLiveMonitor::class)
        ->call('unlockDailyLimit', $this->contract->id)
        ->assertNotified('⚡ Limite Diário Liberado!');

    expect(
        LmsLessonUnlock::query()
            ->where('contract_id', $this->contract->id)
            ->whereNull('lesson_id')
            ->whereDate('unlocked_date', today())
            ->exists()
    )->toBeTrue();
});

it('allows educators to reset current lesson progress in case of student block', function () {
    $progress = LmsStudentLessonProgress::create([
        'contract_id' => $this->contract->id,
        'student_id' => $this->studentPerson->id,
        'lesson_id' => $this->lesson1->id,
        'current_step_id' => $this->step2->id,
        'status' => 'in_progress',
        'score' => 50.0,
        'session_date' => today(),
        'started_at' => now()->subMinutes(20),
    ]);

    LmsStudentStepAttempt::create([
        'progress_id' => $progress->id,
        'step_id' => $this->step2->id,
        'attempt_number' => 1,
        'is_correct' => false,
    ]);

    Livewire::actingAs($this->educator)
        ->test(ClassroomLiveMonitor::class)
        ->call('resetLessonProgress', $progress->id)
        ->assertNotified('🔄 Aula Reiniciada');

    $progress->refresh();
    expect($progress->current_step_id)->toBe($this->step1->id)
        ->and($progress->score)->toBeNull()
        ->and(LmsStudentStepAttempt::where('progress_id', $progress->id)->count())->toBe(0);
});

it('supports filtering by status in the live monitor', function () {
    // Aluno 1: Estudando agora
    LmsStudentLessonProgress::create([
        'contract_id' => $this->contract->id,
        'student_id' => $this->studentPerson->id,
        'lesson_id' => $this->lesson1->id,
        'current_step_id' => $this->step1->id,
        'status' => 'in_progress',
        'session_date' => today(),
        'started_at' => now()->subMinutes(5),
    ]);

    Livewire::actingAs($this->educator)
        ->test(ClassroomLiveMonitor::class)
        ->set('statusFilter', 'completed_today')
        ->assertDontSee('Maria Silva')
        ->set('statusFilter', 'studying')
        ->assertSee('Maria Silva');
});

it('searches arbitrary school students via lookup modal and unlocks next lesson', function () {
    Livewire::actingAs($this->educator)
        ->test(ClassroomLiveMonitor::class)
        ->call('openLookupModal')
        ->assertSet('showLookupModal', true)
        ->set('lookupQuery', 'Maria')
        ->assertSee('Maria Silva')
        ->call('unlockNextLesson', $this->contract->id)
        ->assertNotified('🔓 Próxima Aula Liberada!');
});

it('allows unlocking the next lesson directly from the student attendance calls table', function () {
    $schedule = StudentSchedule::create([
        'contract_item_id' => $this->contractItem->id,
        'classroom_id' => $this->classroom->id,
        'weekday' => today()->dayOfWeek,
        'start_hour' => '14:00',
        'end_hour' => '15:00',
    ]);

    $session = ClassSession::create([
        'classroom_id' => $this->classroom->id,
        'date' => today(),
        'weekday' => today()->dayOfWeek,
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);

    $attendance = StudentAttendance::create([
        'student_schedule_id' => $schedule->id,
        'class_session_id' => $session->id,
        'attendance_date' => today(),
        'status' => 'present',
    ]);

    $table = StudentAttendanceResource::table(new \Filament\Tables\Table(\Livewire\Livewire::new(\App\Filament\Resources\StudentAttendances\Pages\ManageStudentAttendances::class)));
    $action = $table->getAction('unlock_next_lesson');

    expect($action)->not->toBeNull()
        ->and($action->getLabel())->toBe('Liberar Próxima Aula');

    $service = app(\App\Services\LmsProgressionService::class);
    $unlockedLesson = $service->unlockNextLessonForContract($this->contract, $this->educator, 'Liberado via painel de chamadas');

    expect($unlockedLesson->id)->toBe($this->lesson1->id);

    expect(
        LmsLessonUnlock::query()
            ->where('contract_id', $this->contract->id)
            ->where('lesson_id', $this->lesson1->id)
            ->whereDate('unlocked_date', today())
            ->exists()
    )->toBeTrue();
});

it('displays student help call alert in live monitor and allows educator to resolve it', function () {
    $progress = LmsStudentLessonProgress::create([
        'contract_id' => $this->contract->id,
        'student_id' => $this->studentPerson->id,
        'lesson_id' => $this->lesson1->id,
        'current_step_id' => $this->step1->id,
        'status' => 'in_progress',
        'session_date' => today(),
        'started_at' => now()->subMinutes(10),
        'help_requested' => true,
        'help_requested_at' => now()->subMinutes(2),
    ]);

    Livewire::actingAs($this->educator)
        ->test(ClassroomLiveMonitor::class)
        ->assertSee('🚨 Chamou Educador!')
        ->assertSee('Atender Aluno')
        ->call('resolveHelp', $progress->id)
        ->assertNotified('Atendimento Registrado');

    expect($progress->fresh()->help_requested)->toBeFalse();
});

