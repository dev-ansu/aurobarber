<?php

use App\Enums\RelationshipEnum;
use App\Livewire\Filament\ScheduleBoardModal;
use App\Models\Classroom;
use App\Models\Contract;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\StudentSchedule;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createTestStudentAndContract(string $suffix, ?School $school = null): array
{
    $student = Person::create([
        'name' => "Aluno {$suffix}",
        'email' => "aluno-{$suffix}@example.com",
    ]);

    $respPerson = Person::create([
        'name' => "Resp {$suffix}",
        'email' => "resp-{$suffix}@example.com",
    ]);

    $responsible = Responsible::create([
        'person_id' => $respPerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => "CTR-{$suffix}",
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'status' => 'active',
        'total_value' => 100,
        'school_id' => $school?->id,
    ]);

    $formation = Formation::create([
        'name' => "Formação {$suffix}",
        'active' => true,
    ]);

    $contractItem = $contract->items()->create([
        'formation_id' => $formation->id,
    ]);

    return compact('student', 'contract', 'contractItem', 'formation');
}

test('a classroom with 20 capacity and historical deleted schedules still allows new bookings', function () {
    $school = School::create([
        'legal_name' => 'Escola Teste',
        'trade_name' => 'Escola Teste',
    ]);

    $classroom = Classroom::create([
        'name' => 'Laboratório 20 Vagas',
        'capacity' => 20,
        'opening_time' => '08:00',
        'closing_time' => '18:00',
        'active' => true,
        'school_id' => $school->id,
    ]);

    // Create 20 historical schedules and soft-delete them all
    for ($i = 1; $i <= 20; $i++) {
        $oldStudent = createTestStudentAndContract("HIST-{$i}", $school);
        $oldSchedule = $oldStudent['contractItem']->schedules()->create([
            'classroom_id' => $classroom->id,
            'weekday' => 1,
            'start_hour' => '10:00',
            'end_hour' => '11:00',
            'school_id' => $school->id,
        ]);
        $oldSchedule->delete();
    }

    expect(StudentSchedule::withTrashed()->where('classroom_id', $classroom->id)->count())->toBe(20)
        ->and(StudentSchedule::query()->where('classroom_id', $classroom->id)->count())->toBe(0);

    // Now a new student attempts to schedule at Monday 10:00
    $newStudent = createTestStudentAndContract('NEW', $school);
    $availableSlots = StudentSchedule::availableSlotsByClassroom(
        1,
        $newStudent['student']->id,
        null,
        $school->id,
    );

    expect($availableSlots[$classroom->id])->toHaveKey('10:00:00');

    // Should create successfully without "sem vagas" validation exception
    $schedule = $newStudent['contractItem']->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 1,
        'start_hour' => '10:00',
        'end_hour' => '11:00',
        'school_id' => $school->id,
    ]);

    expect($schedule->exists)->toBeTrue();
});

test('a classroom with null school_id is available for tenant school contracts', function () {
    $school = School::create([
        'legal_name' => 'Escola Matriz',
        'trade_name' => 'Matriz',
    ]);

    // Classroom created globally without school_id
    $classroom = Classroom::create([
        'name' => 'Laboratório Global 20 Vagas',
        'capacity' => 20,
        'opening_time' => '08:00',
        'closing_time' => '18:00',
        'active' => true,
        'school_id' => null,
    ]);

    $student = createTestStudentAndContract('GLOBAL-ROOM', $school);

    $staff = User::create([
        'person_id' => Person::create(['name' => 'Admin Test', 'email' => 'admin@test.com'])->id,
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);
    $role = Role::findOrCreate('super_admin', 'web');
    $staff->assignRole('super_admin');
    $this->actingAs($staff);

    Filament::setTenant($school);

    $availableSlots = StudentSchedule::availableSlotsByClassroom(
        2,
        $student['student']->id,
        null,
        $school->id,
    );

    expect($availableSlots)->toHaveKey($classroom->id)
        ->and($availableSlots[$classroom->id])->toHaveKey('14:00:00');

    $schedule = $student['contractItem']->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 2,
        'start_hour' => '14:00',
        'end_hour' => '15:00',
    ]);

    expect($schedule->exists)->toBeTrue();
});

test('schedule board correctly filters occupancy when a specific classroom is selected', function () {
    $school = School::create([
        'legal_name' => 'Escola Board',
        'trade_name' => 'Escola Board',
    ]);

    $room1 = Classroom::create([
        'name' => 'Sala A',
        'capacity' => 20,
        'opening_time' => '08:00',
        'closing_time' => '12:00',
        'active' => true,
        'school_id' => $school->id,
    ]);

    $room2 = Classroom::create([
        'name' => 'Sala B',
        'capacity' => 10,
        'opening_time' => '08:00',
        'closing_time' => '12:00',
        'active' => true,
        'school_id' => $school->id,
    ]);

    // Student 1 booked in Room 1 at 09:00 on Monday
    $student1 = createTestStudentAndContract('BOARD-1', $school);
    $student1['contractItem']->schedules()->create([
        'classroom_id' => $room1->id,
        'weekday' => 1,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
        'school_id' => $school->id,
    ]);

    $staff = User::create([
        'person_id' => Person::create(['name' => 'Admin Board', 'email' => 'admin-board@test.com'])->id,
        'email' => 'admin-board@test.com',
        'password' => 'password',
    ]);
    $role = Role::findOrCreate('super_admin', 'web');
    $staff->assignRole('super_admin');
    $this->actingAs($staff);
    Filament::setTenant($school);

    $component = Livewire::test(ScheduleBoardModal::class)
        ->mountAction('scheduleBoard');

    // Unfiltered: total = 30, occupied = 1
    $contentAll = $component->instance()->getMountedAction()->getModalContent();
    $boardAll = $contentAll->getData()['board'];
    $slot9All = collect($boardAll)->firstWhere('label', '09:00 - 10:00');
    expect($slot9All['cells'][1]['occupied'])->toBe(1)
        ->and($slot9All['cells'][1]['total'])->toBe(30);

    // Filter to Room 2: total = 10, occupied = 0
    $component->set('classroomId', $room2->id);
    $contentRoom2 = $component->instance()->getMountedAction()->getModalContent();
    $boardRoom2 = $contentRoom2->getData()['board'];
    $slot9Room2 = collect($boardRoom2)->firstWhere('label', '09:00 - 10:00');
    expect($slot9Room2['cells'][1]['occupied'])->toBe(0)
        ->and($slot9Room2['cells'][1]['total'])->toBe(10);

    // Filter back to all rooms using empty string
    $component->set('classroomId', '');
    $contentBack = $component->instance()->getMountedAction()->getModalContent();
    $boardBack = $contentBack->getData()['board'];
    $slot9Back = collect($boardBack)->firstWhere('label', '09:00 - 10:00');
    expect($slot9Back['cells'][1]['occupied'])->toBe(1)
        ->and($slot9Back['cells'][1]['total'])->toBe(30);
});
