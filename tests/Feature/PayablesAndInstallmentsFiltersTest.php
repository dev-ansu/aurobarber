<?php

use App\Filament\Resources\Installments\Pages\ListInstallments;
use App\Filament\Resources\Payables\Pages\ManagePayables;
use App\Models\CashRegister;
use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\Installment;
use App\Models\Payable;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::create([
        'id' => 1,
        'legal_name' => 'Escola Matriz',
        'trade_name' => 'Matriz',
    ]);
    Filament::setTenant($this->school, isQuiet: true);
});

test('payables table has above content collapsible filters and defaults to current month', function () {
    $role = Role::findOrCreate('super_admin', 'web');
    $permission = Permission::findOrCreate('ViewAny:Payable', 'web');
    $person = Person::create(['name' => 'Admin Payables', 'email' => 'admin.payables@example.com']);
    $user = User::create([
        'person_id' => $person->id,
        'email' => 'admin.payables@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole($role);
    $user->givePermissionTo($permission);
    $this->actingAs($user);

    Carbon::setTestNow('2026-10-15 12:00:00');

    $type = FinancialType::create(['name' => 'Aluguel', 'is_active' => true]);

    // Current month payable
    $currentMonthPayable = Payable::create([
        'school_id' => $this->school->id,
        'description' => 'Aluguel Outubro',
        'amount' => 1500.00,
        'due_date' => '2026-10-20',
        'status' => 'pending',
        'financial_type_id' => $type->id,
    ]);

    // Last month payable
    $lastMonthPayable = Payable::create([
        'school_id' => $this->school->id,
        'description' => 'Aluguel Setembro',
        'amount' => 1500.00,
        'due_date' => '2026-09-10',
        'status' => 'pending',
        'financial_type_id' => $type->id,
    ]);

    // Next month payable
    $nextMonthPayable = Payable::create([
        'school_id' => $this->school->id,
        'description' => 'Aluguel Novembro',
        'amount' => 1500.00,
        'due_date' => '2026-11-05',
        'status' => 'pending',
        'financial_type_id' => $type->id,
    ]);

    // Test default: should show only current month
    Livewire::test(ManagePayables::class)
        ->assertCanSeeTableRecords([$currentMonthPayable])
        ->assertCanNotSeeTableRecords([$lastMonthPayable, $nextMonthPayable])
        // Test switching to last month
        ->filterTable('due_date_range', [
            'quick_period' => 'last_month',
            'from' => '2026-09-01',
            'until' => '2026-09-30',
        ])
        ->assertCanSeeTableRecords([$lastMonthPayable])
        ->assertCanNotSeeTableRecords([$currentMonthPayable, $nextMonthPayable])
        // Test switching to next month
        ->filterTable('due_date_range', [
            'quick_period' => 'next_month',
            'from' => '2026-11-01',
            'until' => '2026-11-30',
        ])
        ->assertCanSeeTableRecords([$nextMonthPayable])
        ->assertCanNotSeeTableRecords([$currentMonthPayable, $lastMonthPayable]);
});

test('installments table has quick period filters and defaults to current month', function () {
    $role = Role::findOrCreate('super_admin', 'web');
    $permission = Permission::findOrCreate('ViewAny:Installment', 'web');
    $adminPerson = Person::create(['name' => 'Admin Inst', 'email' => 'admin.inst@example.com']);
    $user = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'admin.inst@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole($role);
    $user->givePermissionTo($permission);
    $this->actingAs($user);

    Carbon::setTestNow('2026-10-15 12:00:00');

    $person = Person::create(['name' => 'Aluno Filtro', 'email' => 'filtro@example.com']);
    $contract = Contract::create([
        'school_id' => $this->school->id,
        'student_id' => $person->id,
        'contract_number' => 'CTR-FILTRO-01',
        'status' => 'active',
        'start_date' => '2026-08-01',
    ]);

    $type = FinancialType::create(['name' => 'Mensalidade', 'is_active' => true]);

    $currentInstallment = Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $type->id,
        'amount' => 250.00,
        'due_date' => '2026-10-10',
        'status' => 'pending',
        'installment_number' => 1,
        'total_installments' => 12,
    ]);

    $lastMonthInstallment = Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $type->id,
        'amount' => 250.00,
        'due_date' => '2026-09-10',
        'status' => 'pending',
        'installment_number' => 2,
        'total_installments' => 12,
    ]);

    $nextMonthInstallment = Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $type->id,
        'amount' => 250.00,
        'due_date' => '2026-11-10',
        'status' => 'pending',
        'installment_number' => 3,
        'total_installments' => 12,
    ]);

    Livewire::test(ListInstallments::class)
        ->assertCanSeeTableRecords([$currentInstallment])
        ->assertCanNotSeeTableRecords([$lastMonthInstallment, $nextMonthInstallment])
        ->filterTable('due_date_range', [
            'quick_period' => 'last_month',
            'from' => '2026-09-01',
            'until' => '2026-09-30',
        ])
        ->assertCanSeeTableRecords([$lastMonthInstallment])
        ->assertCanNotSeeTableRecords([$currentInstallment, $nextMonthInstallment])
        ->filterTable('due_date_range', [
            'quick_period' => 'next_month',
            'from' => '2026-11-01',
            'until' => '2026-11-30',
        ])
        ->assertCanSeeTableRecords([$nextMonthInstallment])
        ->assertCanNotSeeTableRecords([$currentInstallment, $lastMonthInstallment]);
});

test('payable partial payment marks current as paid and generates new remaining payable', function () {
    $role = Role::findOrCreate('super_admin', 'web');
    $adminPerson = Person::create(['name' => 'Admin Partial', 'email' => 'admin.partial@example.com']);
    $user = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'admin.partial@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole($role);
    $this->actingAs($user);

    $type = FinancialType::create(['name' => 'Manutenção', 'is_active' => true]);
    $method = PaymentMethod::create(['name' => 'PIX', 'is_active' => true]);

    $payable = Payable::create([
        'description' => 'Reforma da Sala 3',
        'supplier' => 'Construtora Exemplo',
        'amount' => 1000.00,
        'due_date' => '2026-10-30',
        'status' => 'pending',
        'financial_type_id' => $type->id,
    ]);

    // Baixa parcial de R$ 400,00
    $remaining = $payable->pay([
        'payment_date' => '2026-10-15',
        'amount_paid' => 400.00,
        'payment_method_id' => $method->id,
        'remaining_due_date' => '2026-11-15',
    ]);

    $payable->refresh();

    // Conta original vira paga com valor 400.00
    expect($payable->status)->toBe('paid')
        ->and((float) $payable->amount)->toEqual(400.00)
        ->and((float) $payable->amount_paid)->toEqual(400.00)
        ->and($payable->notes)->toContain('Baixa parcial')
        // Nova conta criada para o saldo restante de R$ 600,00
        ->and($remaining)->not->toBeNull()
        ->and($remaining->status)->toBe('pending')
        ->and((float) $remaining->amount)->toEqual(600.00)
        ->and($remaining->description)->toBe('Reforma da Sala 3 (Saldo restante)')
        ->and($remaining->due_date->format('Y-m-d'))->toBe('2026-11-15');
});

test('installments table pay action allows quick payment and partial split with custom remaining due date', function () {
    $role = Role::findOrCreate('super_admin', 'web');
    $permission = Permission::findOrCreate('ViewAny:Installment', 'web');
    $adminPerson = Person::create(['name' => 'Admin PayAction', 'email' => 'admin.payaction@example.com']);
    $user = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'admin.payaction@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole($role);
    $user->givePermissionTo($permission);
    $this->actingAs($user);

    Carbon::setTestNow('2026-10-15 12:00:00');

    $person = Person::create(['name' => 'Aluno Baixa', 'email' => 'baixa@example.com']);
    $contract = Contract::create([
        'school_id' => $this->school->id,
        'student_id' => $person->id,
        'contract_number' => 'CTR-BAIXA-01',
        'status' => 'active',
        'start_date' => '2026-10-01',
    ]);

    $type = FinancialType::create(['name' => 'Mensalidade', 'is_active' => true]);
    $method = PaymentMethod::create(['name' => 'Dinheiro', 'is_active' => true]);

    $cashRegister = CashRegister::create([
        'user_id' => $user->id,
        'name' => 'Caixa Teste',
        'status' => 'open',
        'opening_balance' => 0.00,
        'current_balance' => 0.00,
        'opened_at' => now(),
    ]);

    $installment = Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $type->id,
        'amount' => 300.00,
        'due_date' => '2026-10-20',
        'status' => 'pending',
        'installment_number' => 1,
        'total_installments' => 5,
    ]);

    Livewire::test(ListInstallments::class)
        ->callTableAction('pay', $installment, data: [
            'cash_register_id' => $cashRegister->id,
            'amount_paid' => 100.00,
            'paid_at' => '2026-10-15',
            'payment_method_id' => $method->id,
            'remaining_due_date' => '2026-11-20',
            'discount' => 0.00,
            'fine' => 0.00,
        ])
        ->assertHasNoTableActionErrors();

    $installment->refresh();

    expect($installment->status)->toBe('paid')
        ->and((float) $installment->amount)->toEqual(100.00)
        ->and((float) $installment->amount_paid)->toEqual(100.00);

    // Verify remaining installment
    $remaining = Installment::where('contract_id', $contract->id)
        ->where('id', '!=', $installment->id)
        ->first();

    expect($remaining)->not->toBeNull()
        ->and($remaining->status)->toBe('pending')
        ->and((float) $remaining->amount)->toEqual(200.00)
        ->and($remaining->due_date->format('Y-m-d'))->toBe('2026-11-20');
});

test('installments table pay action halts if no open cash register exists', function () {
    $role = Role::findOrCreate('super_admin', 'web');
    $permission = Permission::findOrCreate('ViewAny:Installment', 'web');
    $adminPerson = Person::create(['name' => 'Admin NoBox', 'email' => 'admin.nobox@example.com']);
    $user = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'admin.nobox@example.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole($role);
    $user->givePermissionTo($permission);
    $this->actingAs($user);

    CashRegister::where('status', 'open')->update(['status' => 'closed']);

    $person = Person::create(['name' => 'Aluno NoBox', 'email' => 'nobox@example.com']);
    $contract = Contract::create([
        'school_id' => $this->school->id,
        'student_id' => $person->id,
        'contract_number' => 'CTR-NOBOX-01',
        'status' => 'active',
        'start_date' => '2026-10-01',
    ]);
    $type = FinancialType::create(['name' => 'Mensalidade', 'is_active' => true]);

    $installment = Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $type->id,
        'amount' => 150.00,
        'due_date' => '2026-10-20',
        'status' => 'pending',
        'installment_number' => 1,
        'total_installments' => 1,
    ]);

    Livewire::test(ListInstallments::class)
        ->callTableAction('pay', $installment);

    $installment->refresh();
    expect($installment->status)->toBe('pending');
});
