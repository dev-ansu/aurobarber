<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\Collaborator;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::firstOrCreate(
        ['id' => 1],
        ['legal_name' => 'Escola Matriz', 'trade_name' => 'Matriz']
    );

    $this->adminPerson = Person::create(['name' => 'Admin Teste', 'email' => 'admin.pwd@example.com']);
    $this->admin = User::create([
        'person_id' => $this->adminPerson->id,
        'email' => 'admin.pwd@example.com',
        'password' => bcrypt('AdminPass123'),
        'panel_access' => true,
    ]);
    $this->admin->schools()->attach($this->school);

    $superAdmin = Role::findOrCreate('super_admin', 'web');
    $this->admin->assignRole($superAdmin);

    $this->actingAs($this->admin);
});

test('user creation rejects weak passwords (less than 8 chars or letters only or numbers only)', function () {
    $person = Person::create(['name' => 'Funcionario Novo', 'email' => 'func.novo@example.com']);
    Collaborator::create([
        'person_id' => $person->id,
        'role' => 'Secretário',
        'active' => true,
        'admission_date' => today(),
    ]);

    // 1. Senha curta (123456)
    Livewire::test(CreateUser::class)
        ->fillForm([
            'person_id' => $person->id,
            'email' => 'func.novo@example.com',
            'password' => '123456',
            'password_confirmation' => '123456',
        ])
        ->call('create')
        ->assertHasFormErrors(['password']);

    // 2. Senha sem números (somente letras)
    Livewire::test(CreateUser::class)
        ->fillForm([
            'person_id' => $person->id,
            'email' => 'func.novo@example.com',
            'password' => 'somenteletras',
            'password_confirmation' => 'somenteletras',
        ])
        ->call('create')
        ->assertHasFormErrors(['password']);

    // 3. Senha sem letras (somente números)
    Livewire::test(CreateUser::class)
        ->fillForm([
            'person_id' => $person->id,
            'email' => 'func.novo@example.com',
            'password' => '123456789',
            'password_confirmation' => '123456789',
        ])
        ->call('create')
        ->assertHasFormErrors(['password']);
});

test('user creation accepts strong password with letters and numbers and at least 8 chars', function () {
    $person = Person::create(['name' => 'Funcionario Forte', 'email' => 'func.forte@example.com']);
    Collaborator::create([
        'person_id' => $person->id,
        'role' => 'Coordenador',
        'active' => true,
        'admission_date' => today(),
    ]);

    Livewire::test(CreateUser::class)
        ->fillForm([
            'person_id' => $person->id,
            'email' => 'func.forte@example.com',
            'password' => 'EscolaForte2026',
            'password_confirmation' => 'EscolaForte2026',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(User::where('email', 'func.forte@example.com')->exists())->toBeTrue();
});

