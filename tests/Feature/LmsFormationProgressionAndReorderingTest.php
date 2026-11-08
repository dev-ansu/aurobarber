<?php

use App\Enums\RelationshipEnum;
use App\Models\Classroom;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\StudentSchedule;
use App\Models\User;
use App\Services\LmsProgressionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->classroom = Classroom::create([
        'name' => 'Sala 01',
        'capacity' => 20,
        'active' => true,
    ]);

    $this->student = Person::create([
        'name' => 'Aluno Multi Formação',
        'email' => 'aluno.multi@aurosoft.com',
        'birth_date' => '2004-06-20',
    ]);

    $this->responsiblePerson = Person::create([
        'name' => 'Responsável Financeiro',
        'email' => 'resp@aurosoft.com',
    ]);

    $this->responsible = Responsible::create([
        'person_id' => $this->responsiblePerson->id,
        'relationship' => RelationshipEnum::Father,
        'financial_responsible' => true,
    ]);

    $this->contract = Contract::create([
        'contract_number' => 'MAT-MULTI-001',
        'student_id' => $this->student->id,
        'responsible_id' => $this->responsible->id,
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 2000,
        'validity_months' => 12,
        'enrollment_fee' => 100,
        'monthly_payment' => 200,
        'installments_count' => 10,
        'payment_due_day' => 10,
    ]);

    $this->service = app(LmsProgressionService::class);
});

it('scopes sequential course prerequisite lock strictly to the same formation and not across different formations', function () {
    // Formação X: Desenvolvimento Web (Curso 1: HTML e CSS - Ouro Moderno)
    $courseX1 = Course::create([
        'name' => 'HTML e CSS',
        'active' => true,
        'ouro_moderno_id' => 101,
    ]);
    $courseX2 = Course::create([
        'name' => 'JavaScript Avançado',
        'active' => true,
        'ouro_moderno_id' => null,
    ]);

    $formationX = Formation::create(['name' => 'Desenvolvimento Web', 'active' => true]);
    $formationX->courses()->attach([$courseX1->id => ['order' => 1], $courseX2->id => ['order' => 2]]);

    $itemX = ContractItem::create([
        'contract_id' => $this->contract->id,
        'formation_id' => $formationX->id,
        'order' => 1,
    ]);
    ContractCourse::create([
        'contract_item_id' => $itemX->id,
        'course_id' => $courseX1->id,
        'order' => 1,
        'status' => 'in_progress', // Incompleto na Ouro Moderno
    ]);
    ContractCourse::create([
        'contract_item_id' => $itemX->id,
        'course_id' => $courseX2->id,
        'order' => 2,
        'status' => 'not_started',
    ]);

    // Agendamento de X para Terça-feira 14h às 15h
    StudentSchedule::create([
        'contract_item_id' => $itemX->id,
        'classroom_id' => $this->classroom->id,
        'weekday' => 2, // Terça-feira
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);

    // Formação Y: Informática Empresarial (Curso 1: Windows 11 - Aurosoft LMS)
    $courseY1 = Course::create([
        'name' => 'Windows 11 Essencial',
        'active' => true,
        'ouro_moderno_id' => null,
    ]);

    $formationY = Formation::create(['name' => 'Informática Empresarial', 'active' => true]);
    $formationY->courses()->attach([$courseY1->id => ['order' => 1]]);

    $itemY = ContractItem::create([
        'contract_id' => $this->contract->id,
        'formation_id' => $formationY->id,
        'order' => 2,
    ]);
    ContractCourse::create([
        'contract_item_id' => $itemY->id,
        'course_id' => $courseY1->id,
        'order' => 1,
        'status' => 'in_progress',
    ]);

    // Agendamento de Y para Quinta-feira 14h às 15h (dias diferentes!)
    StudentSchedule::create([
        'contract_item_id' => $itemY->id,
        'classroom_id' => $this->classroom->id,
        'weekday' => 4, // Quinta-feira
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);

    // Simulando que hoje é Quinta-feira 14h30 (horário de Formação Y)
    $thursdayClassTime = Carbon::parse('next thursday 14:30:00');
    Carbon::setTestNow($thursdayClassTime);

    // 1. O curso da Formação Y NÃO DEVE SER BLOQUEADO pelo curso incompleto da Formação X!
    $accessY1 = $this->service->canAccessCourse($this->contract, $courseY1);
    expect($accessY1['can_access'])->toBeTrue();
    expect($accessY1['reason'] ?? '')->not->toBe('previous_om_course_incomplete');

    // 2. Se o aluno tentar acessar o curso de X na quinta-feira, é bloqueado pelo dia da formação
    $accessX1 = $this->service->canAccessCourse($this->contract, $courseX1);
    expect($accessX1['can_access'])->toBeFalse();
    expect($accessX1['reason'])->toBe('no_schedule_today');

    Carbon::setTestNow();
});

it('blocks a second formation if it shares the exact same schedule and the first formation is incomplete', function () {
    // Formação 1
    $course1 = Course::create(['name' => 'Módulo Inicial', 'active' => true]);
    $formation1 = Formation::create(['name' => 'Etapa 1', 'active' => true]);
    $formation1->courses()->attach($course1->id);

    $item1 = ContractItem::create([
        'contract_id' => $this->contract->id,
        'formation_id' => $formation1->id,
        'order' => 1,
    ]);
    ContractCourse::create([
        'contract_item_id' => $item1->id,
        'course_id' => $course1->id,
        'order' => 1,
        'status' => 'in_progress', // Ainda não concluído
    ]);
    StudentSchedule::create([
        'contract_item_id' => $item1->id,
        'classroom_id' => $this->classroom->id,
        'weekday' => 1, // Segunda 14-15h
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);

    // Formação 2 (mesmo horário!)
    $course2 = Course::create(['name' => 'Módulo Avançado', 'active' => true]);
    $formation2 = Formation::create(['name' => 'Etapa 2', 'active' => true]);
    $formation2->courses()->attach($course2->id);

    $item2 = ContractItem::create([
        'contract_id' => $this->contract->id,
        'formation_id' => $formation2->id,
        'order' => 2,
    ]);
    ContractCourse::create([
        'contract_item_id' => $item2->id,
        'course_id' => $course2->id,
        'order' => 1,
        'status' => 'not_started',
    ]);
    StudentSchedule::withoutEvents(function () use ($item2) {
        StudentSchedule::create([
            'contract_item_id' => $item2->id,
            'classroom_id' => $this->classroom->id,
            'weekday' => 1, // Segunda 14-15h (compartilha o mesmo horário de Etapa 1!)
            'start_hour' => '14:00:00',
            'end_hour' => '15:00:00',
        ]);
    });

    $mondayTime = Carbon::parse('next monday 14:30:00');
    Carbon::setTestNow($mondayTime);

    // Como Etapa 1 não está concluída e compartilham o mesmo horário, Etapa 2 fica bloqueada
    $access2 = $this->service->canAccessCourse($this->contract, $course2);
    expect($access2['can_access'])->toBeFalse();
    expect($access2['reason'])->toBe('previous_formation_incomplete');

    Carbon::setTestNow();
});

it('automatically advances next course to in_progress when previous is completed, and resets on rollback', function () {
    $c1 = Course::create(['name' => 'Curso A', 'active' => true]);
    $c2 = Course::create(['name' => 'Curso B', 'active' => true]);
    $c3 = Course::create(['name' => 'Curso C', 'active' => true]);

    $formation = Formation::create(['name' => 'Formação Teste', 'active' => true]);
    $formation->courses()->attach([$c1->id, $c2->id, $c3->id]);

    $item = ContractItem::create([
        'contract_id' => $this->contract->id,
        'formation_id' => $formation->id,
        'order' => 1,
    ]);

    $cc1 = ContractCourse::create(['contract_item_id' => $item->id, 'course_id' => $c1->id, 'order' => 1, 'status' => 'not_started']);
    $cc2 = ContractCourse::create(['contract_item_id' => $item->id, 'course_id' => $c2->id, 'order' => 2, 'status' => 'not_started']);
    $cc3 = ContractCourse::create(['contract_item_id' => $item->id, 'course_id' => $c3->id, 'order' => 3, 'status' => 'not_started']);

    // sync inicial: primeiro curso deve se tornar 'in_progress'
    $this->service->syncContractProgression($this->contract);
    expect($cc1->fresh()->status)->toBe('in_progress');
    expect($cc2->fresh()->status)->toBe('not_started');
    expect($cc3->fresh()->status)->toBe('not_started');

    // Marcando Curso 1 como concluído: Curso 2 deve virar 'in_progress'
    $cc1->update(['status' => 'completed']);
    expect($cc1->fresh()->status)->toBe('completed');
    expect($cc2->fresh()->status)->toBe('in_progress');
    expect($cc3->fresh()->status)->toBe('not_started');

    // Revertendo Curso 1 para 'in_progress': Curso 2 deve voltar para 'not_started'!
    $cc1->update(['status' => 'in_progress']);
    expect($cc1->fresh()->status)->toBe('in_progress');
    expect($cc2->fresh()->status)->toBe('not_started');
    expect($cc3->fresh()->status)->toBe('not_started');
});

it('strictly excludes unlinked courses from contract and portal listing', function () {
    $c1 = Course::create(['name' => 'Curso Contratado', 'active' => true]);
    $c2 = Course::create(['name' => 'Curso Desvinculado', 'active' => true]);

    $formation = Formation::create(['name' => 'Formação com 2 Cursos', 'active' => true]);
    $formation->courses()->attach([$c1->id => ['order' => 1], $c2->id => ['order' => 2]]);

    $item = ContractItem::create([
        'contract_id' => $this->contract->id,
        'formation_id' => $formation->id,
        'order' => 1,
    ]);

    // Apenas c1 é vinculado em contract_item_courses
    ContractCourse::create(['contract_item_id' => $item->id, 'course_id' => $c1->id, 'order' => 1, 'status' => 'in_progress']);

    $orderedCourses = $this->service->getOrderedCoursesForContract($this->contract);

    expect($orderedCourses->pluck('id')->all())->toBe([$c1->id]);
    expect($orderedCourses->pluck('id')->contains($c2->id))->toBeFalse();
});
