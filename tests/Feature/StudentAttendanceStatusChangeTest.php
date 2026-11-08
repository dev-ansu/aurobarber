<?php

use App\Filament\Pages\Agenda;
use App\Filament\Resources\Contracts\RelationManagers\AttendancesRelationManager;
use App\Filament\Resources\StudentAttendances\Pages\ManageStudentAttendances;
use App\Filament\Resources\StudentAttendances\StudentAttendanceResource;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Contract;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createStatusChangeFixture(string $suffix, Carbon $date): array
{
    $student = Person::create([
        'name' => "Aluno {$suffix}",
        'email' => "student-{$suffix}@example.com",
    ]);

    $responsiblePerson = Person::create([
        'name' => "Responsável {$suffix}",
        'email' => "responsible-{$suffix}@example.com",
    ]);

    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => \App\Enums\RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => "CHG-{$suffix}",
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => $date->toDateString(),
        'total_value' => 1000,
        'status' => 'active',
    ]);

    $formation = Formation::create([
        'name' => "Formação {$suffix}",
        'active' => true,
    ]);

    $contractItem = $contract->items()->create([
        'formation_id' => $formation->id,
    ]);

    $classroom = Classroom::create([
        'name' => "Sala {$suffix}",
        'capacity' => 15,
        'active' => true,
    ]);

    $schedule = $contractItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => $date->dayOfWeek,
        'start_hour' => '14:00',
        'end_hour' => '15:00',
    ]);

    $session = $classroom->sessions()->create([
        'date' => $date,
        'start_hour' => '14:00',
        'end_hour' => '15:00',
        'weekday' => $date->dayOfWeek,
    ]);

    $attendance = StudentAttendance::create([
        'class_session_id' => $session->id,
        'student_schedule_id' => $schedule->id,
        'attendance_date' => $date,
        'status' => 'absent',
    ]);

    return compact('student', 'contract', 'schedule', 'attendance', 'session');
}

function createAuthorizedUser(string $email): User
{
    $person = Person::create([
        'name' => 'Usuário Autorizado',
        'email' => $email,
    ]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => $email,
        'password' => bcrypt('password'),
    ]);

    Permission::findOrCreate('ChangeStatus:StudentAttendance', 'web');
    Permission::findOrCreate('View:Agenda', 'web');
    Permission::findOrCreate('ViewAny:StudentAttendance', 'web');
    $user->givePermissionTo(['ChangeStatus:StudentAttendance', 'View:Agenda', 'ViewAny:StudentAttendance']);

    return $user;
}

function createUnauthorizedUser(string $email): User
{
    $person = Person::create([
        'name' => 'Usuário Sem Permissão',
        'email' => $email,
    ]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => $email,
        'password' => bcrypt('password'),
    ]);

    Permission::findOrCreate('View:Agenda', 'web');
    Permission::findOrCreate('ViewAny:StudentAttendance', 'web');
    $user->givePermissionTo(['View:Agenda', 'ViewAny:StudentAttendance']);

    return $user;
}

test('student attendance changeStatus method switches absent to present with audit log', function () {
    $date = Carbon::today();
    $fixture = createStatusChangeFixture('MODEL-P', $date);
    $user = createAuthorizedUser('auth-model-p@example.com');

    $this->actingAs($user);

    /** @var StudentAttendance $attendance */
    $attendance = $fixture['attendance'];
    expect($attendance->status)->toBe('absent');

    $attendance->changeStatus('present', $user, 'Aluno esteve presente, erro ao lançar falta.');

    $attendance->refresh();
    expect($attendance->status)->toBe('present')
        ->and($attendance->marked_by_user_id)->toBe($user->id)
        ->and($attendance->marking_source)->toBe('staff')
        ->and($attendance->note)->toBe('Aluno esteve presente, erro ao lançar falta.');

    $auditLog = AuditLog::query()
        ->where('auditable_type', StudentAttendance::class)
        ->where('auditable_id', $attendance->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->user_id)->toBe($user->id)
        ->and($auditLog->contract_id)->toBe($fixture['contract']->id)
        ->and(data_get($auditLog->changes, 'before.status'))->toBe('absent')
        ->and(data_get($auditLog->changes, 'after.status'))->toBe('present')
        ->and(data_get($auditLog->changes, 'reason'))->toBe('Aluno esteve presente, erro ao lançar falta.')
        ->and($auditLog->actionLabel())->toBe('Frequência alterada (Falta ➔ Presença)');
});

test('student attendance changeStatus switches present to absent with audit log', function () {
    $date = Carbon::today();
    $fixture = createStatusChangeFixture('MODEL-A', $date);
    $user = createAuthorizedUser('auth-model-a@example.com');

    $this->actingAs($user);

    /** @var StudentAttendance $attendance */
    $attendance = $fixture['attendance'];
    $attendance->mark('present', $user, 'staff', 'Inicial');

    $attendance->changeStatus('absent', $user, 'Lançado presença indevida antes do aluno iniciar o curso.');

    $attendance->refresh();
    expect($attendance->status)->toBe('absent')
        ->and($attendance->note)->toBe('Lançado presença indevida antes do aluno iniciar o curso.');

    $auditLog = AuditLog::query()
        ->where('auditable_type', StudentAttendance::class)
        ->where('auditable_id', $attendance->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and(data_get($auditLog->changes, 'before.status'))->toBe('present')
        ->and(data_get($auditLog->changes, 'after.status'))->toBe('absent')
        ->and(data_get($auditLog->changes, 'reason'))->toBe('Lançado presença indevida antes do aluno iniciar o curso.')
        ->and($auditLog->actionLabel())->toBe('Frequência alterada (Presença ➔ Falta)');
});

test('student attendance changeStatus requires justification reason', function () {
    $date = Carbon::today();
    $fixture = createStatusChangeFixture('VALIDATE', $date);
    $user = createAuthorizedUser('auth-val@example.com');

    /** @var StudentAttendance $attendance */
    $attendance = $fixture['attendance'];

    expect(fn () => $attendance->changeStatus('present', $user, '   '))
        ->toThrow(ValidationException::class);
});

test('resource action change_status works for authorized user and is hidden from unauthorized user', function () {
    $date = Carbon::today();
    $fixture = createStatusChangeFixture('RESOURCE', $date);
    $authorized = createAuthorizedUser('res-auth@example.com');
    $unauthorized = createUnauthorizedUser('res-unauth@example.com');

    // Authorized user can call the action
    $this->actingAs($authorized);
    Livewire::test(ManageStudentAttendances::class)
        ->mountTableAction('change_status', $fixture['attendance'])
        ->set('mountedActions.0.data.status', 'present')
        ->set('mountedActions.0.data.reason', 'Aluno compareceu após tolerância de horário.')
        ->callMountedAction()
        ->assertHasNoTableActionErrors();

    expect($fixture['attendance']->fresh()->status)->toBe('present')
        ->and($fixture['attendance']->fresh()->note)->toBe('Aluno compareceu após tolerância de horário.');

    // Unauthorized user cannot call the action
    $this->actingAs($unauthorized);
    Livewire::test(ManageStudentAttendances::class)
        ->assertTableActionHidden('change_status', $fixture['attendance']);
});

test('relation manager action change_status updates attendance from contract page', function () {
    $date = Carbon::today();
    $fixture = createStatusChangeFixture('RELATION', $date);
    $authorized = createAuthorizedUser('rel-auth@example.com');

    $this->actingAs($authorized);
    Livewire::test(AttendancesRelationManager::class, [
        'ownerRecord' => $fixture['contract'],
        'pageClass' => 'App\Filament\Resources\Contracts\Pages\ViewContract',
    ])
        ->mountTableAction('change_status', $fixture['attendance'])
        ->set('mountedActions.0.data.status', 'present')
        ->set('mountedActions.0.data.reason', 'Data de início agendada no mesmo dia, presença corrigida pela secretaria.')
        ->callMountedAction()
        ->assertHasNoTableActionErrors();

    expect($fixture['attendance']->fresh()->status)->toBe('present')
        ->and($fixture['attendance']->fresh()->note)->toBe('Data de início agendada no mesmo dia, presença corrigida pela secretaria.');
});

test('agenda page requires ChangeStatus permission and reason when altering already marked attendance', function () {
    $date = Carbon::today();
    $fixture = createStatusChangeFixture('AGENDA', $date);
    $authorized = createAuthorizedUser('agenda-auth@example.com');
    $unauthorized = createUnauthorizedUser('agenda-unauth@example.com');

    /** @var StudentAttendance $attendance */
    $attendance = $fixture['attendance'];
    $attendance->mark('present', $authorized, 'staff', 'Presente no dia');

    // Unauthorized user attempts to change status of already marked attendance
    $this->actingAs($unauthorized);
    Livewire::test(Agenda::class)
        ->set('date', $date->toDateString())
        ->set("attendanceEdits.{$attendance->id}.status", 'absent')
        ->set("attendanceEdits.{$attendance->id}.note", 'Tentando mudar sem permissao')
        ->call('saveAttendance', $attendance->id)
        ->assertHasErrors(["attendanceEdits.{$attendance->id}.status"]);

    // Authorized user attempts to change without reason
    $this->actingAs($authorized);
    Livewire::test(Agenda::class)
        ->set('date', $date->toDateString())
        ->set("attendanceEdits.{$attendance->id}.status", 'absent')
        ->set("attendanceEdits.{$attendance->id}.note", '   ')
        ->call('saveAttendance', $attendance->id)
        ->assertHasErrors(["attendanceEdits.{$attendance->id}.note"]);

    // Authorized user changes with reason
    Livewire::test(Agenda::class)
        ->set('date', $date->toDateString())
        ->set("attendanceEdits.{$attendance->id}.status", 'absent')
        ->set("attendanceEdits.{$attendance->id}.note", 'Aluno saiu mais cedo sem autorização e levou falta.')
        ->call('saveAttendance', $attendance->id)
        ->assertHasNoErrors();

    expect($attendance->fresh()->status)->toBe('absent')
        ->and($attendance->fresh()->note)->toBe('Aluno saiu mais cedo sem autorização e levou falta.');

    $auditLog = AuditLog::query()
        ->where('auditable_type', StudentAttendance::class)
        ->where('auditable_id', $attendance->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($auditLog)->not->toBeNull()
        ->and(data_get($auditLog->changes, 'before.status'))->toBe('present')
        ->and(data_get($auditLog->changes, 'after.status'))->toBe('absent')
        ->and(data_get($auditLog->changes, 'reason'))->toBe('Aluno saiu mais cedo sem autorização e levou falta.');
});
