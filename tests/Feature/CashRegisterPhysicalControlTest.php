<?php

use App\Filament\Resources\CashRegisters\Pages\ListCashRegisters;
use App\Models\CashRegister;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('cash register supports suprimento, sangria, and closing with physical conference', function () {
    $person = Person::create([
        'name' => 'Operador Teste',
        'email' => 'operador@example.com',
    ]);
    $user = User::create([
        'person_id' => $person->id,
        'email' => 'operador@example.com',
        'password' => 'secret',
    ]);
    $user->givePermissionTo([
        Permission::findOrCreate('ViewAny:CashRegister', 'web'),
        Permission::findOrCreate('View:CashRegister', 'web'),
        Permission::findOrCreate('Update:CashRegister', 'web'),
    ]);

    $moneyMethod = PaymentMethod::create([
        'name' => 'Dinheiro',
        'slug' => 'dinheiro',
        'is_active' => true,
    ]);

    $caixa = CashRegister::create([
        'name' => 'Caixa Recepção 01',
        'status' => 'open',
        'opening_balance' => 100.00,
        'current_balance' => 100.00,
        'user_id' => $user->id,
        'opened_at' => now(),
    ]);

    $this->actingAs($user);

    // 1. Testa Suprimento (+ R$ 50,00)
    Livewire::test(ListCashRegisters::class)
        ->callTableAction('suprimento', $caixa, data: [
            'amount' => 50.00,
            'description' => 'Troco moedas',
        ]);

    $caixa->refresh();
    expect((float) $caixa->current_balance)->toEqual(150.00)
        ->and((float) $caixa->expectedCashBalance())->toEqual(150.00)
        ->and($caixa->transactions()->count())->toBe(1)
        ->and($caixa->transactions()->first()->type)->toBe('input')
        ->and((float) $caixa->transactions()->first()->amount)->toEqual(50.00);

    // 2. Testa Sangria (- R$ 30,00)
    Livewire::test(ListCashRegisters::class)
        ->callTableAction('sangria', $caixa, data: [
            'amount' => 30.00,
            'description' => 'Retirada cofre',
        ]);

    $caixa->refresh();
    expect((float) $caixa->current_balance)->toEqual(120.00)
        ->and((float) $caixa->expectedCashBalance())->toEqual(120.00)
        ->and($caixa->transactions()->count())->toBe(2)
        ->and($caixa->transactions()->latest('id')->first()->type)->toBe('output');

    // 3. Testa Fechamento com Conferência (esperado: R$ 120, contado: R$ 125 -> sobra de R$ 5)
    Livewire::test(ListCashRegisters::class)
        ->callTableAction('fecharCaixa', $caixa, data: [
            'counted_cash' => 125.00,
            'closing_notes' => 'Sobra de moedas de 1 real',
        ]);

    $caixa->refresh();
    expect($caixa->status)->toBe('closed')
        ->and($caixa->closed_at)->not->toBeNull()
        ->and((float) $caixa->counted_cash)->toEqual(125.00)
        ->and((float) $caixa->difference)->toEqual(5.00)
        ->and($caixa->closing_notes)->toBe('Sobra de moedas de 1 real');

    // 4. Testa relatório de caixa
    $response = $this->get(route('admin.caixa.relatorio', $caixa));
    $response->assertOk()
        ->assertSee('Caixa Recepção 01')
        ->assertSee('R$ 100,00')
        ->assertSee('R$ 125,00')
        ->assertSee('Sobra')
        ->assertSee('Troco moedas')
        ->assertSee('Retirada cofre');
});
