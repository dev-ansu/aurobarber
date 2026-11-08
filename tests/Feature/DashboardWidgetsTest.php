<?php

use App\Enums\RelationshipEnum;
use App\Filament\Widgets\AttendanceRateChart;
use App\Filament\Widgets\BirthdaysTableWidget;
use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\DropoutRiskRadarWidget;
use App\Filament\Widgets\OverdueReceivablesChart;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\SchoolGoalsProgressWidget;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\CommercialLead;
use App\Models\Contract;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function createDashboardUser(string $suffix, array $permissions): User
{
    $person = Person::create([
        'name' => "Usuário {$suffix}",
        'email' => "dashboard-user-{$suffix}@example.com",
    ]);
    $user = User::create([
        'person_id' => $person->id,
        'email' => "dashboard-login-{$suffix}@example.com",
        'password' => 'password',
    ]);

    if ($permissions !== []) {
        $user->givePermissionTo(
            collect($permissions)
                ->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'web'))
                ->all(),
        );
    }

    return $user;
}

function createDashboardAttendance(string $suffix, Carbon $date, string $status, bool $marked = true): StudentAttendance
{
    $student = Person::create([
        'name' => "Aluno {$suffix}",
        'email' => "dashboard-student-{$suffix}@example.com",
    ]);
    $responsiblePerson = Person::create([
        'name' => "Responsável {$suffix}",
        'email' => "dashboard-responsible-{$suffix}@example.com",
    ]);
    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);
    $contract = Contract::create([
        'contract_number' => "DASH-{$suffix}",
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
        'capacity' => 10,
        'active' => true,
    ]);
    $schedule = $contractItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => $date->dayOfWeek,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]);
    $session = ClassSession::create([
        'classroom_id' => $classroom->id,
        'date' => $date->toDateString(),
        'weekday' => $date->dayOfWeek,
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]);

    return StudentAttendance::create([
        'class_session_id' => $session->id,
        'student_schedule_id' => $schedule->id,
        'attendance_date' => $date->toDateString(),
        'status' => $status,
        'marked_at' => $marked ? $date->copy()->setTime(10, 0) : null,
        'marking_source' => $marked ? 'staff' : null,
    ]);
}

test('dashboard widgets are only visible to users with related permissions', function () {
    expect(DashboardStatsOverview::canView())->toBeFalse()
        ->and(AttendanceRateChart::canView())->toBeFalse();

    $user = createDashboardUser('FINANCE', ['View:Stat:ContasReceberHoje']);
    $this->actingAs($user);

    expect(DashboardStatsOverview::canView())->toBeTrue()
        ->and(AttendanceRateChart::canView())->toBeFalse();

    Livewire::test(DashboardStatsOverview::class)
        ->assertSee('Contas a receber hoje')
        ->assertDontSee('Recebimentos no mês')
        ->assertDontSee('Contratos ativos');

    $user->givePermissionTo(
        Permission::findOrCreate('View:DashboardStatsOverview', 'web'),
        Permission::findOrCreate('View:AttendanceRateChart', 'web'),
    );

    expect(AttendanceRateChart::canView())->toBeTrue();

    Livewire::test(DashboardStatsOverview::class)
        ->assertSee('Contas a receber hoje')
        ->assertSee('Recebimentos no mês')
        ->assertSee('Contratos ativos')
        ->assertSee('Matrículas no mês')
        ->assertSee('Leads em aberto');
});

test('each dashboard stat permission only reveals its own card', function (
    string $permission,
    string $visibleStat,
    array $hiddenStats,
) {
    $user = createDashboardUser(str_replace(':', '-', $permission), [$permission]);
    $this->actingAs($user);

    $widget = Livewire::test(DashboardStatsOverview::class)
        ->assertSee($visibleStat);

    foreach ($hiddenStats as $hiddenStat) {
        $widget->assertDontSee($hiddenStat);
    }
})->with([
    'monthly receipts' => [
        'View:Stat:RecebimentosMes',
        'Recebimentos no mês',
        ['Contas a receber hoje', 'Contratos ativos'],
    ],
    'receivables due today' => [
        'View:Stat:ContasReceberHoje',
        'Contas a receber hoje',
        ['Recebimentos no mês', 'Contratos ativos'],
    ],
    'active contracts' => [
        'View:Stat:ContratosAtivos',
        'Contratos ativos',
        ['Recebimentos no mês', 'Contas a receber hoje'],
    ],
]);

test('dashboard widgets safely hide when configured permissions are absent', function () {
    Permission::query()
        ->whereIn('name', [
            'View:DashboardStatsOverview',
            'View:AttendanceRateChart',
            'View:Stat:RecebimentosMes',
            'View:Stat:ContasReceberHoje',
            'View:Stat:ContratosAtivos',
        ])
        ->delete();

    $user = createDashboardUser('NO-PERMISSIONS', []);
    $this->actingAs($user);

    expect(DashboardStatsOverview::canView())->toBeFalse()
        ->and(AttendanceRateChart::canView())->toBeFalse();
});

test('attendance chart shows daily present and absent percentages for the selected period', function () {
    Carbon::setTestNow('2026-10-09 12:00:00');
    $user = createDashboardUser('PEDAGOGICAL', ['View:AttendanceRateChart']);
    $this->actingAs($user);

    createDashboardAttendance('TODAY-PRESENT', Carbon::today(), 'present');
    createDashboardAttendance('TODAY-ABSENT', Carbon::today(), 'absent');
    createDashboardAttendance('TODAY-UNMARKED', Carbon::today(), 'absent', marked: false);
    createDashboardAttendance('YESTERDAY-PRESENT', Carbon::yesterday(), 'present');

    $component = Livewire::test(AttendanceRateChart::class);
    $getData = new ReflectionMethod(AttendanceRateChart::class, 'getData');
    $chartData = $getData->invoke($component->instance());

    expect($chartData['labels'])->toHaveCount(7)
        ->and($chartData['datasets'][0]['data'])->toHaveCount(7)
        ->and($chartData['datasets'][1]['data'])->toHaveCount(7)
        ->and($chartData['datasets'][0]['data'][5])->toBe(100.0)
        ->and($chartData['datasets'][1]['data'][5])->toBe(0.0)
        ->and($chartData['datasets'][0]['data'][6])->toBe(50.0)
        ->and($chartData['datasets'][1]['data'][6])->toBe(50.0);

    $component->set('filter', '30');
    $chartData = $getData->invoke($component->instance());

    expect($chartData['labels'])->toHaveCount(30);

    $component->set('filter', 'invalid');
    $chartData = $getData->invoke($component->instance());

    expect($chartData['labels'])->toHaveCount(7);

    Carbon::setTestNow();
});

test('admin panel only registers streamlined dashboard widgets', function () {
    $widgets = filament()->getPanel('admin')->getWidgets();

    expect($widgets)->toEqualCanonicalizing([
        QuickActionsWidget::class,
        DashboardStatsOverview::class,
        SchoolGoalsProgressWidget::class,
        AttendanceRateChart::class,
        OverdueReceivablesChart::class,
        DropoutRiskRadarWidget::class,
        BirthdaysTableWidget::class,
    ]);
});

test('dashboard overview counts monthly enrollments and open leads', function () {
    $user = createDashboardUser('FULL-ACCESS', ['View:DashboardStatsOverview']);
    $this->actingAs($user);

    CommercialLead::create([
        'name' => 'Lead Aberto',
        'person_id' => Person::create(['name' => 'Lead Aberto', 'email' => 'lead-aberto@example.com'])->id,
        'status' => 'new',
    ]);
    CommercialLead::create([
        'name' => 'Lead Perdido',
        'person_id' => Person::create(['name' => 'Lead Perdido', 'email' => 'lead-perdido@example.com'])->id,
        'status' => 'lost',
    ]);

    Livewire::test(DashboardStatsOverview::class)
        ->assertSee('Contratos ativos')
        ->assertSee('Matrículas no mês')
        ->assertSee('Contas a receber hoje')
        ->assertSee('Recebimentos no mês')
        ->assertSee('Leads em aberto');
});
