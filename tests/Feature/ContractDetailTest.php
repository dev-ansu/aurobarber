<?php

use App\Enums\RelationshipEnum;
use App\Filament\Exports\StudentAttendanceExporter;
use App\Filament\Resources\Contracts\ContractsResource;
use App\Filament\Resources\Contracts\Pages\ViewContracts;
use App\Filament\Resources\Contracts\RelationManagers\AttendancesRelationManager;
use App\Filament\Resources\Contracts\RelationManagers\ContractHistoriesRelationManager;
use App\Filament\Resources\Contracts\RelationManagers\DebtAgreementsRelationManager;
use App\Filament\Resources\Contracts\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Contracts\RelationManagers\OuroModernoLogsRelationManager;
use App\Filament\Resources\Contracts\RelationManagers\StudentSchedulesRelationManager;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\Contract;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\StudentAttendance;
use App\Models\User;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * @return array{contract: Contract, attendance: StudentAttendance}
 */
function createContractDetailAttendance(string $suffix, string $status): array
{
    $student = Person::create([
        'name' => "Aluno {$suffix}",
        'email' => "contract-detail-student-{$suffix}@example.com",
    ]);
    $responsiblePerson = Person::create([
        'name' => "Responsável {$suffix}",
        'email' => "contract-detail-responsible-{$suffix}@example.com",
    ]);
    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);
    $contract = Contract::create([
        'contract_number' => "DETAIL-{$suffix}",
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
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
        'capacity' => 10,
        'active' => true,
    ]);
    $date = now()->toDateString();
    $session = ClassSession::create([
        'classroom_id' => $classroom->id,
        'date' => $date,
        'weekday' => now()->dayOfWeek,
        'start_hour' => '09:00:00',
        'end_hour' => '10:00:00',
    ]);
    $schedule = $contractItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => now()->dayOfWeek,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]);
    $attendance = StudentAttendance::create([
        'class_session_id' => $session->id,
        'student_schedule_id' => $schedule->id,
        'attendance_date' => $date,
        'status' => $status,
        'marking_source' => 'staff',
    ]);

    return compact('contract', 'attendance');
}

test('contract page presents its information and relation managers in the requested tab order', function () {
    expect((new ViewContracts)->hasCombinedRelationManagerTabsWithContent())->toBeTrue()
        ->and((new ViewContracts)->getContentTabLabel())->toBe('Informações do contrato')
        ->and(ContractsResource::getRelations())->toBe([
            ItemsRelationManager::class,
            StudentSchedulesRelationManager::class,
            AttendancesRelationManager::class,
            ContractHistoriesRelationManager::class,
            DebtAgreementsRelationManager::class,
            OuroModernoLogsRelationManager::class,
        ]);
});

test('contract schedule relation manager creates individual one-hour slots linked to a formation', function () {
    Role::findOrCreate('secretaria', 'web');
    $staffPerson = Person::create([
        'name' => 'Secretaria Agenda',
        'email' => 'contract-schedule-staff@example.com',
    ]);
    $staff = User::create([
        'person_id' => $staffPerson->id,
        'email' => 'contract-schedule-user@example.com',
        'password' => 'password',
    ]);
    $staff->assignRole('secretaria');
    $staff->givePermissionTo(
        Permission::findOrCreate('ViewAny:Contract', 'web'),
        Permission::findOrCreate('View:Contract', 'web'),
        Permission::findOrCreate('ViewAny:StudentSchedule', 'web'),
        Permission::findOrCreate('View:StudentSchedule', 'web'),
        Permission::findOrCreate('Create:StudentSchedule', 'web'),
    );
    $this->actingAs($staff);

    $contract = createContractDetailAttendance('SCHEDULE', 'present')['contract'];
    $contractItem = $contract->items()->firstOrFail();
    $weekday = (now()->dayOfWeek + 1) % 7;
    $classroom = Classroom::create([
        'name' => 'Sala para RelationManager',
        'capacity' => 10,
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'active' => true,
    ]);

    $relationManager = Livewire::test(StudentSchedulesRelationManager::class, [
        'ownerRecord' => $contract,
        'pageClass' => ViewContracts::class,
    ]);

    expect($relationManager->instance()->isReadOnly())->toBeFalse()
        ->and($staff->can('Create:StudentSchedule'))->toBeTrue();

    $relationManager
        ->callTableAction('create', data: [
            'contract_item_id' => $contractItem->id,
            'weekday' => $weekday,
            'classroom_id' => $classroom->id,
            'start_hour' => '09:00:00',
            'end_hour' => '10:00:00',
        ])
        ->assertHasNoTableActionErrors();

    $schedule = $contract->schedules()
        ->where('weekday', $weekday)
        ->where('start_hour', '09:00:00')
        ->sole();

    expect($schedule->contract_item_id)->toBe($contractItem->id)
        ->and($schedule->start_hour)->toBe('09:00:00')
        ->and($schedule->end_hour)->toBe('10:00:00');
});

test('contract attendance tab is scoped to its contract and offers csv and xlsx export', function () {
    Role::findOrCreate('secretaria', 'web');
    $staffPerson = Person::create([
        'name' => 'Secretaria Frequência',
        'email' => 'contract-detail-staff@example.com',
    ]);
    $staff = User::create([
        'person_id' => $staffPerson->id,
        'email' => 'contract-detail-user@example.com',
        'password' => 'password',
    ]);
    $staff->assignRole('secretaria');
    $staff->givePermissionTo(
        Permission::findOrCreate('ViewAny:Contract', 'web'),
        Permission::findOrCreate('View:Contract', 'web'),
    );
    $this->actingAs($staff);

    $contractAttendance = createContractDetailAttendance('ONE', 'absent');
    $otherContractAttendance = createContractDetailAttendance('TWO', 'present');

    Livewire::test(ViewContracts::class, ['record' => $contractAttendance['contract']->getKey()])
        ->assertSee('Informações do contrato')
        ->assertSee('Formações contratadas')
        ->assertSee('Presenças e faltas');

    $livewire = Livewire::test(AttendancesRelationManager::class, [
        'ownerRecord' => $contractAttendance['contract'],
        'pageClass' => ViewContracts::class,
    ])
        ->assertCanSeeTableRecords([$contractAttendance['attendance']])
        ->assertCanNotSeeTableRecords([$otherContractAttendance['attendance']])
        ->assertSee('Falta')
        ->assertTableHeaderActionsExistInOrder(['export']);

    $exportAction = collect($livewire->instance()->getTable()->getHeaderActions())
        ->first(fn ($action): bool => $action instanceof ExportAction);
    $exporter = new StudentAttendanceExporter(new Export, [
        'attendance_date' => 'Data',
        'studentSchedule.contractItem.contract.contract_number' => 'Contrato',
        'studentSchedule.contractItem.contract.student.name' => 'Aluno',
        'status' => 'Presença',
    ], []);
    $exportedRow = $exporter($contractAttendance['attendance']->load([
        'session.classroom',
        'studentSchedule.contractItem.contract.student',
        'studentSchedule.contractItem.formation',
        'markedBy.person',
    ]));

    expect(StudentAttendanceExporter::getModel())->toBe(StudentAttendance::class)
        ->and($exportAction->getFormats())->toBe([ExportFormat::Csv, ExportFormat::Xlsx])
        ->and($exportedRow)->toContain(
            'DETAIL-ONE',
            'Aluno ONE',
            'Falta',
        );
});

test('formation courses modal renders linked courses and activities without blade errors', function () {
    $student = Person::create(['name' => 'Aluno Modal Test', 'email' => 'modal-test@example.com']);
    $responsible = Responsible::create(['person_id' => $student->id, 'relationship' => RelationshipEnum::Mother, 'financial_responsible' => true]);
    $contract = Contract::create([
        'contract_number' => 'MODAL-TEST-1',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'total_value' => 1000,
        'status' => 'active',
    ]);

    $formation = Formation::create(['name' => 'Formação Informática', 'active' => true]);
    $course = \App\Models\Course::create(['name' => 'Windows 11', 'code' => 'WIN11', 'active' => true]);
    $formation->courses()->attach($course->id, ['order' => 1]);

    $contractItem = \App\Models\ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
        'status' => 'in_progress',
    ]);

    $contractCourse = \App\Models\ContractCourse::create([
        'contract_item_id' => $contractItem->id,
        'course_id' => $course->id,
        'order' => 1,
        'status' => 'in_progress',
        'is_unlocked' => true,
        'ouro_moderno_lesson_status' => [
            ['aula' => 1, 'nome' => 'Introdução', 'concluida' => true],
        ],
        'ouro_moderno_test_status' => [
            ['teste' => 1, 'nome' => 'Avaliação 1', 'nota' => 10],
        ],
    ]);

    $contractItem->load(['contract', 'formation.courses', 'contractCourses.course']);

    $rendered = view('filament.resources.contracts.formation-courses-modal', [
        'item' => $contractItem,
    ])->render();

    expect($rendered)->toContain('Windows 11')
        ->toContain('Em andamento no Aurosoft')
        ->toContain('Atividades do curso')
        ->toContain('Introdução');
});

