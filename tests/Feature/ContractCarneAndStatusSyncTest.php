<?php

use App\Enums\RelationshipEnum;
use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\Installment;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'services.ouro_moderno.url' => 'http://lms.test',
        'services.ouro_moderno.token' => 'token-test',
    ]);
});

test('unauthenticated users cannot view contract carne', function () {
    $student = Person::create(['name' => 'Aluno Carne', 'email' => 'aluno.carne@example.com']);
    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);
    $contract = Contract::create([
        'contract_number' => 'CARNE-001',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'total_value' => 600,
        'status' => 'active',
    ]);

    $response = $this->get(route('contracts.carne', $contract));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view contract carne in landscape format with installments', function () {
    $userPerson = Person::create(['name' => 'Admin Usuário', 'email' => 'admin@example.com']);
    $user = User::create(['person_id' => $userPerson->id, 'email' => 'admin@example.com', 'password' => 'password']);
    $role = Role::findOrCreate('Super Admin');
    $user->assignRole($role);

    $school = School::create([
        'legal_name' => 'Escola Modelo Ltda',
        'trade_name' => 'Aurosoft Academy',
        'cnpj' => '12.345.678/0001-90',
        'phone' => '(11) 98765-4321',
        'email' => 'contato@aurosoft.com',
        'city' => 'São Paulo',
        'state' => 'SP',
    ]);

    $student = Person::create([
        'name' => 'Mariana Silva',
        'email' => 'mariana@example.com',
        'cpf' => '123.456.789-00',
    ]);

    $respPerson = Person::create([
        'name' => 'Carlos Silva',
        'email' => 'carlos@example.com',
        'cpf' => '987.654.321-99',
    ]);

    $responsible = Responsible::create([
        'person_id' => $respPerson->id,
        'relationship' => RelationshipEnum::Father,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CARNE-2026',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'total_value' => 450,
        'status' => 'active',
    ]);

    $finType = FinancialType::create([
        'name' => 'Mensalidade',
        'slug' => 'mensalidade',
        'type' => 'income',
        'is_active' => true,
    ]);

    Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $finType->id,
        'installment_number' => 1,
        'total_installments' => 3,
        'amount' => 150.00,
        'due_date' => now()->addMonth()->toDateString(),
        'status' => 'pending',
    ]);

    Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $finType->id,
        'installment_number' => 2,
        'total_installments' => 3,
        'amount' => 150.00,
        'due_date' => now()->addMonths(2)->toDateString(),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('contracts.carne', $contract));

    $response->assertOk();
    $response->assertSee('A4 landscape');
    $response->assertSee('Aurosoft Academy');
    $response->assertSee('Mariana Silva');
    $response->assertSee('Carlos Silva');
    $response->assertSee('CARNE-2026');
    $response->assertSee('Via do Caixa / Escola');
    $response->assertSee('150,00');
    $response->assertSee('window.print()');
    $response->assertSee('✂');
});

test('contract status change to finished updates student in LMS to inactive (N) if no other active contracts', function () {
    Http::fake(['lms.test/*' => Http::response(['ok' => true])]);

    $student = Person::create([
        'name' => 'Aluno LMS',
        'email' => 'aluno.lms@example.com',
        'ouro_moderno_cod_int' => 5924,
    ]);

    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'LMS-001',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'total_value' => 500,
        'status' => 'active',
    ]);

    // O contrato foi criado como ativo -> envia 'ativo' => 'S'
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/alunos/atualizar/')
        && (int) $request['cod_int'] === 5924
        && $request['ativo'] === 'S'
    );

    // Muda o status para 'finished'
    $contract->changeStatus('finished', 'Curso concluído');

    // Como o aluno não possui outro contrato ativo, deve enviar 'ativo' => 'N'
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/alunos/atualizar/')
        && (int) $request['cod_int'] === 5924
        && $request['ativo'] === 'N'
    );
});

test('contract status change keeps student active (S) in LMS if student has another active contract', function () {
    Http::fake(['lms.test/*' => Http::response(['ok' => true])]);

    $student = Person::create([
        'name' => 'Aluno Multi Contratos',
        'email' => 'multi@example.com',
        'ouro_moderno_cod_int' => 8899,
    ]);

    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract1 = Contract::create([
        'contract_number' => 'LMS-101',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'total_value' => 500,
        'status' => 'active',
    ]);

    $contract2 = Contract::create([
        'contract_number' => 'LMS-102',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'total_value' => 500,
        'status' => 'active',
    ]);

    // Cancela o contrato 1
    $contract1->changeStatus('canceled', 'Desistência deste curso');

    // O aluno ainda tem o contrato 2 ativo! Portanto deve enviar 'ativo' => 'S'
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/alunos/atualizar/')
        && (int) $request['cod_int'] === 8899
        && $request['ativo'] === 'S'
    );
});

test('authenticated user can access profile page', function () {
    $person = Person::create([
        'name' => 'Admin Usuário',
        'email' => 'admin.profile@example.com',
    ]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => 'admin.profile@example.com',
        'password' => 'password',
    ]);

    $response = $this->actingAs($user)->get('/admin/profile');

    $response->assertOk();
    $response->assertSee('Meu perfil');
    $response->assertSee('Admin Usuário');
});
