<?php

use App\Enums\RelationshipEnum;
use App\Models\Classroom;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\StudentSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function createContractWithStudent(string $suffix): Contract
{
    $student = Person::create([
        'name' => "Aluno {$suffix}",
        'email' => "aluno-{$suffix}@example.com",
    ]);

    $responsiblePerson = Person::create([
        'name' => "Responsável {$suffix}",
        'email' => "responsavel-{$suffix}@example.com",
    ]);

    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    return Contract::create([
        'contract_number' => "TEST-{$suffix}",
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'total_value' => 0,
        'status' => 'active',
    ]);
}

function createContractItemWithCourses(Contract $contract, string $suffix, int $courseCount = 1): ContractItem
{
    $formation = Formation::create([
        'name' => "Formação {$suffix}",
        'active' => true,
    ]);

    $contractItem = $contract->items()->create([
        'formation_id' => $formation->id,
    ]);

    for ($courseNumber = 1; $courseNumber <= $courseCount; $courseNumber++) {
        $course = Course::create([
            'name' => "Curso {$suffix} {$courseNumber}",
            'active' => true,
        ]);
        $formation->courses()->attach($course->id, ['order' => $courseNumber]);
        $contractItem->contractCourses()->create(['course_id' => $course->id]);
    }

    return $contractItem;
}

test('schedules belong to the formation purchased in the contract', function () {
    $contract = createContractWithStudent('ONE');
    $contractItem = createContractItemWithCourses($contract, 'ONE');
    $classroom = Classroom::create([
        'name' => 'Sala A',
        'capacity' => 10,
        'active' => true,
    ]);

    $schedule = $contractItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 1,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]);

    expect($schedule->contractItem->is($contractItem))->toBeTrue()
        ->and($schedule->contractItem->formation->is($contractItem->formation))->toBeTrue()
        ->and($contract->schedules()->sole()->is($schedule))->toBeTrue()
        ->and($schedule->classroom->is($classroom))->toBeTrue()
        ->and($schedule->start_hour)->toBe('09:00:00');
});

test('a student cannot have overlapping schedules for different contract items', function () {
    $contract = createContractWithStudent('TWO');
    $firstFormation = createContractItemWithCourses($contract, 'TWO-A');
    $secondFormation = createContractItemWithCourses($contract, 'TWO-B');
    $classroom = Classroom::create([
        'name' => 'Sala B',
        'capacity' => 10,
        'active' => true,
    ]);

    $firstFormation->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 2,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]);

    expect(fn () => $secondFormation->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 2,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]))->toThrow(ValidationException::class);
});

test('a student may have consecutive one-hour schedule blocks', function () {
    $contract = createContractWithStudent('CONSECUTIVE');
    $contractItem = createContractItemWithCourses($contract, 'CONSECUTIVE');
    $classroom = Classroom::create([
        'name' => 'Sala de blocos consecutivos',
        'capacity' => 10,
        'active' => true,
    ]);

    $firstSchedule = $contractItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 1,
        'start_hour' => '18:00',
        'end_hour' => '19:00',
    ]);
    $secondSchedule = $contractItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 1,
        'start_hour' => '19:00',
        'end_hour' => '20:00',
    ]);

    expect($firstSchedule->exists)->toBeTrue()
        ->and($secondSchedule->exists)->toBeTrue();
});

test('available classroom slots exclude full rooms and conflicts for the student', function () {
    $scheduledContract = createContractWithStudent('AVAILABLE-BOOKED');
    $scheduledItem = createContractItemWithCourses($scheduledContract, 'AVAILABLE-BOOKED');
    $fullClassroom = Classroom::create([
        'name' => 'Sala sem vaga no primeiro bloco',
        'capacity' => 1,
        'opening_time' => '09:00',
        'closing_time' => '12:00',
        'active' => true,
    ]);
    $otherClassroom = Classroom::create([
        'name' => 'Sala com vagas',
        'capacity' => 2,
        'opening_time' => '09:00',
        'closing_time' => '11:00',
        'active' => true,
    ]);
    $fullyBookedClassroom = Classroom::create([
        'name' => 'Sala totalmente ocupada',
        'capacity' => 1,
        'opening_time' => '09:00',
        'closing_time' => '10:00',
        'active' => true,
    ]);
    $bookedSchedule = $scheduledItem->schedules()->create([
        'classroom_id' => $fullClassroom->id,
        'weekday' => 1,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]);
    $fullyBookedContract = createContractWithStudent('AVAILABLE-FULL');
    createContractItemWithCourses($fullyBookedContract, 'AVAILABLE-FULL')->schedules()->create([
        'classroom_id' => $fullyBookedClassroom->id,
        'weekday' => 1,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]);
    $otherContract = createContractWithStudent('AVAILABLE-OTHER');

    $otherStudentSlots = StudentSchedule::availableSlotsByClassroom(1, $otherContract->student_id);
    $sameStudentSlots = StudentSchedule::availableSlotsByClassroom(1, $scheduledContract->student_id);
    $editingOwnScheduleSlots = StudentSchedule::availableSlotsByClassroom(
        1,
        $scheduledContract->student_id,
        $bookedSchedule->id,
    );

    expect($otherStudentSlots[$fullClassroom->id])->not->toHaveKey('09:00:00')
        ->and($otherStudentSlots[$fullClassroom->id])->toHaveKey('10:00:00', '10:00 - 11:00')
        ->and($otherStudentSlots[$fullyBookedClassroom->id])->toBe([])
        ->and($sameStudentSlots[$otherClassroom->id])->not->toHaveKey('09:00:00')
        ->and($sameStudentSlots[$otherClassroom->id])->toHaveKey('10:00:00')
        ->and($editingOwnScheduleSlots[$fullClassroom->id])->toHaveKey('09:00:00');
});

test('each schedule record must represent exactly one full-hour block', function () {
    $contract = createContractWithStudent('INVALID-DURATION');
    $contractItem = createContractItemWithCourses($contract, 'INVALID-DURATION');
    $classroom = Classroom::create([
        'name' => 'Sala de duração inválida',
        'capacity' => 10,
        'opening_time' => '08:00',
        'closing_time' => '20:00',
        'active' => true,
    ]);

    foreach ([
        ['09:30', '10:30'],
        ['09:00', '09:30'],
        ['09:00', '10:30'],
        ['09:00', '11:00'],
    ] as [$startHour, $endHour]) {
        expect(fn () => $contractItem->schedules()->create([
            'classroom_id' => $classroom->id,
            'weekday' => 1,
            'start_hour' => $startHour,
            'end_hour' => $endHour,
        ]))->toThrow(ValidationException::class);
    }
});

test('a schedule can be created for a later free block after an earlier block is full', function () {
    $classroom = Classroom::create([
        'name' => 'Sala com intervalo ocupado',
        'capacity' => 1,
        'opening_time' => '09:00',
        'closing_time' => '12:00',
        'active' => true,
    ]);
    $firstContract = createContractWithStudent('PARTIAL-RANGE-BOOKED');
    createContractItemWithCourses($firstContract, 'PARTIAL-RANGE-BOOKED')->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 1,
        'start_hour' => '10:00',
        'end_hour' => '11:00',
    ]);
    $secondContract = createContractWithStudent('LATER-FREE-SLOT');
    $secondItem = createContractItemWithCourses($secondContract, 'LATER-FREE-SLOT');
    $laterSchedule = $secondItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 1,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]);
    $thirdContract = createContractWithStudent('FOLLOWING-FREE-SLOT');
    $thirdItem = createContractItemWithCourses($thirdContract, 'FOLLOWING-FREE-SLOT');
    $followingSchedule = $thirdItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 1,
        'start_hour' => '11:00',
        'end_hour' => '12:00',
    ]);

    expect($laterSchedule->exists)->toBeTrue()
        ->and($followingSchedule->exists)->toBeTrue();
});

test('a schedule must fit within the classroom operating hours', function () {
    $contract = createContractWithStudent('ROOM-HOURS');
    $contractItem = createContractItemWithCourses($contract, 'ROOM-HOURS');
    $classroom = Classroom::create([
        'name' => 'Sala com funcionamento reduzido',
        'capacity' => 10,
        'opening_time' => '09:00',
        'closing_time' => '11:00',
        'active' => true,
    ]);

    foreach ([['08:00', '09:00'], ['11:00', '12:00']] as [$startHour, $endHour]) {
        expect(fn () => $contractItem->schedules()->create([
            'classroom_id' => $classroom->id,
            'weekday' => 1,
            'start_hour' => $startHour,
            'end_hour' => $endHour,
        ]))->toThrow(ValidationException::class);
    }
});

test('classroom operating hours require full hours and opening before closing', function () {
    expect(fn () => Classroom::create([
        'name' => 'Sala com horário fracionado',
        'capacity' => 10,
        'opening_time' => '08:30',
        'closing_time' => '10:00',
        'active' => true,
    ]))->toThrow(ValidationException::class)
        ->and(fn () => Classroom::create([
            'name' => 'Sala com intervalo inválido',
            'capacity' => 10,
            'opening_time' => '10:00',
            'closing_time' => '09:00',
            'active' => true,
        ]))->toThrow(ValidationException::class);
});

test('classroom hours cannot be shortened past an existing weekly schedule', function () {
    $contract = createContractWithStudent('ROOM-HOURS-UPDATE');
    $contractItem = createContractItemWithCourses($contract, 'ROOM-HOURS-UPDATE');
    $classroom = Classroom::create([
        'name' => 'Sala com horário existente',
        'capacity' => 10,
        'active' => true,
    ]);
    $contractItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 1,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]);

    expect(fn () => $classroom->update([
        'opening_time' => '10:00',
        'closing_time' => '20:00',
    ]))->toThrow(ValidationException::class);
});

test('a classroom cannot be scheduled beyond its capacity', function () {
    $classroom = Classroom::create([
        'name' => 'Sala C',
        'capacity' => 2,
        'active' => true,
    ]);

    foreach (['THREE', 'FOUR'] as $suffix) {
        $contract = createContractWithStudent($suffix);
        createContractItemWithCourses($contract, $suffix)->schedules()->create([
            'classroom_id' => $classroom->id,
            'weekday' => 3,
            'start_hour' => '09:00',
            'end_hour' => '10:00',
        ]);
    }

    $thirdContract = createContractWithStudent('FIVE');
    $thirdFormation = createContractItemWithCourses($thirdContract, 'FIVE');

    expect(fn () => $thirdFormation->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 3,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]))->toThrow(ValidationException::class);
});

test('a contract course allows personalized courses added to the contract', function () {
    $contract = createContractWithStudent('SIX');
    $contractItem = createContractItemWithCourses($contract, 'SIX');
    $otherCourse = Course::create([
        'name' => 'Curso fora da formação',
        'active' => true,
    ]);

    $created = $contractItem->contractCourses()->create([
        'course_id' => $otherCourse->id,
    ]);

    expect($created->exists)->toBeTrue();
});

test('unlinking a course preserves schedules attached to the formation', function () {
    $contract = createContractWithStudent('SEVEN');
    $contractItem = createContractItemWithCourses($contract, 'SEVEN', 2);
    $classroom = Classroom::create([
        'name' => 'Sala de Desvinculação',
        'capacity' => 10,
        'active' => true,
    ]);
    $contractItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 4,
        'start_hour' => '11:00',
        'end_hour' => '12:00',
    ]);

    $contractCourse = $contractItem->contractCourses()->firstOrFail();
    $course = $contractCourse->course;
    $formation = $contractItem->formation;
    $contractCourse->delete();

    expect($course->formations()->whereKey($formation->id)->exists())->toBeTrue()
        ->and($course->exists)->toBeTrue()
        ->and($contractItem->schedules()->exists())->toBeTrue()
        ->and($classroom->schedules()->exists())->toBeTrue();
});

test('a contract item cannot contain a standalone course', function () {
    $contract = createContractWithStudent('EIGHT');

    expect(fn () => $contract->items()->create([]))
        ->toThrow(ValidationException::class);
});
