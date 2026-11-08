<?php

use App\Filament\Resources\Users\UserResource;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('UserResource, CashRegisters and CashTransactions do not query non-existent users.name column', function (): void {
    $person = Person::create([
        'name' => 'Operador Sistema',
        'email' => 'operador@example.com',
    ]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => 'operador@example.com',
        'password' => 'secret123',
    ]);

    // Test record title and global search attributes on UserResource
    expect(UserResource::getRecordTitle($user))->toBe('Operador Sistema')
        ->and(UserResource::getGloballySearchableAttributes())->toContain('email', 'person.name');

    // Test CashRegister operator filter options
    $register = CashRegister::create([
        'name' => 'Caixa Principal',
        'status' => 'open',
        'opening_balance' => 100,
        'current_balance' => 100,
        'user_id' => $user->id,
        'opened_at' => now(),
    ]);

    $transaction = CashTransaction::create([
        'cash_register_id' => $register->id,
        'user_id' => $user->id,
        'type' => 'input',
        'amount' => 50,
        'description' => 'Aporte inicial',
    ]);

    expect($register->operator->name)->toBe('Operador Sistema')
        ->and($transaction->user->name)->toBe('Operador Sistema');
});
