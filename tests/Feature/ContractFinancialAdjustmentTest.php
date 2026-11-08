<?php

use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\Installment;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use App\Services\ContractFinancialAdjustmentService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
});

test('user can adjust contract value and recalculate pending installments from 130 to 1300 in 12x', function () {
    $school = School::create(['id' => 1, 'legal_name' => 'Escola Matriz', 'trade_name' => 'Matriz']);
    $student = Person::create(['name' => 'Aluno Teste', 'email' => 'aluno@teste.com']);

    $adminPerson = Person::create(['name' => 'Admin User', 'email' => 'admin@teste.com']);
    $admin = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'admin@teste.com',
        'password' => bcrypt('password'),
    ]);
    $role = Role::findOrCreate('super_admin', 'web');
    $admin->assignRole($role);
    $this->actingAs($admin);

    $financialType = FinancialType::create([
        'name' => 'Mensalidade',
        'slug' => 'mensalidade',
        'is_active' => true,
    ]);

    // Contrato cadastrado errado: 130 reais em 12x
    $contract = Contract::create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'contract_number' => 'MAT-130',
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 130.00,
    ]);

    for ($i = 1; $i <= 12; $i++) {
        Installment::create([
            'contract_id' => $contract->id,
            'financial_type_id' => $financialType->id,
            'installment_number' => $i,
            'total_installments' => 12,
            'amount' => 10.83,
            'due_date' => today()->addMonths($i - 1),
            'status' => 'pending',
            'type' => 'tuition',
        ]);
    }

    expect($contract->installments()->count())->toBe(12)
        ->and((float) $contract->total_value)->toBe(130.00);

    // Executa o ajuste para 1.300 em 12x
    $service = app(ContractFinancialAdjustmentService::class);
    $result = $service->adjust($contract, [
        'mode' => 'recalculate',
        'calculation_type' => 'divide',
        'new_total_value' => 1300.00,
        'qty_installments' => 12,
        'first_due_date' => today()->toDateString(),
        'financial_type_id' => $financialType->id,
        'reason' => 'Correção de valor: lançado R$ 130 por engano em vez de R$ 1.300',
    ]);

    $contract->refresh();

    // 1. Valor total do contrato foi corrigido
    expect((float) $contract->total_value)->toBe(1300.00)
        ->and($result['new_total'])->toBe(1300.00)
        ->and($result['deleted_count'])->toBe(12)
        ->and($result['generated_count'])->toBe(12);

    // 2. Novas parcelas somam exatamente 1.300,00
    $newInstallments = $contract->installments()->orderBy('installment_number')->get();
    expect($newInstallments->count())->toBe(12)
        ->and((float) $newInstallments->sum('amount'))->toBe(1300.00)
        ->and((float) $newInstallments->first()->amount)->toBe(108.37)
        ->and((float) $newInstallments->last()->amount)->toBe(108.33);

    // 3. Histórico gravado
    expect($contract->statusHistories()->count())->toBeGreaterThan(0)
        ->and($contract->observations)->toContain('1.300');
});

test('paid installments are preserved and remaining balance is recalculated proportionally', function () {
    $school = School::create(['id' => 1, 'legal_name' => 'Escola Matriz', 'trade_name' => 'Matriz']);
    $student = Person::create(['name' => 'Aluno Parcial', 'email' => 'parcial@teste.com']);

    $financialType = FinancialType::create([
        'name' => 'Mensalidade',
        'slug' => 'mensalidade',
        'is_active' => true,
    ]);

    $contract = Contract::create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'contract_number' => 'MAT-PAGA',
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 130.00,
    ]);

    // Parcela 1 PAGA (R$ 10,83)
    $paidInst = Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $financialType->id,
        'installment_number' => 1,
        'total_installments' => 12,
        'amount' => 10.83,
        'amount_paid' => 10.83,
        'due_date' => today(),
        'paid_at' => today(),
        'status' => 'paid',
        'type' => 'tuition',
    ]);

    // Parcelas 2 a 12 PENDENTES
    for ($i = 2; $i <= 12; $i++) {
        Installment::create([
            'contract_id' => $contract->id,
            'financial_type_id' => $financialType->id,
            'installment_number' => $i,
            'total_installments' => 12,
            'amount' => 10.83,
            'due_date' => today()->addMonths($i - 1),
            'status' => 'pending',
            'type' => 'tuition',
        ]);
    }

    // Ajusta o contrato para R$ 1.300 em 11 novas parcelas para o saldo restante (1300 - 10.83 = 1289.17)
    $service = app(ContractFinancialAdjustmentService::class);
    $result = $service->adjust($contract, [
        'mode' => 'recalculate',
        'calculation_type' => 'divide',
        'new_total_value' => 1300.00,
        'qty_installments' => 11,
        'first_due_date' => today()->addMonth()->toDateString(),
        'financial_type_id' => $financialType->id,
        'reason' => 'Ajuste após pagamento da 1ª parcela',
    ]);

    $contract->refresh();

    // A parcela 1 paga continua existindo
    expect(Installment::find($paidInst->id))->not->toBeNull()
        ->and(Installment::find($paidInst->id)->status)->toBe('paid');

    // Total de parcelas agora: 1 paga + 11 novas = 12
    $allInstallments = $contract->installments()->get();
    expect($allInstallments->count())->toBe(12)
        ->and(round((float) $allInstallments->sum('amount'), 2))->toBe(1300.00)
        ->and((float) $contract->total_value)->toBe(1300.00);
});

test('cannot adjust contract total to less than what was already paid', function () {
    $school = School::create(['id' => 1, 'legal_name' => 'Escola Matriz', 'trade_name' => 'Matriz']);
    $student = Person::create(['name' => 'Aluno Bloqueio', 'email' => 'bloqueio@teste.com']);

    $contract = Contract::create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'contract_number' => 'MAT-BLQ',
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 500.00,
    ]);

    Installment::create([
        'contract_id' => $contract->id,
        'installment_number' => 1,
        'total_installments' => 1,
        'amount' => 500.00,
        'amount_paid' => 500.00,
        'due_date' => today(),
        'paid_at' => today(),
        'status' => 'paid',
        'type' => 'tuition',
    ]);

    $service = app(ContractFinancialAdjustmentService::class);

    expect(fn () => $service->adjust($contract, [
        'mode' => 'recalculate',
        'new_total_value' => 300.00,
        'qty_installments' => 1,
    ]))->toThrow(DomainException::class);
});
