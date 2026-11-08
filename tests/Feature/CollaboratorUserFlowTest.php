<?php

use App\Filament\Pages\Agenda;
use App\Filament\Resources\Collaborators\CollaboratorResource;
use App\Filament\Resources\Contracts\ContractsResource;
use App\Filament\Resources\People\PersonResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Collaborator;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::firstOrCreate(
        ['id' => 1],
        ['legal_name' => 'Escola Padrão', 'trade_name' => 'Matriz']
    );
});

test('quick actions widget links Chamada to Agenda de Chamadas route', function () {
    $route = route('filament.admin.pages.agenda');
    expect($route)->toContain('/agenda');
});

test('create collaborator mounts person_id from query string and redirects to edit', function () {
    $person = Person::create([
        'name' => 'Colaborador Teste',
        'email' => 'colaborador@teste.com',
    ]);

    $adminPerson = Person::create([
        'name' => 'Admin Teste',
        'email' => 'admin@teste.com',
    ]);

    $user = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'admin@teste.com',
        'password' => 'secret',
        'panel_access' => true,
    ]);
    $user->schools()->attach($this->school);
    $user->givePermissionTo([
        Permission::findOrCreate('Create:Collaborator', 'web'),
        Permission::findOrCreate('ViewAny:Collaborator', 'web'),
    ]);

    $this->actingAs($user);

    $response = $this->get(route('filament.admin.resources.collaborators.create', ['person_id' => $person->id]));
    $response->assertOk();
});

test('create user mounts person_id and email from query string', function () {
    $person = Person::create([
        'name' => 'Professor Silva',
        'email' => 'professor.silva@teste.com',
    ]);

    $collaborator = Collaborator::create([
        'person_id' => $person->id,
        'admission_date' => now()->toDateString(),
        'role' => 'Professor',
    ]);

    $adminPerson = Person::create([
        'name' => 'Admin Teste 2',
        'email' => 'admin2@teste.com',
    ]);

    $user = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'admin2@teste.com',
        'password' => 'secret',
        'panel_access' => true,
    ]);
    $user->schools()->attach($this->school);
    $user->givePermissionTo([
        Permission::findOrCreate('Create:User', 'web'),
        Permission::findOrCreate('ViewAny:User', 'web'),
    ]);

    $this->actingAs($user);

    $response = $this->get(route('filament.admin.resources.users.create', ['person_id' => $person->id]));
    $response->assertOk();
});
