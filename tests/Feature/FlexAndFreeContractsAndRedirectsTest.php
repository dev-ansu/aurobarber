<?php

use App\Enums\RelationshipEnum;
use App\Filament\Pages\FlexAndFreeStudentsReport;
use App\Filament\Resources\Contracts\Pages\CreateContracts;
use App\Filament\Resources\Contracts\Pages\EditContracts;
use App\Filament\Resources\Formations\Pages\CreateFormation;
use App\Filament\Resources\Formations\Pages\EditFormation;
use App\Filament\Resources\People\Pages\EditPerson;
use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createTestSuperAdmin(): User
{
    $person = Person::create([
        'name' => 'Admin Teste',
        'email' => 'admin.flexfree@example.com',
    ]);

    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::create([
        'person_id' => $person->id,
        'email' => 'admin.flexfree@example.com',
        'password' => bcrypt('password'),
        'panel_access' => true,
    ]);

    $user->assignRole($role);

    $permissions = [
        'ViewAny:Person', 'View:Person', 'Create:Person', 'Update:Person', 'Delete:Person',
        'ViewAny:Contract', 'Create:Contract', 'View:Contract', 'Update:Contract', 'Delete:Contract',
        'ViewAny:Formation', 'Create:Formation', 'View:Formation', 'Update:Formation', 'Delete:Formation',
        'ViewAny:AuditLog', 'View:FlexAndFreeStudentsReport',
    ];

    foreach ($permissions as $pName) {
        $perm = Permission::findOrCreate($pName, 'web');
        $user->givePermissionTo($perm);
    }

    return $user;
}

beforeEach(function () {
    $admin = createTestSuperAdmin();
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('forms with relation managers redirect to edit url on creation and save', function () {
    $person = Person::create(['name' => 'Aluno Redirecionamento', 'email' => 'aluno.redir@teste.com']);
    $resp = Responsible::create(['person_id' => $person->id, 'relationship' => RelationshipEnum::Mother, 'financial_responsible' => true]);

    // 1. CreateContracts redirects to edit
    Livewire::test(CreateContracts::class)
        ->fillForm([
            'student_id' => $person->id,
            'responsible_id' => $resp->id,
            'start_date' => now()->toDateString(),
            'is_free' => true,
            'total_value' => 0,
            'status' => 'draft',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $contract = Contract::latest('id')->first();
    expect($contract)->not->toBeNull()
        ->and((bool) $contract->is_free)->toBeTrue();

    // 2. EditContracts redirects to edit
    Livewire::test(EditContracts::class, ['record' => $contract->getKey()])
        ->fillForm([
            'is_flex' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(EditContracts::getUrl(['record' => $contract->getKey()]));

    // 3. EditPerson redirects to edit
    Livewire::test(EditPerson::class, ['record' => $person->getKey()])
        ->fillForm([
            'name' => 'Aluno Redirecionamento Editado',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(EditPerson::getUrl(['record' => $person->getKey()]));

    // 4. CreateFormation redirects to edit
    Livewire::test(CreateFormation::class)
        ->fillForm([
            'name' => 'Formacao Teste Redir',
            'workload' => 80,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $formation = Formation::where('name', 'Formacao Teste Redir')->first();
    expect($formation)->not->toBeNull();

    // 5. EditFormation redirects to edit
    Livewire::test(EditFormation::class, ['record' => $formation->getKey()])
        ->fillForm([
            'workload' => 100,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertRedirect(EditFormation::getUrl(['record' => $formation->getKey()]));
});

test('contract and person deletion writes audit log successfully without foreign key violation', function () {
    $student = Person::create(['name' => 'Aluno Audit Delete', 'email' => 'audit.del@teste.com']);
    $resp = Responsible::create(['person_id' => $student->id, 'relationship' => RelationshipEnum::Mother, 'financial_responsible' => true]);

    $contract = Contract::create([
        'contract_number' => 'AUDIT-DEL-99',
        'student_id' => $student->id,
        'responsible_id' => $resp->id,
        'total_value' => 0,
        'is_free' => true,
        'status' => 'draft',
        'start_date' => now(),
    ]);

    // Deleta o contrato
    $contract->delete();

    $contractAudit = AuditLog::where('auditable_type', Contract::class)
        ->where('event', 'deleted')
        ->latest('id')
        ->first();

    expect($contractAudit)->not->toBeNull()
        ->and($contractAudit->auditable_id)->toBe($contract->id)
        ->and(data_get($contractAudit->changes, 'before.contract_number'))->toBe('AUDIT-DEL-99')
        ->and($contractAudit->contract_id)->toBeNull(); // Nulo para evitar quebra de FK

    // Deleta a pessoa
    $resp->delete();
    $student->delete();

    $personAudit = AuditLog::where('auditable_type', Person::class)
        ->where('event', 'deleted')
        ->latest('id')
        ->first();

    expect($personAudit)->not->toBeNull()
        ->and($personAudit->auditable_id)->toBe($student->id)
        ->and(data_get($personAudit->changes, 'before.name'))->toBe('Aluno Audit Delete');
});

test('free contract does not require financial responsible or installments', function () {
    $student = Person::create(['name' => 'Aluno Gratuito Puro', 'email' => 'gratuito@teste.com']);

    // Cria contrato gratuito sem responsible_id
    Livewire::test(CreateContracts::class)
        ->fillForm([
            'student_id' => $student->id,
            'responsible_id' => null,
            'is_free' => true,
            'is_flex' => false,
            'total_value' => 0,
            'status' => 'draft',
            'start_date' => now()->toDateString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $contract = Contract::where('student_id', $student->id)->first();
    expect($contract)->not->toBeNull()
        ->and($contract->isFree())->toBeTrue()
        ->and($contract->responsible_id)->toBeNull();
});

test('flex and free students report calculates metrics, filters data and exports csv', function () {
    $student1 = Person::create(['name' => 'Aluno Somente Flex', 'email' => 'flex@teste.com']);
    $student2 = Person::create(['name' => 'Aluno Somente Gratuito', 'email' => 'free@teste.com']);
    $student3 = Person::create(['name' => 'Aluno Ambos Flex E Gratuito', 'email' => 'ambos@teste.com']);

    // Contrato 1: Flex ativo com valor
    Contract::create([
        'contract_number' => 'TEST-FLEX-01',
        'student_id' => $student1->id,
        'status' => 'active',
        'is_flex' => true,
        'is_free' => false,
        'total_value' => 500,
        'start_date' => now(),
    ]);

    // Contrato 2: Gratuito ativo
    Contract::create([
        'contract_number' => 'TEST-FREE-01',
        'student_id' => $student2->id,
        'status' => 'active',
        'is_flex' => false,
        'is_free' => true,
        'total_value' => 0,
        'start_date' => now(),
    ]);

    // Contrato 3: Ambos Flex e Gratuito ativo
    Contract::create([
        'contract_number' => 'TEST-BOTH-01',
        'student_id' => $student3->id,
        'status' => 'active',
        'is_flex' => true,
        'is_free' => true,
        'total_value' => 0,
        'start_date' => now(),
    ]);

    $component = Livewire::test(FlexAndFreeStudentsReport::class);

    $metrics = $component->instance()->metrics();
    expect($metrics['active_flex'])->toBe(2) // student1 e student3
        ->and($metrics['active_free'])->toBe(2) // student2 e student3
        ->and($metrics['active_both'])->toBe(1); // student3

    // Filtra apenas Flex
    $component->set('filterType', 'flex');
    expect($component->instance()->records())->toHaveCount(2);

    // Filtra apenas Gratuitos
    $component->set('filterType', 'free');
    expect($component->instance()->records())->toHaveCount(2);

    // Filtra Ambos
    $component->set('filterType', 'both');
    expect($component->instance()->records())->toHaveCount(1);

    // Exportação CSV
    $response = $component->instance()->exportCsv();
    expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class);
});

