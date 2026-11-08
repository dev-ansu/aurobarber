<?php

use App\Enums\ContactTypeEnum;
use App\Enums\RelationshipEnum;
use App\Filament\Resources\Contracts\Pages\CreateContracts;
use App\Filament\Resources\People\Pages\EditPerson;
use App\Filament\Resources\People\RelationManagers\ContactsRelationManager;
use App\Models\Address;
use App\Models\City;
use App\Models\Contact;
use App\Models\FinancialType;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\State;
use App\Models\User;
use Database\Seeders\FinancialTypeSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createAdminForEnhancements(): User
{
    $person = Person::create([
        'name' => 'Admin Enhancements',
        'email' => 'admin.enhancements@example.com',
    ]);

    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::create([
        'person_id' => $person->id,
        'email' => 'admin.enhancements@example.com',
        'password' => bcrypt('password'),
        'panel_access' => true,
    ]);

    $user->assignRole($role);

    foreach (['ViewAny:Person', 'View:Person', 'Create:Person', 'Update:Person', 'ViewAny:Contract', 'Create:Contract', 'View:Contract'] as $p) {
        $perm = Permission::findOrCreate($p, 'web');
        $user->givePermissionTo($perm);
    }

    return $user;
}

beforeEach(function () {
    $admin = createAdminForEnhancements();
    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('contacts relation manager accepts masked phone and cellphone and validates invalid formats', function () {
    $person = Person::create(['name' => 'Contato Teste', 'email' => 'contato.teste@exemplo.com']);

    // 1. WhatsApp com máscara (11) 98765-4321
    Livewire::test(ContactsRelationManager::class, [
        'ownerRecord' => $person,
        'pageClass' => EditPerson::class,
    ])
        ->mountTableAction('create')
        ->setTableActionData([
            'type' => ContactTypeEnum::WhatsApp->value,
            'value' => '(11) 98765-4321',
            'is_primary' => true,
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas(Contact::class, [
        'person_id' => $person->id,
        'type' => ContactTypeEnum::WhatsApp,
        'value' => '(11) 98765-4321',
        'is_primary' => true,
    ]);

    // 2. Telefone fixo com máscara (11) 3456-7890
    Livewire::test(ContactsRelationManager::class, [
        'ownerRecord' => $person,
        'pageClass' => EditPerson::class,
    ])
        ->mountTableAction('create')
        ->setTableActionData([
            'type' => ContactTypeEnum::Telefone->value,
            'value' => '(11) 3456-7890',
            'is_primary' => false,
        ])
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    // 3. Telefone inválido deve falhar na validação
    Livewire::test(ContactsRelationManager::class, [
        'ownerRecord' => $person,
        'pageClass' => EditPerson::class,
    ])
        ->mountTableAction('create')
        ->setTableActionData([
            'type' => ContactTypeEnum::Celular->value,
            'value' => 'invalido-123',
            'is_primary' => false,
        ])
        ->callMountedTableAction()
        ->assertHasTableActionErrors(['value']);
});

test('global date picker and datetime picker components are standardized to native allowing keyboard typing', function () {
    $datePicker = DatePicker::make('test_date');
    $dateTimePicker = DateTimePicker::make('test_datetime');

    expect($datePicker->isNative())->toBeTrue()
        ->and($dateTimePicker->isNative())->toBeTrue();
});

test('contracts form calculation mode multiply calculates total value from installments', function () {
    $student = Person::create(['name' => 'Aluno Multiplicacao', 'email' => 'multi@teste.com']);
    $resp = Responsible::create(['person_id' => $student->id, 'relationship' => RelationshipEnum::Mother, 'financial_responsible' => true]);
    $finType = FinancialType::create(['name' => 'Mensalidade', 'slug' => 'tuition', 'is_active' => true]);

    Livewire::test(CreateContracts::class)
        ->fillForm([
            'student_id' => $student->id,
            'responsible_id' => $resp->id,
            'status' => 'draft',
            'start_date' => now()->toDateString(),
            'first_due_date' => now()->toDateString(),
            'simulation_financial_type_id' => $finType->id,
            'calculation_mode' => 'multiply',
            'installment_value' => 140.00,
            'qty_installments' => 12,
        ])
        ->assertFormSet([
            'total_value' => 1680.00,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $contract = \App\Models\Contract::where('student_id', $student->id)->first();
    expect($contract)->not->toBeNull()
        ->and((float) $contract->total_value)->toBe(1680.00)
        ->and($contract->installments()->count())->toBe(12)
        ->and((float) $contract->installments()->first()->amount)->toBe(140.00);
});

test('payment methods and financial types seeders create standard records', function () {
    $this->seed(PaymentMethodSeeder::class);
    $this->seed(FinancialTypeSeeder::class);

    expect(PaymentMethod::where('slug', 'pix')->exists())->toBeTrue()
        ->and(PaymentMethod::where('slug', 'dinheiro')->exists())->toBeTrue()
        ->and(PaymentMethod::where('slug', 'cartao_de_credito')->exists())->toBeTrue()
        ->and(FinancialType::where('slug', 'mensalidade')->exists())->toBeTrue()
        ->and(FinancialType::where('slug', 'taxa_de_matricula')->exists())->toBeTrue()
        ->and(FinancialType::where('slug', 'multa_de_rescisao')->exists())->toBeTrue()
        ->and(FinancialType::where('slug', 'taxa_de_reposicao_de_aula')->exists())->toBeTrue();
});
