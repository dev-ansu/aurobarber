<?php

use App\Enums\RelationshipEnum;
use App\Filament\Pages\SalesBySellerReport;
use App\Models\Collaborator;
use App\Models\CommercialLead;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\FinancialType;
use App\Models\Formation;
use App\Models\Installment;
use App\Models\Payable;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\User;
use App\Services\CurrencyInWords;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeSellerTestUser(string $name, string $email, string $roleName = 'super_admin'): User
{
    $person = Person::create(['name' => $name, 'email' => $email]);
    $user = User::create([
        'person_id' => $person->id,
        'email' => $email,
        'password' => bcrypt('password'),
    ]);

    $role = Role::findOrCreate($roleName, 'web');
    $user->assignRole($role);

    return $user;
}

test('currency in words converts monetary values correctly to portuguese', function () {
    expect(CurrencyInWords::toWords(1.00))->toBe('um real')
        ->and(CurrencyInWords::toWords(2.00))->toBe('dois reais')
        ->and(CurrencyInWords::toWords(100.00))->toBe('cem reais')
        ->and(CurrencyInWords::toWords(150.00))->toBe('cento e cinquenta reais')
        ->and(CurrencyInWords::toWords(250.50))->toBe('duzentos e cinquenta reais e cinquenta centavos')
        ->and(CurrencyInWords::toWords(1200.00))->toBe('mil e duzentos reais')
        ->and(CurrencyInWords::toWords(1450.90))->toBe('mil quatrocentos e cinquenta reais e noventa centavos');
});

test('installment receipt route renders two copies with student, school and words details', function () {
    $user = makeSellerTestUser('Secretaria Teste', 'sec.recibo@example.com');
    $this->actingAs($user);

    $school = School::create([
        'legal_name' => 'Centro de Educação Profissionalizante Ltda',
        'trade_name' => 'Aurosoft Tecnologia',
        'cnpj' => '22.333.444/0001-55',
        'phone' => '(41) 3333-4444',
        'email' => 'financeiro@aurosoft.com',
        'city' => 'Curitiba',
        'state' => 'PR',
    ]);

    $student = Person::create(['name' => 'Gabriel Henrique Recibo', 'email' => 'gabriel.recibo@example.com']);
    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-RECIBO-01',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => today()->toDateString(),
        'total_value' => 1500.00,
        'status' => 'active',
    ]);

    $formation = Formation::create(['name' => 'Programador Full Stack', 'active' => true]);
    ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
    ]);

    $paymentMethod = PaymentMethod::create(['name' => 'Pix', 'is_active' => true]);
    $financialType = FinancialType::create(['name' => 'Mensalidade', 'is_active' => true]);

    $installment = Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $financialType->id,
        'payment_method_id' => $paymentMethod->id,
        'installment_number' => 2,
        'total_installments' => 6,
        'amount' => 250.00,
        'amount_paid' => 250.00,
        'due_date' => today()->subDays(5)->toDateString(),
        'paid_at' => today()->toDateString(),
        'status' => 'paid',
    ]);

    $response = $this->get(route('installments.receipt', $installment));

    $response->assertOk();
    $response->assertSee('Aurosoft Tecnologia');
    $response->assertSee('Gabriel Henrique Recibo');
    $response->assertSee('CTR-RECIBO-01');
    $response->assertSee('Programador Full Stack');
    $response->assertSee('1ª Via · Aluno');
    $response->assertSee('2ª Via · Arquivo da Escola');
    $response->assertSee('R$ 250,00');
    $response->assertSee('duzentos e cinquenta reais');
});

test('payable supports recurring generation of future occurrences', function () {
    $user = makeSellerTestUser('Gestor Contas', 'gestor.contas@example.com');
    $this->actingAs($user);

    $finType = FinancialType::create(['name' => 'Aluguel e Condomínio', 'is_active' => true]);

    $initialDueDate = today()->toDateString();

    $payable = Payable::create([
        'description' => 'Aluguel Sala 102',
        'supplier' => 'Imobiliária Alvorada',
        'financial_type_id' => $finType->id,
        'amount' => 1800.00,
        'due_date' => $initialDueDate,
        'status' => 'pending',
        'is_recurring' => true,
        'recurrence_period' => 'monthly',
        'recurrence_limit' => 3,
    ]);

    $occurrences = $payable->generateRecurringOccurrences(3, 'monthly');

    expect($occurrences)->toHaveCount(2);

    // Primeira conta atualizada com descrição (1/3)
    $payable->refresh();
    expect($payable->description)->toBe('Aluguel Sala 102 (1/3)')
        ->and($payable->recurrence_group_id)->not->toBeNull();

    // Próximas 2 contas geradas com vencimentos mensais
    $second = $occurrences[0];
    $third = $occurrences[1];

    expect($second->description)->toBe('Aluguel Sala 102 (2/3)')
        ->and($second->recurrence_group_id)->toBe($payable->recurrence_group_id)
        ->and($second->due_date->toDateString())->toBe(today()->addMonth()->toDateString())
        ->and((float) $second->amount)->toBe(1800.00)
        ->and($third->description)->toBe('Aluguel Sala 102 (3/3)')
        ->and($third->due_date->toDateString())->toBe(today()->addMonths(2)->toDateString());
});

test('payable allows attaching receipt and paying expense', function () {
    $user = makeSellerTestUser('Operador Pagamentos', 'op.pagto@example.com');
    $this->actingAs($user);

    $finType = FinancialType::create(['name' => 'Internet e Telefonia', 'is_active' => true]);
    $payMethod = PaymentMethod::create(['name' => 'Transferência Bancária', 'is_active' => true]);

    $payable = Payable::create([
        'description' => 'Internet Fibra Óptica',
        'supplier' => 'Telecom Net',
        'financial_type_id' => $finType->id,
        'amount' => 220.00,
        'due_date' => today()->toDateString(),
        'status' => 'pending',
    ]);

    $payable->pay([
        'amount_paid' => 220.00,
        'payment_date' => today()->toDateString(),
        'payment_method_id' => $payMethod->id,
        'receipt_path' => 'payables/receipts/comprovante-telecom.pdf',
    ]);

    $payable->refresh();

    expect($payable->status)->toBe('paid')
        ->and((float) $payable->amount_paid)->toBe(220.00)
        ->and($payable->receipt_path)->toBe('payables/receipts/comprovante-telecom.pdf');
});

test('sales by seller report computes performance metrics and exports csv correctly', function () {
    $user = makeSellerTestUser('Diretor Comercial', 'diretor.comercial@example.com');
    $this->actingAs($user);

    // Cria vendedor
    $personCollab = Person::create(['name' => 'Mariana Consultora', 'email' => 'mariana.consultora@example.com']);
    $collab = Collaborator::create([
        'person_id' => $personCollab->id,
        'role' => 'Consultora de Vendas',
        'admission_date' => today()->subYear()->toDateString(),
        'active' => true,
    ]);

    // Cria leads atribuídos
    CommercialLead::create([
        'name' => 'Lead 1 Mariana',
        'phone' => '(41) 99999-1111',
        'status' => 'contacted',
        'collaborator_id' => $collab->id,
        'created_at' => today(),
    ]);
    CommercialLead::create([
        'name' => 'Lead 2 Mariana',
        'phone' => '(41) 99999-2222',
        'status' => 'enrolled',
        'collaborator_id' => $collab->id,
        'created_at' => today(),
    ]);

    // Cria aluno e contrato fechado
    $student = Person::create(['name' => 'Aluno Matriculado Mariana', 'email' => 'aluno.mariana@example.com']);
    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'MAT-MARIANA-01',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'collaborator_id' => $collab->id,
        'start_date' => today()->toDateString(),
        'total_value' => 2000.00,
        'discount_value' => 200.00,
        'status' => 'active',
        'created_at' => today(),
    ]);

    $formation = Formation::create(['name' => 'Informática Empresarial', 'active' => true]);
    ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
    ]);

    $finType = FinancialType::create(['name' => 'Mensalidade', 'is_active' => true]);

    // Parcela paga
    Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $finType->id,
        'installment_number' => 1,
        'total_installments' => 2,
        'amount' => 1000.00,
        'amount_paid' => 1000.00,
        'due_date' => today()->toDateString(),
        'paid_at' => today()->toDateString(),
        'status' => 'paid',
    ]);

    // Parcela em aberto
    Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $finType->id,
        'installment_number' => 2,
        'total_installments' => 2,
        'amount' => 1000.00,
        'due_date' => today()->addMonth()->toDateString(),
        'status' => 'pending',
    ]);

    $report = new SalesBySellerReport;
    $report->fromDate = today()->subDays(5)->toDateString();
    $report->untilDate = today()->toDateString();
    $report->collaboratorId = (string) $collab->id;

    $sellers = $report->sellersReport();
    expect($sellers)->toHaveCount(1);

    $mariana = $sellers[0];
    expect($mariana['name'])->toBe('Mariana Consultora')
        ->and($mariana['role'])->toBe('Consultora de Vendas')
        ->and($mariana['leads_count'])->toBe(2)
        ->and($mariana['contracts_count'])->toBe(1)
        ->and($mariana['conversion_rate'])->toBe(50.0) // 1 contrato / 2 leads = 50%
        ->and((float) $mariana['total_sales'])->toBe(2000.00)
        ->and((float) $mariana['total_discounts'])->toBe(200.00)
        ->and((float) $mariana['average_ticket'])->toBe(2000.00)
        ->and((float) $mariana['total_paid'])->toBe(1000.00)
        ->and((float) $mariana['total_pending'])->toBe(1000.00)
        ->and($mariana['contracts'])->toHaveCount(1);

    $metrics = $report->summaryMetrics();
    expect($metrics['total_contracts'])->toBe(1)
        ->and((float) $metrics['total_sales'])->toBe(2000.00)
        ->and((float) $metrics['total_paid'])->toBe(1000.00);

    // Testa exportação de CSV
    $csvResponse = $report->exportCsv();
    expect($csvResponse->getStatusCode())->toBe(200)
        ->and($csvResponse->headers->get('Content-Type'))->toContain('text/csv');

    ob_start();
    $csvResponse->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('Mariana Consultora')
        ->and($content)->toContain('Consultora de Vendas')
        ->and($content)->toContain('2.000,00')
        ->and($content)->toContain('1.000,00');
});
