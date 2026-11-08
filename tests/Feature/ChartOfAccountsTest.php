<?php

namespace Tests\Feature;

use App\Filament\Resources\FinancialTypes\FinancialTypeResource;
use App\Filament\Resources\FinancialTypes\Pages\ManageFinancialTypes;
use App\Models\FinancialType;
use App\Models\School;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\FinancialTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChartOfAccountsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'legal_name' => 'Escola Matriz',
            'trade_name' => 'Matriz',
            'is_main' => true,
        ]);

        $person = \App\Models\Person::create([
            'name' => 'Admin Test',
            'email' => 'admin_test@aurosoft.com',
        ]);

        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->admin = User::factory()->create([
            'person_id' => $person->id,
            'email' => 'admin_test@aurosoft.com',
        ]);
        $this->admin->assignRole('super_admin');
    }

    public function test_seeder_populates_standard_school_chart_of_accounts(): void
    {
        // 1º roda o seeder antigo de tipos
        $this->seed(FinancialTypeSeeder::class);
        $this->assertDatabaseHas('financial_types', ['name' => 'Mensalidade']);

        // 2º roda o novo seeder do Plano de Contas
        $this->seed(ChartOfAccountsSeeder::class);

        // Verifica contas sintéticas raiz
        $receitas = FinancialType::where('code', '1')->first();
        $this->assertNotNull($receitas);
        $this->assertTrue($receitas->is_synthetic);
        $this->assertEquals(FinancialType::TYPE_REVENUE, $receitas->type);

        $custos = FinancialType::where('code', '2')->first();
        $this->assertNotNull($custos);
        $this->assertTrue($custos->is_synthetic);
        $this->assertEquals(FinancialType::TYPE_COST, $custos->type);

        $despesasFixas = FinancialType::where('code', '3')->first();
        $this->assertNotNull($despesasFixas);
        $this->assertTrue($despesasFixas->is_synthetic);
        $this->assertEquals(FinancialType::TYPE_EXPENSE, $despesasFixas->type);

        $despesasComerciais = FinancialType::where('code', '4')->first();
        $this->assertNotNull($despesasComerciais);
        $this->assertEquals(FinancialType::TYPE_EXPENSE, $despesasComerciais->type);

        $despesasFinanceiras = FinancialType::where('code', '5')->first();
        $this->assertNotNull($despesasFinanceiras);
        $this->assertEquals(FinancialType::TYPE_FINANCIAL, $despesasFinanceiras->type);

        // Verifica que Mensalidade virou analítica com código 1.1.1 e pai 1.1
        $mensalidade = FinancialType::where('code', '1.1.1')->first();
        $this->assertNotNull($mensalidade);
        $this->assertEquals('Mensalidade', $mensalidade->name);
        $this->assertFalse($mensalidade->is_synthetic);
        $this->assertNotNull($mensalidade->parent_id);
        $this->assertEquals('1.1', $mensalidade->parent->code);

        // Verifica contas de despesas operacionais (ex: Aluguel e Tráfego Pago)
        $aluguel = FinancialType::where('code', '3.1.1')->first();
        $this->assertNotNull($aluguel);
        $this->assertFalse($aluguel->is_synthetic);
        $this->assertEquals(FinancialType::TYPE_EXPENSE, $aluguel->type);

        $trafego = FinancialType::where('code', '4.1.1')->first();
        $this->assertNotNull($trafego);
        $this->assertEquals(FinancialType::TYPE_EXPENSE, $trafego->type);
    }

    public function test_super_admin_can_access_chart_of_accounts_page(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);

        $this->actingAs($this->admin);

        Livewire::test(ManageFinancialTypes::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(FinancialType::query()->take(5)->get());
    }

    public function test_can_create_custom_analytical_account(): void
    {
        $this->seed(ChartOfAccountsSeeder::class);

        $parent = FinancialType::where('code', '3.1')->firstOrFail();

        $account = FinancialType::create([
            'code' => '3.1.9',
            'name' => 'Manutenção Predial e Reformas',
            'parent_id' => $parent->id,
            'type' => FinancialType::TYPE_EXPENSE,
            'is_synthetic' => false,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('financial_types', [
            'code' => '3.1.9',
            'name' => 'Manutenção Predial e Reformas',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals('3.1.9 - Manutenção Predial e Reformas', $account->full_name);
    }
}
