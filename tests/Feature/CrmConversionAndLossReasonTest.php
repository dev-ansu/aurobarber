<?php

use App\Enums\GenderEnum;
use App\Enums\RelationshipEnum;
use App\Filament\Pages\CommercialLeadsKanban;
use App\Filament\Resources\CommercialLeads\CommercialLeadResource;
use App\Filament\Resources\Contracts\Pages\CreateContracts;
use App\Models\Collaborator;
use App\Models\CommercialLead;
use App\Models\CommercialLossReason;
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
    Permission::findOrCreate('ViewAny:CommercialLead', 'web');
    Permission::findOrCreate('Update:CommercialLead', 'web');
    Permission::findOrCreate('Create:Contract', 'web');
    Permission::findOrCreate('ViewAny:Contract', 'web');
});

test('kanban enrollLead creates person with gender, creates responsible and redirects to contract creation', function () {
    $school = School::create([
        'legal_name' => 'Escola CRM Test LTDA',
        'trade_name' => 'Aurosoft CRM Test',
        'is_headquarters' => true,
    ]);

    $collabPerson = Person::create(['name' => 'Vendedor CRM', 'email' => 'vcrm@teste.com']);
    $collab = Collaborator::create([
        'person_id' => $collabPerson->id,
        'school_id' => $school->id,
        'admission_date' => now()->toDateString(),
        'role' => 'seller',
        'active' => true,
    ]);

    // Lead sem person_id vinculado previamente, com gênero definido
    $lead = CommercialLead::create([
        'school_id' => $school->id,
        'name' => 'Nova Aluna Interessada',
        'email' => 'aluna@teste.com',
        'phone' => '(85) 99999-8888',
        'birth_date' => '2005-06-15',
        'gender' => GenderEnum::Feminino,
        'status' => 'proposal',
        'collaborator_id' => $collab->id,
    ]);

    $user = User::factory()->create([
        'person_id' => Person::create(['name' => 'Admin CRM', 'email' => 'admincrm@teste.com'])->id,
        'email' => 'admincrm@teste.com',
    ]);
    $user->assignRole('super_admin');

    $this->actingAs($user);
    Filament::setTenant($school);

    Livewire::test(CommercialLeadsKanban::class)
        ->call('enrollLead', $lead->id)
        ->assertSet('showEnrollModal', true)
        ->call('confirmEnroll')
        ->assertRedirect();

    $lead->refresh();
    expect($lead->person_id)->not->toBeNull();

    $person = $lead->person;
    expect($person->name)->toBe('Nova Aluna Interessada')
        ->and($person->email)->toBe('aluna@teste.com')
        ->and($person->gender)->toBe(GenderEnum::Feminino)
        ->and($person->contacts()->where('value', '(85) 99999-8888')->exists())->toBeTrue()
        ->and($person->schools()->where('schools.id', $school->id)->exists())->toBeTrue()
        ->and($person->responsibles()->where('financial_responsible', true)->exists())->toBeTrue();
});

test('kanban moving lead to lost opens loss modal and confirmLoss records reason and notes', function () {
    $school = School::create([
        'legal_name' => 'Escola Loss Test LTDA',
        'trade_name' => 'Aurosoft Loss Test',
        'is_headquarters' => true,
    ]);

    CommercialLossReason::ensureDefaultsForSchool($school->id);
    $reason = CommercialLossReason::where('school_id', $school->id)->first();

    $lead = CommercialLead::create([
        'school_id' => $school->id,
        'name' => 'Lead Desistente',
        'status' => 'contacted',
    ]);

    $user = User::factory()->create([
        'person_id' => Person::create(['name' => 'Admin Loss', 'email' => 'adminloss@teste.com'])->id,
        'email' => 'adminloss@teste.com',
    ]);
    $user->assignRole('super_admin');

    $this->actingAs($user);
    Filament::setTenant($school);

    // 1. Tentar mover para 'lost' deve abrir o modal de motivo
    $component = Livewire::test(CommercialLeadsKanban::class)
        ->call('updateLeadStatus', $lead->id, 'lost');

    $component->assertSet('showLossModal', true)
        ->assertSet('lossLeadId', $lead->id);

    // 2. Confirma a perda informando o motivo
    $component->set('lossReasonId', $reason->id)
        ->set('lossNotes', 'Optou por curso online gratuito')
        ->call('confirmLoss')
        ->assertSet('showLossModal', false);

    $lead->refresh();
    expect($lead->status)->toBe('lost')
        ->and($lead->commercial_loss_reason_id)->toBe($reason->id)
        ->and($lead->loss_notes)->toBe('Optou por curso online gratuito')
        ->and($lead->lost_at)->not->toBeNull();
});

test('create contracts form mounts with prefilled student, lead, and seller from query parameters', function () {
    $school = School::create([
        'legal_name' => 'Escola Mount Test LTDA',
        'trade_name' => 'Aurosoft Mount Test',
        'is_headquarters' => true,
    ]);

    $student = Person::create(['name' => 'Aluno Query Param', 'email' => 'alunoqp@teste.com']);
    $student->schools()->attach($school->id);

    $collab = Collaborator::create([
        'person_id' => Person::create(['name' => 'Vendedor QP', 'email' => 'vqp@teste.com'])->id,
        'school_id' => $school->id,
        'admission_date' => now()->toDateString(),
        'role' => 'seller',
        'active' => true,
    ]);

    $lead = CommercialLead::create([
        'school_id' => $school->id,
        'person_id' => $student->id,
        'name' => 'Lead QP',
        'status' => 'proposal',
        'collaborator_id' => $collab->id,
    ]);

    $user = User::factory()->create([
        'person_id' => Person::create(['name' => 'Admin QP', 'email' => 'adminqp@teste.com'])->id,
        'email' => 'adminqp@teste.com',
    ]);
    $user->assignRole('super_admin');

    $this->actingAs($user);
    Filament::setTenant($school);

    // Simula acesso com os parâmetros na URL
    Livewire::withQueryParams([
        'student_id' => $student->id,
        'commercial_lead_id' => $lead->id,
        'collaborator_id' => $collab->id,
    ])->test(CreateContracts::class)
        ->assertFormSet([
            'student_id' => (string) $student->id,
            'commercial_lead_id' => (string) $lead->id,
            'collaborator_id' => (string) $collab->id,
        ]);
});

test('kanban enrolling with separate responsible creates both persons and responsible record', function () {
    $school = School::create([
        'legal_name' => 'Escola CRM Resp LTDA',
        'trade_name' => 'Aurosoft CRM Resp Test',
        'is_headquarters' => true,
    ]);

    $lead = CommercialLead::create([
        'school_id' => $school->id,
        'name' => 'Aluno Menor',
        'email' => 'menor@teste.com',
        'phone' => '(85) 99999-1111',
        'status' => 'proposal',
        'responsible_name' => 'Maria Santos (Mãe)',
        'responsible_relationship' => RelationshipEnum::Mother,
        'responsible_phone' => '(85) 98888-7777',
    ]);

    $user = User::factory()->create([
        'person_id' => Person::create(['name' => 'Admin Resp', 'email' => 'adminresp@teste.com'])->id,
        'email' => 'adminresp@teste.com',
    ]);
    $user->assignRole('super_admin');

    $this->actingAs($user);
    Filament::setTenant($school);

    Livewire::test(CommercialLeadsKanban::class)
        ->call('openEnrollModal', $lead->id)
        ->assertSet('showEnrollModal', true)
        ->assertSet('enrollResponsibleMode', 'other')
        ->assertSet('enrollResponsibleName', 'Maria Santos (Mãe)')
        ->call('confirmEnroll')
        ->assertRedirect();

    $student = $lead->fresh()->person;
    expect($student)->not->toBeNull()
        ->and($student->name)->toBe('Aluno Menor');

    $respPerson = Person::where('name', 'Maria Santos (Mãe)')->first();
    expect($respPerson)->not->toBeNull()
        ->and($respPerson->contacts()->where('value', '(85) 98888-7777')->exists())->toBeTrue();

    $responsible = Responsible::where('person_id', $respPerson->id)->first();
    expect($responsible)->not->toBeNull()
        ->and($responsible->financial_responsible)->toBeTrue()
        ->and($responsible->relationship)->toBe(RelationshipEnum::Mother);
});
