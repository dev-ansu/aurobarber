<?php

use App\Enums\RelationshipEnum;
use App\Models\CashRegister;
use App\Models\City;
use App\Models\Classroom;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\ContractType;
use App\Models\Course;
use App\Models\DebtAgreement;
use App\Models\FinancialType;
use App\Models\Formation;
use App\Models\Installment;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\State;
use App\Models\User;
use App\Services\DebtAgreementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createTestContractWithEntities(): array
{
    $state = State::create(['name' => 'São Paulo', 'acronym' => 'SP']);
    $city = City::create(['name' => 'Campinas', 'ibge_code' => '3509502', 'state_id' => $state->id]);

    $school = School::create([
        'legal_name' => 'Aurosoft Cursos LTDA',
        'trade_name' => 'Aurosoft Treinamentos',
        'cnpj' => '12.345.678/0001-90',
        'city' => 'Campinas',
        'state' => 'SP',
        'pix_key' => '12345678000190',
        'pix_merchant_name' => 'Aurosoft Cursos',
        'pix_merchant_city' => 'Campinas',
        'pix_enabled' => true,
    ]);

    $userPerson = Person::create([
        'name' => 'Operador Secretaria',
        'email' => 'operador@aurosoft.com',
    ]);

    $user = User::create([
        'person_id' => $userPerson->id,
        'email' => 'operador@aurosoft.com',
        'password' => 'password',
    ]);

    $student = Person::create([
        'name' => 'Ana Clara Silva',
        'cpf' => '123.456.789-00',
        'rg' => '45.678.901-2',
        'birth_date' => '2007-08-20',
        'email' => 'anaclara@example.com',
    ]);

    $student->addresses()->create([
        'street' => 'Avenida Brasil',
        'number' => '500',
        'neighborhood' => 'Jardim Guanabara',
        'city_id' => $city->id,
        'type' => 'residential',
        'is_primary' => true,
    ]);

    $student->contacts()->create([
        'type' => 'cellphone',
        'value' => '(19) 98765-4321',
        'is_primary' => true,
    ]);

    $responsiblePerson = Person::create([
        'name' => 'Carlos Alberto Silva',
        'cpf' => '987.654.321-99',
        'rg' => '23.456.789-0',
        'email' => 'carlos@example.com',
    ]);

    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Father,
        'financial_responsible' => true,
    ]);

    $contractType = ContractType::create([
        'name' => 'Contrato Profissionalizante',
        'version' => '1.0',
        'content' => '<p>Termos gerais...</p>',
        'is_active' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-2026-001',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'contract_type_id' => $contractType->id,
        'start_date' => '2026-02-01',
        'total_value' => 1800.00,
        'discount_value' => 0.00,
        'status' => 'active',
    ]);

    $formation = Formation::create(['name' => 'Formação em Informática e Gestão', 'active' => true]);
    $course1 = Course::create(['name' => 'Operador de Computador', 'workload' => 40, 'active' => true]);
    $course2 = Course::create(['name' => 'Excel Avançado', 'workload' => 30, 'active' => true]);
    $formation->courses()->attach([$course1->id, $course2->id]);

    $item = ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
        'value' => 1800.00,
    ]);

    $contractCourse1 = ContractCourse::create([
        'contract_item_id' => $item->id,
        'course_id' => $course1->id,
        'status' => 'completed',
        'grade' => 9.5,
        'completed_at' => '2026-04-10 12:00:00',
    ]);

    $contractCourse2 = ContractCourse::create([
        'contract_item_id' => $item->id,
        'course_id' => $course2->id,
        'status' => 'completed',
        'grade' => 8.8,
        'completed_at' => '2026-06-20 12:00:00',
    ]);

    $classroom = Classroom::create(['name' => 'Laboratório 1', 'capacity' => 15, 'is_active' => true]);

    $item->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 1,
        'start_hour' => '14:00',
        'end_hour' => '15:00',
    ]);

    $financialType = FinancialType::create(['name' => 'Mensalidade', 'type' => 'income']);
    $paymentMethod = PaymentMethod::create(['name' => 'Dinheiro', 'is_active' => true]);

    return compact(
        'school',
        'user',
        'student',
        'responsible',
        'contract',
        'formation',
        'course1',
        'course2',
        'contractCourse1',
        'contractCourse2',
        'financialType',
        'paymentMethod'
    );
}

test('contract certificate renders front and back with modules, grades, workload, period and authenticity QR code', function () {
    $data = createTestContractWithEntities();
    $contract = $data['contract'];
    $user = $data['user'];

    $response = $this->actingAs($user)->get(route('contracts.certificate', $contract));

    $response->assertOk();
    // Página 1 (Frente): Certificado solene
    $response->assertSee('Certificado de Conclusão');
    $response->assertSee('Ana Clara Silva');
    $response->assertSee('Formação em Informática e Gestão');
    $response->assertSee('70 horas'); // 40 + 30

    // Página 2 (Verso): Histórico Escolar Oficial com módulos, notas e carga horária
    $response->assertSee('Histórico Escolar');
    $response->assertSee('Conteúdo Programático');
    $response->assertSee('Operador de Computador');
    $response->assertSee('Excel Avançado');
    $response->assertSee('9,5');
    $response->assertSee('8,8');
    $response->assertSee('9,2'); // Média geral
    $response->assertSee('01/02/2026');
    $response->assertSee('20/06/2026');
    $response->assertSee('Lei Federal nº 9.394/1996');
    $response->assertSee('Decreto Federal nº 5.154/2004');
    $response->assertSee('svg'); // QR Code vetorial presente

    // Também funciona para emissão de curso individual
    $courseResponse = $this->actingAs($user)->get(route('contracts.certificate.course', [$contract, $data['contractCourse1']]));
    $courseResponse->assertOk();
    $courseResponse->assertSee('Operador de Computador');
    $courseResponse->assertSee('40 horas');
});

test('declaracao de matricula e frequencia renders official document with student, workload and schedule', function () {
    $data = createTestContractWithEntities();
    $contract = $data['contract'];
    $user = $data['user'];

    $response = $this->actingAs($user)->get(route('contracts.declaracao-matricula', $contract));

    $response->assertOk();
    $response->assertSee('Declaração de Matrícula e Frequência');
    $response->assertSee('Ana Clara Silva');
    $response->assertSee('123.456.789-00');
    $response->assertSee('Formação em Informática e Gestão');
    $response->assertSee('70 horas');
    $response->assertSee('Segunda-feira das 14:00 às 15:00 (Laboratório 1)');
    $response->assertSee('Aurosoft Treinamentos');
});

test('declaracao de quitacao anual debits renders according to Lei 12.007/2009 with paid installments breakdown', function () {
    $data = createTestContractWithEntities();
    $contract = $data['contract'];
    $user = $data['user'];
    $finType = $data['financialType'];

    // Cria parcelas pagas no ano de 2025
    Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $finType->id,
        'installment_number' => 1,
        'total_installments' => 2,
        'amount' => 150.00,
        'amount_paid' => 150.00,
        'due_date' => '2025-05-10',
        'paid_at' => '2025-05-08',
        'status' => 'paid',
    ]);
    Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $finType->id,
        'installment_number' => 2,
        'total_installments' => 2,
        'amount' => 150.00,
        'amount_paid' => 150.00,
        'due_date' => '2025-06-10',
        'paid_at' => '2025-06-10',
        'status' => 'paid',
    ]);

    $response = $this->actingAs($user)->get(route('contracts.declaracao-quitacao', [$contract, 'year' => 2025]));

    $response->assertOk();
    $response->assertSee('Declaração de Quitação Anual de Débitos');
    $response->assertSee('Lei Federal nº 12.007/2009');
    $response->assertSee('2025');
    $response->assertSee('Ana Clara Silva');
    $response->assertSee('R$ 150,00');
    $response->assertSee('QUITOU INTEGRALMENTE');
});

test('ficha cadastral de matricula dossie do aluno renders registration sheet with 3x4 photo box, lgpd terms and signatures', function () {
    $data = createTestContractWithEntities();
    $contract = $data['contract'];
    $user = $data['user'];

    $response = $this->actingAs($user)->get(route('contracts.ficha-matricula', $contract));

    $response->assertOk();
    $response->assertSee('FICHA CADASTRAL DE MATRÍCULA');
    $response->assertSee('DOSSIÊ DO ALUNO');
    $response->assertSee('FOTO 3X4');
    $response->assertSee('Ana Clara Silva');
    $response->assertSee('Carlos Alberto Silva');
    $response->assertSee('Avenida Brasil');
    $response->assertSee('Campinas');
    $response->assertSee('LGPD - Lei 13.709/2018');
    $response->assertSee('Autorização de Uso de Imagem e Voz');
});

test('debt agreement service renegotiates multiple overdue installments, cancels old, creates new and updates cash register when down payment is paid', function () {
    $data = createTestContractWithEntities();
    $contract = $data['contract'];
    $user = $data['user'];
    $finType = $data['financialType'];
    $paymentMethod = $data['paymentMethod'];

    // Cria caixa diário aberto para o usuário
    $cashRegister = CashRegister::create([
        'name' => 'Caixa Principal',
        'user_id' => $user->id,
        'opening_balance' => 100.00,
        'current_balance' => 100.00,
        'opened_at' => now(),
        'status' => 'open',
    ]);

    // Cria 3 parcelas em atraso (R$ 200 cada = R$ 600 total)
    $inst1 = Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $finType->id,
        'installment_number' => 1,
        'total_installments' => 3,
        'amount' => 200.00,
        'due_date' => '2026-01-10',
        'status' => 'pending',
    ]);
    $inst2 = Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $finType->id,
        'installment_number' => 2,
        'total_installments' => 3,
        'amount' => 200.00,
        'due_date' => '2026-02-10',
        'status' => 'pending',
    ]);
    $inst3 = Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $finType->id,
        'installment_number' => 3,
        'total_installments' => 3,
        'amount' => 200.00,
        'due_date' => '2026-03-10',
        'status' => 'pending',
    ]);

    $service = app(DebtAgreementService::class);
    $agreement = $service->createAgreement($contract, [
        'installment_ids' => [$inst1->id, $inst2->id, $inst3->id],
        'discount_amount' => 100.00, // Saldo líquido = 500.00
        'down_payment_amount' => 100.00, // Entrada = 100.00
        'down_payment_paid' => true,
        'payment_method_id' => $paymentMethod->id,
        'installments_count' => 2, // Saldo restante 400.00 / 2 = 2x R$ 200.00
        'first_due_date' => '2026-11-10',
        'notes' => 'Acordo firmado presencialmente com o pai do aluno.',
    ], $user);

    expect($agreement)->toBeInstanceOf(DebtAgreement::class)
        ->and($agreement->total_original_amount)->toEqual('600.00')
        ->and($agreement->discount_amount)->toEqual('100.00')
        ->and($agreement->total_agreed_amount)->toEqual('500.00')
        ->and($agreement->down_payment_amount)->toEqual('100.00')
        ->and($agreement->down_payment_paid)->toBeTrue()
        ->and($agreement->installments_count)->toBe(2);

    // As 3 parcelas originais foram canceladas
    expect($inst1->fresh()->status)->toBe('canceled')
        ->and($inst2->fresh()->status)->toBe('canceled')
        ->and($inst3->fresh()->status)->toBe('canceled');

    // Novas parcelas geradas: 1 entrada (paga) + 2 parcelas (pendentes de R$ 200)
    $newInstallments = $agreement->new_installments;
    expect($newInstallments)->toHaveCount(3);

    $downPaymentInst = $newInstallments->firstWhere('status', 'paid');
    expect($downPaymentInst)->not->toBeNull()
        ->and((float) $downPaymentInst->amount)->toBe(100.00)
        ->and((float) $downPaymentInst->amount_paid)->toBe(100.00);

    $pendingInsts = $newInstallments->where('status', 'pending');
    expect($pendingInsts)->toHaveCount(2);
    expect((float) $pendingInsts->sum('amount'))->toBe(400.00);

    // Caixa diário incrementado e transação registrada
    $cashRegister->refresh();
    expect((float) $cashRegister->current_balance)->toBe(200.00); // 100 inicial + 100 entrada
    expect($cashRegister->transactions()->count())->toBe(1);

    // Registro no histórico do contrato gerado
    expect($contract->histories()->count())->toBeGreaterThanOrEqual(1);

    // Termo de confissão de dívida renderizado com sucesso
    $termoResponse = $this->actingAs($user)->get(route('contracts.termo-acordo', [$contract, $agreement]));
    $termoResponse->assertOk();
    $termoResponse->assertSee('Termo de Acordo e Confissão de Dívida');
    $termoResponse->assertSee('Artigo 784, Inciso III, do Código de Processo Civil');
    $termoResponse->assertSee($agreement->agreement_number);
    $termoResponse->assertSee('R$ 600,00'); // Total original
    $termoResponse->assertSee('R$ 100,00'); // Desconto e entrada
    $termoResponse->assertSee('R$ 500,00'); // Total acordado
    $termoResponse->assertSee('Carlos Alberto Silva'); // Responsável
    $termoResponse->assertSee('Testemunhas Instrumentárias');
    $termoResponse->assertSee('Campinas - SP');
});

test('documents dynamically resolve school city and state instead of hardcoded Sao Paulo', function () {
    $state = State::create(['name' => 'Minas Gerais', 'acronym' => 'MG']);
    $city = City::create(['name' => 'Belo Horizonte', 'ibge_code' => '3106200', 'state_id' => $state->id]);

    $school = School::create([
        'legal_name' => 'Escola Mineira LTDA',
        'trade_name' => 'Escola BH',
        'cnpj' => '99.888.777/0001-66',
        'city' => 'Belo Horizonte',
        'state' => 'MG',
        'pix_enabled' => true,
    ]);

    $userPerson = Person::create(['name' => 'Secretaria BH', 'email' => 'bh@example.com']);
    $user = User::create(['person_id' => $userPerson->id, 'email' => 'bh@example.com', 'password' => 'password']);

    $student = Person::create(['name' => 'Carlos Silva', 'cpf' => '987.654.321-11', 'email' => 'carlos@example.com']);
    $resp = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Legal_guardian,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'BH-2026-001',
        'student_id' => $student->id,
        'responsible_id' => $resp->id,
        'start_date' => '2026-01-10',
        'total_value' => 1500.00,
        'discount_value' => 0.00,
        'status' => 'active',
        // snapshot com city nulo para simular contrato criado antes do preenchimento da escola
        'school_snapshot' => [
            'trade_name' => 'Escola BH',
            'city' => null,
            'state' => null,
        ],
    ]);

    $response = $this->actingAs($user)->get(route('contracts.declaracao-matricula', $contract));
    $response->assertOk();
    $response->assertSee('Belo Horizonte - MG');
    $response->assertDontSee('São Paulo - SP');

    $certResponse = $this->actingAs($user)->get(route('contracts.certificate', $contract));
    $certResponse->assertOk();
    $certResponse->assertSee('Belo Horizonte - MG');
    $certResponse->assertDontSee('São Paulo - SP');
});
