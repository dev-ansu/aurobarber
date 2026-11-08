<?php

use App\Filament\Resources\Contracts\Pages\ListContracts;
use App\Filament\Resources\Contracts\Tables\ContractsTable;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->adminPerson = Person::create([
        'name' => 'Admin Secretaria',
        'email' => 'secretaria@aurosoft.com',
    ]);

    $this->admin = User::create([
        'person_id' => $this->adminPerson->id,
        'email' => 'secretaria@aurosoft.com',
        'password' => bcrypt('secret'),
    ]);

    $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->admin->assignRole($superAdmin);
    $this->admin->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('ViewAny:Contract', 'web'));
    $this->admin->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('Update:Contract', 'web'));

    $this->formation = Formation::create([
        'name' => 'Gestão Empresarial',
        'active' => true,
    ]);
});

it('correctly filters active contracts with 100% completed formations in pending completion tab', function () {
    $student1 = Person::create(['name' => 'Aluno Concluído', 'email' => 'concluido@teste.com']);
    $resp1 = Responsible::create(['person_id' => $student1->id, 'relationship' => \App\Enums\RelationshipEnum::Outro, 'financial_responsible' => true]);

    // Contrato 1: Ativo, formação concluída
    $c1 = Contract::create([
        'contract_number' => 'CTR-CONCLUIDO',
        'student_id' => $student1->id,
        'responsible_id' => $resp1->id,
        'start_date' => today(),
        'total_value' => 1000,
        'status' => 'active',
    ]);
    ContractItem::create([
        'contract_id' => $c1->id,
        'formation_id' => $this->formation->id,
        'formation_completed_at' => now(),
    ]);

    // Contrato 2: Ativo, formação AINDA NÃO concluída
    $student2 = Person::create(['name' => 'Aluno Em Andamento', 'email' => 'andamento@teste.com']);
    $resp2 = Responsible::create(['person_id' => $student2->id, 'relationship' => \App\Enums\RelationshipEnum::Outro, 'financial_responsible' => true]);
    $c2 = Contract::create([
        'contract_number' => 'CTR-ANDAMENTO',
        'student_id' => $student2->id,
        'responsible_id' => $resp2->id,
        'start_date' => today(),
        'total_value' => 1000,
        'status' => 'active',
    ]);
    ContractItem::create([
        'contract_id' => $c2->id,
        'formation_id' => $this->formation->id,
        'formation_completed_at' => null,
    ]);

    // Contrato 3: Já Finalizado pela secretaria
    $student3 = Person::create(['name' => 'Aluno Já Finalizado', 'email' => 'finalizado@teste.com']);
    $resp3 = Responsible::create(['person_id' => $student3->id, 'relationship' => \App\Enums\RelationshipEnum::Outro, 'financial_responsible' => true]);
    $c3 = Contract::create([
        'contract_number' => 'CTR-FINALIZADO',
        'student_id' => $student3->id,
        'responsible_id' => $resp3->id,
        'start_date' => today(),
        'total_value' => 1000,
        'status' => 'finished',
    ]);
    ContractItem::create([
        'contract_id' => $c3->id,
        'formation_id' => $this->formation->id,
        'formation_completed_at' => now(),
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(ListContracts::class)
        ->assertSuccessful()
        ->assertSee('Aguardando Conclusão')
        ->set('activeTab', 'pending_completion');

    // Apenas CTR-CONCLUIDO deve aparecer na aba
    $component->assertSee('CTR-CONCLUIDO')
        ->assertDontSee('CTR-ANDAMENTO')
        ->assertDontSee('CTR-FINALIZADO');
});

it('allows secretaria to quickly finish contract directly from the table action', function () {
    $student = Person::create(['name' => 'Aluno Formando', 'email' => 'formando@teste.com']);
    $resp = Responsible::create(['person_id' => $student->id, 'relationship' => \App\Enums\RelationshipEnum::Outro, 'financial_responsible' => true]);

    $contract = Contract::create([
        'contract_number' => 'CTR-QUICK-FINISH',
        'student_id' => $student->id,
        'responsible_id' => $resp->id,
        'start_date' => today(),
        'total_value' => 1000,
        'status' => 'active',
    ]);
    ContractItem::create([
        'contract_id' => $contract->id,
        'formation_id' => $this->formation->id,
        'formation_completed_at' => now(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(ListContracts::class)
        ->callTableAction('finishContract', $contract, ['reason' => 'Formado com louvor e certificado emitido'])
        ->assertHasNoTableActionErrors();

    expect($contract->fresh()->status)->toBe('finished');
});

it('renders contracts stats overview cards matching each badge and status', function () {
    $student = Person::create(['name' => 'Aluno Stats', 'email' => 'stats@teste.com']);
    $resp = Responsible::create(['person_id' => $student->id, 'relationship' => \App\Enums\RelationshipEnum::Outro, 'financial_responsible' => true]);

    $c1 = Contract::create([
        'contract_number' => 'CTR-ST-1',
        'student_id' => $student->id,
        'responsible_id' => $resp->id,
        'start_date' => today(),
        'total_value' => 1500,
        'status' => 'active',
    ]);
    ContractItem::create([
        'contract_id' => $c1->id,
        'formation_id' => $this->formation->id,
        'formation_completed_at' => now(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(\App\Filament\Resources\Contracts\Widgets\ContractsStatsOverview::class)
        ->assertSuccessful()
        ->assertSee('Total de Contratos')
        ->assertSee('Contratos Ativos')
        ->assertSee('Aguardando Conclusão')
        ->assertSee('Finalizados')
        ->assertSee('1.500,00');
});

it('hides financial revenue amounts from secretary without financial permissions', function () {
    $secretaryPerson = Person::create(['name' => 'Secretária Atendimento', 'email' => 'atendimento@teste.com']);
    $secretary = User::create([
        'person_id' => $secretaryPerson->id,
        'email' => 'atendimento@teste.com',
        'password' => bcrypt('secret'),
    ]);
    $secretary->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate('ViewAny:Contract', 'web'));

    $student = Person::create(['name' => 'Aluno Sec', 'email' => 'sec@teste.com']);
    $resp = Responsible::create(['person_id' => $student->id, 'relationship' => \App\Enums\RelationshipEnum::Outro, 'financial_responsible' => true]);

    $c1 = Contract::create([
        'contract_number' => 'CTR-SEC-1',
        'student_id' => $student->id,
        'responsible_id' => $resp->id,
        'start_date' => today(),
        'total_value' => 9999.00,
        'status' => 'active',
    ]);

    Livewire::actingAs($secretary)
        ->test(\App\Filament\Resources\Contracts\Widgets\ContractsStatsOverview::class)
        ->assertSuccessful()
        ->assertSee('Total de Contratos')
        ->assertSee('Total de matrículas na base')
        ->assertSee('Alunos cursando atualmente')
        ->assertDontSee('9.999,00')
        ->assertDontSee('contratados');
});


