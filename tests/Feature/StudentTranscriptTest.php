<?php

use App\Enums\RelationshipEnum;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('student academic transcript renders clean official layout with attendance and grades', function () {
    $person = Person::create(['name' => 'Secretaria Admin', 'email' => 'admin.boletim@example.com']);
    $user = User::create(['person_id' => $person->id, 'email' => 'admin.boletim@example.com', 'password' => bcrypt('password')]);
    $role = Role::findOrCreate('super_admin', 'web');
    $user->assignRole($role);
    $this->actingAs($user);

    $school = School::create([
        'legal_name' => 'Escola Técnica Vocacional Modelo Ltda',
        'trade_name' => 'Aurosoft Treinamentos',
        'cnpj' => '33.444.555/0001-66',
        'phone' => '(41) 3222-1111',
        'email' => 'secretaria@aurosoft.com',
        'city' => 'Curitiba',
        'state' => 'PR',
    ]);

    $student = Person::create(['name' => 'Marcos Vinicius Boletim', 'email' => 'marcos.boletim@example.com']);
    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-BOL-2026',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => today()->subMonths(3)->toDateString(),
        'end_date' => today()->addMonths(3)->toDateString(),
        'total_value' => 2400.00,
        'status' => 'active',
    ]);

    $formation = Formation::create(['name' => 'Desenvolvimento de Sistemas', 'active' => true]);
    $courseA = Course::create(['name' => 'Lógica de Programação', 'workload' => 40]);
    $courseB = Course::create(['name' => 'Banco de Dados SQL', 'workload' => 60]);
    $formation->courses()->attach([$courseA->id, $courseB->id]);

    $item = ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
    ]);

    $ccA = ContractCourse::create([
        'contract_item_id' => $item->id,
        'course_id' => $courseA->id,
        'workload' => 40,
        'grade' => 9.5,
        'status' => 'completed',
        'completed_at' => today()->subMonth(),
    ]);

    $ccB = ContractCourse::create([
        'contract_item_id' => $item->id,
        'course_id' => $courseB->id,
        'workload' => 60,
        'grade' => 8.0,
        'status' => 'in_progress',
    ]);

    // Boletim Geral do Contrato
    $response = $this->get(route('contracts.ouro-moderno-transcript', $contract));

    $response->assertOk();
    $response->assertSee('Aurosoft Treinamentos');
    $response->assertSee('Marcos Vinicius Boletim');
    $response->assertSee('CTR-BOL-2026');
    $response->assertSee('Desenvolvimento de Sistemas');
    $response->assertSee('Lógica de Programação');
    $response->assertSee('Banco de Dados SQL');
    $response->assertSee('9,5');
    $response->assertSee('8,0');
    $response->assertSee('100 h');
    $response->assertSee('Coordenação Pedagógica');
    $response->assertSee('Secretaria Escolar');

    // Boletim Individual do Curso
    $courseResponse = $this->get(route('contracts.ouro-moderno-transcript.course', [$contract, $ccA]));
    $courseResponse->assertOk();
    $courseResponse->assertSee('Lógica de Programação');
    $courseResponse->assertSee('9,5');
});

test('student can access their own academic transcript through student.boletim', function () {
    $student = Person::create(['name' => 'Aluno Proprietario', 'email' => 'aluno.proprietario@example.com']);
    $studentUser = User::create([
        'person_id' => $student->id,
        'email' => 'aluno.proprietario@example.com',
        'password' => bcrypt('password'),
        'panel_access' => false,
    ]);

    $resp = Responsible::create(['person_id' => $student->id, 'relationship' => RelationshipEnum::Outro, 'financial_responsible' => true]);
    $contract = Contract::create([
        'contract_number' => 'MATR-OWN-99',
        'student_id' => $student->id,
        'responsible_id' => $resp->id,
        'start_date' => today()->toDateString(),
        'total_value' => 1200.00,
        'status' => 'active',
    ]);

    $formation = Formation::create(['name' => 'Robótica e IA', 'active' => true]);
    $course = Course::create(['name' => 'Introdução à Robótica', 'workload' => 40]);
    $formation->courses()->attach([$course->id]);
    $item = ContractItem::create(['contract_id' => $contract->id, 'formation_id' => $formation->id]);
    $cc = ContractCourse::create([
        'contract_item_id' => $item->id,
        'course_id' => $course->id,
        'grade' => 9.0,
        'status' => 'completed',
    ]);

    $this->actingAs($studentUser);

    // Acesso com parâmetro do contrato
    $response = $this->get(route('student.boletim', $contract));
    $response->assertOk();
    $response->assertSee('Aluno Proprietario');
    $response->assertSee('Robótica e IA');
    $response->assertSee('Introdução à Robótica');

    // Acesso direto sem parâmetro (resolve o contrato do aluno logado)
    $responseDirect = $this->get(route('student.boletim'));
    $responseDirect->assertOk();
    $responseDirect->assertSee('Aluno Proprietario');
});

test('student is forbidden from accessing another student transcript', function () {
    $studentA = Person::create(['name' => 'Aluno A', 'email' => 'aluno.a@example.com']);
    $userA = User::create(['person_id' => $studentA->id, 'email' => 'aluno.a@example.com', 'password' => bcrypt('password'), 'panel_access' => false]);

    $studentB = Person::create(['name' => 'Aluno B', 'email' => 'aluno.b@example.com']);
    $respB = Responsible::create(['person_id' => $studentB->id, 'relationship' => RelationshipEnum::Outro, 'financial_responsible' => true]);
    $contractB = Contract::create([
        'contract_number' => 'MATR-B-100',
        'student_id' => $studentB->id,
        'responsible_id' => $respB->id,
        'start_date' => today()->toDateString(),
        'total_value' => 1000.00,
        'status' => 'active',
    ]);

    // Logado como Aluno A tentando acessar o boletim do Aluno B
    $this->actingAs($userA);
    $response = $this->get(route('student.boletim', $contractB));
    $response->assertForbidden();
});
