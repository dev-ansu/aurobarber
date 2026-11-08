<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\City;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Person;
use App\Models\School;
use App\Models\State;
use App\Models\User;
use App\Services\OuroModernoEadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OuroModernoEadTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $state = State::query()->firstOrCreate(['acronym' => 'SP'], ['name' => 'São Paulo']);
        City::query()->firstOrCreate(['name' => 'São Paulo'], ['state_id' => $state->id, 'ibge_code' => '3550308']);

        $this->school = School::query()->create([
            'legal_name' => 'Escola EAD Teste LTDA',
            'trade_name' => 'Aurosoft EAD School',
            'cnpj' => '11.222.333/0001-99',
            'ouro_moderno_ead_enabled' => true,
            'ouro_moderno_ead_api_key' => '53afd7ab97449cdbb682b6b7e335c524',
            'ouro_moderno_ead_unidade_id' => '1416',
            'ouro_moderno_ead_unidade_nome' => 'PREPARA FORTALEZA',
        ]);

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $adminPerson = Person::query()->create(['name' => 'Admin EAD']);
        $this->admin = User::query()->create([
            'person_id' => $adminPerson->id,
            'email' => 'admin_ead@aurosoft.test',
            'password' => bcrypt('password'),
            'panel_access' => true,
        ]);
        $this->admin->assignRole($superAdminRole);
    }

    public function test_contract_has_ead_flags_and_scopes(): void
    {
        $person = Person::query()->create([
            'name' => 'Aluno EAD e Flex',
            'birth_date' => '2005-04-12',
        ]);

        $contract = Contract::query()->create([
            'school_id' => $this->school->id,
            'student_id' => $person->id,
            'contract_number' => 'CTR-EAD-01',
            'start_date' => today(),
            'is_flex' => true,
            'is_ead' => true,
            'ead_student_id' => '998877',
            'total_value' => 200.00,
        ]);

        $this->assertTrue($contract->isFlex());
        $this->assertTrue($contract->isEad());
        $this->assertCount(1, Contract::ead()->get());
        $this->assertCount(1, Contract::flex()->get());
    }

    public function test_ead_service_test_connection(): void
    {
        Http::fake([
            'https://meuappdecursos.com.br/ws/v2/unidades' => Http::response([
                'status' => 'true',
                'data' => [
                    [
                        'id' => '1416',
                        'nome' => 'PREPARA FORTALEZA',
                    ],
                ],
            ], 200),
        ]);

        $service = app(OuroModernoEadService::class);
        $res = $service->testConnection('fake_api_key');

        $this->assertTrue($res['success']);
        $this->assertEquals('1416', $res['unidade']['id']);
        $this->assertStringContainsString('PREPARA FORTALEZA', $res['message']);
    }

    public function test_ead_service_registers_student_manually_without_auto_creating_contracts(): void
    {
        $studentData = [
            'id' => '5010',
            'nome' => 'Carlos Aluno Importado',
            'email' => 'carlos.importado@ead.test',
            'cpf' => '12345678900',
            'telefone' => '11999998888',
            'data_nascimento' => '2000-10-29',
        ];

        $service = app(OuroModernoEadService::class);
        $person = $service->registerEadStudent($this->school, $studentData);

        $this->assertEquals('Carlos Aluno Importado', $person->name);
        $this->assertEquals('2000-10-29', Carbon::parse($person->birth_date)->format('Y-m-d'));
        $this->assertTrue($person->schools()->where('school_id', $this->school->id)->exists());

        // Contratos NÃO são criados automaticamente!
        $this->assertDatabaseMissing('contracts', [
            'school_id' => $this->school->id,
            'is_ead' => true,
            'student_id' => $person->id,
        ]);

        // Criação manual sob demanda funciona perfeitamente
        $contract = $service->createEadContract($this->school, $person, '5010');
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'school_id' => $this->school->id,
            'is_ead' => true,
            'student_id' => $person->id,
            'ead_student_id' => '5010',
        ]);
    }

    public function test_ead_service_syncs_student_progress(): void
    {
        $person = Person::query()->create(['name' => 'Aluno Sincronismo']);
        $contract = Contract::query()->create([
            'school_id' => $this->school->id,
            'student_id' => $person->id,
            'contract_number' => 'CTR-SYNC-01',
            'start_date' => today(),
            'is_ead' => true,
            'ead_student_id' => '7722',
        ]);

        Http::fake([
            'https://meuappdecursos.com.br/ws/v2/alunos/cursos/7722' => Http::response([
                'status' => 'true',
                'data' => [
                    ['id_curso' => 10, 'nome' => 'Excel Avançado', 'progresso' => 80.0],
                    ['id_curso' => 12, 'nome' => 'Power BI', 'progresso' => 60.0],
                ],
            ], 200),
        ]);

        $service = app(OuroModernoEadService::class);
        $res = $service->syncStudentProgress($contract, 'fake_key');

        $this->assertTrue($res['success']);
        $this->assertEquals(70.0, $res['progress_percentage']);

        $contract->refresh();
        $this->assertEquals(70.0, (float) $contract->ead_last_progress_percentage);
        $this->assertNotNull($contract->ead_synced_at);
    }

    public function test_ead_service_generates_auto_login_url(): void
    {
        $person = Person::query()->create(['name' => 'Aluno Login']);
        $contract = Contract::query()->create([
            'school_id' => $this->school->id,
            'student_id' => $person->id,
            'contract_number' => 'CTR-LOGIN-01',
            'start_date' => today(),
            'is_ead' => true,
            'ead_student_id' => '8833',
        ]);

        Http::fake([
            'https://meuappdecursos.com.br/ws/v2/alunos/token/8833' => Http::response([
                'status' => 'true',
                'data' => ['token' => 'secure_ead_token_abc123'],
            ], 200),
        ]);

        $service = app(OuroModernoEadService::class);
        $url = $service->getStudentAutoLoginUrl($contract, 'fake_key');

        $this->assertEquals('https://meuappdecursos.com.br/index.php?pag=entrar&token=secure_ead_token_abc123', $url);
    }

    public function test_ouro_moderno_ead_students_page_renders_successfully(): void
    {
        $person = Person::query()->create([
            'name' => 'Aluno EAD Na Tela',
            'birth_date' => Carbon::now()->format('Y-m-d'),
        ]);

        Contract::query()->create([
            'school_id' => $this->school->id,
            'student_id' => $person->id,
            'contract_number' => 'CTR-EAD-TELA-01',
            'start_date' => today(),
            'is_ead' => true,
            'ead_student_id' => '1100',
            'ead_last_progress_percentage' => 45.0,
        ]);

        $this->actingAs($this->admin);
        \Filament\Facades\Filament::setTenant($this->school);
        session(['filament_tenant_id' => $this->school->id]);

        Livewire::test(\App\Filament\Pages\OuroModernoEadStudents::class)
            ->set('activeTab', 'contracts')
            ->assertSuccessful()
            ->assertSee('Aluno EAD Na Tela')
            ->assertSee('CTR-EAD-TELA-01')
            ->assertSee('45%');
    }

    public function test_rollback_command_safely_removes_ead_contracts_and_orphan_people(): void
    {
        // 1. Contrato e pessoa legítimos da escola (NUNCA podem ser apagados)
        $legitPerson = Person::query()->create(['name' => 'Aluno Legítimo da Escola']);
        $legitContract = Contract::query()->create([
            'school_id' => $this->school->id,
            'student_id' => $legitPerson->id,
            'contract_number' => 'CTR-LEGIT-001',
            'start_date' => today(),
            'total_value' => 500.00,
        ]);

        // 2. Pessoa legítima que também recebeu um contrato EAD automático
        $existingPerson = Person::query()->create(['name' => 'Aluno Existente com EAD']);
        Contract::query()->create([
            'school_id' => $this->school->id,
            'student_id' => $existingPerson->id,
            'contract_number' => 'CTR-REAL-002',
            'start_date' => today(),
            'total_value' => 600.00,
        ]);
        Contract::query()->create([
            'school_id' => $this->school->id,
            'student_id' => $existingPerson->id,
            'contract_number' => 'EAD-99001',
            'is_ead' => true,
            'start_date' => today(),
            'total_value' => 0.00,
        ]);

        // 3. Pessoas e contratos órfãos gerados automaticamente em lote
        for ($i = 1; $i <= 3; $i++) {
            $orphanPerson = Person::query()->create(['name' => "Aluno Órfão {$i}"]);
            Contract::query()->create([
                'school_id' => $this->school->id,
                'student_id' => $orphanPerson->id,
                'contract_number' => "EAD-8800{$i}",
                'is_ead' => true,
                'start_date' => today(),
                'total_value' => 0.00,
            ]);
        }

        $this->assertEquals(6, Contract::withoutGlobalScopes()->count());
        $this->assertEquals(6, Person::withoutGlobalScopes()->count());

        // Executa rollback com --force
        $this->artisan('ead:rollback-imported-contracts', ['--force' => true])
            ->assertSuccessful();

        // 4. Validações de integridade
        // Somente os contratos legítimos devem sobrar
        $this->assertEquals(2, Contract::withoutGlobalScopes()->count());
        $this->assertDatabaseHas('contracts', ['contract_number' => 'CTR-LEGIT-001']);
        $this->assertDatabaseHas('contracts', ['contract_number' => 'CTR-REAL-002']);
        $this->assertDatabaseMissing('contracts', ['contract_number' => 'EAD-99001']);
        $this->assertDatabaseMissing('contracts', ['contract_number' => 'EAD-88001']);

        // Somente as pessoas legítimas devem sobrar (órfãos foram deletados, existente e admin preservados)
        $this->assertEquals(3, Person::withoutGlobalScopes()->count());
        $this->assertDatabaseHas('people', ['name' => 'Admin EAD']);
        $this->assertDatabaseHas('people', ['name' => 'Aluno Legítimo da Escola']);
        $this->assertDatabaseHas('people', ['name' => 'Aluno Existente com EAD']);
        $this->assertDatabaseMissing('people', ['name' => 'Aluno Órfão 1']);
        $this->assertDatabaseMissing('people', ['name' => 'Aluno Órfão 2']);
        $this->assertDatabaseMissing('people', ['name' => 'Aluno Órfão 3']);
    }

    public function test_two_way_migration_between_presencial_and_ead(): void
    {
        Http::fake([
            'https://meuappdecursos.com.br/ws/v2/unidades/token/1416' => Http::response([
                'status' => 'true',
                'data' => ['id' => '1416', 'token' => 'unit_fake_token_123'],
            ], 200),
            'https://meuappdecursos.com.br/ws/v2/alunos' => Http::response([
                'status' => 'true',
                'data' => ['id' => '995544', 'nome' => 'Aluno Teste Mao Dupla'],
            ], 200),
            'https://meuappdecursos.com.br/ws/v2/alunos/matricula/995544' => Http::response([
                'status' => 'true',
                'data' => ['id' => 1234, 'data_fim' => '2027-01-01'],
            ], 200),
            'https://meuappdecursos.com.br/ws/v2/alunos/token/995544' => Http::response([
                'status' => 'true',
                'data' => ['token' => 'student_token_xyz789'],
            ], 200),
            'https://meuappdecursos.com.br/ws/v2/alunos/cursos/995544' => Http::response([
                'status' => 'true',
                'data' => [
                    ['id' => '35', 'curso' => 'Administrativo', 'progresso' => ['percentual' => 85.0]],
                ],
            ], 200),
        ]);

        $person = Person::query()->create([
            'name' => 'Aluno Teste Mao Dupla',
            'email' => 'maodupla@aurosoft.test',
            'birth_date' => '2001-05-10',
        ]);

        $contract = Contract::query()->create([
            'school_id' => $this->school->id,
            'student_id' => $person->id,
            'contract_number' => 'CTR-DUPLA-01',
            'start_date' => today(),
            'is_ead' => false,
            'status' => 'active',
            'total_value' => 1200.00,
        ]);

        $service = app(OuroModernoEadService::class);

        // 1. VIA DE IDA: Presencial -> EAD
        $resIda = $service->migrateContractToEad($contract);
        $this->assertTrue($resIda['success']);
        $this->assertEquals('995544', $resIda['ead_student_id']);
        $this->assertStringContainsString('student_token_xyz789', $resIda['auto_login_url']);

        $contract->refresh();
        $this->assertTrue($contract->is_ead);
        $this->assertEquals('995544', $contract->ead_student_id);

        // 2. VIA DE VOLTA: EAD -> Presencial (LMS Local)
        $resVolta = $service->migrateContractToLocalLms($contract);
        $this->assertTrue($resVolta['success']);

        $contract->refresh();
        $this->assertFalse($contract->is_ead);
        $this->assertEquals(85.0, (float) $contract->ead_last_progress_percentage);
    }

    public function test_classroom_live_monitor_renders_ead_online_students(): void
    {
        Http::fake([
            'https://meuappdecursos.com.br/ws/v2/relatorio/alunos_log*' => Http::response([
                [
                    'id' => 123456,
                    'aluno' => '99887766',
                    'autor' => 'Maria Yasmin Ao Vivo',
                    'curso' => '599',
                    'aula' => '4',
                    'conteudo' => 'Aluno acessou a aula 4',
                    'data' => now()->format('d/m/Y H:i:s'),
                    'IP' => '201.20.97.246',
                ],
            ], 200),
        ]);

        $this->actingAs($this->admin);
        \Filament\Facades\Filament::setTenant($this->school);
        session(['filament_tenant_id' => $this->school->id]);

        Livewire::test(\App\Filament\Pages\ClassroomLiveMonitor::class)
            ->set('activeMode', 'ead')
            ->assertSuccessful()
            ->assertSee('Maria Yasmin Ao Vivo')
            ->assertSee('ONLINE AGORA')
            ->assertSee('Aluno acessou a aula 4');
    }
}
