<?php

declare(strict_types=1);

use App\Actions\GenerateStudentAttendancesForDate;
use App\Enums\RelationshipEnum;
use App\Filament\Kiosk\Pages\StudentAttendanceKiosk;
use App\Filament\Resources\Collaborators\CollaboratorResource;
use App\Models\Classroom;
use App\Models\Collaborator;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Formation;
use App\Models\IntegrationSetting;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use App\Services\OuroModernoContractSyncService;
use App\Services\OuroModernoService;
use Filament\Facades\Filament;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('OuroModernoService isolates Tailscale URL and token strictly per school without leaking across tenants', function () {
    config([
        'services.ouro_moderno.url' => '',
        'services.ouro_moderno.token' => '',
    ]);

    $matriz = School::create(['legal_name' => 'Escola Matriz', 'trade_name' => 'Unidade Matriz']);
    $filial = School::create(['legal_name' => 'Escola Filial Y', 'trade_name' => 'Unidade Y']);
    $semIntegracao = School::create(['legal_name' => 'Escola Sem LMS', 'trade_name' => 'Unidade Z']);

    IntegrationSetting::create([
        'school_id' => $matriz->id,
        'provider' => 'ouro_moderno',
        'base_url' => 'https://ouro-matriz.tail1111.ts.net',
        'token' => 'token-exclusivo-matriz',
    ]);

    IntegrationSetting::create([
        'school_id' => $filial->id,
        'provider' => 'ouro_moderno',
        'base_url' => 'https://ouro-escola-y.tail2222.ts.net',
        'token' => 'token-exclusivo-filial-y',
    ]);

    $service = app(OuroModernoService::class);

    expect($service->forSchool($matriz->id)->isConfigured())->toBeTrue()
        ->and($service->forSchool($matriz->id)->getLoginUrl())->toBe('https://ouro-matriz.tail1111.ts.net/logar.php?acao=logar')
        ->and($service->forSchool($filial->id)->isConfigured())->toBeTrue()
        ->and($service->forSchool($filial->id)->getLoginUrl())->toBe('https://ouro-escola-y.tail2222.ts.net/logar.php?acao=logar')
        ->and($service->forSchool($semIntegracao->id)->isConfigured())->toBeFalse();
});

test('Kiosk marks attendance per school and redirects each contract to its own school Tailscale Ouro Moderno URL', function () {
    Carbon::setTestNow(Carbon::today()->setTime(14, 30, 0));
    config([
        'services.ouro_moderno.url' => '',
        'services.ouro_moderno.token' => '',
    ]);

    $matriz = School::create(['legal_name' => 'Escola Matriz', 'trade_name' => 'Unidade Matriz']);
    $filial = School::create(['legal_name' => 'Escola Y', 'trade_name' => 'Unidade Y']);

    IntegrationSetting::create([
        'school_id' => $matriz->id,
        'provider' => 'ouro_moderno',
        'base_url' => 'https://ouro-matriz.tail1111.ts.net',
        'token' => 'token-matriz',
    ]);

    IntegrationSetting::create([
        'school_id' => $filial->id,
        'provider' => 'ouro_moderno',
        'base_url' => 'https://ouro-escola-y.tail2222.ts.net',
        'token' => 'token-filial-y',
    ]);

    $omCourse = Course::syncingFromOuroModerno(fn () => Course::create([
        'name' => 'Excel Avançado OM',
        'workload' => 40,
        'ouro_moderno_id' => 101,
        'active' => true,
    ]));

    $formation = Formation::create([
        'name' => 'Formação Empresarial',
        'active' => true,
    ]);
    $formation->courses()->attach($omCourse->id, ['order' => 1]);

    // Cria contrato e horário na Matriz
    $classroomMatriz = Classroom::withoutGlobalScopes()->create([
        'school_id' => $matriz->id,
        'name' => 'Lab Matriz 01',
        'capacity' => 15,
    ]);
    $studentMatriz = Person::create([
        'name' => 'Lucas Matriz',
        'cpf' => '11122233344',
        'email' => 'lucas.matriz@teste.local',
        'ouro_moderno_cod_int' => 501,
        'ouro_moderno_usuario' => 'MAT-001',
        'ouro_moderno_senha' => 'senhaMatriz123',
        'ouro_moderno_registered_at' => now(),
    ]);
    $respMatriz = Responsible::create([
        'person_id' => $studentMatriz->id,
        'relationship' => RelationshipEnum::Legal_guardian,
        'financial_responsible' => true,
    ]);
    $contractMatriz = Contract::withoutGlobalScopes()->create([
        'school_id' => $matriz->id,
        'contract_number' => 'MAT-001',
        'student_id' => $studentMatriz->id,
        'responsible_id' => $respMatriz->id,
        'status' => 'active',
        'start_date' => today()->subDays(5),
        'total_value' => 1200,
    ]);
    $itemMatriz = ContractItem::create([
        'contract_id' => $contractMatriz->id,
        'formation_id' => $formation->id,
        'order' => 1,
    ]);
    ContractCourse::create([
        'contract_item_id' => $itemMatriz->id,
        'course_id' => $omCourse->id,
        'order' => 1,
        'status' => 'in_progress',
        'progress_source' => 'ouro_moderno',
        'lms_synced_at' => now(),
    ]);
    StudentSchedule::withoutGlobalScopes()->create([
        'school_id' => $matriz->id,
        'contract_item_id' => $itemMatriz->id,
        'classroom_id' => $classroomMatriz->id,
        'weekday' => now()->dayOfWeek,
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);

    // Gera chamada previamente SOMENTE para a Matriz (simulando que a Escola Y ainda não gerou hoje!)
    app(GenerateStudentAttendancesForDate::class)->execute(now(), $matriz->id, ignoreTenantScope: true);
    expect(StudentAttendance::withoutGlobalScopes()->where('school_id', $matriz->id)->count())->toBe(1);

    // Agora cria contrato e horário na Escola Y (sem chamada gerada ainda!)
    $classroomFilial = Classroom::withoutGlobalScopes()->create([
        'school_id' => $filial->id,
        'name' => 'Lab Filial Y 01',
        'capacity' => 15,
    ]);
    $studentFilial = Person::create([
        'name' => 'Mariana Escola Y',
        'cpf' => '55566677788',
        'email' => 'mariana.y@teste.local',
        'ouro_moderno_cod_int' => 502,
        'ouro_moderno_usuario' => 'FIL-001',
        'ouro_moderno_senha' => 'senhaFilial456',
        'ouro_moderno_registered_at' => now(),
    ]);
    $respFilial = Responsible::create([
        'person_id' => $studentFilial->id,
        'relationship' => RelationshipEnum::Legal_guardian,
        'financial_responsible' => true,
    ]);
    $contractFilial = Contract::withoutGlobalScopes()->create([
        'school_id' => $filial->id,
        'contract_number' => 'FIL-001',
        'student_id' => $studentFilial->id,
        'responsible_id' => $respFilial->id,
        'status' => 'active',
        'start_date' => today()->subDays(5),
        'total_value' => 1200,
    ]);
    $itemFilial = ContractItem::create([
        'contract_id' => $contractFilial->id,
        'formation_id' => $formation->id,
        'order' => 1,
    ]);
    ContractCourse::create([
        'contract_item_id' => $itemFilial->id,
        'course_id' => $omCourse->id,
        'order' => 1,
        'status' => 'in_progress',
        'progress_source' => 'ouro_moderno',
        'lms_synced_at' => now(),
    ]);
    StudentSchedule::withoutGlobalScopes()->create([
        'school_id' => $filial->id,
        'contract_item_id' => $itemFilial->id,
        'classroom_id' => $classroomFilial->id,
        'weekday' => now()->dayOfWeek,
        'start_hour' => '14:00:00',
        'end_hour' => '15:00:00',
    ]);

    expect(StudentAttendance::withoutGlobalScopes()->where('school_id', $filial->id)->count())->toBe(0);

    // Mesmo que o contexto Filament estivesse apontando para Matriz, o Kiosk deve funcionar para Escola Y!
    Filament::setTenant($matriz, isQuiet: true);

    Livewire::test(StudentAttendanceKiosk::class)
        ->set('data.contract_number', 'FIL-001')
        ->call('confirmPresence')
        ->assertDispatched(
            'open-lms-autologin',
            url: route('kiosk.lms-autologin', ['contract' => $contractFilial->id]),
            target: 'OuroModernoLMS',
        );

    $filialAttendance = StudentAttendance::withoutGlobalScopes()->where('school_id', $filial->id)->first();
    expect($filialAttendance)->not->toBeNull()
        ->and($filialAttendance->status)->toBe('present')
        ->and($filialAttendance->marking_source)->toBe('kiosk');

    // Testa o endpoint de autologin da Escola Y -> deve postar para o Tailscale da Escola Y!
    $responseFilial = $this->get(route('kiosk.lms-autologin', ['contract' => $contractFilial->id]));
    $responseFilial->assertOk()
        ->assertSee('https://ouro-escola-y.tail2222.ts.net/logar.php?acao=logar', escape: false)
        ->assertSee('FIL-001')
        ->assertSee('senhaFilial456')
        ->assertSee('Unidade Y');

    // Testa o endpoint de autologin da Matriz -> deve postar para o Tailscale da Matriz!
    $responseMatriz = $this->get(route('kiosk.lms-autologin', ['contract' => $contractMatriz->id]));
    $responseMatriz->assertOk()
        ->assertSee('https://ouro-matriz.tail1111.ts.net/logar.php?acao=logar', escape: false)
        ->assertSee('MAT-001')
        ->assertSee('senhaMatriz123')
        ->assertSee('Unidade Matriz');
});

test('OuroModernoContractSyncService sends each contract to its own school Tailscale API and token', function () {
    config([
        'services.ouro_moderno.url' => '',
        'services.ouro_moderno.token' => '',
    ]);

    $matriz = School::create(['legal_name' => 'Escola Matriz', 'trade_name' => 'Matriz']);
    $filial = School::create(['legal_name' => 'Escola Y', 'trade_name' => 'Filial Y']);

    IntegrationSetting::create([
        'school_id' => $matriz->id,
        'provider' => 'ouro_moderno',
        'base_url' => 'https://ouro-matriz.tail1111.ts.net',
        'token' => 'tok-matriz',
    ]);
    IntegrationSetting::create([
        'school_id' => $filial->id,
        'provider' => 'ouro_moderno',
        'base_url' => 'https://ouro-escola-y.tail2222.ts.net',
        'token' => 'tok-filial-y',
    ]);

    Http::fake([
        'ouro-escola-y.tail2222.ts.net/*' => Http::response(['status' => 'ok', 'cursos' => []]),
        'ouro-matriz.tail1111.ts.net/*' => Http::response(['error' => 'wrong server'], 500),
    ]);

    $omCourse = Course::syncingFromOuroModerno(fn () => Course::create([
        'name' => 'Informática Essencial',
        'workload' => 30,
        'ouro_moderno_id' => 77,
        'active' => true,
    ]));
    $formation = Formation::create(['name' => 'Formação Essencial', 'active' => true]);

    $student = Person::create([
        'name' => 'Aluno Filial Sync',
        'email' => 'sync.y@teste.local',
    ]);
    $resp = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Legal_guardian,
        'financial_responsible' => true,
    ]);
    $contract = Contract::withoutGlobalScopes()->create([
        'school_id' => $filial->id,
        'contract_number' => 'FIL-SYNC-01',
        'student_id' => $student->id,
        'responsible_id' => $resp->id,
        'status' => 'active',
        'start_date' => today(),
        'total_value' => 900,
    ]);
    $item = ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $formation->id,
        'order' => 1,
    ]);
    ContractCourse::create([
        'contract_item_id' => $item->id,
        'course_id' => $omCourse->id,
        'order' => 1,
        'status' => 'not_started',
    ]);

    $result = app(OuroModernoContractSyncService::class)->sync($contract, 'create', 'senha123');

    expect($result['courses_enrolled'])->toBe(1);

    Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://ouro-escola-y.tail2222.ts.net/api/alunos/cadastrar/')
        && $request['token'] === 'tok-filial-y'
        && $request['usuario'] === 'FIL-SYNC-01');

    Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), 'ouro-matriz.tail1111.ts.net'));
});

test('kiosk:seed-test-contracts creates active test contracts with schedules for each school and allows kiosk check-in', function () {
    Carbon::setTestNow(Carbon::today()->setTime(10, 15, 0));

    $matriz = School::create(['legal_name' => 'Escola Matriz Real', 'trade_name' => 'Matriz']);
    $filial = School::create(['legal_name' => 'Escola Y Real', 'trade_name' => 'Escola Y']);

    $this->artisan('kiosk:seed-test-contracts')
        ->assertSuccessful();

    $omMatriz = Contract::withoutGlobalScopes()->where('contract_number', "TESTE-OM-ESC{$matriz->id}")->first();
    $omFilial = Contract::withoutGlobalScopes()->where('contract_number', "TESTE-OM-ESC{$filial->id}")->first();
    $portalFilial = Contract::withoutGlobalScopes()->where('contract_number', "TESTE-PORTAL-ESC{$filial->id}")->first();

    expect($omMatriz)->not->toBeNull()
        ->and($omMatriz->school_id)->toBe($matriz->id)
        ->and($omFilial)->not->toBeNull()
        ->and($omFilial->school_id)->toBe($filial->id)
        ->and($portalFilial)->not->toBeNull()
        ->and($portalFilial->school_id)->toBe($filial->id);

    Livewire::test(StudentAttendanceKiosk::class)
        ->set('data.contract_number', "TESTE-PORTAL-ESC{$filial->id}")
        ->call('confirmPresence')
        ->assertDispatched(
            'open-lms-autologin',
            url: route('student.kiosk-autologin', ['contract' => $portalFilial->id]),
            target: 'AurosoftStudentPortal',
        );
});

test('CollaboratorResource tenant query filters collaborators per school without SQL error on people table', function () {
    $matriz = School::create(['legal_name' => 'Matriz', 'trade_name' => 'Matriz']);
    $filial = School::create(['legal_name' => 'Filial', 'trade_name' => 'Filial']);

    $personMatriz = Person::create(['name' => 'Prof Matriz', 'email' => 'prof.m@teste.local']);
    $personMatriz->schools()->attach($matriz->id);
    $collabMatriz = Collaborator::create([
        'person_id' => $personMatriz->id,
        'admission_date' => today(),
        'role' => 'Professor',
        'active' => true,
        'is_educator' => true,
    ]);

    $personFilial = Person::create(['name' => 'Prof Filial', 'email' => 'prof.f@teste.local']);
    $personFilial->schools()->attach($filial->id);
    $collabFilial = Collaborator::create([
        'person_id' => $personFilial->id,
        'admission_date' => today(),
        'role' => 'Professor',
        'active' => true,
        'is_educator' => true,
    ]);

    Filament::setTenant($filial, isQuiet: true);

    $ids = CollaboratorResource::getEloquentQuery()
        ->pluck('id')
        ->all();

    expect($ids)->toContain($collabFilial->id)
        ->and($ids)->not->toContain($collabMatriz->id);
});

test('IntegrationSetting and OuroModernoService gracefully handle invalid MAC or mismatched APP_KEY without throwing 500 DecryptException', function () {
    $school = School::create(['legal_name' => 'Escola Teste Key', 'trade_name' => 'Teste Key']);

    // Insere diretamente um ciphertext gerado com outra chave / inválido
    DB::table('integration_settings')->insert([
        'school_id' => $school->id,
        'provider' => 'ouro_moderno',
        'base_url' => 'https://servidor-teste.local',
        'token' => 'eyJpdiI6ImludmFsaWRJViIsInZhbHVlIjoiYmFkRGF0YSIsIm1hYyI6ImJhZE1hYyIsInRhZyI6IiJ9',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $setting = IntegrationSetting::where('school_id', $school->id)->first();
    expect($setting)->not->toBeNull()
        ->and($setting->token)->toBeNull();

    $service = app(OuroModernoService::class)->forSchool($school->id);
    expect(fn () => $service->isConfigured())->not->toThrow(DecryptException::class);
});
