<?php

declare(strict_types=1);

use App\Enums\RelationshipEnum;
use App\Filament\Resources\SchoolGoals\Pages\ManageSchoolGoals;
use App\Filament\Widgets\SchoolGoalsProgressWidget;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\SchoolAppointment;
use App\Models\SchoolGoal;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

function createPedagogicalGoalTestUser(string $name, string $email): User
{
    $person = Person::create([
        'name' => $name,
        'email' => $email,
    ]);

    return User::create([
        'person_id' => $person->id,
        'email' => $email,
        'password' => bcrypt('password123'),
    ]);
}

test('manager can define pedagogical goals along with commercial and financial targets', function () {
    $manager = createPedagogicalGoalTestUser('Gestor Pedagógico', 'gestor.ped@escola.com');
    $manager->assignRole($this->superAdminRole);
    $this->actingAs($manager);

    $now = Carbon::now();

    Livewire::test(ManageSchoolGoals::class)
        ->assertSuccessful()
        ->callAction('create', [
            'month' => $now->month,
            'year' => $now->year,
            'target_enrollments' => 25,
            'target_sales_amount' => 50000.00,
            'target_revenue_amount' => 45000.00,
            'target_attendance_rate' => 90.00,
            'target_max_cancellations' => 2,
            'target_retention_interviews' => 12,
            'notes' => 'Foco em 90% de frequência no laboratório de informática.',
        ])
        ->assertHasNoActionErrors();

    $goal = SchoolGoal::forMonthAndYear($now->month, $now->year);
    expect($goal)->not->toBeNull();
    expect((float) $goal->target_attendance_rate)->toBe(90.00);
    expect($goal->target_max_cancellations)->toBe(2);
    expect($goal->target_retention_interviews)->toBe(12);
});

test('school goals progress widget correctly calculates attendance rate and kiosk anti-fraud audit breakdown', function () {
    $manager = createPedagogicalGoalTestUser('Diretor Geral', 'diretor.geral@escola.com');
    $manager->assignRole($this->superAdminRole);
    $this->actingAs($manager);

    $now = Carbon::now();

    // 1. Criar meta
    $goal = SchoolGoal::create([
        'month' => $now->month,
        'year' => $now->year,
        'target_enrollments' => 20,
        'target_sales_amount' => 30000.00,
        'target_revenue_amount' => 25000.00,
        'target_attendance_rate' => 85.00,
        'target_max_cancellations' => 3,
        'target_retention_interviews' => 5,
    ]);

    // 2. Criar estrutura para chamada
    $student1 = Person::create(['name' => 'Aluno Kiosk 1', 'email' => 'kiosk1@escola.com']);
    $student2 = Person::create(['name' => 'Aluno Kiosk 2', 'email' => 'kiosk2@escola.com']);
    $student3 = Person::create(['name' => 'Aluno Manual', 'email' => 'manual@escola.com']);
    $student4 = Person::create(['name' => 'Aluno Faltoso', 'email' => 'faltoso@escola.com']);

    $formation = Formation::create(['name' => 'Formação TI']);
    $classroom = Classroom::create(['name' => 'Sala 1', 'capacity' => 20]);

    $responsible = Responsible::create([
        'person_id' => $student1->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-PED-001',
        'student_id' => $student1->id,
        'responsible_id' => $responsible->id,
        'status' => 'active',
        'start_date' => $now->toDateString(),
        'total_value' => 1200.00,
    ]);

    $item = ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
        'status' => 'in_progress',
    ]);

    $schedule1 = StudentSchedule::create([
        'contract_item_id' => $item->id,
        'classroom_id' => $classroom->id,
        'weekday' => 1,
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);
    $schedule2 = StudentSchedule::create([
        'contract_item_id' => $item->id,
        'classroom_id' => $classroom->id,
        'weekday' => 2,
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);
    $schedule3 = StudentSchedule::create([
        'contract_item_id' => $item->id,
        'classroom_id' => $classroom->id,
        'weekday' => 3,
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);
    $schedule4 = StudentSchedule::create([
        'contract_item_id' => $item->id,
        'classroom_id' => $classroom->id,
        'weekday' => 4,
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);

    $session = ClassSession::create([
        'classroom_id' => $classroom->id,
        'date' => $now->toDateString(),
        'weekday' => $now->dayOfWeek,
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);

    // Presença 1: validada pelo Totem (kiosk)
    StudentAttendance::create([
        'class_session_id' => $session->id,
        'student_schedule_id' => $schedule1->id,
        'attendance_date' => $now->toDateString(),
        'status' => 'present',
        'marking_source' => 'kiosk',
        'marked_at' => $now,
    ]);

    // Presença 2: validada pelo Totem (kiosk)
    $session2 = ClassSession::create([
        'classroom_id' => $classroom->id,
        'date' => $now->toDateString(),
        'weekday' => $now->dayOfWeek,
        'start_hour' => '15:00:00',
        'end_hour' => '16:00:00',
    ]);
    StudentAttendance::create([
        'class_session_id' => $session2->id,
        'student_schedule_id' => $schedule2->id,
        'attendance_date' => $now->toDateString(),
        'status' => 'present',
        'marking_source' => 'kiosk',
        'marked_at' => $now,
    ]);

    // Presença 3: manual por educador (staff)
    $session3 = ClassSession::create([
        'classroom_id' => $classroom->id,
        'date' => $now->toDateString(),
        'weekday' => $now->dayOfWeek,
        'start_hour' => '16:00:00',
        'end_hour' => '17:00:00',
    ]);
    StudentAttendance::create([
        'class_session_id' => $session3->id,
        'student_schedule_id' => $schedule3->id,
        'attendance_date' => $now->toDateString(),
        'status' => 'present',
        'marking_source' => 'staff',
        'marked_by_user_id' => $manager->id,
        'marked_at' => $now,
    ]);

    // Falta 4: ausente
    $session4 = ClassSession::create([
        'classroom_id' => $classroom->id,
        'date' => $now->toDateString(),
        'weekday' => $now->dayOfWeek,
        'start_hour' => '17:00:00',
        'end_hour' => '18:00:00',
    ]);
    StudentAttendance::create([
        'class_session_id' => $session4->id,
        'student_schedule_id' => $schedule4->id,
        'attendance_date' => $now->toDateString(),
        'status' => 'absent',
        'marking_source' => 'staff',
        'marked_by_user_id' => $manager->id,
        'marked_at' => $now,
    ]);

    // Criar um compromisso de retenção concluído
    SchoolAppointment::create([
        'title' => 'Entrevista de Retenção Pedagógica',
        'type' => 'cancelamento',
        'priority' => 'urgente',
        'date' => $now->toDateString(),
        'status' => 'completed',
        'created_by_user_id' => $manager->id,
    ]);

    // Total chamadas: 4. Presenças: 3. Assiduidade = 3/4 = 75.0%
    // Presenças no Totem: 2 de 3 = 66.7% auditadas via Kiosk
    $widget = new SchoolGoalsProgressWidget;
    $data = $widget->getViewData();

    expect($data['totalAttendances'])->toBe(4);
    expect($data['presentAttendances'])->toBe(3);
    expect($data['actualAttendanceRate'])->toBe(75.0);
    expect($data['kioskPresences'])->toBe(2);
    expect($data['manualPresences'])->toBe(1);
    expect($data['kioskAuditRate'])->toBe(66.7);
    expect($data['attendanceGoalReached'])->toBeFalse(); // 75% < 85% meta
    expect($data['actualRetentionInterviews'])->toBe(1);

    // Testar render do componente Livewire
    Livewire::test(SchoolGoalsProgressWidget::class)
        ->assertSuccessful()
        ->assertSee('Metas Pedagógicas')
        ->assertSee('Assiduidade Geral')
        ->assertSee('75%')
        ->assertSee('Totem de Autoatendimento')
        ->assertSee('Lançamento Manual');
});
