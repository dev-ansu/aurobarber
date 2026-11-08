<?php

use App\Filament\Auth\EditOwnProfile;
use App\Filament\Pages\CommercialLeadsKanban;
use App\Filament\Pages\DreReport;
use App\Filament\Pages\OuroModernoStudentLogsReport;
use App\Filament\Pages\PixSettings;
use App\Filament\Pages\SalesBySellerReport;
use App\Filament\Pages\StudentAbsencesReport;
use App\Filament\Pages\StudentContactsReport;
use App\Filament\Pages\UserGuide;
use App\Filament\Resources\Payables\PayableResource;
use App\Filament\Resources\StudentAnnouncements\StudentAnnouncementResource;
use App\Filament\Widgets\BirthdaysTableWidget;
use App\Models\Payable;
use App\Models\Person;
use App\Models\StudentAnnouncement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = \App\Models\School::first() ?: \App\Models\School::create([
        'legal_name' => 'Escola Matriz',
        'trade_name' => 'Matriz',
    ]);
});

test('zero-trust: user with zero permissions cannot access protected resources, pages, or widgets', function () {
    $person = Person::create([
        'name' => 'Usuário Sem Permissão',
        'email' => 'sempermissao@example.com',
    ]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => 'sempermissao@example.com',
        'password' => 'secret123',
    ]);
    $user->schools()->attach($this->school);

    $this->actingAs($user);

    // 1. Tabela de aniversariantes (Widget)
    expect(BirthdaysTableWidget::canView())->toBeFalse();

    // 2. Contas a pagar (Resource e Policy)
    expect(PayableResource::canViewAny())->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewAny', Payable::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('create', Payable::class))->toBeFalse();

    // 3. Avisos do kiosk (Resource e Policy)
    expect(StudentAnnouncementResource::canViewAny())->toBeFalse()
        ->and(Gate::forUser($user)->allows('viewAny', StudentAnnouncement::class))->toBeFalse();

    // 4. Funil de vendas (Kanban)
    expect(CommercialLeadsKanban::canAccess())->toBeFalse();
    $this->get(CommercialLeadsKanban::getUrl())->assertForbidden();

    // 5. Relatórios
    expect(SalesBySellerReport::canAccess())->toBeFalse();
    $this->get(SalesBySellerReport::getUrl())->assertForbidden();

    expect(StudentContactsReport::canAccess())->toBeFalse();
    $this->get(StudentContactsReport::getUrl())->assertForbidden();

    expect(StudentAbsencesReport::canAccess())->toBeFalse();
    $this->get(StudentAbsencesReport::getUrl())->assertForbidden();

    expect(OuroModernoStudentLogsReport::canAccess())->toBeFalse();
    $this->get(OuroModernoStudentLogsReport::getUrl())->assertForbidden();

    expect(DreReport::canAccess())->toBeFalse();
    $this->get(DreReport::getUrl())->assertForbidden();

    expect(PixSettings::canAccess())->toBeFalse();
    $this->get(PixSettings::getUrl())->assertForbidden();
});

test('perfil e ajuda sao acessiveis por qualquer usuario autenticado', function () {
    $person = Person::create([
        'name' => 'Usuário Comum',
        'email' => 'comum@example.com',
    ]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => 'comum@example.com',
        'password' => 'secret123',
    ]);
    $user->schools()->attach($this->school);

    $this->actingAs($user);

    // Ajuda e tutoriais acessível
    expect(UserGuide::canAccess())->toBeTrue();
    $this->get(UserGuide::getUrl())->assertOk();

    // Perfil acessível
    expect(EditOwnProfile::canAccess())->toBeTrue();
    $profileResponse = $this->get(EditOwnProfile::getUrl());
    $profileResponse->assertOk();

    // Edição de perfil via Livewire
    Livewire::actingAs($user)
        ->test(EditOwnProfile::class)
        ->set('data.name', 'Nome Alterado do Usuário')
        ->call('save')
        ->assertHasNoErrors();

    $person->refresh();
    expect($person->name)->toBe('Nome Alterado do Usuário');

    // Validação de senha fraca (sem número/símbolo ou curta)
    Livewire::actingAs($user)
        ->test(EditOwnProfile::class)
        ->set('data.currentPassword', 'secret123')
        ->set('data.password', 'simples')
        ->set('data.passwordConfirmation', 'simples')
        ->call('save')
        ->assertHasErrors(['data.password']);

    // Validação de senha forte (8+ chars, letras, números e símbolos)
    Livewire::actingAs($user)
        ->test(EditOwnProfile::class)
        ->set('data.currentPassword', 'secret123')
        ->set('data.password', 'NovaSenha@2026')
        ->set('data.passwordConfirmation', 'NovaSenha@2026')
        ->call('save')
        ->assertHasNoErrors();
});

test('permissao individual concede acesso exclusivamente ao item atribuido (zero-trust)', function () {
    $person = Person::create([
        'name' => 'Vendedor',
        'email' => 'vendedor@example.com',
    ]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => 'vendedor@example.com',
        'password' => 'secret123',
    ]);
    $user->schools()->attach($this->school);

    // Concede apenas a permissão do relatório de vendas
    $permission = Permission::findOrCreate('View:SalesBySellerReport', 'web');
    $user->givePermissionTo($permission);

    $this->actingAs($user);

    // Tem acesso ao relatório de vendas por vendedor
    expect(SalesBySellerReport::canAccess())->toBeTrue();
    $this->get(SalesBySellerReport::getUrl())->assertOk();

    // Permanece sem acesso a todos os outros
    expect(CommercialLeadsKanban::canAccess())->toBeFalse();
    expect(StudentContactsReport::canAccess())->toBeFalse();
    expect(StudentAbsencesReport::canAccess())->toBeFalse();
    expect(BirthdaysTableWidget::canView())->toBeFalse();
    expect(PayableResource::canViewAny())->toBeFalse();
});

test('super_admin possui acesso a todos os itens protegidos', function () {
    $superAdminRole = Role::findOrCreate('super_admin', 'web');

    $person = Person::create([
        'name' => 'Super Administrador',
        'email' => 'superadmin@example.com',
    ]);

    $user = User::create([
        'person_id' => $person->id,
        'email' => 'superadmin@example.com',
        'password' => 'secret123',
    ]);
    $user->assignRole($superAdminRole);

    $this->actingAs($user);

    expect(BirthdaysTableWidget::canView())->toBeTrue();
    expect(PayableResource::canViewAny())->toBeTrue();
    expect(StudentAnnouncementResource::canViewAny())->toBeTrue();
    expect(CommercialLeadsKanban::canAccess())->toBeTrue();
    expect(SalesBySellerReport::canAccess())->toBeTrue();
    expect(StudentContactsReport::canAccess())->toBeTrue();
    expect(StudentAbsencesReport::canAccess())->toBeTrue();
    expect(DreReport::canAccess())->toBeTrue();
    expect(PixSettings::canAccess())->toBeTrue();
});
