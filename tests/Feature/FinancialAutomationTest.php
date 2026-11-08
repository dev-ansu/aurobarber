<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\City;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\FinancialAccount;
use App\Models\Installment;
use App\Models\Payable;
use App\Models\Person;
use App\Models\School;
use App\Models\State;
use App\Models\User;
use App\Services\CashFlowForecastService;
use App\Services\FinancialCalculationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancialAutomationTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $state = State::query()->firstOrCreate(['acronym' => 'SP'], ['name' => 'São Paulo']);
        $city = City::query()->firstOrCreate(['name' => 'São Paulo'], ['state_id' => $state->id, 'ibge_code' => '3550308']);

        $this->school = School::query()->create([
            'legal_name' => 'Escola Teste LTDA',
            'trade_name' => 'Aurosoft School',
            'cnpj' => '11.222.333/0001-99',
            'late_fine_percentage' => 2.00,
            'daily_interest_percentage' => 0.0333,
            'grace_days' => 0,
            'punctuality_discount_enabled' => true,
            'punctuality_discount_type' => 'fixed',
            'punctuality_discount_value' => 20.00,
            'punctuality_discount_days_tolerance' => 0,
            'pix_enabled' => true,
            'pix_key' => '11222333000199',
            'pix_key_type' => 'cnpj',
            'pix_merchant_name' => 'AUROSOFT SCHOOL',
            'pix_merchant_city' => 'SAO PAULO',
        ]);

        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $adminPerson = Person::query()->create(['name' => 'Admin Financeiro']);
        $this->admin = User::query()->create([
            'person_id' => $adminPerson->id,
            'email' => 'admin_financeiro@aurosoft.test',
            'password' => bcrypt('password'),
            'panel_access' => true,
        ]);
        $this->admin->assignRole($superAdminRole);
    }

    private function createContractAndInstallment(float $amount = 200.00, ?Carbon $dueDate = null): array
    {
        $person = Person::query()->create([
            'name' => 'Aluno Financeiro',
        ]);

        Contact::query()->create([
            'person_id' => $person->id,
            'type' => 'whatsapp',
            'value' => '11988887777',
            'is_primary' => true,
        ]);

        $contract = Contract::query()->create([
            'contract_number' => uniqid('CTR'),
            'student_id' => $person->id,
            'school_id' => $this->school->id,
            'total_value' => $amount,
            'status' => 'active',
            'start_date' => today(),
        ]);

        $dueDate ??= today();

        $installment = Installment::query()->create([
            'school_id' => $this->school->id,
            'contract_id' => $contract->id,
            'installment_number' => 1,
            'total_installments' => 1,
            'amount' => $amount,
            'discount' => 0.00,
            'fine' => 0.00,
            'due_date' => $dueDate->toDateString(),
            'status' => 'pending',
        ]);

        return [$contract, $installment];
    }

    public function test_overdue_fine_and_daily_interest_calculation(): void
    {
        $dueDate = Carbon::today()->subDays(10); // 10 dias de atraso
        [, $installment] = $this->createContractAndInstallment(200.00, $dueDate);

        $calc = app(FinancialCalculationService::class);
        $charges = $calc->calculateOverdueCharges($installment, Carbon::today(), $this->school);

        $this->assertTrue($charges['is_overdue']);
        $this->assertSame(10, $charges['days_late']);

        // Multa: 200 * 2% = 4.00
        $this->assertEquals(4.00, $charges['fine_amount']);

        // Juros: 200 * (0.0333 / 100) * 10 = 0.67
        $this->assertEquals(0.67, $charges['interest_amount']);
        $this->assertEquals(4.67, $charges['total_late_charges']);
    }

    public function test_punctuality_discount_calculation(): void
    {
        // 1. Pagamento em dia (elegível)
        $dueDate = Carbon::today()->addDays(5);
        [, $installment] = $this->createContractAndInstallment(200.00, $dueDate);

        $calc = app(FinancialCalculationService::class);
        $discountInfo = $calc->calculatePunctualityDiscount($installment, Carbon::today(), $this->school);

        $this->assertTrue($discountInfo['is_enabled']);
        $this->assertTrue($discountInfo['is_eligible']);
        $this->assertEquals(20.00, $discountInfo['discount_amount']);

        // 2. Pagamento atrasado (não elegível)
        $overdueDate = Carbon::today()->subDays(2);
        [, $overdueInstallment] = $this->createContractAndInstallment(200.00, $overdueDate);

        $overdueDiscountInfo = $calc->calculatePunctualityDiscount($overdueInstallment, Carbon::today(), $this->school);
        $this->assertFalse($overdueDiscountInfo['is_eligible']);
        $this->assertEquals(0.00, $overdueDiscountInfo['discount_amount']);
    }

    public function test_resolves_contact_phone_and_formats_international_whatsapp(): void
    {
        [, $installment] = $this->createContractAndInstallment(150.00);

        $calc = app(FinancialCalculationService::class);
        $phone = $calc->resolveContactPhone($installment);

        $this->assertNotNull($phone);
        $this->assertSame('5511988887777', $phone);
    }

    public function test_installment_can_be_canceled_with_audit_trail(): void
    {
        [, $installment] = $this->createContractAndInstallment(150.00);

        $this->actingAs($this->admin);

        $installment->cancel('Bolsa de estudo integral concedida', $this->admin->id);

        $installment->refresh();

        $this->assertSame('canceled', $installment->status);
        $this->assertSame('Bolsa de estudo integral concedida', $installment->cancellation_reason);
        $this->assertNotNull($installment->cancelled_at);
        $this->assertSame($this->admin->id, $installment->cancelled_by_id);
        $this->assertStringContainsString('Bolsa de estudo integral concedida', (string) $installment->notes);
    }

    public function test_financial_accounts_can_receive_deposits_and_track_balance(): void
    {
        $account = FinancialAccount::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Banco Cora PJ',
            'type' => 'bank',
            'initial_balance' => 1000.00,
            'current_balance' => 1000.00,
            'is_active' => true,
        ]);

        $this->assertEquals(1000.00, $account->current_balance);

        $account->deposit(350.50);
        $this->assertEquals(1350.50, $account->fresh()->current_balance);

        $account->withdraw(200.00);
        $this->assertEquals(1150.50, $account->fresh()->current_balance);
    }

    public function test_cash_flow_forecast_service_computes_projected_totals(): void
    {
        // 1. Receita prevista
        $inflowDate = Carbon::today()->addDays(5);
        $this->createContractAndInstallment(300.00, $inflowDate);

        // 2. Despesa agendada
        Payable::query()->create([
            'school_id' => $this->school->id,
            'description' => 'Conta de Luz',
            'amount' => 120.00,
            'due_date' => Carbon::today()->addDays(7)->toDateString(),
            'status' => 'pending',
        ]);

        $service = app(CashFlowForecastService::class);
        $forecast = $service->generate(Carbon::today(), Carbon::today()->addDays(15), $this->school->id);

        $this->assertEquals(300.00, $forecast['total_projected_inflow']);
        $this->assertEquals(120.00, $forecast['total_projected_outflow']);
        $this->assertEquals(180.00, $forecast['projected_net_result']);
        $this->assertNotEmpty($forecast['timeline']);
    }

    public function test_zero_trust_installment_policy_checks(): void
    {
        [, $installment] = $this->createContractAndInstallment(250.00);

        $operadorPerson = Person::query()->create(['name' => 'Operador Caixa']);
        $restrictedUser = User::query()->create([
            'person_id' => $operadorPerson->id,
            'email' => 'operador@aurosoft.test',
            'password' => bcrypt('password'),
            'panel_access' => true,
        ]);

        // Sem permissão: não pode cancelar nem cobrar WhatsApp
        $this->assertFalse($restrictedUser->can('cancel', $installment));
        $this->assertFalse($restrictedUser->can('chargeWhatsApp', $installment));

        // Conceder permissão Cancel:Installment
        Permission::findOrCreate('Cancel:Installment', 'web');
        $restrictedUser->givePermissionTo('Cancel:Installment');

        $this->assertTrue($restrictedUser->fresh()->can('cancel', $installment));
        $this->assertFalse($restrictedUser->fresh()->can('chargeWhatsApp', $installment));

        // Conceder permissão ChargeWhatsApp:Installment
        Permission::findOrCreate('ChargeWhatsApp:Installment', 'web');
        $restrictedUser->givePermissionTo('ChargeWhatsApp:Installment');

        $this->assertTrue($restrictedUser->fresh()->can('chargeWhatsApp', $installment));
    }

    public function test_collection_kanban_page_renders_successfully(): void
    {
        $this->createContractAndInstallment(200.00, today()->addDays(2));
        $this->actingAs($this->admin);

        \Livewire\Livewire::test(\App\Filament\Pages\CollectionKanban::class)
            ->assertSuccessful()
            ->assertSee('Régua e Fila de Cobrança');
    }

    public function test_cash_flow_forecast_page_renders_successfully(): void
    {
        $this->createContractAndInstallment(200.00, today()->addDays(2));
        $this->actingAs($this->admin);

        \Livewire\Livewire::test(\App\Filament\Pages\CashFlowForecast::class)
            ->assertSuccessful()
            ->assertSee('Projeção de Fluxo de Caixa');
    }

    public function test_financial_accounts_resource_page_renders_successfully(): void
    {
        $this->actingAs($this->admin);

        \Livewire\Livewire::test(\App\Filament\Resources\FinancialAccounts\Pages\ManageFinancialAccounts::class)
            ->assertSuccessful();
    }

    public function test_closing_cash_register_can_transfer_balance_to_financial_account(): void
    {
        $account = FinancialAccount::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Cofre Central da Escola',
            'type' => 'vault',
            'current_balance' => 0.00,
            'is_active' => true,
        ]);

        $cashRegister = \App\Models\CashRegister::query()->create([
            'school_id' => $this->school->id,
            'operator_id' => $this->admin->id,
            'name' => 'Caixa Recepção 1',
            'status' => 'open',
            'opening_balance' => 100.00,
            'current_balance' => 350.00,
            'opened_at' => now(),
        ]);

        $paymentMethod = \App\Models\PaymentMethod::firstOrCreate(
            ['name' => 'Dinheiro'],
            ['school_id' => $this->school->id, 'type' => 'cash', 'is_active' => true]
        );

        $this->actingAs($this->admin);

        \Livewire\Livewire::test(\App\Filament\Resources\CashRegisters\Pages\ListCashRegisters::class)
            ->callTableAction('fecharCaixa', $cashRegister, data: [
                'counted_cash' => 350.00,
                'destination_account_id' => $account->id,
                'closing_notes' => 'Transferência para o cofre no fechamento do dia.',
            ])
            ->assertHasNoTableActionErrors();

        $cashRegister->refresh();
        $account->refresh();

        $this->assertEquals('closed', $cashRegister->status);
        $this->assertEquals(0.00, (float) $cashRegister->current_balance);
        $this->assertEquals(350.00, (float) $cashRegister->counted_cash);
        $this->assertEquals(350.00, (float) $account->current_balance);

        $this->assertDatabaseHas('cash_transactions', [
            'cash_register_id' => $cashRegister->id,
            'destination_account_id' => $account->id,
            'type' => 'output',
            'amount' => 350.00,
        ]);
    }

    public function test_financial_account_transfer_between_accounts_and_generates_statement(): void
    {
        $vault = FinancialAccount::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Cofre Físico',
            'type' => 'vault',
            'initial_balance' => 1000.00,
            'current_balance' => 1000.00,
            'is_active' => true,
        ]);

        $bank = FinancialAccount::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Banco Cora PJ',
            'type' => 'bank',
            'initial_balance' => 200.00,
            'current_balance' => 200.00,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);

        $vault->transferTo($bank, 400.00, 'Depósito de sangrias da semana', $this->admin->id);

        $vault->refresh();
        $bank->refresh();

        $this->assertEquals(600.00, (float) $vault->current_balance);
        $this->assertEquals(600.00, (float) $bank->current_balance);

        $this->assertDatabaseHas('financial_account_transactions', [
            'financial_account_id' => $vault->id,
            'type' => 'transfer_out',
            'amount' => 400.00,
            'balance_after' => 600.00,
            'related_account_id' => $bank->id,
        ]);

        $this->assertDatabaseHas('financial_account_transactions', [
            'financial_account_id' => $bank->id,
            'type' => 'transfer_in',
            'amount' => 400.00,
            'balance_after' => 600.00,
            'related_account_id' => $vault->id,
        ]);
    }

    public function test_kanban_agreement_modal_formalizes_agreement_successfully(): void
    {
        [$contract, $installment] = $this->createContractAndInstallment(300.00, today()->subDays(45));

        $this->actingAs($this->admin);

        \Livewire\Livewire::test(\App\Filament\Pages\CollectionKanban::class)
            ->call('openAgreementModal', $contract->id)
            ->assertSet('showAgreementModal', true)
            ->assertSet('agreementContractId', $contract->id)
            ->set('agreementDiscount', 50.00)
            ->set('agreementDownPayment', 50.00)
            ->set('agreementDownPaymentPaid', false)
            ->set('agreementInstallmentsCount', 2)
            ->call('saveAgreement')
            ->assertSet('showAgreementModal', false);

        $installment->refresh();
        $this->assertEquals('canceled', $installment->status);

        $this->assertDatabaseHas('debt_agreements', [
            'contract_id' => $contract->id,
            'total_original_amount' => 300.00,
            'discount_amount' => 50.00,
            'total_agreed_amount' => 250.00,
            'down_payment_amount' => 50.00,
        ]);
    }
}
