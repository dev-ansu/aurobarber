<?php

use App\Filament\Resources\CashRegisters\Pages\ListCashRegisters;
use App\Filament\Resources\CashTransactions\Pages\CreateCashTransactions;
use App\Models\CashRegister;
use App\Models\FinancialType;
use App\Models\Payable;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('payable payment atomically records transaction and decrements cash register balance within db transaction', function () {
    $person = Person::create(['name' => 'Financeiro', 'email' => 'fin@example.com']);
    $user = User::create(['person_id' => $person->id, 'email' => 'fin@example.com', 'password' => 'secret']);
    $type = FinancialType::create(['name' => 'Despesas Gerais', 'slug' => 'despesas_gerais', 'type' => 'expense', 'is_active' => true]);
    $method = PaymentMethod::create(['name' => 'Dinheiro', 'slug' => 'dinheiro', 'is_active' => true]);

    $caixa = CashRegister::create([
        'name' => 'Caixa Principal',
        'status' => 'open',
        'opening_balance' => 500.00,
        'current_balance' => 500.00,
        'user_id' => $user->id,
        'opened_at' => now(),
    ]);

    $payable = Payable::create([
        'description' => 'Conta de Luz',
        'amount' => 150.00,
        'due_date' => today(),
        'status' => 'pending',
        'financial_type_id' => $type->id,
        'created_by_id' => $user->id,
    ]);

    $this->actingAs($user);

    $payable->pay([
        'amount_paid' => 150.00,
        'payment_date' => today()->toDateString(),
        'payment_method_id' => $method->id,
        'cash_register_id' => $caixa->id,
    ]);

    $payable->refresh();
    $caixa->refresh();

    expect($payable->status)->toBe('paid')
        ->and((float) $payable->amount_paid)->toEqual(150.00)
        ->and((float) $caixa->current_balance)->toEqual(350.00)
        ->and($caixa->transactions()->count())->toBe(1)
        ->and($caixa->transactions()->first()->type)->toBe('output')
        ->and((float) $caixa->transactions()->first()->amount)->toEqual(150.00);
});

test('payable recurring generation executes atomically within transaction', function () {
    $person = Person::create(['name' => 'Financeiro 2', 'email' => 'fin2@example.com']);
    $user = User::create(['person_id' => $person->id, 'email' => 'fin2@example.com', 'password' => 'secret']);
    $type = FinancialType::create(['name' => 'Aluguel', 'slug' => 'aluguel', 'type' => 'expense', 'is_active' => true]);

    $payable = Payable::create([
        'description' => 'Aluguel Predial',
        'amount' => 1200.00,
        'due_date' => today(),
        'status' => 'pending',
        'financial_type_id' => $type->id,
        'is_recurring' => true,
        'recurrence_period' => 'monthly',
        'recurrence_limit' => 3,
        'created_by_id' => $user->id,
    ]);

    $this->actingAs($user);

    $occurrences = $payable->generateRecurringOccurrences(3, 'monthly');

    expect(count($occurrences))->toBe(2)
        ->and(Payable::where('recurrence_group_id', $payable->recurrence_group_id)->count())->toBe(3);
});

test('create cash transactions page atomically updates cash register balance', function () {
    $person = Person::create(['name' => 'Operador', 'email' => 'op@example.com']);
    $user = User::create(['person_id' => $person->id, 'email' => 'op@example.com', 'password' => 'secret']);
    $user->givePermissionTo([
        Permission::findOrCreate('Create:CashTransaction', 'web'),
        Permission::findOrCreate('ViewAny:CashTransaction', 'web'),
    ]);
    $method = PaymentMethod::create(['name' => 'Dinheiro', 'slug' => 'dinheiro', 'is_active' => true]);

    $caixa = CashRegister::create([
        'name' => 'Caixa 01',
        'status' => 'open',
        'opening_balance' => 200.00,
        'current_balance' => 200.00,
        'user_id' => $user->id,
        'opened_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test(CreateCashTransactions::class)
        ->fillForm([
            'cash_register_id' => $caixa->id,
            'type' => 'input',
            'amount' => 80.00,
            'payment_method_id' => $method->id,
            'description' => 'Entrada avulsa',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $caixa->refresh();
    expect((float) $caixa->current_balance)->toEqual(280.00)
        ->and($caixa->transactions()->count())->toBe(1);
});

