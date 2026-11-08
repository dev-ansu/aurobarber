<?php

use App\Enums\RelationshipEnum;
use App\Models\City;
use App\Models\Classroom;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Course;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('contract print route renders complete legal document with witnesses and schedule', function () {
    $state = State::create(['name' => 'São Paulo', 'acronym' => 'SP']);
    $city = City::create(['name' => 'Campinas', 'ibge_code' => '3509502', 'state_id' => $state->id]);

    $school = School::create([
        'legal_name' => 'Aurosoft Escola LTDA',
        'trade_name' => 'Aurosoft Treinamentos',
        'cnpj' => '12.345.678/0001-90',
        'city_id' => $city->id,
    ]);

    $student = Person::create([
        'name' => 'Lucas Estudanete',
        'cpf' => '111.222.333-44',
        'birth_date' => '2008-05-15',
        'email' => 'lucas@example.com',
    ]);
    $student->addresses()->create([
        'street' => 'Rua das Flores',
        'number' => '123',
        'neighborhood' => 'Centro',
        'city_id' => $city->id,
        'type' => 'residential',
        'is_primary' => true,
    ]);

    $responsiblePerson = Person::create([
        'name' => 'Marcos Pai',
        'cpf' => '555.666.777-88',
        'email' => 'marcos@example.com',
    ]);
    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Father,
        'financial_responsible' => true,
    ]);

    $contractType = ContractType::create([
        'name' => 'Contrato Padrão 2026',
        'version' => '2.0',
        'content' => '<p>Cláusula 1: O aluno {aluno} se compromete a comparecer às aulas.</p>',
        'is_active' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-2026-TEST',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'contract_type_id' => $contractType->id,
        'start_date' => '2026-03-01',
        'total_value' => 2400.00,
        'discount_value' => 200.00,
        'status' => 'active',
    ]);

    $formation = Formation::create([
        'name' => 'Formação TI Completo',
        'active' => true,
    ]);
    $course1 = Course::create(['name' => 'Informática Básica', 'workload' => 40, 'active' => true]);
    $course2 = Course::create(['name' => 'Programação Web', 'workload' => 60, 'active' => true]);
    $formation->courses()->attach([$course1->id, $course2->id]);

    $contractItem = $contract->items()->create([
        'formation_id' => $formation->id,
    ]);

    $classroom = Classroom::create(['name' => 'Laboratório 1', 'capacity' => 15, 'active' => true]);
    $contractItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => 6, // Sábado
        'start_hour' => '09:00',
        'end_hour' => '10:00',
    ]);

    $contract->installments()->create([
        'installment_number' => 1,
        'total_installments' => 12,
        'amount' => 200.00,
        'due_date' => '2026-03-10',
        'status' => 'pending',
    ]);

    $user = User::create([
        'person_id' => $responsiblePerson->id,
        'email' => 'admin-print@example.com',
        'password' => 'secret',
    ]);

    $response = $this->actingAs($user)->get(route('contracts.print', $contract));

    $response->assertOk()
        ->assertSee('Aurosoft Treinamentos')
        ->assertSee('Lucas Estudanete')
        ->assertSee('111.222.333-44')
        ->assertSee('Marcos Pai')
        ->assertSee('555.666.777-88')
        ->assertSee('Formação TI Completo')
        ->assertSee('Informática Básica')
        ->assertSee('Programação Web')
        ->assertSee('100 horas') // 40 + 60
        ->assertSee('Sábado')
        ->assertSee('Laboratório 1')
        ->assertSee('R$ 2.400,00')
        ->assertSee('Testemunhas')
        ->assertSee('Art. 784, III do Código de Processo Civil');
});

test('contract print renders without error even if school state is missing or null', function () {
    $school = School::create([
        'legal_name' => 'Escola Sem Estado LTDA',
        'trade_name' => 'Escola Sem Estado',
        'city' => 'Interior',
        'state' => null,
    ]);

    $student = Person::create(['name' => 'Aluno Teste', 'email' => 'sem.estado@example.com']);
    $contract = Contract::create([
        'contract_number' => 'CTR-SEM-ESTADO',
        'student_id' => $student->id,
        'start_date' => '2026-03-01',
        'total_value' => 1000.00,
        'status' => 'active',
        'school_snapshot' => ['trade_name' => 'Escola Sem Estado'],
    ]);

    $user = User::create(['person_id' => $student->id, 'email' => 'admin-no-state@example.com', 'password' => 'secret']);

    $response = $this->actingAs($user)->get(route('contracts.print', $contract));
    $response->assertOk()->assertSee('Escola Sem Estado');
});
