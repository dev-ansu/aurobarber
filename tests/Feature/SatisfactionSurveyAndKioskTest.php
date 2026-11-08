<?php

declare(strict_types=1);

use App\Enums\RelationshipEnum;
use App\Filament\Kiosk\Pages\StudentAttendanceKiosk;
use App\Filament\Resources\SatisfactionSurveys\Pages\CreateSatisfactionSurvey;
use App\Filament\Resources\SatisfactionSurveys\SatisfactionSurveyResource;
use App\Models\Classroom;
use App\Models\Contract;
use App\Models\Course;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\SatisfactionSurvey;
use App\Models\SatisfactionSurveyResponse;
use App\Models\StudentAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createSurveyFixture(string $suffix, Carbon $date): array
{
    $student = Person::create([
        'name' => "Aluno Pesquisa {$suffix}",
        'email' => "student-survey-{$suffix}@example.com",
    ]);

    $responsiblePerson = Person::create([
        'name' => "Responsavel {$suffix}",
        'email' => "resp-survey-{$suffix}@example.com",
    ]);

    $responsible = Responsible::create([
        'person_id' => $responsiblePerson->id,
        'relationship' => RelationshipEnum::Father,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => "SURVEY-{$suffix}",
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => $date->toDateString(),
        'total_value' => 100,
        'status' => 'active',
    ]);

    $formation = Formation::create([
        'name' => "Formação Survey {$suffix}",
        'active' => true,
    ]);

    $contractItem = $contract->items()->create([
        'formation_id' => $formation->id,
    ]);

    $classroom = Classroom::create([
        'name' => "Sala Survey {$suffix}",
        'capacity' => 10,
        'active' => true,
    ]);

    $schedule = $contractItem->schedules()->create([
        'classroom_id' => $classroom->id,
        'weekday' => $date->dayOfWeek,
        'start_hour' => '14:00',
        'end_hour' => '15:00',
    ]);

    return compact('student', 'contract', 'schedule');
}

test('satisfaction survey resource follows zero-trust policy', function () {
    $guestPerson = Person::create(['name' => 'Usuário Sem Permissão', 'email' => 'sem-permissao-survey@example.com']);
    $guestUser = User::create([
        'person_id' => $guestPerson->id,
        'email' => 'sem-permissao-survey@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($guestUser);
    expect(SatisfactionSurveyResource::canViewAny())->toBeFalse();

    $managerPerson = Person::create(['name' => 'Gestor Survey', 'email' => 'gestor-survey@example.com']);
    $manager = User::create([
        'person_id' => $managerPerson->id,
        'email' => 'gestor-survey@example.com',
        'password' => bcrypt('password'),
    ]);
    $manager->givePermissionTo(Permission::findOrCreate('ViewAny:SatisfactionSurvey', 'web'));

    $this->actingAs($manager);
    expect(SatisfactionSurveyResource::canViewAny())->toBeTrue();
});

test('manager can create a satisfaction survey with questions in admin', function () {
    $superAdminRole = Role::findOrCreate('super_admin', 'web');
    $superAdminRole->givePermissionTo(Permission::findOrCreate('Create:SatisfactionSurvey', 'web'));
    $superAdminRole->givePermissionTo(Permission::findOrCreate('ViewAny:SatisfactionSurvey', 'web'));
    $adminPerson = Person::create(['name' => 'Admin Survey', 'email' => 'admin-survey@example.com']);
    $admin = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'admin-survey@example.com',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole($superAdminRole);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

    Livewire::actingAs($admin)
        ->test(CreateSatisfactionSurvey::class)
        ->fillForm([
            'title' => 'Pesquisa de Outubro',
            'description' => 'Avalie a qualidade das aulas e do laboratório',
            'is_active' => true,
            'questions' => [
                [
                    'question' => 'Como você avalia os computadores?',
                    'type' => 'rating_5',
                    'is_required' => true,
                ],
                [
                    'question' => 'Deixe uma sugestão',
                    'type' => 'text',
                    'is_required' => false,
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $survey = SatisfactionSurvey::query()->first();
    expect($survey)->not->toBeNull()
        ->and($survey->title)->toBe('Pesquisa de Outubro')
        ->and($survey->is_active)->toBeTrue()
        ->and($survey->questions()->count())->toBe(2);
});

test('kiosk marks attendance directly when there is no active survey', function () {
    $date = Carbon::today();
    $fixture = createSurveyFixture('NO-SURVEY', $date);

    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    $this->travelTo($date->copy()->setTime(14, 15));

    $attendance = StudentAttendance::query()
        ->where('student_schedule_id', $fixture['schedule']->id)
        ->sole();

    expect($attendance->status)->toBe('absent');

    Livewire::test(StudentAttendanceKiosk::class)
        ->set('data.contract_number', $fixture['contract']->contract_number)
        ->call('confirmPresence')
        ->assertNotified();

    expect($attendance->fresh()->status)->toBe('present')
        ->and($attendance->fresh()->marking_source)->toBe('kiosk');
});

test('kiosk prompts student for active survey before confirming attendance', function () {
    $date = Carbon::today();
    $fixture = createSurveyFixture('WITH-SURVEY', $date);

    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    $attendance = StudentAttendance::query()
        ->where('student_schedule_id', $fixture['schedule']->id)
        ->sole();

    $survey = SatisfactionSurvey::create([
        'title' => 'Pesquisa Obrigatória',
        'description' => 'Por favor responda',
        'is_active' => true,
    ]);

    $q1 = $survey->questions()->create([
        'question' => 'Avalie os professores',
        'type' => 'rating_5',
        'is_required' => true,
        'sort_order' => 1,
    ]);

    $q2 = $survey->questions()->create([
        'question' => 'Sugestão',
        'type' => 'text',
        'is_required' => false,
        'sort_order' => 2,
    ]);

    $this->travelTo($date->copy()->setTime(14, 20));

    // 1. Initial attempt to confirm presence
    $kiosk = Livewire::test(StudentAttendanceKiosk::class)
        ->set('data.contract_number', $fixture['contract']->contract_number)
        ->call('confirmPresence');

    // Attendance is NOT marked yet, kiosk transitions to survey mode
    expect($attendance->fresh()->status)->toBe('absent');
    $kiosk->assertSet('isAnsweringSurvey', true)
        ->assertSet('pendingContractNumber', $fixture['contract']->contract_number);

    // 2. Submitting without answering required question fails
    $kiosk->call('submitSurveyAndConfirmPresence')
        ->assertNotified();
    expect($attendance->fresh()->status)->toBe('absent')
        ->and(SatisfactionSurveyResponse::query()->count())->toBe(0);

    // 3. Answering the questions and submitting
    $kiosk->call('setAnswerRating', $q1->id, 5)
        ->call('setAnswerText', $q2->id, 'Excelente aula!')
        ->call('submitSurveyAndConfirmPresence')
        ->assertNotified();

    // Now attendance is marked and survey response is recorded!
    expect($attendance->fresh()->status)->toBe('present')
        ->and($attendance->fresh()->marking_source)->toBe('kiosk')
        ->and($kiosk->get('isAnsweringSurvey'))->toBeFalse();

    $response = SatisfactionSurveyResponse::query()->sole();
    expect($response->satisfaction_survey_id)->toBe($survey->id)
        ->and($response->contract_id)->toBe($fixture['contract']->id)
        ->and($response->student_attendance_id)->toBe($attendance->id)
        ->and($response->answers()->count())->toBe(2);

    $answer1 = $response->answers()->where('satisfaction_survey_question_id', $q1->id)->sole();
    expect($answer1->rating_value)->toBe(5);

    $answer2 = $response->answers()->where('satisfaction_survey_question_id', $q2->id)->sole();
    expect($answer2->text_value)->toBe('Excelente aula!');
});

test('student is not prompted for the same survey twice and marks attendance directly', function () {
    $date = Carbon::today();
    $fixture = createSurveyFixture('REPEAT', $date);

    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    $attendance = StudentAttendance::query()
        ->where('student_schedule_id', $fixture['schedule']->id)
        ->sole();

    $survey = SatisfactionSurvey::create([
        'title' => 'Pesquisa Única',
        'is_active' => true,
    ]);

    $survey->questions()->create([
        'question' => 'Nota geral',
        'type' => 'rating_5',
        'is_required' => true,
    ]);

    // Already answered previously
    SatisfactionSurveyResponse::create([
        'satisfaction_survey_id' => $survey->id,
        'contract_id' => $fixture['contract']->id,
        'student_id' => $fixture['student']->id,
    ]);

    $this->travelTo($date->copy()->setTime(14, 30));

    // When student enters contract, skips survey and marks presence directly
    Livewire::test(StudentAttendanceKiosk::class)
        ->set('data.contract_number', $fixture['contract']->contract_number)
        ->call('confirmPresence')
        ->assertSet('isAnsweringSurvey', false)
        ->assertNotified();

    expect($attendance->fresh()->status)->toBe('present');
});

test('kiosk triggers student portal autologin when ouro moderno is not configured', function () {
    config()->set('services.ouro_moderno.url', null);
    config()->set('services.ouro_moderno.token', null);

    $date = Carbon::today();
    $fixture = createSurveyFixture('NO-LMS', $date);

    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    $this->travelTo($date->copy()->setTime(14, 15));

    $expectedUrl = route('student.kiosk-autologin', ['contract' => $fixture['contract']->id]);

    Livewire::test(StudentAttendanceKiosk::class)
        ->set('data.contract_number', $fixture['contract']->contract_number)
        ->call('confirmPresence')
        ->assertNotified()
        ->assertDispatched('open-lms-autologin', url: $expectedUrl);
});

test('kiosk triggers lms autologin when student has lms account and ouro moderno is configured', function () {
    config()->set('services.ouro_moderno.url', 'https://escola.ouromoderno.com.br');
    config()->set('services.ouro_moderno.token', 'test-token');

    $date = Carbon::today();
    $fixture = createSurveyFixture('WITH-LMS', $date);

    $fixture['student']->update([
        'ouro_moderno_usuario' => 'aluno.lms',
        'ouro_moderno_senha' => 'segredo123',
    ]);

    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    $this->travelTo($date->copy()->setTime(14, 15));

    $expectedUrl = route('kiosk.lms-autologin', $fixture['contract']);

    Livewire::test(StudentAttendanceKiosk::class)
        ->set('data.contract_number', $fixture['contract']->contract_number)
        ->call('confirmPresence')
        ->assertNotified()
        ->assertDispatched('open-lms-autologin', url: $expectedUrl);
});

test('lms autologin endpoint returns html with form pointing to ouro moderno login url and student credentials', function () {
    config()->set('services.ouro_moderno.url', 'https://escola.ouromoderno.com.br');
    config()->set('services.ouro_moderno.token', 'test-token');

    $date = Carbon::today();
    $fixture = createSurveyFixture('ENDPOINT', $date);

    $fixture['student']->update([
        'ouro_moderno_usuario' => 'aluno.endpoint',
        'ouro_moderno_senha' => 'senhaSuperSecreta',
    ]);

    $this->travelTo($date->copy()->setTime(14, 15));

    $response = $this->get(route('kiosk.lms-autologin', $fixture['contract']));

    $response->assertSuccessful()
        ->assertSee('action="https://escola.ouromoderno.com.br/logar.php?acao=logar"', false)
        ->assertSee('value="'.$fixture['contract']->contract_number.'"', false)
        ->assertSee('value="senhaSuperSecreta"', false)
        ->assertSee('Conectando ao LMS Ouro Moderno');
});

test('lms autologin uses contract number as fallback password when ouro_moderno_senha is not set', function () {
    config()->set('services.ouro_moderno.url', 'https://escola.ouromoderno.com.br');
    config()->set('services.ouro_moderno.token', 'test-token');

    $date = Carbon::today();
    $fixture = createSurveyFixture('FALLBACK-PWD', $date);

    $fixture['student']->update([
        'ouro_moderno_cod_int' => 9999,
        'ouro_moderno_senha' => null,
    ]);

    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    $this->travelTo($date->copy()->setTime(14, 15));

    $expectedUrl = route('kiosk.lms-autologin', $fixture['contract']);

    Livewire::test(StudentAttendanceKiosk::class)
        ->set('data.contract_number', $fixture['contract']->contract_number)
        ->call('confirmPresence')
        ->assertNotified()
        ->assertDispatched('open-lms-autologin', url: $expectedUrl);

    $response = $this->get($expectedUrl);
    $response->assertSuccessful()
        ->assertSee('value="'.$fixture['contract']->contract_number.'"', false);
});

test('kiosk redirects to student portal when student is not from ouro moderno even if person has credentials', function () {
    config()->set('services.ouro_moderno.url', 'https://escola.ouromoderno.com.br');
    config()->set('services.ouro_moderno.token', 'test-token');

    $date = Carbon::today();
    $fixture = createSurveyFixture('LOCAL-LMS', $date);

    // Cria curso cadastrado localmente (ouro_moderno_id nulo)
    $localCourse = Course::create([
        'name' => 'Curso Local Aurosoft',
        'active' => true,
        'ouro_moderno_id' => null,
    ]);

    $contractItem = $fixture['contract']->items()->first();
    $contractItem->formation->courses()->attach($localCourse->id, ['order' => 1]);
    $contractItem->contractCourses()->create([
        'course_id' => $localCourse->id,
        'status' => 'in_progress',
    ]);

    // Mesmo que a pessoa tenha credenciais de Ouro Moderno salvas no perfil
    $fixture['student']->update([
        'ouro_moderno_usuario' => 'aluno.antigo',
        'ouro_moderno_cod_int' => 8888,
        'ouro_moderno_senha' => 'senhaAntiga123',
    ]);

    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    $this->travelTo($date->copy()->setTime(14, 15));

    $expectedUrl = route('student.kiosk-autologin', ['contract' => $fixture['contract']->id]);

    // No Totem, o aluno confirma presença e é redirecionado ao Portal do Aluno Aurosoft
    Livewire::test(StudentAttendanceKiosk::class)
        ->set('data.contract_number', $fixture['contract']->contract_number)
        ->call('confirmPresence')
        ->assertNotified()
        ->assertDispatched('open-lms-autologin', url: $expectedUrl);
});

test('lms autologin endpoint redirects to student portal when contract has only local aurosoft lms courses', function () {
    config()->set('services.ouro_moderno.url', 'https://escola.ouromoderno.com.br');
    config()->set('services.ouro_moderno.token', 'test-token');

    $date = Carbon::today();
    $fixture = createSurveyFixture('LOCAL-ENDPOINT', $date);

    $localCourse = Course::create([
        'name' => 'Curso Exclusivo Aurosoft',
        'active' => true,
        'ouro_moderno_id' => null,
    ]);

    $contractItem = $fixture['contract']->items()->first();
    $contractItem->formation->courses()->attach($localCourse->id, ['order' => 1]);
    $contractItem->contractCourses()->create([
        'course_id' => $localCourse->id,
        'status' => 'in_progress',
    ]);

    $response = $this->get(route('kiosk.lms-autologin', $fixture['contract']));

    $response->assertRedirect(route('student.portal'));
});

test('totem redirects to student portal when student has not been migrated to ouro even if course is from ouro moderno', function () {
    config()->set('services.ouro_moderno.url', 'https://escola.ouromoderno.com.br');
    config()->set('services.ouro_moderno.token', 'test-token');

    $date = Carbon::today();
    $fixture = createSurveyFixture('UNMIGRATED', $date);

    // O curso no catálogo É da Ouro Moderno (ouro_moderno_id = 999)
    $omCourse = Course::create([
        'name' => 'Curso Ouro Moderno Não Sincronizado',
        'active' => true,
        'ouro_moderno_id' => 999,
    ]);

    $contractItem = $fixture['contract']->items()->first();
    $contractItem->formation->courses()->attach($omCourse->id, ['order' => 1]);
    $contractItem->contractCourses()->create([
        'course_id' => $omCourse->id,
        'status' => 'in_progress',
    ]);

    // O aluno NÃO foi migrado para a Ouro Moderno (todos os campos de integração nulos)
    $fixture['student']->update([
        'ouro_moderno_usuario' => null,
        'ouro_moderno_cod_int' => null,
        'ouro_moderno_senha' => null,
        'ouro_moderno_registered_at' => null,
    ]);

    expect($fixture['student']->isMigratedToOuroModerno())->toBeFalse();

    $this->artisan('attendance:generate-daily', ['--date' => $date->toDateString()])
        ->assertSuccessful();

    $this->travelTo($date->copy()->setTime(14, 15));

    $expectedUrl = route('student.kiosk-autologin', ['contract' => $fixture['contract']->id]);

    // No Totem, o aluno confirma presença e é direcionado ao Portal do Aluno
    Livewire::test(StudentAttendanceKiosk::class)
        ->set('data.contract_number', $fixture['contract']->contract_number)
        ->call('confirmPresence')
        ->assertNotified()
        ->assertDispatched('open-lms-autologin', url: $expectedUrl);

    // Ao tentar acessar o endpoint de login direto, retorna 404 informando que o aluno não foi migrado
    $response = $this->get(route('kiosk.lms-autologin', $fixture['contract']));
    $response->assertNotFound();
});

test('responses relation manager view_answers action renders modal without type error', function () {
    $date = Carbon::today();
    $fixture = createSurveyFixture('RELATION_MGR', $date);

    $survey = \App\Models\SatisfactionSurvey::create([
        'title' => 'Pesquisa de Avaliação',
        'is_active' => true,
    ]);

    $question = $survey->questions()->create([
        'question' => 'O que achou da aula de hoje?',
        'type' => 'rating_5',
        'sort_order' => 1,
    ]);

    $response = $survey->responses()->create([
        'contract_id' => $fixture['contract']->id,
        'student_id' => $fixture['student']->id,
    ]);

    $response->answers()->create([
        'satisfaction_survey_question_id' => $question->id,
        'rating_value' => 5,
    ]);

    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

    Livewire::test(\App\Filament\Resources\SatisfactionSurveys\RelationManagers\ResponsesRelationManager::class, [
        'ownerRecord' => $survey,
        'pageClass' => \App\Filament\Resources\SatisfactionSurveys\Pages\EditSatisfactionSurvey::class,
    ])
        ->mountTableAction('view_answers', $response)
        ->assertHasNoTableActionErrors();
});
