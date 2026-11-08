<?php

declare(strict_types=1);

use App\Enums\RelationshipEnum;
use App\Livewire\Student\StudentLogin;
use App\Models\Contract;
use App\Models\Formation;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createStudentLoginFixture(string $suffix, Carbon $birthDate): array
{
    $student = Person::create([
        'name' => "Aluno Login {$suffix}",
        'email' => "aluno-login-{$suffix}@example.com",
        'birth_date' => $birthDate->toDateString(),
        'cpf' => '12345678901',
    ]);

    $respPerson = Person::create([
        'name' => "Resp {$suffix}",
        'email' => "resp-{$suffix}@example.com",
    ]);

    $responsible = Responsible::create([
        'person_id' => $respPerson->id,
        'relationship' => RelationshipEnum::Mother,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => "MATR-{$suffix}",
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'total_value' => 1200,
        'status' => 'active',
    ]);

    $formation = Formation::create([
        'name' => "Formação {$suffix}",
        'active' => true,
    ]);

    $contract->items()->create([
        'formation_id' => $formation->id,
    ]);

    return compact('student', 'contract');
}

test('student login page renders successfully', function () {
    $response = $this->get(route('student.login'));

    $response->assertSuccessful()
        ->assertSee('Portal do Aluno')
        ->assertSee('Matrícula ou CPF')
        ->assertSee('Data de Nascimento ou Senha');
});

test('student can login with contract number and birth date d/m/Y', function () {
    $birthDate = Carbon::parse('2005-08-20');
    $fixture = createStudentLoginFixture('01', $birthDate);

    Livewire::test(StudentLogin::class)
        ->set('identifier', $fixture['contract']->contract_number)
        ->set('passwordOrBirthDate', '20/08/2005')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('student.portal'));

    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->person_id)->toBe($fixture['student']->id);
    expect(Auth::user()->can('View:StudentPortal'))->toBeTrue();
    expect(Auth::user()->can('View:LmsPlayer'))->toBeTrue();
});

test('student can login with cpf digits and birth date', function () {
    $birthDate = Carbon::parse('2006-03-15');
    $fixture = createStudentLoginFixture('02', $birthDate);

    Livewire::test(StudentLogin::class)
        ->set('identifier', '123.456.789-01')
        ->set('passwordOrBirthDate', '15032006')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('student.portal'));

    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->person_id)->toBe($fixture['student']->id);
});

test('student cannot login with invalid credentials and sees error message', function () {
    $birthDate = Carbon::parse('2005-08-20');
    $fixture = createStudentLoginFixture('03', $birthDate);

    Livewire::test(StudentLogin::class)
        ->set('identifier', $fixture['contract']->contract_number)
        ->set('passwordOrBirthDate', '01/01/2000')
        ->call('login')
        ->assertSee('Data de nascimento ou senha incorreta');

    expect(Auth::check())->toBeFalse();
});

test('student with ouro moderno password can authenticate with it', function () {
    $birthDate = Carbon::parse('2004-12-10');
    $fixture = createStudentLoginFixture('04', $birthDate);

    $fixture['student']->update([
        'ouro_moderno_senha' => 'senhaOm123',
    ]);

    Livewire::test(StudentLogin::class)
        ->set('identifier', $fixture['contract']->contract_number)
        ->set('passwordOrBirthDate', 'senhaOm123')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('student.portal'));

    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->person_id)->toBe($fixture['student']->id);
});

test('unregistered contract or cpf cannot authenticate', function () {
    Livewire::test(StudentLogin::class)
        ->set('identifier', 'MATR-INEXISTENTE')
        ->set('passwordOrBirthDate', '123456')
        ->call('login')
        ->assertSee('Nenhum aluno ativo encontrado');

    expect(Auth::check())->toBeFalse();
});

test('collaborator can login via educator tab without error', function () {
    $person = Person::create([
        'name' => 'Professora Ana',
        'email' => 'prof.ana@example.com',
        'birth_date' => '1990-05-10',
    ]);
    \App\Models\Collaborator::create([
        'person_id' => $person->id,
        'role' => 'Educadora',
        'active' => true,
        'is_educator' => true,
        'admission_date' => today(),
    ]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => 'prof.ana@example.com',
        'password' => bcrypt('secret'),
    ]);
    $role = Role::findOrCreate('educador', 'web');
    $user->assignRole($role);

    Livewire::test(StudentLogin::class)
        ->set('loginType', 'educator')
        ->set('identifier', 'prof.ana@example.com')
        ->set('passwordOrBirthDate', '10/05/1990')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('student.portal'));

    expect(Auth::check())->toBeTrue();
    expect(Auth::user()->person_id)->toBe($person->id);
});

