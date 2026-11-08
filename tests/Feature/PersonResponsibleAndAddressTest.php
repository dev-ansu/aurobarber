<?php

use App\Enums\AddressTypeEnum;
use App\Enums\RelationshipEnum;
use App\Filament\Resources\Contracts\Pages\CreateContracts;
use App\Filament\Resources\People\Pages\CreatePerson;
use App\Filament\Resources\People\Pages\EditPerson;
use App\Filament\Resources\People\RelationManagers\AddressesRelationManager;
use App\Models\Address;
use App\Models\City;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\State;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createAdminUserForPersonTests(): User
{
    $person = Person::create([
        'name' => 'Admin Usuário',
        'email' => 'admin.persontests@example.com',
    ]);

    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::create([
        'person_id' => $person->id,
        'email' => 'admin.persontests@example.com',
        'password' => bcrypt('password'),
        'panel_access' => true,
    ]);

    $user->assignRole($role);

    $permissions = [
        'ViewAny:Person', 'View:Person', 'Create:Person', 'Update:Person', 'Delete:Person',
        'ViewAny:Contract', 'Create:Contract', 'View:Contract', 'Update:Contract',
        'ViewAny:Responsible', 'Create:Responsible', 'View:Responsible', 'Update:Responsible',
    ];

    foreach ($permissions as $permission) {
        $p = Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($p);
    }

    return $user;
}

beforeEach(function () {
    $admin = createAdminUserForPersonTests();
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('person can be created with or without email', function () {
    $personWithEmail = Person::create([
        'name' => 'Pessoa com Email',
        'email' => 'comemail@example.com',
    ]);

    $personWithoutEmail = Person::create([
        'name' => 'Pessoa sem Email',
        'email' => null,
    ]);

    expect($personWithEmail->email)->toBe('comemail@example.com')
        ->and($personWithoutEmail->email)->toBeNull();

    $this->assertDatabaseHas(Person::class, [
        'id' => $personWithEmail->id,
        'email' => 'comemail@example.com',
    ]);

    $this->assertDatabaseHas(Person::class, [
        'id' => $personWithoutEmail->id,
        'email' => null,
    ]);
});

test('create person with is_responsible = true creates Responsible record with relationship and financial_responsible', function () {
    Livewire::test(CreatePerson::class)
        ->fillForm([
            'name' => 'Carlos Responsavel',
            'email' => null,
            'is_responsible' => true,
            'responsible_relationship' => RelationshipEnum::Mother->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $person = Person::where('name', 'Carlos Responsavel')->first();
    expect($person)->not->toBeNull()
        ->and($person->email)->toBeNull();

    $responsible = Responsible::where('person_id', $person->id)->first();
    expect($responsible)->not->toBeNull()
        ->and((bool) $responsible->financial_responsible)->toBeTrue()
        ->and($responsible->relationship)->toBe(RelationshipEnum::Mother);
});

test('create person with is_responsible = false does not create Responsible record', function () {
    Livewire::test(CreatePerson::class)
        ->fillForm([
            'name' => 'Mariana Apenas Aluna',
            'email' => 'mariana@example.com',
            'is_responsible' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $person = Person::where('name', 'Mariana Apenas Aluna')->first();
    expect($person)->not->toBeNull();

    $responsible = Responsible::where('person_id', $person->id)->first();
    expect($responsible)->toBeNull();
});

test('edit person updates responsible relationship', function () {
    $person = Person::create([
        'name' => 'Joao Pai',
        'email' => null,
    ]);

    $resp = Responsible::create([
        'person_id' => $person->id,
        'relationship' => RelationshipEnum::Father,
        'financial_responsible' => true,
    ]);

    Livewire::test(EditPerson::class, ['record' => $person->getKey()])
        ->assertFormSet([
            'is_responsible' => true,
            'responsible_relationship' => RelationshipEnum::Father,
        ])
        ->fillForm([
            'responsible_relationship' => RelationshipEnum::Legal_guardian->value,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($resp->fresh()->relationship)->toBe(RelationshipEnum::Legal_guardian);
});

test('addresses relation manager saves address without zip code when without_zip_code is checked', function () {
    $person = Person::create([
        'name' => 'Pessoa Com Endereco Sem CEP',
        'email' => 'endereco@example.com',
    ]);

    $state = State::create(['name' => 'Paraná', 'acronym' => 'PR', 'ibge_code' => 41]);
    $city = City::create(['name' => 'Curitiba', 'ibge_code' => '4106902', 'state_id' => $state->id]);

    Livewire::test(AddressesRelationManager::class, [
        'ownerRecord' => $person,
        'pageClass' => EditPerson::class,
    ])
        ->mountTableAction('create')
        ->setTableActionData([
            'type' => AddressTypeEnum::Residencial->value,
            'without_zip_code' => true,
            'zip_code' => null,
            'street' => 'Rua Sem CEP Conhecido',
            'number' => '456',
            'neighborhood' => 'Bairro Esperança',
            'city_id' => $city->id,
            'complement' => 'Casa 2',
            'is_primary' => true,
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas(Address::class, [
        'person_id' => $person->id,
        'street' => 'Rua Sem CEP Conhecido',
        'number' => '456',
        'neighborhood' => 'Bairro Esperança',
        'city_id' => $city->id,
        'zip_code' => null,
    ]);
});

test('contracts create pre-fills responsible_id when student_id query param is a financial responsible', function () {
    $person = Person::create([
        'name' => 'Aluno e Responsavel',
        'email' => null,
    ]);

    $resp = Responsible::create([
        'person_id' => $person->id,
        'relationship' => RelationshipEnum::Legal_guardian,
        'financial_responsible' => true,
    ]);

    Livewire::withQueryParams(['student_id' => (string) $person->id])
        ->test(CreateContracts::class)
        ->assertFormSet([
            'student_id' => $person->id,
            'responsible_id' => $resp->id,
        ]);
});

test('createContract action on EditPerson creates new responsible person and responsible record', function () {
    $student = Person::create([
        'name' => 'Aluno Menor de Idade',
        'email' => 'aluno.menor@example.com',
    ]);

    Livewire::test(EditPerson::class, ['record' => $student->id])
        ->callAction('createContract', [
            'responsible_person_id' => $student->id,
            'relationship' => RelationshipEnum::Mother->value,
            'new_person_name' => 'Mãe do Aluno Menor',
            'new_person_cpf' => '123.456.789-00',
            'new_person_phone' => '(85) 99999-8888',
            'new_person_email' => 'mae@example.com',
        ])
        ->assertHasNoActionErrors();

    $mother = Person::where('cpf', '12345678900')->orWhere('name', 'Mãe do Aluno Menor')->first();
    expect($mother)->not->toBeNull();

    $resp = Responsible::where('person_id', $mother->id)->first();
    expect($resp)->not->toBeNull()
        ->and($resp->financial_responsible)->toBeTrue()
        ->and($resp->relationship)->toBe(RelationshipEnum::Mother);
});

test('CreatePerson with for_student_id query param links responsible and redirects to contract', function () {
    $student = Person::create([
        'name' => 'Aluno Precisa Responsavel',
        'email' => 'aluno.precisa@example.com',
    ]);

    Livewire::withQueryParams([
        'for_student_id' => (string) $student->id,
        'relationship' => RelationshipEnum::Father->value,
    ])
        ->test(CreatePerson::class)
        ->fillForm([
            'name' => 'Pai do Aluno Cadastrado',
            'cpf' => '987.654.321-99',
            'email' => 'pai.cadastrado@example.com',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $father = Person::where('name', 'Pai do Aluno Cadastrado')->first();
    expect($father)->not->toBeNull();

    $resp = Responsible::where('person_id', $father->id)->first();
    expect($resp)->not->toBeNull()
        ->and($resp->financial_responsible)->toBeTrue()
        ->and($resp->relationship)->toBe(RelationshipEnum::Father);
});
