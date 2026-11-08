<?php

use App\Filament\Resources\Contracts\Pages\CreateContracts;
use App\Models\Collaborator;
use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('user can create contract directly as active or draft without screen freezing', function () {
    $personUser = Person::create(['name' => 'Admin User', 'email' => 'admin@test.com']);
    $user = User::create(['person_id' => $personUser->id, 'email' => 'admin@test.com', 'password' => 'secret']);
    $user->givePermissionTo([
        Permission::findOrCreate('ViewAny:Contract', 'web'),
        Permission::findOrCreate('Create:Contract', 'web'),
        Permission::findOrCreate('View:Contract', 'web'),
    ]);

    $studentPerson = Person::create(['name' => 'Aluno Teste', 'email' => 'aluno@test.com']);
    $respPerson = Person::create(['name' => 'Resp Teste', 'email' => 'resp@test.com']);
    $responsible = Responsible::create(['person_id' => $respPerson->id, 'relationship' => \App\Enums\RelationshipEnum::Father]);
    $finType = FinancialType::create(['name' => 'Mensalidade', 'slug' => 'tuition', 'is_active' => true]);

    $this->actingAs($user);

    // Cria diretamente com status 'active'
    Livewire::test(CreateContracts::class)
        ->fillForm([
            'status' => 'active',
            'student_id' => $studentPerson->id,
            'responsible_id' => $responsible->id,
            'start_date' => now()->toDateString(),
            'total_value' => 600.00,
            'qty_installments' => 2,
            'simulation_financial_type_id' => $finType->id,
            'installments' => [
                [
                    'financial_type_id' => $finType->id,
                    'installment_number' => 1,
                    'total_installments' => 2,
                    'amount' => 300.00,
                    'due_date' => now()->toDateString(),
                ],
                [
                    'financial_type_id' => $finType->id,
                    'installment_number' => 2,
                    'total_installments' => 2,
                    'amount' => 300.00,
                    'due_date' => now()->addMonth()->toDateString(),
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $contract = Contract::first();
    expect($contract)->not->toBeNull()
        ->and($contract->status)->toBe('active')
        ->and($contract->student_id)->toBe($studentPerson->id)
        ->and($contract->installments()->count())->toBe(2);
});
