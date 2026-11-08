<?php

use App\Enums\ContactTypeEnum;
use App\Enums\RelationshipEnum;
use App\Filament\Pages\CommercialLeadsKanban;
use App\Filament\Pages\StudentAbsencesReport;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Classroom;
use App\Models\ClassSession;
use App\Models\CommercialLead;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Formation;
use App\Models\Payable;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use App\Models\User;
use App\Services\CommercialPerformanceReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function makeTestUser(string $name, string $email): User
{
    $person = Person::create(['name' => $name, 'email' => $email]);
    $user = User::create(['person_id' => $person->id, 'email' => $email, 'password' => 'password']);
    $role = Role::findOrCreate('Super Admin');
    $user->assignRole($role);

    return $user;
}

test('agenda attendance sheet print route renders with physical student signature spaces', function () {
    $user = makeTestUser('Admin Escola', 'admin.agenda@example.com');
    $this->actingAs($user);

    $student = Person::create(['name' => 'Lucas Silva Chamada', 'email' => 'lucas.chamada@example.com']);
    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'AGENDA-001',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'total_value' => 800,
        'status' => 'active',
    ]);

    $formation = Formation::create(['name' => 'Gestão e Informática', 'active' => true]);
    $contractItem = ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
    ]);

    $classroom = Classroom::create([
        'name' => 'Laboratório 01',
        'capacity' => 20,
        'opening_time' => '08:00:00',
        'closing_time' => '22:00:00',
        'active' => true,
    ]);

    $todayWeekday = (int) now()->dayOfWeek;

    StudentSchedule::create([
        'contract_item_id' => $contractItem->id,
        'classroom_id' => $classroom->id,
        'weekday' => $todayWeekday,
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);

    $response = $this->get(route('admin.agenda.folha-presenca', [
        'date' => today()->toDateString(),
        'classroom_id' => $classroom->id,
    ]));

    $response->assertOk();
    $response->assertSee('Pauta de Chamada');
    $response->assertSee('Lucas Silva Chamada');
    $response->assertSee('Assinatura do Aluno');
    $response->assertSee('Laboratório 01');
});

test('contract certificate routes render landscape completion certificate with student and course data', function () {
    $user = makeTestUser('Coordenador Pedagógico', 'coord@example.com');
    $this->actingAs($user);

    $school = School::create([
        'legal_name' => 'Escola Profissionalizante Modelo',
        'trade_name' => 'Aurosoft Cursos',
        'cnpj' => '11.222.333/0001-44',
        'phone' => '(11) 98765-4321',
        'email' => 'secretaria@aurosoft.com',
        'city' => 'Curitiba',
        'state' => 'PR',
    ]);

    $student = Person::create(['name' => 'Mariana Oliveira Certificado', 'email' => 'mariana.cert@example.com']);
    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CERT-2026',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->subMonths(6)->toDateString(),
        'total_value' => 1200,
        'status' => 'finished',
    ]);

    $formation = Formation::create(['name' => 'Programação Web Completa', 'active' => true]);
    $course = Course::create(['name' => 'Desenvolvimento Frontend Vue.js', 'workload' => 60]);
    $formation->courses()->attach($course->id);

    $contractItem = ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
    ]);

    $contractCourse = ContractCourse::create([
        'contract_item_id' => $contractItem->id,
        'course_id' => $course->id,
        'workload' => 60,
    ]);

    // Certificado Geral do Contrato
    $contractCertResponse = $this->get(route('contracts.certificate', $contract));
    $contractCertResponse->assertOk();
    $contractCertResponse->assertSee('Certificado de Conclusão');
    $contractCertResponse->assertSee('Mariana Oliveira Certificado');
    $contractCertResponse->assertSee('Aurosoft Cursos');

    // Mini-certificado por Curso Individual
    $courseCertResponse = $this->get(route('contracts.certificate.course', [$contract, $contractCourse]));
    $courseCertResponse->assertOk();
    $courseCertResponse->assertSee('Certificado de Conclusão');
    $courseCertResponse->assertSee('Mariana Oliveira Certificado');
    $courseCertResponse->assertSee('Desenvolvimento Frontend Vue.js');
    $courseCertResponse->assertSee('60 horas');
});

test('payables module allows recording and paying an expense with automatic cash transaction', function () {
    $user = makeTestUser('Financeiro Operador', 'financeiro.op@example.com');
    $this->actingAs($user);

    $cashRegister = CashRegister::create([
        'name' => 'Caixa Principal',
        'status' => 'open',
        'opening_balance' => 500,
        'current_balance' => 500,
        'user_id' => $user->id,
        'opened_at' => now(),
    ]);

    $payable = Payable::create([
        'description' => 'Conta de Internet Fibra Óptica',
        'supplier' => 'Operadora Telecom',
        'amount' => 199.90,
        'due_date' => today()->toDateString(),
        'status' => 'pending',
    ]);

    expect($payable->status)->toBe('pending')
        ->and($payable->amount_paid)->toBeNull();

    $payable->pay([
        'amount_paid' => 199.90,
        'payment_date' => today()->toDateString(),
        'cash_register_id' => $cashRegister->id,
    ]);

    $payable->refresh();

    expect($payable->status)->toBe('paid')
        ->and((float) $payable->amount_paid)->toBe(199.90)
        ->and($payable->payment_date->toDateString())->toBe(today()->toDateString());

    $transaction = CashTransaction::query()
        ->where('cash_register_id', $cashRegister->id)
        ->where('type', 'output')
        ->first();

    expect($transaction)->not->toBeNull()
        ->and((float) $transaction->amount)->toBe(199.90)
        ->and($transaction->description)->toContain('Conta de Internet Fibra Óptica')
        ->and($transaction->description)->toContain('Operadora Telecom');
});

test('student absences report lists students absent today and calculates risk levels', function () {
    $student = Person::create(['name' => 'Carlos Faltante', 'email' => 'carlos.falta@example.com']);
    Contact::create([
        'person_id' => $student->id,
        'type' => ContactTypeEnum::WhatsApp,
        'value' => '(11) 97777-6666',
        'is_primary' => true,
    ]);

    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'FALTA-001',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'total_value' => 500,
        'status' => 'active',
    ]);

    $formation = Formation::create(['name' => 'Designer Gráfico', 'active' => true]);
    $contractItem = ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
    ]);

    $classroom = Classroom::create([
        'name' => 'Sala Multimídia',
        'capacity' => 15,
        'opening_time' => '08:00:00',
        'closing_time' => '22:00:00',
        'active' => true,
    ]);

    $todayWeekday = (int) now()->dayOfWeek;

    $schedule = StudentSchedule::create([
        'contract_item_id' => $contractItem->id,
        'classroom_id' => $classroom->id,
        'weekday' => $todayWeekday,
        'start_hour' => '10:00:00',
        'end_hour' => '11:00:00',
    ]);

    $session = ClassSession::create([
        'classroom_id' => $classroom->id,
        'date' => today()->toDateString(),
        'weekday' => $todayWeekday,
        'start_hour' => '10:00:00',
        'end_hour' => '11:00:00',
    ]);

    StudentAttendance::create([
        'student_schedule_id' => $schedule->id,
        'class_session_id' => $session->id,
        'attendance_date' => today()->toDateString(),
        'status' => 'absent',
        'marked_at' => now(),
        'marking_source' => 'staff',
        'note' => 'Não compareceu e não avisou',
    ]);

    $reportPage = new StudentAbsencesReport;
    $reportPage->dayDate = today()->toDateString();
    $reportPage->fromDate = today()->subDays(10)->toDateString();
    $reportPage->untilDate = today()->toDateString();

    $dayAbsences = $reportPage->dayAbsences();
    expect($dayAbsences)->toHaveCount(1)
        ->and($dayAbsences[0]['student_name'])->toBe('Carlos Faltante')
        ->and($dayAbsences[0]['clean_phone'])->toBe('11977776666')
        ->and($dayAbsences[0]['classroom'])->toBe('Sala Multimídia')
        ->and($dayAbsences[0]['note'])->toBe('Não compareceu e não avisou');

    $periodAbsences = $reportPage->periodAbsences();
    expect($periodAbsences)->toHaveCount(1)
        ->and($periodAbsences[0]['student_name'])->toBe('Carlos Faltante')
        ->and($periodAbsences[0]['absent_count'])->toBe(1)
        ->and($periodAbsences[0]['risk_level'])->toBe('Acompanhar');
});

test('commercial leads kanban allows moving leads between stages', function () {
    $lead = CommercialLead::create([
        'name' => 'Beatriz Candidata',
        'phone' => '(41) 98888-2222',
        'email' => 'beatriz@example.com',
        'status' => 'new',
    ]);

    $kanban = new CommercialLeadsKanban;

    $kanban->updateLeadStatus($lead->id, 'visited');
    expect($lead->refresh()->status)->toBe('visited');

    $kanban->updateLeadStatus($lead->id, 'enrolled');
    expect($lead->refresh()->status)->toBe('enrolled')
        ->and($lead->converted_at)->not->toBeNull();
});

test('commercial performance report calculates CAC, conversion funnel and objection reasons', function () {
    $from = today()->subDays(10);
    $until = today();

    // 1 lead matriculado
    CommercialLead::create([
        'name' => 'Lead Matriculado',
        'status' => 'enrolled',
        'created_at' => today()->subDays(3),
    ]);

    // 1 lead perdido com objeção
    CommercialLead::create([
        'name' => 'Lead Perdido Objeção',
        'status' => 'lost',
        'notes' => 'Mensalidade fora do orçamento familiar',
        'created_at' => today()->subDays(4),
    ]);

    // 1 contrato cancelado com motivo de perda
    $student = Person::create(['name' => 'Aluno Cancelado', 'email' => 'cancelado@example.com']);
    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    Contract::create([
        'contract_number' => 'CANC-999',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => today()->subDays(8)->toDateString(),
        'total_value' => 600,
        'status' => 'cancelled',
        'cancelled_at' => today()->subDays(2),
        'cancellation_reason' => 'Horário incompatível com o novo emprego',
    ]);

    $service = new CommercialPerformanceReport;
    $data = $service->generate($from, $until, investment: 1200.0);

    expect($data['lead_count'])->toBe(2)
        ->and($data['enrolled_count'])->toBe(1)
        ->and($data['investment'])->toBe(1200.0)
        ->and($data['cac'])->toBe(1200.0) // 1200 / 1 = 1200.0
        ->and($data['funnel'])->toHaveCount(5);

    $lossReasonsText = collect($data['loss_reasons'])->pluck('reason')->implode(' ');
    expect($lossReasonsText)->toContain('Mensalidade fora do orçamento familiar')
        ->and($lossReasonsText)->toContain('Horário incompatível com o novo emprego');
});
