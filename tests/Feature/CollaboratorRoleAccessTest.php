<?php

declare(strict_types=1);

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\SatisfactionSurveyReport;
use App\Livewire\Lms\LmsPlayer;
use App\Livewire\Student\StudentLogin;
use App\Livewire\Student\StudentPortal;
use App\Models\Collaborator;
use App\Models\Person;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('satisfaction survey report permissions control access strictly via permissions', function () {
    $person = Person::create(['name' => 'Funcionário Teste', 'email' => 'func@example.com']);
    $user = User::create(['person_id' => $person->id, 'email' => 'func@example.com', 'password' => bcrypt('secret')]);

    $this->actingAs($user);
    expect(SatisfactionSurveyReport::canAccess())->toBeFalse();

    // Com permissão View:SatisfactionSurveyReport
    $permView = Permission::findOrCreate('View:SatisfactionSurveyReport', 'web');
    $user->givePermissionTo($permView);
    expect(SatisfactionSurveyReport::canAccess())->toBeTrue();

    // Revoga e testa com page_SatisfactionSurveyReport
    $user->revokePermissionTo($permView);
    expect(SatisfactionSurveyReport::canAccess())->toBeFalse();

    $permPage = Permission::findOrCreate('page_SatisfactionSurveyReport', 'web');
    $user->givePermissionTo($permPage);
    expect(SatisfactionSurveyReport::canAccess())->toBeTrue();

    // Revoga e testa com ViewAny:SatisfactionSurvey
    $user->revokePermissionTo($permPage);
    $permViewAny = Permission::findOrCreate('ViewAny:SatisfactionSurvey', 'web');
    $user->givePermissionTo($permViewAny);
    expect(SatisfactionSurveyReport::canAccess())->toBeTrue();

    // Super Admin sempre acessa
    $user->revokePermissionTo($permViewAny);
    $superAdminRole = Role::findOrCreate('super_admin', 'web');
    $user->assignRole($superAdminRole);
    expect(SatisfactionSurveyReport::canAccess())->toBeTrue();
});

test('attendance staff roles seeder creates satisfaction survey report permissions and syncs to super admin', function () {
    $this->seed(\Database\Seeders\AttendanceStaffRolesSeeder::class);

    expect(Permission::where('name', 'View:SatisfactionSurveyReport')->exists())->toBeTrue();
    expect(Permission::where('name', 'Export:SatisfactionSurveyReport')->exists())->toBeTrue();
    expect(Permission::where('name', 'page_SatisfactionSurveyReport')->exists())->toBeTrue();

    $superAdmin = Role::where('name', 'super_admin')->first();
    expect($superAdmin->hasPermissionTo('View:SatisfactionSurveyReport'))->toBeTrue();
    expect($superAdmin->hasPermissionTo('Export:SatisfactionSurveyReport'))->toBeTrue();
});

test('collaborator without role cannot access admin panel or studio panel', function () {
    $person = Person::create(['name' => 'Colaborador Sem Papel', 'email' => 'collab.sem.papel@example.com']);
    $collaborator = Collaborator::create([
        'person_id' => $person->id,
        'admission_date' => now(),
        'role' => 'Instrutor Auxiliar',
        'active' => true,
        'is_educator' => false,
    ]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => 'collab.sem.papel@example.com',
        'password' => bcrypt('senha123'),
        'panel_access' => true,
    ]);

    expect($user->isCollaborator())->toBeTrue();
    expect($user->hasDefinedRole())->toBeFalse();

    $adminPanel = Filament::getPanel('admin');
    $studioPanel = Filament::getPanel('studio');

    // Bloqueado em ambos os painéis
    expect($user->canAccessPanel($adminPanel))->toBeFalse();
    expect($user->canAccessPanel($studioPanel))->toBeFalse();

    // Quando recebe um papel qualquer definido pelo cliente (ex: professor, secretaria, ou personalizado)
    $customRole = Role::findOrCreate('atendimento', 'web');
    $user->assignRole($customRole);

    expect($user->hasDefinedRole())->toBeTrue();
    expect($user->canAccessPanel($adminPanel))->toBeTrue();
});

test('collaborator without role cannot login via admin login page', function () {
    $person = Person::create(['name' => 'Colaborador Login Teste', 'email' => 'login.test@example.com']);
    Collaborator::create([
        'person_id' => $person->id,
        'admission_date' => now(),
        'role' => 'Atendente',
        'active' => true,
    ]);

    User::create([
        'person_id' => $person->id,
        'email' => 'login.test@example.com',
        'password' => bcrypt('senha123'),
        'panel_access' => true,
    ]);

    Livewire::test(Login::class)
        ->set('data.email', 'login.test@example.com')
        ->set('data.password', 'senha123')
        ->call('authenticate')
        ->assertHasErrors(['data.email']);

    expect(auth()->check())->toBeFalse();
});

test('collaborator without role cannot login via student login educator tab', function () {
    $person = Person::create(['name' => 'Educador Sem Papel', 'email' => 'educador.sem.papel@example.com']);
    Collaborator::create([
        'person_id' => $person->id,
        'admission_date' => now(),
        'role' => 'Professor Substituto',
        'active' => true,
        'is_educator' => true,
    ]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => 'educador.sem.papel@example.com',
        'password' => bcrypt('senha123'),
    ]);

    Livewire::test(StudentLogin::class)
        ->set('loginType', 'educator')
        ->set('identifier', 'educador.sem.papel@example.com')
        ->set('passwordOrBirthDate', 'senha123')
        ->call('login')
        ->assertSee('Acesso bloqueado: Este colaborador não possui um papel');

    expect(auth()->check())->toBeFalse();
});

test('collaborator without role is blocked from accessing student portal directly', function () {
    $person = Person::create(['name' => 'Colaborador Portal', 'email' => 'portal.test@example.com']);
    Collaborator::create([
        'person_id' => $person->id,
        'admission_date' => now(),
        'role' => 'Monitor',
        'active' => true,
    ]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => 'portal.test@example.com',
        'password' => bcrypt('senha123'),
    ]);

    $this->actingAs($user);

    Livewire::test(StudentPortal::class)
        ->assertStatus(403);
});
