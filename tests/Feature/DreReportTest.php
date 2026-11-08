<?php

use App\Enums\RelationshipEnum;
use App\Filament\Pages\DreReport;
use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\Installment;
use App\Models\Payable;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use App\Services\DreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('dre service calculates gross revenue, deductions, net revenue, expenses, and net result', function () {
    Carbon::setTestNow('2026-10-16 12:00:00');

    $student = Person::create(['name' => 'Aluno DRE', 'email' => 'dre.aluno@example.com']);
    $respPerson = Person::create(['name' => 'Resp DRE', 'email' => 'dre.resp@example.com']);
    $resp = Responsible::create([
        'person_id' => $respPerson->id,
        'relationship' => RelationshipEnum::Father,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-DRE-001',
        'student_id' => $student->id,
        'responsible_id' => $resp->id,
        'start_date' => '2026-10-01',
        'total_value' => 2000,
        'status' => 'active',
    ]);

    $typeMensalidade = FinancialType::create(['name' => 'Mensalidade Escolar', 'type' => 'receita']);
    $typeAluguel = FinancialType::create(['name' => 'Aluguel do Prédio', 'type' => 'despesa']);
    $typeSalario = FinancialType::create(['name' => 'Folha de Pagamento', 'type' => 'despesa']);

    // Receita 1: Mensalidade de R$ 500, com desconto de R$ 50 e multa de R$ 10 -> amount_paid = 460
    Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $typeMensalidade->id,
        'installment_number' => 1,
        'total_installments' => 4,
        'amount' => 500.00,
        'discount' => 50.00,
        'fine' => 10.00,
        'amount_paid' => 460.00,
        'due_date' => '2026-10-10',
        'paid_at' => '2026-10-12',
        'status' => 'paid',
    ]);

    // Receita 2: Mensalidade de R$ 500 paga integralmente
    Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $typeMensalidade->id,
        'installment_number' => 2,
        'total_installments' => 4,
        'amount' => 500.00,
        'discount' => 0.00,
        'fine' => 0.00,
        'amount_paid' => 500.00,
        'due_date' => '2026-10-15',
        'paid_at' => '2026-10-15',
        'status' => 'paid',
    ]);

    // Despesa 1: Aluguel R$ 300
    Payable::create([
        'description' => 'Aluguel Outubro',
        'financial_type_id' => $typeAluguel->id,
        'amount' => 300.00,
        'amount_paid' => 300.00,
        'due_date' => '2026-10-05',
        'payment_date' => '2026-10-05',
        'status' => 'paid',
    ]);

    // Despesa 2: Salários R$ 400
    Payable::create([
        'description' => 'Salários Professores',
        'financial_type_id' => $typeSalario->id,
        'amount' => 400.00,
        'amount_paid' => 400.00,
        'due_date' => '2026-10-10',
        'payment_date' => '2026-10-10',
        'status' => 'paid',
    ]);

    $service = app(DreService::class);
    $dre = $service->generate(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'), 'caixa');

    // Receita Bruta = 500 + 500 + 10 = 1010
    expect((float) $dre['revenue']['gross'])->toEqual(1010.00);
    // Deduções = 50
    expect((float) $dre['revenue']['deductions'])->toEqual(50.00);
    // Receita Líquida (paga) = 460 + 500 = 960
    expect((float) $dre['revenue']['net'])->toEqual(960.00);
    // Despesas Totais = 300 + 400 = 700
    expect((float) $dre['expenses']['total'])->toEqual(700.00);
    // Resultado Líquido = 960 - 700 = 260
    expect((float) $dre['result']['net_amount'])->toEqual(260.00);
    expect($dre['result']['is_profit'])->toBeTrue();
    // Margem: 260 / 960 = 27.08%
    expect((float) $dre['result']['operating_margin'])->toEqual(27.08);

    // Grupos de despesa
    expect($dre['expenses']['by_type'])->toHaveCount(2);
});

test('dre report page renders executive cards, accounting table and responds to period filters', function () {
    Carbon::setTestNow('2026-10-16 12:00:00');

    $adminRole = Role::findOrCreate('super_admin', 'web');
    $userPerson = Person::create(['name' => 'Diretor Financeiro', 'email' => 'diretor@example.com']);
    $user = User::create([
        'person_id' => $userPerson->id,
        'email' => 'diretor@example.com',
        'password' => 'secret',
    ]);
    $user->assignRole($adminRole);
    $this->actingAs($user);

    expect(DreReport::canAccess())->toBeTrue();

    Livewire::test(DreReport::class)
        ->assertSee('DEMONSTRATIVO DO RESULTADO DO EXERCÍCIO')
        ->assertSee('RECEITA OPERACIONAL BRUTA')
        ->assertSee('RECEITA OPERACIONAL LÍQUIDA')
        ->assertSee('DESPESAS E CUSTOS OPERACIONAIS')
        ->assertSee('RESULTADO OPERACIONAL LÍQUIDO')
        ->call('setQuickPeriod', 'last_month')
        ->assertOk();

    // Testa rota de impressão limpa A4 sem sidebar
    $response = $this->get(route('admin.dre.print', [
        'start' => '2026-10-01',
        'end' => '2026-10-31',
        'regime' => 'caixa',
    ]));

    $response->assertOk()
        ->assertSee('Demonstrativo do Resultado do Exercício (DRE)')
        ->assertSee('RECEITA OPERACIONAL BRUTA')
        ->assertSee('RECEITA OPERACIONAL LÍQUIDA')
        ->assertSee('DESPESAS E CUSTOS OPERACIONAIS')
        ->assertSee('RESULTADO OPERACIONAL LÍQUIDO')
        ->assertSee('Direção Geral / Mantenedor')
        ->assertDontSee('fi-sidebar')
        ->assertDontSee('fi-topbar');
});
