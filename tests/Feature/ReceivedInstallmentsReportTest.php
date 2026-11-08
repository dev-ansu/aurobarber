<?php

declare(strict_types=1);

use App\Filament\Exports\ReceivedInstallmentExporter;
use App\Filament\Pages\ReceivedInstallmentsReport;
use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\Installment;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

it('authorizes super_admin to access the Contas Recebidas report page', function () {
    $school = School::create([
        'legal_name' => 'Escola Matriz LTDA',
        'trade_name' => 'Aurosoft Centro',
        'is_headquarters' => true,
    ]);

    $person = Person::create(['name' => 'Admin User', 'email' => 'admin@teste.com']);
    $user = User::factory()->create([
        'person_id' => $person->id,
        'email' => 'admin@teste.com',
    ]);
    $user->assignRole('super_admin');

    $this->withoutExceptionHandling()
        ->actingAs($user)
        ->get("/admin/{$school->id}/contas-recebidas")
        ->assertSuccessful();
});

it('filters paid installments, calculates KPIs, and isolates enrollment down payment and tuition', function () {
    $school = School::create([
        'legal_name' => 'Escola Fortaleza LTDA',
        'trade_name' => 'Aurosoft Fortaleza',
        'is_headquarters' => true,
    ]);

    $student = Person::create([
        'name' => 'Maria Eduarda',
        'email' => 'maria@teste.com',
        'cpf' => '12345678901',
        'birth_date' => '2008-05-10',
    ]);

    $respPerson = Person::create([
        'name' => 'Carlos Silva',
        'email' => 'carlos@teste.com',
        'cpf' => '98765432100',
    ]);

    $responsible = Responsible::create([
        'person_id' => $respPerson->id,
        'relationship' => 'father',
    ]);

    $contract = Contract::create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'contract_number' => 'CTR-2026-001',
        'total_value' => 2400.00,
        'start_date' => now()->startOfMonth()->toDateString(),
        'status' => 'active',
    ]);

    $tipoMatricula = FinancialType::create([
        'name' => 'Taxa de Matrícula',
        'slug' => 'taxa_de_matricula',
        'is_active' => true,
    ]);

    $tipoMensalidade = FinancialType::create([
        'name' => 'Mensalidade',
        'slug' => 'mensalidade',
        'is_active' => true,
    ]);

    $metodoPix = PaymentMethod::create([
        'name' => 'PIX',
        'is_active' => true,
    ]);

    // 1. Entrada / Matrícula PAGA no mês atual
    Installment::create([
        'school_id' => $school->id,
        'contract_id' => $contract->id,
        'financial_type_id' => $tipoMatricula->id,
        'payment_method_id' => $metodoPix->id,
        'installment_number' => 1,
        'total_installments' => 12,
        'amount' => 200.00,
        'discount' => 0.00,
        'fine' => 0.00,
        'amount_paid' => 200.00,
        'due_date' => now()->startOfMonth()->toDateString(),
        'paid_at' => now()->startOfMonth()->toDateString(),
        'status' => 'paid',
    ]);

    // 2. Primeira mensalidade PAGA no mês atual
    Installment::create([
        'school_id' => $school->id,
        'contract_id' => $contract->id,
        'financial_type_id' => $tipoMensalidade->id,
        'payment_method_id' => $metodoPix->id,
        'installment_number' => 2,
        'total_installments' => 12,
        'amount' => 200.00,
        'discount' => 0.00,
        'fine' => 0.00,
        'amount_paid' => 200.00,
        'due_date' => now()->toDateString(),
        'paid_at' => now()->toDateString(),
        'status' => 'paid',
    ]);

    // 3. Mensalidade futura PENDENTE (NÃO deve entrar no relatório de contas recebidas)
    Installment::create([
        'school_id' => $school->id,
        'contract_id' => $contract->id,
        'financial_type_id' => $tipoMensalidade->id,
        'installment_number' => 3,
        'total_installments' => 12,
        'amount' => 200.00,
        'due_date' => now()->addMonth()->toDateString(),
        'status' => 'pending',
    ]);

    $adminPerson = Person::create(['name' => 'Super Admin', 'email' => 'super@admin.com']);
    $user = User::factory()->create([
        'person_id' => $adminPerson->id,
        'email' => 'super@admin.com',
    ]);
    $user->assignRole('super_admin');

    $this->actingAs($user);

    // Testa os cálculos da página via Livewire
    Livewire::test(ReceivedInstallmentsReport::class)
        ->set('periodStart', now()->startOfMonth()->toDateString())
        ->set('periodEnd', now()->endOfMonth()->toDateString())
        ->assertSee('Maria Eduarda')
        ->assertSee('Carlos Silva')
        ->assertSee('CTR-2026-001')
        ->assertSee('Taxa de Matrícula')
        ->assertSee('Mensalidade');

    // Valida o cálculo de entrada (down payment) do contrato
    $downPayment = ReceivedInstallmentsReport::calculateContractDownPayment($contract);
    expect($downPayment)->toBe(200.0);

    // Valida o total pago
    $totalPaid = ReceivedInstallmentsReport::calculateContractTotalPaid($contract);
    expect($totalPaid)->toBe(400.0);

    // Valida formatação de CPF
    expect(ReceivedInstallmentsReport::formatCpf('12345678901'))->toBe('123.456.789-01')
        ->and(ReceivedInstallmentsReport::formatCpf('98765432100'))->toBe('987.654.321-00');
});

it('renders the printable received installments report view', function () {
    $school = School::create([
        'legal_name' => 'Escola Central LTDA',
        'trade_name' => 'Aurosoft Central',
        'cnpj' => '12.345.678/0001-90',
        'is_headquarters' => true,
    ]);

    $person = Person::create(['name' => 'Admin Print', 'email' => 'print@admin.com']);
    $user = User::factory()->create([
        'person_id' => $person->id,
        'email' => 'print@admin.com',
    ]);
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->get(route('admin.contas-recebidas.print', [
            'start' => now()->startOfMonth()->toDateString(),
            'end' => now()->endOfMonth()->toDateString(),
            'school_id' => $school->id,
        ]))
        ->assertSuccessful()
        ->assertSee('Relatório de Contas Recebidas', false)
        ->assertSee('Aurosoft Central');
});

it('has proper columns configured on ReceivedInstallmentExporter', function () {
    $columns = ReceivedInstallmentExporter::getColumns();

    expect($columns)->toBeArray()
        ->and(count($columns))->toBeGreaterThanOrEqual(10);

    $columnLabels = array_map(fn ($col) => $col->getLabel(), $columns);

    expect($columnLabels)->toContain('Nº Contrato')
        ->and($columnLabels)->toContain('Data Recebimento')
        ->and($columnLabels)->toContain('Valor Recebido (R$)')
        ->and($columnLabels)->toContain('Nome do Aluno')
        ->and($columnLabels)->toContain('CPF do Aluno')
        ->and($columnLabels)->toContain('Tomador Fiscal (ISS)')
        ->and($columnLabels)->toContain('CPF Tomador Fiscal (ISS)');
});

it('renders the fiscal sheet modal with both student and responsible data for ISS Fortaleza', function () {
    $school = School::create([
        'legal_name' => 'Escola Fiscal LTDA',
        'trade_name' => 'Aurosoft Fiscal',
        'is_headquarters' => true,
    ]);

    $student = Person::create([
        'name' => 'Lucas Aluno',
        'email' => 'lucas@teste.com',
        'cpf' => '11122233344',
        'birth_date' => '2005-01-15',
    ]);

    $respPerson = Person::create([
        'name' => 'Ana Paula Responsavel',
        'email' => 'anapaula@teste.com',
        'cpf' => '55566677788',
    ]);

    $responsible = Responsible::create([
        'person_id' => $respPerson->id,
        'relationship' => 'mother',
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'contract_number' => 'CTR-FISCAL-99',
        'total_value' => 1500.00,
        'start_date' => now()->toDateString(),
        'status' => 'active',
    ]);

    $tipo = FinancialType::create([
        'name' => 'Mensalidade',
        'slug' => 'mensalidade',
        'is_active' => true,
    ]);

    $installment = Installment::create([
        'school_id' => $school->id,
        'contract_id' => $contract->id,
        'financial_type_id' => $tipo->id,
        'installment_number' => 1,
        'total_installments' => 6,
        'amount' => 250.00,
        'amount_paid' => 250.00,
        'due_date' => now()->toDateString(),
        'paid_at' => now()->toDateString(),
        'status' => 'paid',
    ]);

    $view = view('filament.pages.actions.installment-fiscal-sheet', [
        'installment' => $installment,
        'contract' => $contract,
        'student' => $student,
        'responsible' => $responsible,
        'downPayment' => 250.00,
        'totalPaid' => 250.00,
    ])->render();

    expect($view)->toContain('Ana Paula Responsavel')
        ->and($view)->toContain('555.666.777-88')
        ->and($view)->toContain('Lucas Aluno')
        ->and($view)->toContain('111.222.333-44')
        ->and($view)->toContain('Dados do Responsável Financeiro')
        ->and($view)->toContain('Tomador do Serviço (Dados para o Portal do ISS Fortaleza)')
        ->and($view)->toContain('Discriminação dos Serviços');
});
