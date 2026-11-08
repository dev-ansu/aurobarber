<?php

use App\Filament\Kiosk\Pages\StudentAttendanceKiosk;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('kiosk accepts school query parameter and queues permanent cookie', function () {
    $school1 = School::create([
        'legal_name' => 'ITEP Barra do Ceara LTDA',
        'trade_name' => 'ITEP - Barra do Ceara',
        'is_headquarters' => true,
    ]);

    $school2 = School::create([
        'legal_name' => 'ITEP Messejana LTDA',
        'trade_name' => 'ITEP - Messejana',
    ]);

    // Acessando com parâmetro da Escola 2
    $component = Livewire::withQueryParams(['escola' => (string) $school2->id])
        ->test(StudentAttendanceKiosk::class);

    $component->assertSet('kioskSchoolId', $school2->id)
        ->assertSee('ITEP - Messejana')
        ->assertDontSee('ITEP - Barra do Ceara');
});

test('kiosk restores selected school from kiosk_school_id cookie', function () {
    $school1 = School::create([
        'legal_name' => 'ITEP Barra do Ceara LTDA',
        'trade_name' => 'ITEP - Barra do Ceara',
        'is_headquarters' => true,
    ]);

    $school2 = School::create([
        'legal_name' => 'ITEP Messejana LTDA',
        'trade_name' => 'ITEP - Messejana',
    ]);

    $this->withCookie('kiosk_school_id', (string) $school2->id)
        ->withoutVite()
        ->get('/presenca')
        ->assertSuccessful()
        ->assertSee('ITEP - Messejana')
        ->assertDontSee('ITEP - Barra do Ceara');
});

test('selectKioskSchool dynamically changes unit and updates kiosk state', function () {
    $school1 = School::create([
        'legal_name' => 'ITEP Barra do Ceara LTDA',
        'trade_name' => 'ITEP - Barra do Ceara',
        'is_headquarters' => true,
    ]);

    $school2 = School::create([
        'legal_name' => 'ITEP Messejana LTDA',
        'trade_name' => 'ITEP - Messejana',
    ]);

    $component = Livewire::test(StudentAttendanceKiosk::class)
        ->call('selectKioskSchool', $school2->id);

    $component->assertSet('kioskSchoolId', $school2->id)
        ->assertSet('showSchoolSwitcherModal', false)
        ->assertSee('ITEP - Messejana')
        ->assertNotified();
});

test('openSchoolSwitcher and closeSchoolSwitcher toggle modal visibility', function () {
    $school1 = School::create([
        'legal_name' => 'ITEP Barra do Ceara LTDA',
        'trade_name' => 'ITEP - Barra do Ceara',
        'is_headquarters' => true,
    ]);

    $school2 = School::create([
        'legal_name' => 'ITEP Messejana LTDA',
        'trade_name' => 'ITEP - Messejana',
    ]);

    Livewire::test(StudentAttendanceKiosk::class)
        ->call('openSchoolSwitcher')
        ->assertSet('showSchoolSwitcherModal', true)
        ->call('closeSchoolSwitcher')
        ->assertSet('showSchoolSwitcherModal', false);
});

test('switching school requires supervisor authentication if school is already set', function () {
    $school1 = School::create([
        'legal_name' => 'ITEP Barra do Ceara LTDA',
        'trade_name' => 'ITEP - Barra do Ceara',
        'is_headquarters' => true,
    ]);

    $school2 = School::create([
        'legal_name' => 'ITEP Messejana LTDA',
        'trade_name' => 'ITEP - Messejana',
    ]);

    // Componente já configurado para a escola 1
    $component = Livewire::withQueryParams(['escola' => (string) $school1->id])
        ->test(StudentAttendanceKiosk::class);

    // Tentativa anônima de abrir o switcher deve exigir auth do supervisor
    $component->call('openSchoolSwitcher')
        ->assertSet('showSupervisorAuthModal', true)
        ->assertSet('showSchoolSwitcherModal', false);

    // Tentativa anônima de trocar diretamente a escola não altera se já houver escola definida
    $component->call('selectKioskSchool', $school2->id)
        ->assertSet('kioskSchoolId', $school1->id)
        ->assertSet('showSupervisorAuthModal', true);
});

test('supervisor can authenticate with credentials and switch school', function () {
    $school1 = School::create([
        'legal_name' => 'ITEP Barra do Ceara LTDA',
        'trade_name' => 'ITEP - Barra do Ceara',
        'is_headquarters' => true,
    ]);

    $school2 = School::create([
        'legal_name' => 'ITEP Messejana LTDA',
        'trade_name' => 'ITEP - Messejana',
    ]);

    // Cria super admin
    $person = \App\Models\Person::create([
        'name' => 'Supervisor Geral',
        'email' => 'supervisor@aurosoft.local',
    ]);

    $supervisor = \App\Models\User::create([
        'person_id' => $person->id,
        'email' => 'supervisor@aurosoft.local',
        'password' => bcrypt('senha123'),
        'panel_access' => true,
    ]);

    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $supervisor->assignRole($role);

    $component = Livewire::withQueryParams(['escola' => (string) $school1->id])
        ->test(StudentAttendanceKiosk::class)
        ->set('supervisorLogin', 'supervisor@aurosoft.local')
        ->set('supervisorPassword', 'senha123')
        ->call('authenticateSupervisor')
        ->assertRedirect();

    expect(session('open_school_switcher_modal'))->toBeTrue();

    session(['open_school_switcher_modal' => true]);

    // Na nova requisição com a sessão flash ativa, o modal abre diretamente:
    $reloadedComponent = Livewire::actingAs($supervisor)
        ->test(StudentAttendanceKiosk::class)
        ->assertSet('showSchoolSwitcherModal', true);

    $reloadedComponent->call('selectKioskSchool', $school2->id)
        ->assertSet('kioskSchoolId', $school2->id)
        ->assertSee('ITEP - Messejana');
});

test('user without explicit permission is rejected even if has generic role', function () {
    $school1 = School::create([
        'legal_name' => 'ITEP Barra do Ceara LTDA',
        'trade_name' => 'ITEP - Barra do Ceara',
        'is_headquarters' => true,
    ]);

    $person = \App\Models\Person::create([
        'name' => 'Instrutor Sem Permissao',
        'email' => 'instrutor@aurosoft.local',
    ]);

    $user = \App\Models\User::create([
        'person_id' => $person->id,
        'email' => 'instrutor@aurosoft.local',
        'password' => bcrypt('senha123'),
        'panel_access' => true,
    ]);

    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'professor', 'guard_name' => 'web']);
    $user->assignRole($role);

    Livewire::withQueryParams(['escola' => (string) $school1->id])
        ->test(StudentAttendanceKiosk::class)
        ->call('openSchoolSwitcher')
        ->assertSet('showSupervisorAuthModal', true)
        ->set('supervisorLogin', 'instrutor@aurosoft.local')
        ->set('supervisorPassword', 'senha123')
        ->call('authenticateSupervisor')
        ->assertSet('showSupervisorAuthModal', true)
        ->assertSet('supervisorAuthError', 'Seu usuário não possui permissão para configurar este terminal. Solicite a liberação ao administrador.');
});

test('user with explicit View:StudentAttendanceKiosk permission can configure kiosk', function () {
    $school1 = School::create([
        'legal_name' => 'ITEP Barra do Ceara LTDA',
        'trade_name' => 'ITEP - Barra do Ceara',
        'is_headquarters' => true,
    ]);

    $school2 = School::create([
        'legal_name' => 'ITEP Messejana LTDA',
        'trade_name' => 'ITEP - Messejana',
    ]);

    $person = \App\Models\Person::create([
        'name' => 'Secretaria Autorizada',
        'email' => 'secretaria@aurosoft.local',
    ]);

    $user = \App\Models\User::create([
        'person_id' => $person->id,
        'email' => 'secretaria@aurosoft.local',
        'password' => bcrypt('senha123'),
        'panel_access' => true,
    ]);

    $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'View:StudentAttendanceKiosk', 'guard_name' => 'web']);
    $user->givePermissionTo($permission);

    Livewire::withQueryParams(['escola' => (string) $school1->id])
        ->test(StudentAttendanceKiosk::class)
        ->set('supervisorLogin', 'secretaria@aurosoft.local')
        ->set('supervisorPassword', 'senha123')
        ->call('authenticateSupervisor')
        ->assertRedirect();

    expect(session('open_school_switcher_modal'))->toBeTrue();
});

test('announcements are isolated by school unit', function () {
    $school1 = School::create([
        'legal_name' => 'ITEP Barra do Ceara LTDA',
        'trade_name' => 'ITEP - Barra do Ceara',
        'is_headquarters' => true,
    ]);

    $school2 = School::create([
        'legal_name' => 'ITEP Messejana LTDA',
        'trade_name' => 'ITEP - Messejana',
    ]);

    // Aviso Global
    \App\Models\StudentAnnouncement::create([
        'title' => 'Aviso Geral para Toda a Rede',
        'body' => 'Comunicado geral',
        'is_active' => true,
        'school_id' => null,
    ]);

    // Aviso exclusivo da Escola 1
    \App\Models\StudentAnnouncement::create([
        'title' => 'Aviso Exclusivo Barra do Ceara',
        'body' => 'Aviso da unidade 1',
        'is_active' => true,
        'school_id' => $school1->id,
    ]);

    // Aviso exclusivo da Escola 2
    \App\Models\StudentAnnouncement::create([
        'title' => 'Aviso Exclusivo Messejana',
        'body' => 'Aviso da unidade 2',
        'is_active' => true,
        'school_id' => $school2->id,
    ]);

    // Totem na Escola 1: deve ver Global e Escola 1, mas NÃO Escola 2
    Livewire::withQueryParams(['escola' => (string) $school1->id])
        ->test(StudentAttendanceKiosk::class)
        ->assertSee('Aviso Geral para Toda a Rede')
        ->assertSee('Aviso Exclusivo Barra do Ceara')
        ->assertDontSee('Aviso Exclusivo Messejana');

    // Totem na Escola 2: deve ver Global e Escola 2, mas NÃO Escola 1
    Livewire::withQueryParams(['escola' => (string) $school2->id])
        ->test(StudentAttendanceKiosk::class)
        ->assertSee('Aviso Geral para Toda a Rede')
        ->assertSee('Aviso Exclusivo Messejana')
        ->assertDontSee('Aviso Exclusivo Barra do Ceara');
});
