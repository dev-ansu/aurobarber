<?php

use App\Filament\Resources\Contracts\Pages\EditContracts;
use App\Models\Collaborator;
use App\Models\CommercialLead;
use App\Models\Contract;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'secretaria', 'guard_name' => 'web']);
    Permission::findOrCreate('ViewAny:CommercialLead', 'web');
    Permission::findOrCreate('ViewAny:Contract', 'web');
    Permission::findOrCreate('Update:Contract', 'web');
    Permission::findOrCreate('ChangeCommercialLead:Contract', 'web');
    Permission::findOrCreate('ChangeCollaborator:Contract', 'web');
});

test('commercial lead and collaborator fields are enabled on active contract when empty, and lock once filled for user without permission', function () {
    $school = School::create([
        'legal_name' => 'Escola Lead Test LTDA',
        'trade_name' => 'Aurosoft Lead Test',
        'is_headquarters' => true,
    ]);

    $student = Person::create([
        'name' => 'Aluno Lead Test',
        'email' => 'leadtest@example.com',
    ]);

    $respPerson = Person::create([
        'name' => 'Resp Lead Test',
        'email' => 'resplead@example.com',
    ]);

    $resp = Responsible::create([
        'person_id' => $respPerson->id,
        'relationship' => \App\Enums\RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    $collabPerson = Person::create(['name' => 'Vendedor 1', 'email' => 'vendedor1@teste.com']);
    $collab = Collaborator::create([
        'person_id' => $collabPerson->id,
        'school_id' => $school->id,
        'admission_date' => now()->toDateString(),
        'role' => 'seller',
        'active' => true,
    ]);

    // Contrato ATIVO sem commercial_lead_id e sem collaborator_id
    $contract = Contract::create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'responsible_id' => $resp->id,
        'contract_number' => 'CTR-LEAD-001',
        'status' => 'active',
        'start_date' => now()->toDateString(),
        'total_value' => 1200,
        'commercial_lead_id' => null,
        'collaborator_id' => null,
    ]);

    $lead = CommercialLead::create([
        'school_id' => $school->id,
        'person_id' => $student->id,
        'name' => 'Lead do Aluno',
        'status' => 'contacted',
    ]);

    $regularPerson = Person::create(['name' => 'Usuario Secretaria', 'email' => 'secretaria@teste.com']);
    $user = User::factory()->create([
        'person_id' => $regularPerson->id,
        'email' => 'secretaria@teste.com',
    ]);
    $user->assignRole('secretaria');
    $user->givePermissionTo(['ViewAny:CommercialLead', 'ViewAny:Contract', 'Update:Contract']);

    $this->actingAs($user);
    Filament::setTenant($school);

    // 1. No contrato ativo sem lead e sem vendedor preenchidos, ambos os campos devem estar habilitados
    $test = Livewire::test(EditContracts::class, ['record' => $contract->getRouteKey()])
        ->assertFormFieldIsEnabled('commercial_lead_id')
        ->assertFormFieldIsEnabled('collaborator_id');

    // 2. Preenche e salva
    $test->set('data.commercial_lead_id', $lead->id);
    $test->set('data.collaborator_id', $collab->id);
    $test->call('save')
        ->assertHasNoFormErrors();

    // 3. Confirma que foram salvos no banco
    $contract->refresh();
    expect($contract->commercial_lead_id)->toBe($lead->id);
    expect($contract->collaborator_id)->toBe($collab->id);

    $lead->refresh();
    expect($lead->status)->toBe('enrolled');

    // 4. Ao recarregar como usuário sem permissão especial, ambos os campos devem estar bloqueados
    Livewire::test(EditContracts::class, ['record' => $contract->getRouteKey()])
        ->assertFormFieldIsDisabled('commercial_lead_id')
        ->assertFormFieldIsDisabled('collaborator_id');
});

test('user with ChangeCommercialLead and ChangeCollaborator permissions can edit both fields even on active contracts already filled', function () {
    $school = School::create([
        'legal_name' => 'Escola Perm Test LTDA',
        'trade_name' => 'Aurosoft Perm Test',
        'is_headquarters' => true,
    ]);

    $student = Person::create([
        'name' => 'Aluno Perm Test',
        'email' => 'permtest@example.com',
    ]);

    $respPerson = Person::create([
        'name' => 'Resp Perm Test',
        'email' => 'respperm@example.com',
    ]);

    $resp = Responsible::create([
        'person_id' => $respPerson->id,
        'relationship' => \App\Enums\RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    $collab1 = Collaborator::create([
        'person_id' => Person::create(['name' => 'Vendedor Original', 'email' => 'vorig@teste.com'])->id,
        'school_id' => $school->id,
        'admission_date' => now()->toDateString(),
        'role' => 'seller',
        'active' => true,
    ]);
    $collab2 = Collaborator::create([
        'person_id' => Person::create(['name' => 'Novo Vendedor', 'email' => 'vnovo@teste.com'])->id,
        'school_id' => $school->id,
        'admission_date' => now()->toDateString(),
        'role' => 'seller',
        'active' => true,
    ]);

    $lead1 = CommercialLead::create([
        'school_id' => $school->id,
        'person_id' => $student->id,
        'name' => 'Lead Original',
        'status' => 'enrolled',
    ]);
    $lead2 = CommercialLead::create([
        'school_id' => $school->id,
        'person_id' => $student->id,
        'name' => 'Novo Lead',
        'status' => 'contacted',
    ]);

    // Contrato ativo COM lead e vendedor já preenchidos
    $contract = Contract::create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'responsible_id' => $resp->id,
        'contract_number' => 'CTR-PERM-001',
        'status' => 'active',
        'start_date' => now()->toDateString(),
        'total_value' => 1500,
        'commercial_lead_id' => $lead1->id,
        'collaborator_id' => $collab1->id,
    ]);

    $privilegedPerson = Person::create(['name' => 'Supervisor Comercial', 'email' => 'supervisor@teste.com']);
    $privilegedUser = User::factory()->create([
        'person_id' => $privilegedPerson->id,
        'email' => 'supervisor@teste.com',
    ]);
    $privilegedUser->givePermissionTo([
        'ViewAny:CommercialLead',
        'ViewAny:Contract',
        'Update:Contract',
        'ChangeCommercialLead:Contract',
        'ChangeCollaborator:Contract',
    ]);

    $this->actingAs($privilegedUser);
    Filament::setTenant($school);

    // O usuário com permissão deve ver ambos os campos HABILITADOS
    $test = Livewire::test(EditContracts::class, ['record' => $contract->getRouteKey()])
        ->assertFormFieldIsEnabled('commercial_lead_id')
        ->assertFormFieldIsEnabled('collaborator_id');

    // Altera ambos os campos
    $test->set('data.commercial_lead_id', $lead2->id);
    $test->set('data.collaborator_id', $collab2->id);
    $test->call('save')
        ->assertHasNoFormErrors();

    // Confirma que foram atualizados no banco
    $contract->refresh();
    expect($contract->commercial_lead_id)->toBe($lead2->id);
    expect($contract->collaborator_id)->toBe($collab2->id);

    // Novo lead promovido a enrolled
    $lead2->refresh();
    expect($lead2->status)->toBe('enrolled');
});

test('commercial lead and collaborator fields remain enabled on draft contracts even if filled', function () {
    $school = School::create([
        'legal_name' => 'Escola Draft Test LTDA',
        'trade_name' => 'Aurosoft Draft Test',
        'is_headquarters' => true,
    ]);

    $student = Person::create([
        'name' => 'Aluno Draft Test',
        'email' => 'drafttest@example.com',
    ]);

    $respPerson = Person::create([
        'name' => 'Resp Draft Test',
        'email' => 'respdraft@example.com',
    ]);

    $resp = Responsible::create([
        'person_id' => $respPerson->id,
        'relationship' => \App\Enums\RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    $collab = Collaborator::create([
        'person_id' => Person::create(['name' => 'Vendedor Draft', 'email' => 'vdraft@teste.com'])->id,
        'school_id' => $school->id,
        'admission_date' => now()->toDateString(),
        'role' => 'seller',
        'active' => true,
    ]);

    $lead = CommercialLead::create([
        'school_id' => $school->id,
        'person_id' => $student->id,
        'name' => 'Lead Draft',
        'status' => 'contacted',
    ]);

    $contract = Contract::create([
        'school_id' => $school->id,
        'student_id' => $student->id,
        'responsible_id' => $resp->id,
        'contract_number' => 'CTR-LEAD-DRAFT',
        'status' => 'draft',
        'start_date' => now()->toDateString(),
        'total_value' => 1200,
        'commercial_lead_id' => $lead->id,
        'collaborator_id' => $collab->id,
    ]);

    $regularUser = User::factory()->create([
        'person_id' => Person::create(['name' => 'Regular User', 'email' => 'regular@teste.com'])->id,
        'email' => 'regular@teste.com',
    ]);
    $regularUser->givePermissionTo(['ViewAny:CommercialLead', 'ViewAny:Contract', 'Update:Contract']);

    $this->actingAs($regularUser);
    Filament::setTenant($school);

    Livewire::test(EditContracts::class, ['record' => $contract->getRouteKey()])
        ->assertFormFieldIsEnabled('commercial_lead_id')
        ->assertFormFieldIsEnabled('collaborator_id');
});
