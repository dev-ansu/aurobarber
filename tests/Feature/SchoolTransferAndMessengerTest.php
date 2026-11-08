<?php

use App\Filament\Pages\SchoolMessenger;
use App\Models\Classroom;
use App\Models\CommercialLead;
use App\Models\Contract;
use App\Models\ContractHistory;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Formation;
use App\Models\Installment;
use App\Models\LmsLesson;
use App\Models\LmsModule;
use App\Models\LmsStudentLessonProgress;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\SchoolMessage;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use App\Models\User;
use App\Services\SchoolTransferService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    School::query()->delete();
});

test('can transfer student contract from origin school X to destination school Y including student, financial responsible, LMS progress, installments, and attendances', function () {
    $schoolA = School::create(['id' => 1, 'legal_name' => 'Unidade Centro', 'trade_name' => 'Centro']);
    $schoolB = School::create(['id' => 2, 'legal_name' => 'Unidade Sul', 'trade_name' => 'Sul']);

    $adminPerson = Person::create(['name' => 'Diretor Rede', 'email' => 'diretor@rede.com']);
    $admin = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'diretor@rede.com',
        'password' => bcrypt('password'),
    ]);
    $role = Role::findOrCreate('super_admin', 'web');
    $admin->assignRole($role);
    $this->actingAs($admin);

    // Aluno e seu Responsável Financeiro (Mãe) na Escola A
    $student = Person::create(['name' => 'Lucas Transferido', 'email' => 'lucas@aluno.com']);
    $motherPerson = Person::create(['name' => 'Maria Responsável', 'email' => 'maria@mae.com']);
    $responsible = Responsible::create([
        'person_id' => $motherPerson->id,
        'financial_responsible' => true,
        'relationship' => \App\Enums\RelationshipEnum::Mother,
    ]);

    DB::table('school_person')->insert([
        ['school_id' => $schoolA->id, 'person_id' => $student->id, 'created_at' => now(), 'updated_at' => now()],
        ['school_id' => $schoolA->id, 'person_id' => $motherPerson->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $contract = Contract::create([
        'school_id' => $schoolA->id,
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'contract_number' => 'TR-2026-01',
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 1200,
    ]);

    $formation = Formation::create([
        'name' => 'Formação Office',
        'price' => 1200,
        'active' => true,
    ]);

    $contractItem = ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
        'description' => 'Curso Informática',
        'quantity' => 1,
        'unit_value' => 1200,
        'total_value' => 1200,
    ]);

    $classroomA = Classroom::create([
        'school_id' => $schoolA->id,
        'name' => 'Lab 01 Centro',
        'capacity' => 10,
        'active' => true,
        'opening_time' => '08:00:00',
        'closing_time' => '18:00:00',
    ]);

    $schedule = StudentSchedule::create([
        'school_id' => $schoolA->id,
        'contract_item_id' => $contractItem->id,
        'classroom_id' => $classroomA->id,
        'weekday' => 1,
        'start_hour' => '09:00:00',
        'end_hour' => '10:00:00',
    ]);

    $session = \App\Models\ClassSession::create([
        'classroom_id' => $classroomA->id,
        'date' => today()->toDateString(),
        'weekday' => 1,
        'start_hour' => '09:00:00',
        'end_hour' => '10:00:00',
    ]);

    $attendance = StudentAttendance::create([
        'school_id' => $schoolA->id,
        'student_schedule_id' => $schedule->id,
        'class_session_id' => $session->id,
        'attendance_date' => today()->toDateString(),
        'status' => 'present',
    ]);

    $installment = Installment::create([
        'contract_id' => $contract->id,
        'installment_number' => 1,
        'amount' => 200,
        'due_date' => today()->addDays(10),
        'status' => 'pending',
        'type' => 'tuition',
    ]);

    $course = Course::create([
        'name' => 'Windows 11',
        'workload' => 20,
        'price' => 200,
        'active' => true,
    ]);
    $module = LmsModule::create([
        'course_id' => $course->id,
        'title' => 'Módulo 1',
        'order' => 1,
        'is_active' => true,
    ]);
    $lesson = LmsLesson::create([
        'module_id' => $module->id,
        'title' => 'Aula 1',
        'position' => 1,
        'is_active' => true,
    ]);

    LmsStudentLessonProgress::create([
        'student_id' => $student->id,
        'contract_id' => $contract->id,
        'lesson_id' => $lesson->id,
        'status' => 'completed',
        'score' => 95,
        'time_spent_seconds' => 3600,
        'completed_at' => now(),
    ]);

    // Executa a transferência da Escola A (origem) para a Escola B (destino)
    $message = app(SchoolTransferService::class)->transferContract(
        contract: $contract,
        targetSchool: $schoolB,
        reason: 'Aluno mudou para o bairro Sul',
        keepOriginPersonLink: false,
        sendNotificationMessage: true,
    );

    $contract->refresh();
    $attendance->refresh();

    // 1. Contrato e Frequência agora pertencem à Escola B
    expect($contract->school_id)->toBe($schoolB->id)
        ->and($attendance->school_id)->toBe($schoolB->id);

    // 2. Tanto o Aluno quanto o Responsável Financeiro (Mãe) foram transferidos da Escola A para a Escola B
    expect(DB::table('school_person')->where('school_id', $schoolB->id)->where('person_id', $student->id)->exists())->toBeTrue()
        ->and(DB::table('school_person')->where('school_id', $schoolB->id)->where('person_id', $motherPerson->id)->exists())->toBeTrue()
        ->and(DB::table('school_person')->where('school_id', $schoolA->id)->where('person_id', $student->id)->exists())->toBeFalse()
        ->and(DB::table('school_person')->where('school_id', $schoolA->id)->where('person_id', $motherPerson->id)->exists())->toBeFalse();

    // 3. Vaga de horário na Escola A foi encerrada (soft deleted)
    expect(StudentSchedule::query()->where('id', $schedule->id)->exists())->toBeFalse()
        ->and(StudentSchedule::query()->withTrashed()->where('id', $schedule->id)->whereNotNull('deleted_at')->exists())->toBeTrue();

    // 4. Progresso LMS e Parcelas continuam intactos no contrato
    expect($contract->lmsLessonProgress()->count())->toBe(1)
        ->and($contract->installments()->where('id', $installment->id)->exists())->toBeTrue();

    // 5. Histórico de auditoria foi gravado no contrato mencionando o responsável transferido
    $history = ContractHistory::query()->where('contract_id', $contract->id)->first();
    expect($history)->not->toBeNull()
        ->and($history->description)->toContain('Centro')
        ->and($history->description)->toContain('Sul')
        ->and($history->description)->toContain('Maria Responsável')
        ->and($history->description)->toContain('Aluno mudou para o bairro Sul');

    // 6. Mensagem automática foi enviada da Escola A para a Escola B
    expect($message)->not->toBeNull()
        ->and($message->sender_school_id)->toBe($schoolA->id)
        ->and($message->recipient_school_id)->toBe($schoolB->id)
        ->and($message->category)->toBe('transfer')
        ->and($message->contract_id)->toBe($contract->id);
});

test('can transfer person from origin school X to destination school Y transferring all contracts, leads, and financial responsible automatically', function () {
    $schoolX = School::create(['id' => 1, 'legal_name' => 'Unidade Norte', 'trade_name' => 'Norte']);
    $schoolY = School::create(['id' => 2, 'legal_name' => 'Unidade Leste', 'trade_name' => 'Leste']);

    $student = Person::create(['name' => 'Pedro Completo', 'cpf' => '111.222.333-44', 'email' => 'pedro@teste.com']);
    $fatherPerson = Person::create(['name' => 'Carlos Pai', 'cpf' => '555.666.777-88', 'email' => 'carlos@pai.com']);
    $responsible = Responsible::create([
        'person_id' => $fatherPerson->id,
        'financial_responsible' => true,
        'relationship' => \App\Enums\RelationshipEnum::Father,
    ]);

    DB::table('school_person')->insert([
        ['school_id' => $schoolX->id, 'person_id' => $student->id, 'created_at' => now(), 'updated_at' => now()],
        ['school_id' => $schoolX->id, 'person_id' => $fatherPerson->id, 'created_at' => now(), 'updated_at' => now()],
    ]);

    $lead = CommercialLead::create([
        'school_id' => $schoolX->id,
        'person_id' => $student->id,
        'name' => 'Pedro Completo',
        'status' => 'enrolled',
    ]);

    $contract = Contract::create([
        'school_id' => $schoolX->id,
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'commercial_lead_id' => $lead->id,
        'contract_number' => 'NOR-001',
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 900,
    ]);

    // Transfere a partir da Pessoa na Escola X para a Escola Y
    $message = app(SchoolTransferService::class)->transferPerson(
        person: $student,
        targetSchool: $schoolY,
        originSchool: $schoolX,
        reason: 'Transferência integral solicitada na secretaria da Unidade Norte',
        keepOriginPersonLink: false,
        sendNotificationMessage: true,
    );

    $contract->refresh();
    $lead->refresh();

    expect($contract->school_id)->toBe($schoolY->id)
        ->and($lead->school_id)->toBe($schoolY->id)
        ->and(DB::table('school_person')->where('school_id', $schoolY->id)->where('person_id', $student->id)->exists())->toBeTrue()
        ->and(DB::table('school_person')->where('school_id', $schoolY->id)->where('person_id', $fatherPerson->id)->exists())->toBeTrue()
        ->and(DB::table('school_person')->where('school_id', $schoolX->id)->where('person_id', $student->id)->exists())->toBeFalse()
        ->and(DB::table('school_person')->where('school_id', $schoolX->id)->where('person_id', $fatherPerson->id)->exists())->toBeFalse()
        ->and($message)->not->toBeNull()
        ->and($message->body)->toContain('Carlos Pai')
        ->and($message->body)->toContain('#NOR-001');
});

test('can transfer commercial lead between schools and notify destination school', function () {
    $schoolA = School::create(['id' => 1, 'legal_name' => 'Unidade Centro', 'trade_name' => 'Centro']);
    $schoolB = School::create(['id' => 2, 'legal_name' => 'Unidade Sul', 'trade_name' => 'Sul']);

    $lead = CommercialLead::create([
        'school_id' => $schoolA->id,
        'name' => 'Mariana Interessada',
        'phone' => '11999990000',
        'status' => 'contacted',
        'notes' => 'Quer fazer curso de Excel.',
    ]);

    $message = app(SchoolTransferService::class)->transferLead(
        lead: $lead,
        targetSchool: $schoolB,
        reason: 'Mora ao lado da Unidade Sul',
        sendNotificationMessage: true,
    );

    $lead->refresh();

    expect($lead->school_id)->toBe($schoolB->id)
        ->and($lead->notes)->toContain('Transferido de Centro para Sul')
        ->and($lead->notes)->toContain('Mora ao lado da Unidade Sul')
        ->and($message)->not->toBeNull()
        ->and($message->recipient_school_id)->toBe($schoolB->id);
});

test('schools can exchange messages and broadcasts via SchoolMessenger page', function () {
    $schoolA = School::create(['id' => 1, 'legal_name' => 'Unidade Centro', 'trade_name' => 'Centro']);
    $schoolB = School::create(['id' => 2, 'legal_name' => 'Unidade Sul', 'trade_name' => 'Sul']);

    $person = Person::create(['name' => 'Coordenador Centro', 'email' => 'coord@centro.com']);
    $user = User::create([
        'person_id' => $person->id,
        'email' => 'coord@centro.com',
        'password' => bcrypt('password'),
    ]);
    $role = Role::findOrCreate('super_admin', 'web');
    $user->assignRole($role);

    $this->actingAs($user);
    Filament::setTenant($schoolA, isQuiet: true);

    // 1. Envia mensagem da Escola A para a Escola B
    $component = Livewire::test(SchoolMessenger::class)
        ->call('openCompose', 'pedagogical', $schoolB->id)
        ->set('composeSubject', 'Material Didático Apostilas')
        ->set('composeBody', 'Vocês têm 5 apostilas de Excel disponíveis para empréstimo?')
        ->call('sendMessage');

    $component->assertHasNoErrors();

    $msg = SchoolMessage::query()->first();
    expect($msg)->not->toBeNull()
        ->and($msg->sender_school_id)->toBe($schoolA->id)
        ->and($msg->recipient_school_id)->toBe($schoolB->id)
        ->and($msg->read_at)->toBeNull();

    // 2. Escola B abre a caixa de entrada, lê a mensagem e responde
    Filament::setTenant($schoolB, isQuiet: true);

    Livewire::test(SchoolMessenger::class)
        ->call('selectMessage', $msg->id)
        ->set('replyBody', 'Sim! Separamos as 5 apostilas na recepção para vocês.')
        ->call('sendReply');

    $msg->refresh();
    expect($msg->read_at)->not->toBeNull()
        ->and($msg->replies()->count())->toBe(1)
        ->and($msg->replies()->first()->sender_school_id)->toBe($schoolB->id);

    // 3. Verifica que o corpo da mensagem está criptografado (AES-256) no banco de dados bruto
    $rawEncryptedBody = DB::table('school_messages')->where('id', $msg->id)->value('body');
    expect($rawEncryptedBody)->not->toBe('Vocês têm 5 apostilas de Excel disponíveis para empréstimo?')
        ->and($msg->body)->toBe('Vocês têm 5 apostilas de Excel disponíveis para empréstimo?');

    // 4. Verifica que a notificação de banco de dados (sininho do header) foi gerada
    expect(DB::table('notifications')->count())->toBeGreaterThan(0);
});

test('collaborators can exchange encrypted 1-on-1 WhatsApp-style direct messages and see online status', function () {
    $schoolA = School::create(['id' => 1, 'legal_name' => 'Unidade Centro', 'trade_name' => 'Centro']);
    $schoolB = School::create(['id' => 2, 'legal_name' => 'Unidade Sul', 'trade_name' => 'Sul']);

    $person1 = Person::create(['name' => 'Ana Secretaria', 'email' => 'ana@centro.com']);
    \App\Models\Collaborator::create([
        'person_id' => $person1->id,
        'admission_date' => today(),
        'role' => 'Secretária',
        'active' => true,
    ]);
    $user1 = User::create([
        'person_id' => $person1->id,
        'email' => 'ana@centro.com',
        'password' => bcrypt('password'),
    ]);
    $user1->schools()->attach($schoolA);

    $person2 = Person::create(['name' => 'Bruno Coordenador', 'email' => 'bruno@sul.com']);
    \App\Models\Collaborator::create([
        'person_id' => $person2->id,
        'admission_date' => today(),
        'role' => 'Coordenador',
        'active' => true,
    ]);
    $user2 = User::create([
        'person_id' => $person2->id,
        'email' => 'bruno@sul.com',
        'password' => bcrypt('password'),
    ]);
    $user2->schools()->attach($schoolB);
    $user2->touchOnlinePresence();

    // Cria também um Aluno que tem User (para garantir que NÃO aparece na lista de colaboradores do chat)
    $studentPerson = Person::create(['name' => 'Aluno Comum Portal', 'email' => 'aluno.portal@sul.com']);
    $studentUser = User::create([
        'person_id' => $studentPerson->id,
        'email' => 'aluno.portal@sul.com',
        'password' => bcrypt('password'),
    ]);

    expect($user2->isOnline())->toBeTrue();

    // Se o colaborador fizer logout, sai imediatamente do status "Online"
    $user2->markOffline();
    expect($user2->isOnline())->toBeFalse();

    // Volta a ficar online
    $user2->touchOnlinePresence();
    expect($user2->isOnline())->toBeTrue();

    $this->actingAs($user1);
    Filament::setTenant($schoolA, isQuiet: true);

    $component = Livewire::test(SchoolMessenger::class)
        ->assertSee('Bruno Coordenador')
        ->assertDontSee('Aluno Comum Portal')
        ->call('openDirectChatWithUser', $user2->id)
        ->set('directMessageText', 'Oi Bruno, o aluno Lucas já chegou aí na unidade Sul?')
        ->call('sendDirectMessage');

    $directMsg = SchoolMessage::query()->where('recipient_user_id', $user2->id)->first();
    expect($directMsg)->not->toBeNull()
        ->and($directMsg->body)->toBe('Oi Bruno, o aluno Lucas já chegou aí na unidade Sul?')
        ->and(DB::table('school_messages')->where('id', $directMsg->id)->value('body'))->not->toContain('Lucas')
        ->and($user2->notifications()->count())->toBe(1);
});
