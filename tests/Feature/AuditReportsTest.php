<?php

use App\Enums\RelationshipEnum;
use App\Filament\Exports\AuditLogExporter;
use App\Filament\Resources\AuditLogs\Pages\ManageAuditLogs;
use App\Models\AuditLog;
use App\Models\CashRegister;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\User;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Models\Export;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function createAuditReportOperator(string $suffix): User
{
    $person = Person::create([
        'name' => "Operador {$suffix}",
        'email' => "audit-report-operator-{$suffix}@example.com",
    ]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => "audit-report-operator-{$suffix}@example.com",
        'password' => Hash::make('password'),
    ]);

    $user->givePermissionTo(Permission::findOrCreate('ViewAny:AuditLog', 'web'));

    return $user;
}

function createAuditReportContract(string $suffix): Contract
{
    $student = Person::create([
        'name' => "Aluno {$suffix}",
        'email' => "audit-report-student-{$suffix}@example.com",
    ]);
    $responsiblePerson = Person::create([
        'name' => "Responsável {$suffix}",
        'email' => "audit-report-responsible-{$suffix}@example.com",
    ]);
    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    return Contract::create([
        'contract_number' => "AUDIT-REPORT-{$suffix}",
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => '2026-10-01',
        'total_value' => 1000,
        'status' => 'active',
    ]);
}

test('audit resource separates contract and financial events in native paginated table tabs', function () {
    Carbon::setTestNow('2026-10-09 12:00:00');
    $operator = createAuditReportOperator('TABS');
    $this->actingAs($operator);
    createAuditReportContract('TABS');
    $contractAuditLog = AuditLog::query()
        ->where('auditable_type', Contract::class)
        ->firstOrFail();
    CashRegister::create([
        'name' => 'Caixa da auditoria',
        'status' => 'open',
        'opening_balance' => 0,
        'current_balance' => 0,
        'user_id' => $operator->id,
        'opened_at' => now(),
    ]);
    $financeAuditLog = AuditLog::query()
        ->where('auditable_type', CashRegister::class)
        ->firstOrFail();

    Livewire::test(ManageAuditLogs::class)
        ->assertCanSeeTableRecords([$contractAuditLog])
        ->assertCanNotSeeTableRecords([$financeAuditLog])
        ->set('activeTab', 'finance')
        ->assertCanSeeTableRecords([$financeAuditLog])
        ->assertCanNotSeeTableRecords([$contractAuditLog]);
});

test('audit export offers spreadsheet formats and includes the current school details', function () {
    Carbon::setTestNow('2026-10-09 12:00:00');
    $operator = createAuditReportOperator('EXPORT');
    $this->actingAs($operator);
    createAuditReportContract('EXPORT');
    $school = School::create([
        'legal_name' => 'Escola Técnica de Demonstração Ltda.',
        'trade_name' => 'Escola Técnica',
        'cnpj' => '12.345.678/0001-90',
        'email' => 'contato@escola.example',
        'phone' => '(11) 3333-4444',
        'street' => 'Rua das Escolas',
        'number' => '123',
        'city' => 'São Paulo',
        'state' => 'SP',
    ]);

    $livewire = Livewire::test(ManageAuditLogs::class)
        ->assertTableHeaderActionsExistInOrder(['export']);
    $exportAction = collect($livewire->instance()->getTable()->getHeaderActions())
        ->first(fn ($action): bool => $action instanceof ExportAction);
    $options = $exportAction->getOptions();
    $formats = $exportAction->getFormats();
    $contractAuditLog = AuditLog::query()
        ->where('auditable_type', Contract::class)
        ->firstOrFail();
    $columnMap = [
        'school_legal_name' => 'Razão social',
        'school_cnpj' => 'CNPJ',
        'school_address' => 'Endereço',
    ];
    $exporter = new AuditLogExporter(new Export, $columnMap, $options);
    $exportedRow = $exporter($contractAuditLog);

    expect($formats)->toHaveCount(2)
        ->and(collect($formats)->map(fn ($format): string => $format->value)->all())->toBe(['csv', 'xlsx'])
        ->and(data_get($options, 'school.legal_name'))->toBe($school->legal_name)
        ->and(data_get($options, 'school.cnpj'))->toBe($school->cnpj)
        ->and($exportedRow[0])->toBe($school->legal_name)
        ->and($exportedRow[1])->toBe($school->cnpj)
        ->and($exportedRow[2])->toContain('Rua das Escolas');
});

test('partial and full installment write-offs are identifiable in the financial audit', function () {
    Carbon::setTestNow('2026-10-09 12:00:00');
    $operator = createAuditReportOperator('PAYMENT');
    $this->actingAs($operator);
    $contract = createAuditReportContract('PAYMENT');
    CashRegister::create([
        'name' => 'Caixa da auditoria',
        'status' => 'open',
        'opening_balance' => 0,
        'current_balance' => 0,
        'user_id' => $operator->id,
        'opened_at' => now(),
    ]);
    $installment = Installment::create([
        'contract_id' => $contract->id,
        'installment_number' => 1,
        'total_installments' => 2,
        'amount' => 100,
        'due_date' => '2026-10-20',
        'status' => 'pending',
    ]);

    $installment->processarBaixa([
        'status' => 'partial',
        'amount' => 100,
        'discount' => 0,
        'fine' => 0,
        'amount_paid' => 40,
        'due_date' => '2026-10-20',
    ]);

    $paymentAudits = $installment->auditLogs()
        ->where('event', 'updated')
        ->get();

    expect($installment->fresh()->status)->toBe('paid')
        ->and($paymentAudits)->toHaveCount(2)
        ->and($paymentAudits->pluck('user_id')->unique()->all())->toBe([$operator->id])
        ->and($paymentAudits->pluck('created_at')->every(fn ($date): bool => $date !== null))->toBeTrue()
        ->and($paymentAudits->map(fn (AuditLog $auditLog): string => $auditLog->actionLabel())->all())
        ->toBe(['Baixa parcial registrada', 'Baixa de parcela registrada']);
});

test('audit resource works correctly when scoped to a tenant without querying invalid school_id column on people', function () {
    Carbon::setTestNow('2026-10-09 12:00:00');
    $operator = createAuditReportOperator('TENANT');
    $this->actingAs($operator);

    $school = School::create([
        'legal_name' => 'Escola Teste Tenant',
        'trade_name' => 'Escola Tenant',
        'cnpj' => '99.888.777/0001-66',
    ]);
    Filament::setTenant($school);

    $person = Person::create([
        'name' => 'Pessoa Tenant Audit',
        'cpf' => '11122233344',
    ]);
    $school->people()->attach($person->id);

    $auditLogPerson = AuditLog::create([
        'contract_id' => null,
        'user_id' => $operator->id,
        'auditable_type' => Person::class,
        'auditable_id' => $person->id,
        'event' => 'created',
        'changes' => ['after' => ['name' => 'Pessoa Tenant Audit']],
    ]);

    Livewire::test(ManageAuditLogs::class)
        ->set('activeTab', 'people')
        ->assertCanSeeTableRecords([$auditLogPerson]);
});
