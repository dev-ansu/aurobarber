<?php

use App\Filament\Pages\NetworkOverview;
use App\Models\CashRegister;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\Payable;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::bootCurrentPanel();
    School::query()->delete();
});

test('super admin can access network overview page and view consolidated metrics', function () {
    $school1 = School::create(['id' => 1, 'legal_name' => 'Escola Matriz', 'trade_name' => 'Matriz']);
    $school2 = School::create(['id' => 2, 'legal_name' => 'Escola Barra', 'trade_name' => 'Barra']);

    $adminPerson = Person::create(['name' => 'Admin Owner', 'email' => 'admin@network.com']);
    $admin = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'admin@network.com',
        'password' => bcrypt('password'),
    ]);
    $role = Role::findOrCreate('super_admin', 'web');
    $admin->assignRole($role);

    // Contratos e Alunos
    $student1 = Person::create(['name' => 'Aluno 1', 'email' => 'a1@test.com']);
    $student2 = Person::create(['name' => 'Aluno 2', 'email' => 'a2@test.com']);

    $contract1 = Contract::create([
        'school_id' => $school1->id,
        'student_id' => $student1->id,
        'contract_number' => 'MAT-001',
        'status' => 'active',
        'start_date' => today(),
        'amount' => 500,
    ]);

    $contract2 = Contract::create([
        'school_id' => $school2->id,
        'student_id' => $student2->id,
        'contract_number' => 'BAR-001',
        'status' => 'active',
        'start_date' => today(),
        'amount' => 600,
    ]);

    // Parcelas pagas no mês atual
    Installment::create([
        'contract_id' => $contract1->id,
        'installment_number' => 1,
        'amount' => 500,
        'amount_paid' => 500,
        'due_date' => today(),
        'paid_at' => today(),
        'status' => 'paid',
        'type' => 'tuition',
    ]);

    Installment::create([
        'contract_id' => $contract2->id,
        'installment_number' => 1,
        'amount' => 600,
        'amount_paid' => 600,
        'due_date' => today(),
        'paid_at' => today(),
        'status' => 'paid',
        'type' => 'tuition',
    ]);

    // Contas pagas
    Payable::create([
        'school_id' => $school1->id,
        'description' => 'Energia Matriz',
        'amount' => 200,
        'amount_paid' => 200,
        'due_date' => today(),
        'payment_date' => today(),
        'status' => 'paid',
    ]);

    Payable::create([
        'school_id' => $school2->id,
        'description' => 'Internet Barra',
        'amount' => 150,
        'amount_paid' => 150,
        'due_date' => today(),
        'payment_date' => today(),
        'status' => 'paid',
    ]);

    // Caixas abertos
    CashRegister::create([
        'school_id' => $school1->id,
        'user_id' => $admin->id,
        'name' => 'Caixa Matriz',
        'status' => 'open',
        'opening_balance' => 300,
        'current_balance' => 300,
        'opened_at' => now(),
    ]);

    CashRegister::create([
        'school_id' => $school2->id,
        'user_id' => $admin->id,
        'name' => 'Caixa Barra',
        'status' => 'open',
        'opening_balance' => 450,
        'current_balance' => 450,
        'opened_at' => now(),
    ]);

    $this->actingAs($admin);
    Filament::setTenant($school1, isQuiet: true);

    $test = Livewire::test(NetworkOverview::class);

    $test->assertSuccessful()
        ->assertSee('Painel Geral da Rede (Dono de Todas as Escolas)')
        ->assertSee('Matriz')
        ->assertSee('Barra');

    // Totais consolidados financeiros e pedagógicos 360º
    expect($test->get('totals.total_schools'))->toBe(2)
        ->and($test->get('totals.active_students'))->toBe(2)
        ->and($test->get('totals.revenue'))->toBe(1100.0)
        ->and($test->get('totals.expenses'))->toBe(350.0)
        ->and($test->get('totals.net_balance'))->toBe(750.0)
        ->and($test->get('totals.cash_balance'))->toBe(750.0)
        ->and($test->get('totals'))->toHaveKeys([
            'attendance_rate',
            'lms_completed_lessons',
            'lms_avg_score',
            'at_risk_students',
            'completed_contracts',
            'canceled_contracts',
            'avg_satisfaction',
            'leads_count',
            'conversion_rate',
        ]);
});

test('regular users cannot access network overview page', function () {
    $school = School::create(['id' => 1, 'legal_name' => 'Escola Matriz', 'trade_name' => 'Matriz']);

    $person = Person::create(['name' => 'Regular User', 'email' => 'user@test.com']);
    $user = User::create([
        'person_id' => $person->id,
        'email' => 'user@test.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($user);
    Filament::setTenant($school, isQuiet: true);

    expect(NetworkOverview::canAccess())->toBeFalse();
});
