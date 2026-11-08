<?php

use App\Enums\RelationshipEnum;
use App\Filament\Pages\OverdueInstallmentReport;
use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\Installment;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function overdueReportUser(): User
{
    $person = Person::create(['name' => 'Operador relatório', 'email' => 'overdue-report@example.com']);
    $user = User::create(['person_id' => $person->id, 'email' => 'overdue-report@example.com', 'password' => 'password']);
    $user->givePermissionTo(Permission::findOrCreate('View:OverdueInstallmentReport', 'web'));

    return $user;
}

function overdueReportInstallment(string $suffix, string $dueDate, string $status = 'pending'): Installment
{
    $student = Person::create(['name' => "Aluno {$suffix}", 'email' => "student-{$suffix}@example.com"]);
    $responsiblePerson = Person::create(['name' => "Responsável {$suffix}", 'email' => "responsible-{$suffix}@example.com"]);
    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);
    $contract = Contract::create([
        'contract_number' => "OVERDUE-{$suffix}",
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->subMonths(2)->toDateString(),
        'total_value' => 1000,
        'status' => 'active',
    ]);
    $type = FinancialType::create(['name' => 'Mensalidade']);

    return Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $type->id,
        'installment_number' => 1,
        'total_installments' => 1,
        'amount' => 100,
        'discount' => 5,
        'fine' => 2,
        'amount_paid' => 0,
        'due_date' => $dueDate,
        'status' => $status,
    ]);
}

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-15 12:00:00');
});

afterEach(function (): void {
    Carbon::setTestNow();
});

test('overdue report displays outstanding pending installments and supports aging bands', function () {
    $this->actingAs(overdueReportUser());
    $sevenDays = overdueReportInstallment('SEVEN', '2026-10-08');
    $thirtyDays = overdueReportInstallment('THIRTY', '2026-09-15');
    $today = overdueReportInstallment('TODAY', '2026-10-15');
    $paid = overdueReportInstallment('PAID', '2026-10-01', 'paid');

    Livewire::test(OverdueInstallmentReport::class)
        ->assertCanSeeTableRecords([$sevenDays, $thirtyDays])
        ->assertCanNotSeeTableRecords([$today, $paid])
        ->filterTable('aging_band', '1_7')
        ->assertCanSeeTableRecords([$sevenDays])
        ->assertCanNotSeeTableRecords([$thirtyDays]);

    expect(OverdueInstallmentReport::agingBand(7))->toBe('1 a 7 dias')
        ->and(OverdueInstallmentReport::agingBand(30))->toBe('8 a 30 dias')
        ->and(OverdueInstallmentReport::agingBand(90))->toBe('61 a 90 dias')
        ->and(OverdueInstallmentReport::agingBand(91))->toBe('Mais de 90 dias')
        ->and(OverdueInstallmentReport::outstandingAmount($sevenDays))->toBe(97.0);
});

test('overdue report export action requires its separate permission', function () {
    $user = overdueReportUser();
    $this->actingAs($user);

    $withoutExport = Livewire::test(OverdueInstallmentReport::class);
    expect($withoutExport->instance()->getTable()->getHeaderActions()[0]->isAuthorized())->toBeFalse();

    $user->givePermissionTo(Permission::findOrCreate('Export:OverdueInstallmentReport', 'web'));
    $withExport = Livewire::test(OverdueInstallmentReport::class);
    expect($withExport->instance()->getTable()->getHeaderActions()[0]->isAuthorized())->toBeTrue();
});
