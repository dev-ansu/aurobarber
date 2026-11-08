<?php

use App\Filament\Resources\Contracts\ContractsResource;
use App\Filament\Resources\Contracts\Pages\ListContracts;
use App\Filament\Resources\Payables\Pages\ManagePayables;
use App\Models\Contract;
use App\Models\Payable;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    School::query()->delete();
});

test('user can access only their assigned schools while super admin accesses all', function () {
    $schoolA = School::create(['id' => 1, 'legal_name' => 'Escola Barra', 'trade_name' => 'Barra']);
    $schoolB = School::create(['id' => 2, 'legal_name' => 'Escola Carlito', 'trade_name' => 'Carlito']);

    $personRegular = Person::create(['name' => 'Funcionario Barra', 'email' => 'barra@example.com']);
    $userRegular = User::create([
        'person_id' => $personRegular->id,
        'email' => 'barra@example.com',
        'password' => bcrypt('password'),
    ]);
    // Vincula apenas à escola Barra
    $userRegular->schools()->attach($schoolA);

    $personAdmin = Person::create(['name' => 'Dono Rede', 'email' => 'dono@example.com']);
    $userAdmin = User::create([
        'person_id' => $personAdmin->id,
        'email' => 'dono@example.com',
        'password' => bcrypt('password'),
    ]);
    $role = Role::findOrCreate('super_admin', 'web');
    $userAdmin->assignRole($role);

    $panel = Filament::getCurrentOrDefaultPanel();

    // Regular user
    expect($userRegular->canAccessTenant($schoolA))->toBeTrue()
        ->and($userRegular->canAccessTenant($schoolB))->toBeFalse()
        ->and($userRegular->getTenants($panel)->pluck('id')->all())->toEqual([1]);

    // Super Admin / Owner
    expect($userAdmin->canAccessTenant($schoolA))->toBeTrue()
        ->and($userAdmin->canAccessTenant($schoolB))->toBeTrue()
        ->and($userAdmin->getTenants($panel)->count())->toBe(2);
});

test('contracts and payables are scoped to the active tenant', function () {
    $schoolBarra = School::create(['id' => 1, 'legal_name' => 'Escola Barra', 'trade_name' => 'Barra']);
    $schoolCarlito = School::create(['id' => 2, 'legal_name' => 'Escola Carlito', 'trade_name' => 'Carlito']);

    $adminPerson = Person::create(['name' => 'Admin Test', 'email' => 'admin.tenant@example.com']);
    $admin = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'admin.tenant@example.com',
        'password' => bcrypt('password'),
    ]);
    $role = Role::findOrCreate('super_admin', 'web');
    $admin->assignRole($role);
    $admin->givePermissionTo(Permission::findOrCreate('ViewAny:Contract', 'web'));
    $admin->givePermissionTo(Permission::findOrCreate('ViewAny:Payable', 'web'));
    $this->actingAs($admin);

    $student = Person::create(['name' => 'Aluno Barra', 'email' => 'aluno.barra@example.com']);

    // Contrato da Barra
    $contractBarra = Contract::create([
        'school_id' => $schoolBarra->id,
        'student_id' => $student->id,
        'contract_number' => 'CTR-BARRA-01',
        'status' => 'active',
        'start_date' => '2026-10-01',
    ]);

    // Contrato do Carlito
    $contractCarlito = Contract::create([
        'school_id' => $schoolCarlito->id,
        'student_id' => $student->id,
        'contract_number' => 'CTR-CARLITO-01',
        'status' => 'active',
        'start_date' => '2026-10-01',
    ]);

    // Conta da Barra
    $payableBarra = Payable::create([
        'school_id' => $schoolBarra->id,
        'description' => 'Luz Barra',
        'amount' => 500.00,
        'due_date' => '2026-10-25',
        'status' => 'pending',
    ]);

    // Conta do Carlito
    $payableCarlito = Payable::create([
        'school_id' => $schoolCarlito->id,
        'description' => 'Luz Carlito',
        'amount' => 700.00,
        'due_date' => '2026-10-25',
        'status' => 'pending',
    ]);

    // 1. Acessando a Escola BARRA
    Filament::setTenant($schoolBarra, isQuiet: true);

    Livewire::test(ListContracts::class)
        ->assertCanSeeTableRecords([$contractBarra])
        ->assertCanNotSeeTableRecords([$contractCarlito]);

    Livewire::test(ManagePayables::class)
        ->assertCanSeeTableRecords([$payableBarra])
        ->assertCanNotSeeTableRecords([$payableCarlito]);

    // 2. Acessando a Escola CARLITO
    Filament::setTenant($schoolCarlito, isQuiet: true);

    Livewire::test(ListContracts::class)
        ->assertCanSeeTableRecords([$contractCarlito])
        ->assertCanNotSeeTableRecords([$contractBarra]);

    Livewire::test(ManagePayables::class)
        ->assertCanSeeTableRecords([$payableCarlito])
        ->assertCanNotSeeTableRecords([$payableBarra]);
});

test('ouro moderno integration settings are isolated per school', function () {
    $schoolBarra = School::create(['id' => 1, 'legal_name' => 'Escola Barra', 'trade_name' => 'Barra']);
    $schoolCarlito = School::create(['id' => 2, 'legal_name' => 'Escola Carlito', 'trade_name' => 'Carlito']);

    \App\Models\IntegrationSetting::create([
        'school_id' => $schoolBarra->id,
        'provider' => 'ouro_moderno',
        'base_url' => 'http://servidorouro-barra.local',
        'token' => 'token-barra-123',
    ]);

    \App\Models\IntegrationSetting::create([
        'school_id' => $schoolCarlito->id,
        'provider' => 'ouro_moderno',
        'base_url' => 'http://servidorouro-carlito.local',
        'token' => 'token-carlito-456',
    ]);

    $service = app(\App\Services\OuroModernoService::class);

    // Direct school targeting via forSchool
    expect($service->forSchool($schoolBarra)->getBaseUrl())->toBe('http://servidorouro-barra.local')
        ->and($service->forSchool($schoolCarlito)->getBaseUrl())->toBe('http://servidorouro-carlito.local');

    // Tenant-aware resolution in Filament context
    Filament::setTenant($schoolBarra, isQuiet: true);
    expect(app(\App\Services\OuroModernoService::class)->getBaseUrl())->toBe('http://servidorouro-barra.local');

    Filament::setTenant($schoolCarlito, isQuiet: true);
    expect(app(\App\Services\OuroModernoService::class)->getBaseUrl())->toBe('http://servidorouro-carlito.local');
});

test('authenticated user can access profile page without tenant billing typeerror', function () {
    $school = School::create(['id' => 1, 'legal_name' => 'Escola Matriz', 'trade_name' => 'Matriz']);
    $person = Person::create(['name' => 'Profile User', 'email' => 'profile@example.com']);
    $user = User::create([
        'person_id' => $person->id,
        'email' => 'profile@example.com',
        'password' => bcrypt('password'),
    ]);
    $role = Role::findOrCreate('super_admin', 'web');
    $user->assignRole($role);

    $response = $this->actingAs($user)->get('/admin/profile?tenant=1');
    $response->assertSuccessful();

    $responseWithoutQuery = $this->actingAs($user)->get('/admin/profile');
    $responseWithoutQuery->assertSuccessful();
});

test('people are scoped by school and can be linked or transferred across schools without duplicate cpf', function () {
    $schoolBarra = School::create(['id' => 1, 'legal_name' => 'Escola Barra', 'trade_name' => 'Barra']);
    $schoolCarlito = School::create(['id' => 2, 'legal_name' => 'Escola Carlito', 'trade_name' => 'Carlito']);

    $personBarra = Person::create([
        'name' => 'Aluno Barra Original',
        'cpf' => '123.456.789-00',
        'email' => 'aluno.barra@example.com',
    ]);
    $personBarra->schools()->attach($schoolBarra);

    $personCarlito = Person::create([
        'name' => 'Aluno Carlito Original',
        'cpf' => '987.654.321-00',
        'email' => 'aluno.carlito@example.com',
    ]);
    $personCarlito->schools()->attach($schoolCarlito);

    // 1. Escopo na Barra
    Filament::setTenant($schoolBarra, isQuiet: true);
    $barraPeople = \App\Filament\Resources\People\PersonResource::getEloquentQuery()->pluck('id')->all();
    expect($barraPeople)->toContain($personBarra->id)
        ->and($barraPeople)->not->toContain($personCarlito->id);

    // 2. Escopo no Carlito
    Filament::setTenant($schoolCarlito, isQuiet: true);
    $carlitoPeople = \App\Filament\Resources\People\PersonResource::getEloquentQuery()->pluck('id')->all();
    expect($carlitoPeople)->toContain($personCarlito->id)
        ->and($carlitoPeople)->not->toContain($personBarra->id);

    // 3. Transferência / Vínculo: Carlito vincula o aluno da Barra
    $personBarra->schools()->syncWithoutDetaching([$schoolCarlito->id]);

    $carlitoPeopleAfterTransfer = \App\Filament\Resources\People\PersonResource::getEloquentQuery()->pluck('id')->all();
    expect($carlitoPeopleAfterTransfer)->toContain($personBarra->id)
        ->and($personBarra->schools()->pluck('schools.id')->all())->toEqualCanonicalizing([$schoolBarra->id, $schoolCarlito->id]);

    // Continua sendo apenas 1 registro único no banco de dados para esse CPF
    expect(Person::where('cpf', '123.456.789-00')->count())->toBe(1);
});

test('cash registers are scoped by school and enforce active school register on payments', function () {
    $schoolBarra = School::create(['id' => 1, 'legal_name' => 'Escola Barra', 'trade_name' => 'Barra']);
    $schoolCarlito = School::create(['id' => 2, 'legal_name' => 'Escola Carlito', 'trade_name' => 'Carlito']);

    $person = Person::create(['name' => 'Operadora Joana', 'email' => 'joana@example.com']);
    $user = User::create([
        'person_id' => $person->id,
        'email' => 'joana@example.com',
        'password' => bcrypt('password'),
    ]);

    $paymentMethod = \App\Models\PaymentMethod::create([
        'name' => 'Dinheiro',
        'is_active' => true,
    ]);

    // Operadora tem caixa aberto na BARRA
    $caixaBarra = \App\Models\CashRegister::create([
        'school_id' => $schoolBarra->id,
        'user_id' => $user->id,
        'name' => 'Caixa Barra 01',
        'status' => 'open',
        'opening_balance' => 100,
        'current_balance' => 100,
        'opened_at' => now(),
    ]);

    // Usuário também pode ter caixa aberto no CARLITO sem colisão de índice único
    $caixaCarlito = \App\Models\CashRegister::create([
        'school_id' => $schoolCarlito->id,
        'user_id' => $user->id,
        'name' => 'Caixa Carlito 01',
        'status' => 'open',
        'opening_balance' => 50,
        'current_balance' => 50,
        'opened_at' => now(),
    ]);

    expect($caixaBarra->exists)->toBeTrue()
        ->and($caixaCarlito->exists)->toBeTrue();

    // Cria conta a pagar na Barra e no Carlito
    $payableCarlito = \App\Models\Payable::create([
        'school_id' => $schoolCarlito->id,
        'description' => 'Internet Carlito',
        'amount' => 200,
        'due_date' => today()->addDays(5),
        'status' => 'pending',
    ]);

    // Executa baixa parcial no Carlito vinculando ao Caixa do Carlito
    $this->actingAs($user);
    Filament::setTenant($schoolCarlito, isQuiet: true);

    $remaining = $payableCarlito->pay([
        'payment_date' => today()->toDateString(),
        'amount_paid' => 120,
        'payment_method_id' => $paymentMethod->id,
        'cash_register_id' => $caixaCarlito->id,
        'remaining_due_date' => today()->addDays(15)->toDateString(),
    ]);

    // 1. O saldo restante deve pertencer à mesma escola (Carlito)
    expect($remaining)->not->toBeNull()
        ->and($remaining->school_id)->toBe($schoolCarlito->id)
        ->and((float) $remaining->amount)->toBe(80.00);

    // 2. O débito ocorreu no caixa do Carlito e não afetou o da Barra
    $caixaCarlito->refresh();
    $caixaBarra->refresh();

    expect($caixaCarlito->current_balance)->toBe(50.0 - 120.0) // -70
        ->and($caixaBarra->current_balance)->toBe(100.0);      // inalterado
});
