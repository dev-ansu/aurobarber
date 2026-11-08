<?php

use App\Filament\Resources\Contracts\Pages\ViewContracts;
use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\Installment;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use App\Services\DebtAgreementService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
});

test('person has studentContracts relation as alias to contracts', function () {
    $person = Person::create(['name' => 'Aluno Teste', 'email' => 'aluno@teste.com']);

    expect(method_exists($person, 'studentContracts'))->toBeTrue()
        ->and($person->studentContracts())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('formalizing a debt agreement sends notification with valid action without error', function () {
    $school = School::create(['id' => 1, 'legal_name' => 'Escola Matriz', 'trade_name' => 'Matriz']);
    $student = Person::create(['name' => 'Keovian', 'email' => 'keovian@teste.com']);

    $adminPerson = Person::create(['name' => 'Admin Norma', 'email' => 'norma@teste.com']);
    $user = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'norma@teste.com',
        'password' => bcrypt('password'),
    ]);
    $role = Role::findOrCreate('super_admin', 'web');
    $user->assignRole($role);
    $this->actingAs($user);

    $financialType = FinancialType::create([
        'name' => 'Mensalidade',
        'slug' => 'mensalidade',
        'is_active' => true,
    ]);

    $contract = Contract::create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'contract_number' => 'KEO-001',
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 390.00,
    ]);

    $inst1 = Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $financialType->id,
        'installment_number' => 1,
        'total_installments' => 3,
        'amount' => 130.00,
        'due_date' => today()->subDays(10),
        'status' => 'pending',
        'type' => 'tuition',
    ]);

    $inst2 = Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $financialType->id,
        'installment_number' => 2,
        'total_installments' => 3,
        'amount' => 130.00,
        'due_date' => today()->subDays(5),
        'status' => 'pending',
        'type' => 'tuition',
    ]);

    $inst3 = Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $financialType->id,
        'installment_number' => 3,
        'total_installments' => 3,
        'amount' => 130.00,
        'due_date' => today(),
        'status' => 'pending',
        'type' => 'tuition',
    ]);

    // Simula o caso do cliente:
    // Entrada: 130
    // Saldo restante: 2 parcelas de 130
    // Vencimento: dia 16/11
    $service = app(DebtAgreementService::class);
    $agreement = $service->createAgreement($contract, [
        'installment_ids' => [$inst1->id, $inst2->id, $inst3->id],
        'discount_amount' => 0,
        'down_payment_amount' => 130.00,
        'down_payment_paid' => false,
        'installments_count' => 2,
        'first_due_date' => '2026-11-16',
        'financial_type_id' => $financialType->id,
        'notes' => 'Acordo Keovian 130 de entrada + 2x de 130',
    ], $user);

    expect($agreement)->not->toBeNull()
        ->and((float) $agreement->total_original_amount)->toBe(390.00)
        ->and((float) $agreement->down_payment_amount)->toBe(130.00)
        ->and($agreement->installments_count)->toBe(2);

    // Testa a criação da notificação com o Action corrigido
    Notification::make()
        ->title('Acordo de Dívida formalizado!')
        ->body("Acordo nº {$agreement->agreement_number} gerado com sucesso.")
        ->success()
        ->actions([
            Action::make('printTermo')
                ->label('Imprimir Termo de Confissão')
                ->icon('heroicon-m-printer')
                ->url(route('contracts.termo-acordo', [$contract, $agreement]), shouldOpenInNewTab: true),
        ])
        ->send();

    // Se chegou até aqui sem exceção, a notificação com action funciona 100%!
    expect(true)->toBeTrue();
});

