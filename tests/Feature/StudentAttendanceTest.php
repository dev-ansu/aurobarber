<?php

use App\Actions\GenerateStudentAttendancesForDate;
use App\Actions\MarkStudentAttendanceAtKiosk;
use App\Enums\ContactTypeEnum;
use App\Enums\RelationshipEnum;
use App\Filament\Kiosk\Pages\StudentAttendanceKiosk;
use App\Filament\Pages\Agenda;
use App\Filament\Resources\Contracts\ContractsResource;
use App\Filament\Resources\People\PersonResource;
use App\Filament\Resources\StudentAttendances\Pages\ManageStudentAttendances;
use App\Filament\Resources\StudentAttendances\StudentAttendanceResource;
use App\Filament\Resources\StudentSchedules\Pages\ManageStudentSchedules;
use App\Filament\Resources\StudentSchedules\StudentScheduleResource;
use App\Livewire\Filament\ScheduleBoardModal;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * @return array{
 *     student: Person,
 *     contract: Contract,
 *     schedule: StudentSchedule
 * }
 */
function createAttendanceFixture(string $suffix, Carbon $date): array
{
    $student = Person::create([
        'name' => "Aluno {$suffix}",
        'email' => "attendance-{$suffix}@example.com",
    ]);
    $responsiblePerson = Person::create([
        'name' => "Responsável {$suffix}",
        'email' => "responsible-{$suffix}@example.com",
    ]);
    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);
    $contract = Contract::create([
        'contract_number' => "ATT-{$suffix}",
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => $date->toDateString(),
        'total_value' => 0,
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
        'capacity' => 20,
        'active' => true,
    ]);
    $schedule = $contractItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => $date->dayOfWeek,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]);

    return compact('student', 'contract', 'schedule');
}

function createAttendanceStaff(string $suffix): User
{
    $role = Role::findOrCreate('secretaria', 'web');
    $role->givePermissionTo(Permission::findOrCreate('View:Agenda', 'web'));
    $person = Person::create([
        'name' => "Secretaria {$suffix}",
        'email' => "secretaria-{$suffix}@example.com",
    ]);
    $user = User::create([
        'person_id' => $person->id,
        'email' => "staff-{$suffix}@example.com",
        'password' => 'password',
    ]);
    $user->assignRole('secretaria');

    return $user;
}

test('daily call generation creates absent records once for active and incomplete formations', function () {
    $date = Carbon::today();
    $activeFixture = createAttendanceFixture('ACTIVE', $date);
    $finishedContractFixture = createAttendanceFixture('FINISHED-CONTRACT', $date);
    $finishedContractFixture['contract']->update(['status' => 'finished']);
    $completedFormationFixture = createAttendanceFixture('COMPLETED-FORMATION', $date);
    $completedFormationFixture['schedule']->contractItem
        ->forceFill(['formation_completed_at' => $date])
        ->saveQuietly();

    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->expectsOutput('Chamadas geradas: 1.')
        ->assertSuccessful();
    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->expectsOutput('Chamadas geradas: 0.')
        ->assertSuccessful();

    $attendance = StudentAttendance::query()->sole();

    expect($attendance->student_schedule_id)->toBe($activeFixture['schedule']->id)
        ->and($attendance->attendance_date->toDateString())->toBe($date->toDateString())
        ->and($attendance->status)->toBe('absent')
        ->and($attendance->marked_at)->toBeNull();
});

test('daily calls share one class session for students in the same room and time slot', function () {
    $date = Carbon::today();
    $first = createAttendanceFixture('GROUP-A', $date);
    $second = createAttendanceFixture('GROUP-B', $date);
    $second['schedule']->update([
        'classroom_id' => $first['schedule']->classroom_id,
    ]);

    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->expectsOutput('Chamadas geradas: 2.')
        ->assertSuccessful();

    $attendances = StudentAttendance::query()->with('session')->get();

    expect($attendances)->toHaveCount(2)
        ->and($attendances->pluck('class_session_id')->unique())->toHaveCount(1)
        ->and(ClassSession::query()->count())->toBe(1);
});

test('daily call generation does not create attendance retroactively for past dates', function () {
    $date = Carbon::yesterday();
    createAttendanceFixture('PAST', $date);

    expect(fn () => app(GenerateStudentAttendancesForDate::class)->execute($date))
        ->toThrow(ValidationException::class)
        ->and(StudentAttendance::query()->count())->toBe(0)
        ->and(ClassSession::query()->count())->toBe(0);
});

test('the agenda previews upcoming formation schedules and supports filters', function () {
    $date = Carbon::tomorrow();
    $first = createAttendanceFixture('PREVIEW-A', $date);
    $second = createAttendanceFixture('PREVIEW-B', $date);
    $second['schedule']->update([
        'start_hour' => '11:00',
        'end_hour' => '12:00',
    ]);
    $staff = createAttendanceStaff('PREVIEW');
    $this->actingAs($staff);

    Livewire::test(Agenda::class)
        ->set('date', $date->toDateString())
        ->assertSee('Aluno PREVIEW-A')
        ->assertSee('Aluno PREVIEW-B')
        ->set('startHour', '11:00')
        ->assertDontSee('Aluno PREVIEW-A')
        ->assertSee('Aluno PREVIEW-B')
        ->set('startHour', '')
        ->set('search', 'PREVIEW-B')
        ->assertDontSee('Aluno PREVIEW-A')
        ->assertSee('Aluno PREVIEW-B')
        ->set('search', '')
        ->set('classroomId', (string) $first['schedule']->classroom_id)
        ->assertSee('Aluno PREVIEW-A')
        ->assertDontSee('Aluno PREVIEW-B')
        ->assertSee('Horário')
        ->assertSee('Agendado');

    expect(Agenda::canAccess())->toBeTrue();
});

test('the agenda follows the IMPresenca student-card interactions', function () {
    $date = Carbon::tomorrow();
    $fixture = createAttendanceFixture('CARD', $date);
    Contact::create([
        'person_id' => $fixture['student']->id,
        'type' => ContactTypeEnum::Telefone,
        'value' => '(11) 99999-0000',
    ]);
    $staff = createAttendanceStaff('CARD');
    $staff->givePermissionTo(
        Permission::findOrCreate('View:Contract', 'web'),
        Permission::findOrCreate('View:Person', 'web'),
    );
    $this->actingAs($staff);

    Livewire::test(Agenda::class)
        ->set('date', $date->toDateString())
        ->assertSee('Ver turma')
        ->assertSee($fixture['contract']->contract_number)
        ->assertSee('Ver contrato')
        ->assertSee('Ver dados completos')
        ->assertSee(ContractsResource::getUrl('view', ['record' => $fixture['contract']]), false)
        ->assertSee(PersonResource::getUrl('view', ['record' => $fixture['student']]), false)
        ->assertSee('Contatos')
        ->assertDontSee('(11) 99999-0000')
        ->call('toggleContacts', $fixture['student']->id)
        ->assertSee('Telefone')
        ->assertSee('(11) 99999-0000')
        ->call('toggleContacts', $fixture['student']->id)
        ->assertDontSee('(11) 99999-0000')
        ->set('startHour', '09:00')
        ->assertDontSee('Ver turma')
        ->assertSee('Aluno CARD');
});

test('the agenda preserves historical calls and allows staff to update status and notes', function () {
    $date = Carbon::yesterday();
    $fixture = createAttendanceFixture('EDIT', $date);
    $session = $fixture['schedule']->classroom->sessions()->create([
        'date' => $date->toDateString(),
        'weekday' => $date->dayOfWeek,
        'start_hour' => $fixture['schedule']->start_hour,
        'end_hour' => $fixture['schedule']->end_hour,
    ]);
    $attendance = $session->attendances()->create([
        'student_schedule_id' => $fixture['schedule']->id,
        'attendance_date' => $date->toDateString(),
        'status' => 'absent',
    ]);
    $staff = createAttendanceStaff('EDIT');
    $this->actingAs($staff);

    Livewire::test(Agenda::class)
        ->set('date', $date->toDateString())
        ->assertSee('Aluno EDIT')
        ->set("attendanceEdits.{$attendance->id}.status", 'present')
        ->set("attendanceEdits.{$attendance->id}.note", 'Presença confirmada pela secretaria')
        ->call('saveAttendance', $attendance->id)
        ->assertHasNoErrors();

    expect($attendance->fresh()->status)->toBe('present')
        ->and($attendance->fresh()->note)->toBe('Presença confirmada pela secretaria')
        ->and($attendance->fresh()->marked_by_user_id)->toBe($staff->id);
});

test('the global room board modal shows recurring occupancy and supports room filtering', function () {
    $date = Carbon::today();
    $fixture = createAttendanceFixture('BOARD', $date);
    $fixture['schedule']->classroom->update([
        'opening_time' => '09:00',
        'closing_time' => '11:00',
    ]);
    $staff = createAttendanceStaff('BOARD');
    $this->actingAs($staff);

    $component = Livewire::test(ScheduleBoardModal::class)
        ->assertSee('Quadro de horários')
        ->assertActionExists('scheduleBoard')
        ->assertActionVisible('scheduleBoard')
        ->mountAction('scheduleBoard')
        ->assertSet('mountedActions.0.name', 'scheduleBoard');

    $modalAction = $component->instance()->getMountedAction();
    $modalContent = $modalAction->getModalContent();

    expect($modalAction->shouldOpenModal())
        ->toBeTrue()
        ->and($modalContent->render())
        ->toContain('Consulte a ocupação das salas')
        ->toContain('Sala BOARD')
        ->toContain('09:00 - 10:00')
        ->toContain('10:00 - 11:00')
        ->not->toContain('08:00 - 09:00')
        ->toContain('1/20');

    $component->set('classroomId', $fixture['schedule']->classroom_id);
    $filteredModalContent = $component->instance()->getMountedAction()->getModalContent();

    expect($filteredModalContent->render())->toContain('1/20');
});

test('the schedule board modal is available from the shared Filament topbar', function () {
    $staff = createAttendanceStaff('TOPBAR');
    $this->actingAs($staff);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();

    expect(FilamentView::renderHook(PanelsRenderHook::GLOBAL_SEARCH_BEFORE)->toHtml())
        ->toContain('filament.schedule-board-modal')
        ->and(FilamentView::renderHook(PanelsRenderHook::SIDEBAR_NAV_START)->toHtml())
        ->toContain('filament.schedule-board-modal');

    Livewire::test(ScheduleBoardModal::class, ['mode' => 'topbar'])
        ->dispatch('open-schedule-board')
        ->assertSet('mountedActions.0.name', 'scheduleBoard');
});

test('the former schedule board standalone page is no longer registered', function () {
    $routeExists = collect(app('router')->getRoutes())
        ->contains(fn ($route): bool => $route->getName() === 'filament.admin.pages.schedule-board');

    expect($routeExists)->toBeFalse();
});

test('board component access is restricted to attendance staff', function () {
    $student = Person::create([
        'name' => 'Aluno sem permissão',
        'email' => 'no-schedule-board@example.com',
    ]);
    $user = User::create([
        'person_id' => $student->id,
        'email' => 'no-schedule-board-user@example.com',
        'password' => 'password',
    ]);

    $this->actingAs($user);
    Livewire::test(ScheduleBoardModal::class)
        ->assertDontSee('Quadro de horários');
});

test('a schedule board classroom filter rejects inactive rooms', function () {
    $date = Carbon::today();
    $fixture = createAttendanceFixture('INACTIVE-BOARD', $date);
    $fixture['schedule']->classroom->update(['active' => false]);
    $staff = createAttendanceStaff('INACTIVE-BOARD');
    $this->actingAs($staff);

    Livewire::test(ScheduleBoardModal::class)
        ->set('classroomId', $fixture['schedule']->classroom_id)
        ->assertHasErrors(['classroomId']);
});

test('the public attendance kiosk confirms only the selected contract during its active class', function () {
    $date = Carbon::today();
    $studentFixture = createAttendanceFixture('KIOSK-OWN', $date);
    $otherContractFixture = createAttendanceFixture('KIOSK-OTHER', $date);
    $otherContractFixture['contract']->update([
        'student_id' => $studentFixture['student']->id,
    ]);
    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    $ownAttendance = StudentAttendance::query()
        ->where('student_schedule_id', $studentFixture['schedule']->id)
        ->sole();
    $otherContractAttendance = StudentAttendance::query()
        ->where('student_schedule_id', $otherContractFixture['schedule']->id)
        ->sole();
    $this->travelTo($date->copy()->setTime(9, 30));

    $this->withoutVite()
        ->get('/presenca')
        ->assertSuccessful()
        ->assertSee('Digite o número do seu contrato');

    Livewire::test(StudentAttendanceKiosk::class)
        ->set('data.contract_number', strtolower($studentFixture['contract']->contract_number))
        ->call('confirmPresence')
        ->assertNotified();

    expect($ownAttendance->fresh()->status)->toBe('present')
        ->and($ownAttendance->fresh()->marked_by_user_id)->toBeNull()
        ->and($ownAttendance->fresh()->marking_source)->toBe('kiosk')
        ->and($otherContractAttendance->fresh()->status)->toBe('absent');

    expect(AuditLog::query()
        ->where('auditable_type', StudentAttendance::class)
        ->where('auditable_id', $ownAttendance->id)
        ->whereNull('user_id')
        ->where('event', 'updated')
        ->exists())->toBeTrue();
});

test('the attendance kiosk does not mark presence when there is no class in progress', function () {
    $date = Carbon::today();
    $fixture = createAttendanceFixture('KIOSK-EARLY', $date);
    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    $attendance = StudentAttendance::query()
        ->where('student_schedule_id', $fixture['schedule']->id)
        ->sole();
    $this->travelTo($date->copy()->setTime(8, 59));

    expect(app(MarkStudentAttendanceAtKiosk::class)
        ->execute($fixture['contract']->contract_number, now()))->toBeNull()
        ->and($attendance->fresh()->status)->toBe('absent')
        ->and($attendance->fresh()->marked_at)->toBeNull();
});

test('the attendance kiosk accepts the exact scheduled class end time', function () {
    $date = Carbon::today();
    $fixture = createAttendanceFixture('KIOSK-END', $date);
    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    $attendance = StudentAttendance::query()
        ->where('student_schedule_id', $fixture['schedule']->id)
        ->sole();
    $this->travelTo($date->copy()->setTime(10, 0));

    expect(app(MarkStudentAttendanceAtKiosk::class)
        ->execute($fixture['contract']->contract_number, now()))->not->toBeNull()
        ->and($attendance->fresh()->status)->toBe('present')
        ->and($attendance->fresh()->marking_source)->toBe('kiosk');
});

test('the daily call generator is scheduled once shortly after midnight', function () {
    $event = collect(Schedule::events())->first(
        fn ($event): bool => str_contains($event->command, 'attendance:generate-daily'),
    );

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('5 0 * * *');
});

test('archiving a schedule preserves its generated calls and prevents edits overwriting history', function () {
    $date = Carbon::today();
    $fixture = createAttendanceFixture('HISTORY', $date);
    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    expect(fn () => $fixture['schedule']->update([
        'start_hour' => '10:00',
        'end_hour' => '11:00',
    ]))
        ->toThrow(ValidationException::class);

    $fixture['schedule']->delete();
    $attendance = StudentAttendance::query()->sole();

    expect($fixture['schedule']->fresh()->trashed())->toBeTrue()
        ->and($attendance->studentSchedule->trashed())->toBeTrue()
        ->and($attendance->studentSchedule->start_hour)->toBe('09:00:00');

    $replacementSchedule = $fixture['schedule']->contractItem->schedules()->create([
        'classroom_id' => $fixture['schedule']->classroom_id,
        'weekday' => $fixture['schedule']->weekday,
        'start_hour' => $fixture['schedule']->start_hour,
        'end_hour' => $fixture['schedule']->end_hour,
    ]);

    expect($replacementSchedule->exists)->toBeTrue()
        ->and(StudentAttendance::query()->count())->toBe(1);
});

test('only attendance staff and super administrators can access the schedule and call tables', function () {
    expect(StudentAttendanceResource::canViewAny())->toBeFalse()
        ->and(StudentScheduleResource::canViewAny())->toBeFalse()
        ->and(Agenda::canAccess())->toBeFalse();

    $professorRole = Role::create(['name' => 'professor', 'guard_name' => 'web']);
    $professorRole->givePermissionTo([
        Permission::findOrCreate('View:Agenda', 'web'),
        Permission::findOrCreate('ViewAny:StudentAttendance', 'web'),
        Permission::findOrCreate('ViewAny:StudentSchedule', 'web'),
    ]);
    $fixture = createAttendanceFixture('PROFESSOR', Carbon::today());
    $professor = User::create([
        'person_id' => $fixture['student']->id,
        'email' => 'professor@example.com',
        'password' => 'password',
    ]);
    $professor->assignRole('professor');
    $this->actingAs($professor);

    $date = Carbon::today();
    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    expect(StudentAttendanceResource::canViewAny())->toBeTrue()
        ->and(StudentScheduleResource::canViewAny())->toBeTrue()
        ->and(Agenda::canAccess())->toBeTrue();

    Livewire::test(ManageStudentAttendances::class)
        ->assertCanSeeTableRecords([StudentAttendance::query()->sole()]);

    Livewire::test(ManageStudentSchedules::class)
        ->assertCanSeeTableRecords([$fixture['schedule']]);
});

test('the attendance kiosk marks presence when using student email', function () {
    $date = Carbon::today();
    $fixture = createAttendanceFixture('KIOSK-EMAIL', $date);
    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    $attendance = StudentAttendance::query()
        ->where('student_schedule_id', $fixture['schedule']->id)
        ->sole();

    $this->travelTo($date->copy()->setTime(9, 30));

    Livewire::test(StudentAttendanceKiosk::class)
        ->set('data.contract_number', $fixture['student']->email)
        ->call('confirmPresence')
        ->assertNotified();

    expect($attendance->fresh()->status)->toBe('present')
        ->and($attendance->fresh()->marking_source)->toBe('kiosk');
});

test('the attendance kiosk does not false match consultor or other users when entering unmatched email', function () {
    $consultorPerson = Person::create(['name' => 'Consultor Demo', 'email' => 'consultor.demo@aurosoft.test']);
    $consultorUser = User::create([
        'person_id' => $consultorPerson->id,
        'email' => 'consultor.demo@aurosoft.test',
        'password' => 'password',
    ]);

    Livewire::test(StudentAttendanceKiosk::class)
        ->set('data.contract_number', 'nonexistent.student@example.com')
        ->call('confirmPresence')
        ->assertNotDispatched('open-lms-autologin')
        ->assertNotified('Nenhuma presença prevista para este contrato ou credencial neste horário.');
});
