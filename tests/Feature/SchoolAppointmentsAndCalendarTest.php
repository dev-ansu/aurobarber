<?php

declare(strict_types=1);

use App\Actions\MarkStudentAttendanceAtKiosk;
use App\Enums\RelationshipEnum;
use App\Filament\Pages\Agenda;
use App\Filament\Pages\SchoolAppointmentsCalendar;
use App\Models\Classroom;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\SchoolAppointment;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use App\Models\TaskBoard;
use App\Models\TaskCard;
use App\Models\TaskColumn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $viewAgenda = Permission::firstOrCreate(['name' => 'View:Agenda', 'guard_name' => 'web']);
    $this->superAdminRole->givePermissionTo($viewAgenda);
});

function createCalendarTestUser(string $name, string $email): User
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

test('automatic on-demand daily call generation works when kiosk marks attendance', function () {
    $date = Carbon::today();
    $student = Person::create(['name' => 'Aluno Autogerado', 'email' => 'autogerado@escola.com']);
    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-AUTO-001',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'status' => 'active',
        'start_date' => $date->toDateString(),
        'total_value' => 1200.00,
    ]);

    $formation = Formation::create(['name' => 'Informática']);
    $contractItem = ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
        'status' => 'in_progress',
    ]);

    $classroom = Classroom::create([
        'name' => 'Lab 1',
        'opening_time' => '08:00:00',
        'closing_time' => '22:00:00',
        'capacity' => 20,
    ]);

    StudentSchedule::create([
        'contract_item_id' => $contractItem->id,
        'classroom_id' => $classroom->id,
        'weekday' => $date->dayOfWeek,
        'start_hour' => '10:00:00',
        'end_hour' => '11:00:00',
    ]);

    // Note: Nós NÃO chamamos attendance:generate-daily aqui!
    expect(StudentAttendance::whereDate('attendance_date', $date->toDateString())->count())->toBe(0);

    // O aluno passa no totem às 10:30
    $checkTime = $date->copy()->setTime(10, 30);
    $attendance = app(MarkStudentAttendanceAtKiosk::class)->execute($contract->contract_number, $checkTime);

    expect($attendance)->not->toBeNull();
    expect($attendance->status)->toBe('present');
    expect($attendance->marking_source)->toBe('kiosk');
    expect(StudentAttendance::whereDate('attendance_date', $date->toDateString())->count())->toBeGreaterThan(0);
});

test('agenda page automatically generates today call if none exist', function () {
    $user = createCalendarTestUser('Professor Agenda', 'prof@escola.com');
    $user->assignRole($this->superAdminRole);
    $this->actingAs($user);

    $date = Carbon::today();

    $student = Person::create(['name' => 'Aluno Agenda Test', 'email' => 'aluno.agenda@escola.com']);
    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-AGENDA-001',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'status' => 'active',
        'start_date' => $date->toDateString(),
        'total_value' => 1000.00,
    ]);

    $formation = Formation::create(['name' => 'Design Gráfico']);
    $contractItem = ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
        'status' => 'in_progress',
    ]);

    $classroom = Classroom::create([
        'name' => 'Sala 2',
        'opening_time' => '08:00:00',
        'closing_time' => '22:00:00',
        'capacity' => 15,
    ]);

    StudentSchedule::create([
        'contract_item_id' => $contractItem->id,
        'classroom_id' => $classroom->id,
        'weekday' => $date->dayOfWeek,
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);

    expect(StudentAttendance::whereDate('attendance_date', $date->toDateString())->count())->toBe(0);

    Livewire::test(Agenda::class)
        ->assertSuccessful();

    // Como é hoje, a chamada é auto-gerada sem falhas
    expect(StudentAttendance::whereDate('attendance_date', $date->toDateString())->exists())->toBeTrue();
});

test('super admin can access and create appointments in school calendar', function () {
    $user = createCalendarTestUser('Gestor Diretor', 'diretor@escola.com');
    $user->assignRole($this->superAdminRole);
    $this->actingAs($user);

    $student = Person::create(['name' => 'Aluno Entrevista', 'email' => 'entrevista@escola.com']);

    Livewire::test(SchoolAppointmentsCalendar::class)
        ->assertSuccessful()
        ->assertSee('Agenda Institucional')
        ->call('openCreateModal')
        ->set('appointmentTitle', 'Entrevista de Cancelamento - Aluno Entrevista')
        ->set('type', SchoolAppointment::TYPE_CANCELAMENTO)
        ->set('priority', SchoolAppointment::PRIORITY_URGENTE)
        ->set('date', today()->toDateString())
        ->set('start_time', '14:00')
        ->set('end_time', '15:00')
        ->set('location', 'Sala da Direção')
        ->set('description', 'Aluno quer trancar devido a horário de trabalho.')
        ->set('student_id', $student->id)
        ->set('is_shared_with_all', true)
        ->call('saveAppointment')
        ->assertHasNoErrors();

    expect(SchoolAppointment::count())->toBe(1);

    $appointment = SchoolAppointment::first();
    expect($appointment->title)->toBe('Entrevista de Cancelamento - Aluno Entrevista');
    expect($appointment->type)->toBe('cancelamento');
    expect($appointment->type_label)->toBe('Entrevista de Cancelamento');
    expect($appointment->is_shared_with_all)->toBeTrue();
    expect($appointment->student_id)->toBe($student->id);
    expect($appointment->status)->toBe('scheduled');
});

test('appointments can be marked as completed or canceled', function () {
    $user = createCalendarTestUser('Coordenador', 'coord@escola.com');
    $user->assignRole($this->superAdminRole);
    $this->actingAs($user);

    $appointment = SchoolAppointment::create([
        'title' => 'Reunião de Pais',
        'type' => SchoolAppointment::TYPE_REUNIAO_PAIS,
        'priority' => SchoolAppointment::PRIORITY_MEDIA,
        'date' => today()->toDateString(),
        'start_time' => '16:00',
        'created_by_user_id' => $user->id,
        'status' => SchoolAppointment::STATUS_SCHEDULED,
    ]);

    Livewire::test(SchoolAppointmentsCalendar::class)
        ->call('markAsCompleted', $appointment->id);

    expect($appointment->fresh()->status)->toBe(SchoolAppointment::STATUS_COMPLETED);
    expect($appointment->fresh()->completed_at)->not->toBeNull();

    Livewire::test(SchoolAppointmentsCalendar::class)
        ->call('cancelAppointment', $appointment->id);

    expect($appointment->fresh()->status)->toBe(SchoolAppointment::STATUS_CANCELED);
});

test('to-do list panel in calendar shows pending task cards from mini trello', function () {
    $user = createCalendarTestUser('Funcionario ToDo', 'todo@escola.com');
    $user->assignRole($this->superAdminRole);
    $this->actingAs($user);

    $board = TaskBoard::create(['name' => 'Quadro Geral']);
    $todoCol = TaskColumn::create(['task_board_id' => $board->id, 'name' => 'A Fazer', 'position' => 0, 'is_done_column' => false]);
    $doneCol = TaskColumn::create(['task_board_id' => $board->id, 'name' => 'Concluído', 'position' => 1, 'is_done_column' => true]);

    $card = TaskCard::create([
        'task_board_id' => $board->id,
        'task_column_id' => $todoCol->id,
        'title' => 'Ligar para fornecedor de apostilas',
        'priority' => 'urgente',
        'assigned_to_all' => true,
        'created_by_user_id' => $user->id,
    ]);

    Livewire::test(SchoolAppointmentsCalendar::class)
        ->assertSuccessful()
        ->assertSee('Ligar para fornecedor de apostilas')
        ->call('completeTaskCard', $card->id);

    expect($card->fresh()->task_column_id)->toBe($doneCol->id);
    expect($card->fresh()->completed_at)->not->toBeNull();
});

test('user without permissions cannot access school appointments calendar', function () {
    $user = createCalendarTestUser('Sem Acesso', 'noaccess_agenda@escola.com');
    $this->actingAs($user);

    Livewire::test(SchoolAppointmentsCalendar::class)
        ->assertForbidden();
});
