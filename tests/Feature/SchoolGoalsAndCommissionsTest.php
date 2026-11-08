<?php

declare(strict_types=1);

use App\Enums\RelationshipEnum;
use App\Filament\Pages\CommissionReport;
use App\Filament\Resources\SchoolGoals\Pages\ManageSchoolGoals;
use App\Filament\Widgets\SchoolGoalsProgressWidget;
use App\Models\Collaborator;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\SchoolGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
});

function createGoalTestUser(string $name, string $email): User
{
    $person = Person::create([
        'name' => $name,
        'email' => $email,
    ]);

    return User::create([
        'person_id' => $person->id,
        'email' => $email,
        'password' => bcrypt('password123'),
    ]);
}

function createTestCollaborator(string $name, string $role, string $type, float $rate, ?string $pix = null): Collaborator
{
    $person = Person::create([
        'name' => $name,
        'email' => strtolower(str_replace(' ', '', $name)).'@escola.com',
    ]);

    return Collaborator::create([
        'person_id' => $person->id,
        'admission_date' => now()->subMonths(6)->toDateString(),
        'role' => $role,
        'pix' => $pix,
        'active' => true,
        'commission_type' => $type,
        'commission_rate' => $rate,
        'hourly_rate' => $type === 'hourly_rate' ? $rate : 0.00,
    ]);
}

test('collaborator calculates commission according to client-defined rules', function () {
    $collabFixed = createTestCollaborator('Vendedor Fixo', 'Comercial', Collaborator::COMMISSION_TYPE_FIXED_PER_CONTRACT, 75.00);
    expect($collabFixed->calculateCommission(contractsCount: 4, totalSales: 8000.00, totalPaid: 2000.00))
        ->toBe(300.00);
    expect($collabFixed->formatted_commission_rule)->toBe('R$ 75,00 por matrícula');

    $collabPercentSales = createTestCollaborator('Vendedor Percentual', 'Comercial', Collaborator::COMMISSION_TYPE_PERCENTAGE_CONTRACT, 5.00);
    expect($collabPercentSales->calculateCommission(contractsCount: 2, totalSales: 6000.00, totalPaid: 1000.00))
        ->toBe(300.00);
    expect($collabPercentSales->formatted_commission_rule)->toBe('5,00% sobre o valor das vendas');

    $collabPercentPaid = createTestCollaborator('Vendedor s/ Recebido', 'Comercial', Collaborator::COMMISSION_TYPE_PERCENTAGE_RECEIVED, 8.00);
    expect($collabPercentPaid->calculateCommission(contractsCount: 5, totalSales: 15000.00, totalPaid: 2500.00))
        ->toBe(200.00);
    expect($collabPercentPaid->formatted_commission_rule)->toBe('8,00% sobre parcelas recebidas');

    $collabTeacher = createTestCollaborator('Professor Silva', 'Educador', Collaborator::COMMISSION_TYPE_HOURLY_RATE, 45.00);
    expect($collabTeacher->calculateCommission(contractsCount: 0, totalSales: 0.00, totalPaid: 0.00, teachingHours: 12.0))
        ->toBe(540.00);
    expect($collabTeacher->formatted_commission_rule)->toBe('R$ 45,00 / hora-aula');

    $collabNone = createTestCollaborator('Secretaria Sem Comissao', 'Secretária', Collaborator::COMMISSION_TYPE_NONE, 0.00);
    expect($collabNone->calculateCommission(contractsCount: 10, totalSales: 20000.00, totalPaid: 5000.00))
        ->toBe(0.0);
    expect($collabNone->formatted_commission_rule)->toBe('Sem comissão');
});

test('school goal model can store and retrieve goals for current month', function () {
    $now = Carbon::now();

    $goal = SchoolGoal::create([
        'month' => $now->month,
        'year' => $now->year,
        'target_enrollments' => 35,
        'target_sales_amount' => 45000.00,
        'target_revenue_amount' => 38000.00,
        'notes' => 'Campanha intensiva de matrículas de verão.',
    ]);

    expect(SchoolGoal::forCurrentMonth()->id)->toBe($goal->id);
    expect($goal->target_enrollments)->toBe(35);
    expect((float) $goal->target_sales_amount)->toBe(45000.00);
    expect($goal->formatted_period)->toContain((string) $now->year);
});

test('school goals progress widget renders correctly with and without goal', function () {
    $user = createGoalTestUser('Gestor Diretor', 'gestor@escola.com');
    $user->assignRole($this->superAdminRole);
    $this->actingAs($user);

    // Sem meta configurada
    Livewire::test(SchoolGoalsProgressWidget::class)
        ->assertSuccessful()
        ->assertSee('Nenhuma meta definida para')
        ->assertSee('Definir Metas');

    // Com meta configurada
    $now = Carbon::now();
    SchoolGoal::create([
        'month' => $now->month,
        'year' => $now->year,
        'target_enrollments' => 25,
        'target_sales_amount' => 30000.00,
        'target_revenue_amount' => 20000.00,
        'notes' => 'Foco nos cursos profissionalizantes.',
    ]);

    Livewire::test(SchoolGoalsProgressWidget::class)
        ->assertSuccessful()
        ->assertSee('Novas Matrículas')
        ->assertSee('Vendas (Contratos)')
        ->assertSee('Arrecadação (Caixa)')
        ->assertSee('Foco nos cursos profissionalizantes');
});

test('super admin can access and manage school goals via resource page', function () {
    $user = createGoalTestUser('Admin Metas', 'admin_metas@escola.com');
    $user->assignRole($this->superAdminRole);
    $this->actingAs($user);

    Livewire::test(ManageSchoolGoals::class)
        ->assertSuccessful()
        ->assertSee('Metas da Escola');
});

test('commission report calculates collaborator commissions and supports csv export', function () {
    $user = createGoalTestUser('Diretor Financeiro', 'financeiro@escola.com');
    $user->assignRole($this->superAdminRole);
    $this->actingAs($user);

    $seller = createTestCollaborator('Consultor Carlos', 'Consultor Comercial', Collaborator::COMMISSION_TYPE_FIXED_PER_CONTRACT, 60.00, '12345678900');

    $studentPerson = Person::create([
        'name' => 'Aluno Matriculado',
        'email' => 'aluno@exemplo.com',
    ]);

    $responsible = Responsible::create([
        'person_id' => $studentPerson->id,
        'relationship' => RelationshipEnum::Outro,
        'financial_responsible' => true,
    ]);

    $contract = Contract::create([
        'contract_number' => 'CTR-TEST-001',
        'student_id' => $studentPerson->id,
        'responsible_id' => $responsible->id,
        'collaborator_id' => $seller->id,
        'status' => 'active',
        'start_date' => now()->toDateString(),
        'total_value' => 2500.00,
        'payment_method' => 'carnet',
    ]);

    Installment::create([
        'contract_id' => $contract->id,
        'installment_number' => 1,
        'amount' => 500.00,
        'amount_paid' => 500.00,
        'due_date' => now()->toDateString(),
        'paid_at' => now(),
        'status' => 'paid',
    ]);

    Livewire::test(CommissionReport::class)
        ->assertSuccessful()
        ->assertSee('Consultor Carlos')
        ->assertSee('R$ 60,00 por matrícula')
        ->assertSee('12345678900')
        ->assertSee('R$ 60,00');

    // Teste de exportação CSV
    $component = Livewire::test(CommissionReport::class);
    $response = $component->instance()->exportCsv();

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('content-type'))->toBe('text/csv; charset=UTF-8');
});

test('user without permissions cannot access commission report or school goals', function () {
    $user = createGoalTestUser('Colaborador Sem Acesso', 'noaccess_goals@escola.com');
    $this->actingAs($user);

    Livewire::test(CommissionReport::class)
        ->assertForbidden();

    Livewire::test(ManageSchoolGoals::class)
        ->assertForbidden();
});
