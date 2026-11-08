<?php

use App\Enums\RelationshipEnum;
use App\Filament\Pages\CompletionReports;
use App\Filament\Pages\ExportHistory;
use App\Models\Classroom;
use App\Models\Contract;
use App\Models\ContractCourseProgressHistory;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function createCompletionTrackingContract(string $suffix): Contract
{
    $student = Person::create([
        'name' => "Aluno {$suffix}",
        'email' => "completion-student-{$suffix}@example.com",
    ]);
    $responsiblePerson = Person::create([
        'name' => "Responsável {$suffix}",
        'email' => "completion-responsible-{$suffix}@example.com",
    ]);
    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    return Contract::create([
        'contract_number' => "COMPLETION-{$suffix}",
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => '2026-01-05',
        'total_value' => 0,
        'status' => 'active',
    ]);
}

function createCompletionTrackingItem(Contract $contract, string $suffix, int $courseCount = 1): ContractItem
{
    $formation = Formation::create([
        'name' => "Formação {$suffix}",
        'active' => true,
    ]);
    $contractItem = $contract->items()->create(['formation_id' => $formation->id]);

    for ($number = 1; $number <= $courseCount; $number++) {
        $course = Course::create([
            'name' => "Curso {$suffix} {$number}",
            'workload' => 20,
            'active' => true,
        ]);
        $formation->courses()->attach($course->id, ['order' => $number]);
        $contractItem->contractCourses()->create(['course_id' => $course->id]);
    }

    return $contractItem;
}

test('course progress is historized and completes its formation without completing the contract', function () {
    Carbon::setTestNow('2026-03-01 12:00:00');

    $contract = createCompletionTrackingContract('PROGRESS');
    $contractItem = createCompletionTrackingItem($contract, 'PROGRESS', 2);
    $courses = $contractItem->contractCourses()->get();

    $courses[0]->update(['status' => 'completed', 'grade' => 85]);

    expect($contractItem->fresh()->formation_completed_at)->toBeNull();

    Carbon::setTestNow('2026-03-10 12:00:00');
    $courses[1]->update(['status' => 'completed', 'grade' => 92]);
    $contractItem->refresh();
    $contract->refresh();

    expect($contractItem->formation_completed_at->toDateString())->toBe('2026-03-10')
        ->and($contractItem->completionHistories()->whereNotNull('completed_at')->count())->toBe(1)
        ->and($contract->status)->toBe('active')
        ->and($contract->statusHistories()->count())->toBe(0)
        ->and($courses[0]->progressHistories()->orderByDesc('id')->first()->grade)->toBe('85.00');

    $contract->changeStatus('finished', 'Concluído pela secretaria');

    $report = new CompletionReports;
    $report->periodStart = '2026-03-01';
    $report->periodEnd = '2026-03-31';

    expect($contract->fresh()->finished_at)->not->toBeNull()
        ->and($contract->statusHistories()->where('to_status', 'finished')->count())->toBe(1)
        ->and($report->completedStudentCounts()['contracts'])->toBe(1)
        ->and($report->completedContracts()->count())->toBe(1);
});

test('reopening a course reverses the formation completion while retaining both transitions', function () {
    Carbon::setTestNow('2026-04-01 12:00:00');

    $contract = createCompletionTrackingContract('REOPEN');
    $contractItem = createCompletionTrackingItem($contract, 'REOPEN', 1);
    $course = $contractItem->contractCourses()->firstOrFail();
    $course->update(['status' => 'completed']);

    $course->update(['status' => 'in_progress']);
    $contractItem->refresh();

    expect($contractItem->formation_completed_at)->toBeNull()
        ->and($contractItem->completionHistories()->count())->toBe(2)
        ->and($contractItem->completionHistories()->whereNull('completed_at')->exists())->toBeTrue();
});

test('estimated contract end date uses total linked workload divided by weekly scheduled hours', function () {
    Carbon::setTestNow('2026-01-05 12:00:00');

    $contract = createCompletionTrackingContract('ESTIMATE');
    $contractItem = createCompletionTrackingItem($contract, 'ESTIMATE', 2);
    $classroom = Classroom::create([
        'name' => 'Sala de estimativa',
        'capacity' => 10,
        'active' => true,
    ]);

    foreach ([1, 2] as $weekday) {
        foreach ([['09:00', '10:00'], ['10:00', '11:00']] as [$startHour, $endHour]) {
            $contractItem->schedules()->create([
                'classroom_id' => $classroom->id,
                'weekday' => $weekday,
                'start_hour' => $startHour,
                'end_hour' => $endHour,
            ]);
        }
    }
    $secondContractItem = createCompletionTrackingItem($contract, 'ESTIMATE-SECOND');
    $secondContractItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 3,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]);

    $report = new CompletionReports;
    $report->nearCompletionDays = 90;

    expect($contract->fresh()->end_date->toDateString())->toBe('2026-03-30')
        ->and($report->studentsNearCompletion()->contains('id', $contract->id))->toBeTrue();
});

test('course completion reports include course history after it is unlinked from the student', function () {
    Carbon::setTestNow('2026-05-12 12:00:00');

    $contract = createCompletionTrackingContract('REPORT');
    $contractItem = createCompletionTrackingItem($contract, 'REPORT');
    $contractCourse = $contractItem->contractCourses()->firstOrFail();
    $contractCourse->update(['status' => 'completed', 'grade' => 100]);

    $contractCourse->delete();
    $replacementLink = $contractItem->contractCourses()->create([
        'course_id' => $contractCourse->course_id,
    ]);

    $report = new CompletionReports;
    $report->periodStart = '2026-05-01';
    $report->periodEnd = '2026-05-31';
    $completedCounts = $report->completedStudentCounts();
    $history = ContractCourseProgressHistory::query()
        ->where('status', 'completed')
        ->firstOrFail();

    expect($completedCounts['courses'])->toBe(1)
        ->and($completedCounts['formations'])->toBe(1)
        ->and($report->completedCourses()->count())->toBe(1)
        ->and($contractItem->contractCourses()->count())->toBe(1)
        ->and($replacementLink->exists)->toBeTrue()
        ->and($history->contractCourse->trashed())->toBeTrue();

    $contractItem->delete();

    expect($report->completedStudentCounts()['courses'])->toBe(1)
        ->and($report->completedStudentCounts()['formations'])->toBe(1)
        ->and($report->completedCourses()->count())->toBe(1)
        ->and($report->completedFormations()->count())->toBe(1)
        ->and($history->contractCourse->contractItem->trashed())->toBeTrue();
});

test('completion report uses its own permission and paginates every matching history row', function () {
    Carbon::setTestNow('2026-06-15 12:00:00');
    $contract = createCompletionTrackingContract('PAGINATION');
    $studentName = $contract->student->name;

    for ($index = 1; $index <= 18; $index++) {
        $history = $contract->statusHistories()->create([
            'from_status' => 'active',
            'to_status' => 'finished',
            'reason' => "Conclusão {$index}",
        ]);
        $history->forceFill(['created_at' => Carbon::parse('2026-06-10')->addMinutes($index)])->saveQuietly();
    }

    $previousContract = createCompletionTrackingContract('PREVIOUS');
    $previousHistory = $previousContract->statusHistories()->create([
        'from_status' => 'active',
        'to_status' => 'finished',
        'reason' => 'Conclusão no período anterior',
    ]);
    $previousHistory->forceFill(['created_at' => Carbon::parse('2026-05-15')])->saveQuietly();

    $person = Person::create(['name' => 'Operadora do relatório', 'email' => 'completion-report-operator@example.com']);
    $user = User::create(['person_id' => $person->id, 'email' => 'completion-report-operator@example.com', 'password' => 'password']);
    $user->givePermissionTo(Permission::findOrCreate('View:CompletionReports', 'web'));
    $this->actingAs($user);

    expect(CompletionReports::canAccess())->toBeTrue();

    $page = Livewire::test(CompletionReports::class)
        ->set('periodStart', '2026-06-01')
        ->set('periodEnd', '2026-06-30');

    expect($page->instance()->completedContracts()->total())->toBe(18)
        ->and($page->instance()->completedContracts()->count())->toBe(15)
        ->and($page->instance()->completedStudentCounts()['contracts'])->toBe(1)
        ->and($page->instance()->completedStudentCounts()['previous']['contracts'])->toBe(1);

    $page->set('search', $studentName);
    expect($page->instance()->completedContracts()->total())->toBe(18);
});

test('agenda permission alone does not grant access to completion reports', function () {
    $person = Person::create(['name' => 'Operador de agenda', 'email' => 'agenda-only@example.com']);
    $user = User::create(['person_id' => $person->id, 'email' => 'agenda-only@example.com', 'password' => 'password']);
    $user->givePermissionTo(Permission::findOrCreate('View:Agenda', 'web'));
    $this->actingAs($user);

    expect(CompletionReports::canAccess())->toBeFalse();
});

test('completion report export is saved in export history and produces a downloadable CSV', function () {
    Carbon::setTestNow('2026-06-15 12:00:00');
    $contract = createCompletionTrackingContract('CSV');
    $contract->statusHistories()->create([
        'from_status' => 'active',
        'to_status' => 'finished',
        'reason' => 'Finalização regular',
    ]);
    $person = Person::create(['name' => 'Operador exportação', 'email' => 'completion-export@example.com']);
    $user = User::create(['person_id' => $person->id, 'email' => 'completion-export@example.com', 'password' => 'password']);
    $user->givePermissionTo([
        Permission::findOrCreate('View:CompletionReports', 'web'),
        Permission::findOrCreate('Export:CompletionReports', 'web'),
        Permission::findOrCreate('ViewAny:Export', 'web'),
        Permission::findOrCreate('Download:Export', 'web'),
    ]);
    $this->actingAs($user);

    Livewire::test(CompletionReports::class)
        ->set('periodStart', '2026-06-01')
        ->set('periodEnd', '2026-06-30')
        ->call('downloadReport', 'contracts')
        ->assertRedirect();

    $export = Export::query()->latest('id')->firstOrFail();

    expect($export->user_id)->toBe($user->id)
        ->and($export->successful_rows)->toBe(1)
        ->and(Storage::disk($export->file_disk)->exists($export->getFileDirectory().'/headers.csv'))->toBeTrue()
        ->and(Storage::disk($export->file_disk)->exists($export->getFileDirectory().'/part-000000.csv'))->toBeTrue();

    Livewire::test(ExportHistory::class)
        ->assertCanSeeTableRecords([$export]);

    $downloadUrl = URL::signedRoute('filament.exports.download', [
        'authGuard' => filament()->getAuthGuard(),
        'export' => $export,
        'format' => ExportFormat::Csv,
    ], absolute: false);
    $this->get($downloadUrl)->assertOk();
});

test('course completion report excludes courses reverted to in_progress or not_started', function () {
    Carbon::setTestNow('2026-05-12 12:00:00');

    $contract = createCompletionTrackingContract('REVERT');
    $contractItem = createCompletionTrackingItem($contract, 'REVERT');
    $contractCourse = $contractItem->contractCourses()->firstOrFail();
    $contractCourse->update(['status' => 'completed', 'grade' => 100]);

    $report = new CompletionReports;
    $report->periodStart = '2026-05-01';
    $report->periodEnd = '2026-05-31';

    expect($report->completedCourses()->count())->toBe(1)
        ->and($report->completedStudentCounts()['courses'])->toBe(1);

    // Revert the course to in_progress
    $contractCourse->update(['status' => 'in_progress']);

    expect($report->completedCourses()->count())->toBe(0)
        ->and($report->completedStudentCounts()['courses'])->toBe(0);
});
