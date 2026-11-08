<?php

use App\Enums\RelationshipEnum;
use App\Filament\Pages\PixSettings;
use App\Filament\Resources\Schools\Schemas\SchoolForm;
use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\Installment;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\User;
use App\Services\PixService;
use Filament\Schemas\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('pix service generates valid bacen br code and qr code svg data uri', function () {
    $service = app(PixService::class);

    $school = [
        'pix_key' => '12.345.678/0001-90',
        'pix_key_type' => 'cnpj',
        'pix_merchant_name' => 'Escola Modelo Cursos',
        'pix_merchant_city' => 'São Paulo',
        'pix_instructions' => 'Favor enviar o comprovante pelo WhatsApp',
    ];

    $installment = new Installment([
        'contract_id' => 12,
        'installment_number' => 1,
        'total_installments' => 6,
        'amount' => 250.00,
        'due_date' => now()->addDays(15),
    ]);
    $installment->id = 99;

    $pixData = $service->generateForInstallment($installment, $school);

    expect($pixData)->not->toBeNull();
    expect($pixData['pix_key'])->toBe('12.345.678/0001-90');
    expect($pixData['merchant_name'])->toBe('ESCOLA MODELO CURSOS');
    expect($pixData['merchant_city'])->toBe('SAO PAULO');
    expect($pixData['amount'])->toBe(250.0);
    expect($pixData['txid'])->toContain('CTR12P01');
    expect($pixData['copy_paste'])->toContain('br.gov.bcb.pix');
    expect($pixData['copy_paste'])->toContain('6304'); // CRC16 tag at the end
    expect($pixData['qr_code_uri'])->toStartWith('data:image/svg+xml;base64,');
});

test('pix service returns null when school has no pix key', function () {
    $service = app(PixService::class);

    $school = [
        'pix_key' => null,
    ];

    $installment = new Installment(['amount' => 100.0]);

    $pixData = $service->generateForInstallment($installment, $school);

    expect($pixData)->toBeNull();
});

test('contract carne renders pix qr code and details when pix is configured', function () {
    $userPerson = Person::create(['name' => 'Admin Usuário', 'email' => 'admin@example.com']);
    $user = User::create(['person_id' => $userPerson->id, 'email' => 'admin@example.com', 'password' => 'password']);
    $role = Role::findOrCreate('super_admin');
    $user->assignRole($role);

    $school = School::create([
        'legal_name' => 'Escola Aurosoft Ltda',
        'trade_name' => 'Aurosoft Cursos',
        'cnpj' => '12.345.678/0001-90',
        'city' => 'Curitiba',
        'state' => 'PR',
        'pix_key' => '12.345.678/0001-90',
        'pix_key_type' => 'cnpj',
        'pix_merchant_name' => 'AUROSOFT CURSOS',
        'pix_merchant_city' => 'CURITIBA',
        'pix_instructions' => 'Enviar comprovante para secretaria',
    ]);

    $student = Person::create([
        'name' => 'Lucas Santos',
        'email' => 'lucas@example.com',
        'cpf' => '111.222.333-44',
    ]);

    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-900',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'total_value' => 300,
        'status' => 'active',
    ]);

    $finType = FinancialType::create([
        'name' => 'Mensalidade',
        'slug' => 'mensalidade',
        'type' => 'income',
        'is_active' => true,
    ]);

    Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $finType->id,
        'installment_number' => 1,
        'total_installments' => 1,
        'amount' => 300.00,
        'due_date' => now()->addDays(10)->toDateString(),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('contracts.carne', $contract));

    $response->assertOk();
    $response->assertSee('PAGUE COM PIX');
    $response->assertSee('data:image/svg+xml;base64,');
    $response->assertSee('12.345.678/0001-90');
    $response->assertSee('AUROSOFT CURSOS');
    $response->assertSee('Enviar comprovante para secretaria');
    $response->assertSee('Chave PIX');
});

test('contract carne renders gracefully when pix is not configured', function () {
    $userPerson = Person::create(['name' => 'Admin Teste', 'email' => 'admin2@example.com']);
    $user = User::create(['person_id' => $userPerson->id, 'email' => 'admin2@example.com', 'password' => 'password']);
    $role = Role::findOrCreate('super_admin');
    $user->assignRole($role);

    $school = School::create([
        'legal_name' => 'Escola Sem Pix Ltda',
        'trade_name' => 'Escola Sem Pix',
        'cnpj' => '99.888.777/0001-66',
        'city' => 'Florianópolis',
        'state' => 'SC',
        'pix_key' => null,
    ]);

    $student = Person::create([
        'name' => 'Ana Paula',
        'email' => 'ana@example.com',
        'cpf' => '555.666.777-88',
    ]);

    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-901',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'total_value' => 200,
        'status' => 'active',
    ]);

    $finType = FinancialType::create([
        'name' => 'Mensalidade',
        'slug' => 'mensalidade-2',
        'type' => 'income',
        'is_active' => true,
    ]);

    Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $finType->id,
        'installment_number' => 1,
        'total_installments' => 1,
        'amount' => 200.00,
        'due_date' => now()->addDays(5)->toDateString(),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('contracts.carne', $contract));

    $response->assertOk();
    $response->assertDontSee('PAGUE COM PIX');
    $response->assertSee('Instruções ao Aluno e ao Caixa:');
});

test('pix settings page can be accessed and saved by authorized administrator', function () {
    $userPerson = Person::create(['name' => 'Admin Pix', 'email' => 'admin.pix@example.com']);
    $user = User::create(['person_id' => $userPerson->id, 'email' => 'admin.pix@example.com', 'password' => 'password']);
    $role = Role::findOrCreate('super_admin');
    $user->assignRole($role);

    $school = School::first() ?: School::create([
        'legal_name' => 'Aurosoft Tecnologias',
        'trade_name' => 'Aurosoft Escola',
        'cnpj' => '33.444.555/0001-22',
        'city' => 'Porto Alegre',
        'state' => 'RS',
    ]);
    $school->update([
        'legal_name' => 'Aurosoft Tecnologias',
        'trade_name' => 'Aurosoft Escola',
        'cnpj' => '33.444.555/0001-22',
        'city' => 'Porto Alegre',
        'state' => 'RS',
    ]);

    $this->actingAs($user);

    Livewire::test(PixSettings::class)
        ->assertSuccessful()
        ->assertSee('Chave e Dados do Recebedor PIX')
        ->fillForm([
            'pix_key_type' => 'cnpj',
            'pix_key' => '33.444.555/0001-22',
            'pix_merchant_name' => 'AUROSOFT ESCOLA',
            'pix_merchant_city' => 'PORTO ALEGRE',
            'pix_instructions' => 'Enviar comprovante via WhatsApp',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Configurações do PIX salvas');

    $school->refresh();
    expect($school->pix_key)->toBe('33.444.555/0001-22');
    expect($school->pix_merchant_name)->toBe('AUROSOFT ESCOLA');
    expect($school->pix_merchant_city)->toBe('PORTO ALEGRE');
    expect($school->pix_instructions)->toBe('Enviar comprovante via WhatsApp');
});

test('unauthorized users cannot access pix settings page', function () {
    $userPerson = Person::create(['name' => 'Aluno Comum', 'email' => 'aluno.comum@example.com']);
    $user = User::create(['person_id' => $userPerson->id, 'email' => 'aluno.comum@example.com', 'password' => 'password']);
    $role = Role::findOrCreate('aluno');
    $user->assignRole($role);

    $this->actingAs($user);

    expect(PixSettings::canAccess())->toBeFalse();
});

test('school form includes pix configuration section and fields', function () {
    $schema = SchoolForm::configure(new Schema);
    $components = $schema->getComponents();

    $pixSection = collect($components)->first(function ($component) {
        return method_exists($component, 'getHeading')
            && str_contains($component->getHeading(), 'PIX');
    });

    expect($pixSection)->not->toBeNull();
});

test('pix service returns null when pix_enabled is false even if key is present', function () {
    $service = app(PixService::class);

    $school = [
        'pix_enabled' => false,
        'pix_key' => '12.345.678/0001-90',
        'pix_merchant_name' => 'Escola Modelo',
    ];

    $installment = new Installment(['amount' => 100.0]);

    $pixData = $service->generateForInstallment($installment, $school);

    expect($pixData)->toBeNull();
});

test('contract carne does not render pix when pix is disabled in school', function () {
    $userPerson = Person::create(['name' => 'Admin Desativar', 'email' => 'admin.desativar@example.com']);
    $user = User::create(['person_id' => $userPerson->id, 'email' => 'admin.desativar@example.com', 'password' => 'password']);
    $role = Role::findOrCreate('super_admin');
    $user->assignRole($role);

    $school = School::create([
        'legal_name' => 'Escola Pix Desativado Ltda',
        'trade_name' => 'Escola Pix Desativado',
        'cnpj' => '44.333.222/0001-11',
        'city' => 'Belo Horizonte',
        'state' => 'MG',
        'pix_enabled' => false,
        'pix_key' => '44.333.222/0001-11',
        'pix_merchant_name' => 'ESCOLA PIX DESATIVADO',
    ]);

    $student = Person::create([
        'name' => 'Juliana Lima',
        'email' => 'juliana@example.com',
        'cpf' => '777.888.999-00',
    ]);

    $responsible = Responsible::create([
        'person_id' => $student->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-NOPIX',
        'student_id' => $student->id,
        'responsible_id' => $responsible->id,
        'start_date' => now()->toDateString(),
        'total_value' => 180,
        'status' => 'active',
    ]);

    $finType = FinancialType::create([
        'name' => 'Mensalidade',
        'slug' => 'mensalidade-nopix',
        'type' => 'income',
        'is_active' => true,
    ]);

    Installment::create([
        'contract_id' => $contract->id,
        'financial_type_id' => $finType->id,
        'installment_number' => 1,
        'total_installments' => 1,
        'amount' => 180.00,
        'due_date' => now()->addDays(5)->toDateString(),
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('contracts.carne', $contract));

    $response->assertOk();
    $response->assertDontSee('PAGUE COM PIX');
    $response->assertDontSee('data:image/svg+xml;base64,');
    $response->assertSee('Instruções ao Aluno e ao Caixa:');
});

test('pix settings can disable pix and toggle persists', function () {
    $userPerson = Person::create(['name' => 'Admin Toggle', 'email' => 'admin.toggle@example.com']);
    $user = User::create(['person_id' => $userPerson->id, 'email' => 'admin.toggle@example.com', 'password' => 'password']);
    $role = Role::findOrCreate('super_admin');
    $user->assignRole($role);

    $school = School::first() ?: School::create([
        'legal_name' => 'Escola Boss',
        'trade_name' => 'Escola Boss',
        'cnpj' => '88.777.666/0001-55',
        'city' => 'Campinas',
        'state' => 'SP',
        'pix_enabled' => true,
        'pix_key' => '88.777.666/0001-55',
    ]);
    $school->update([
        'legal_name' => 'Escola Boss',
        'trade_name' => 'Escola Boss',
        'cnpj' => '88.777.666/0001-55',
        'city' => 'Campinas',
        'state' => 'SP',
        'pix_enabled' => true,
        'pix_key' => '88.777.666/0001-55',
    ]);

    $this->actingAs($user);

    Livewire::test(PixSettings::class)
        ->assertSuccessful()
        ->fillForm([
            'pix_enabled' => false,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified('Configurações do PIX salvas');

    $school->refresh();
    expect($school->pix_enabled)->toBeFalse();
});
