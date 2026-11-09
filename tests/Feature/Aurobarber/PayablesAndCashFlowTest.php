<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\CashRegisterPage;
use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\CashMovement;
use App\Models\Payable;
use App\Models\Professional;
use App\Models\Service;
use App\Services\CashFlowForecastService;
use Carbon\Carbon;
use DomainException;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayablesAndCashFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_forecast_uses_barbershop_appointments_payables_and_manual_movements(): void
    {
        $barbershop = Barbershop::query()->create([
            'name' => 'Barbearia Principal',
            'slug' => 'barbearia-principal',
        ]);
        $otherBarbershop = Barbershop::query()->create([
            'name' => 'Outra Barbearia',
            'slug' => 'outra-barbearia',
        ]);

        $this->createAppointment($barbershop, 'completed', 'paid', Carbon::today()->subDays(2), 120, 10);
        $this->createAppointment($barbershop, 'scheduled', 'pending', Carbon::today()->addDays(5), 100, 25);
        $this->createAppointment($barbershop, 'cancelled', 'pending', Carbon::today()->addDays(6), 500);
        $this->createAppointment($otherBarbershop, 'scheduled', 'pending', Carbon::today()->addDays(5), 900);

        Payable::query()->create([
            'barbershop_id' => $barbershop->id,
            'description' => 'Aluguel pago',
            'amount' => 40,
            'due_date' => Carbon::today()->subDay(),
            'status' => 'paid',
        ]);
        Payable::query()->create([
            'barbershop_id' => $barbershop->id,
            'description' => 'Conta de energia',
            'amount' => 50,
            'due_date' => Carbon::today()->addDays(7),
            'status' => 'pending',
        ]);
        Payable::query()->create([
            'barbershop_id' => $barbershop->id,
            'description' => 'Conta cancelada',
            'amount' => 70,
            'due_date' => Carbon::today()->addDays(8),
            'status' => 'cancelled',
        ]);
        Payable::query()->create([
            'barbershop_id' => $otherBarbershop->id,
            'description' => 'Despesa de outra unidade',
            'amount' => 900,
            'due_date' => Carbon::today()->addDays(7),
            'status' => 'pending',
        ]);

        CashMovement::query()->create([
            'barbershop_id' => $barbershop->id,
            'type' => 'opening',
            'amount' => 200,
            'description' => 'Fundo inicial',
            'date' => Carbon::today()->subDays(10),
        ]);
        CashMovement::query()->create([
            'barbershop_id' => $barbershop->id,
            'type' => 'opening',
            'amount' => 100,
            'description' => 'Abertura posterior do caixa',
            'date' => Carbon::today()->subDays(5),
        ]);
        CashMovement::query()->create([
            'barbershop_id' => $barbershop->id,
            'type' => 'inflow',
            'amount' => 10,
            'description' => 'Suprimento',
            'date' => Carbon::today()->subDays(2),
        ]);
        CashMovement::query()->create([
            'barbershop_id' => $barbershop->id,
            'type' => 'outflow',
            'amount' => 20,
            'description' => 'Sangria',
            'date' => Carbon::today()->subDay(),
        ]);

        $forecast = app(CashFlowForecastService::class)->generate(
            Carbon::today(),
            Carbon::today()->addDays(15),
            $barbershop->id,
        );

        $this->assertSame(280.0, $forecast['current_liquid_balance']);
        $this->assertSame(125.0, $forecast['total_projected_inflow']);
        $this->assertSame(50.0, $forecast['total_projected_outflow']);
        $this->assertSame(75.0, $forecast['projected_net_result']);
        $this->assertSame(355.0, $forecast['projected_final_balance']);
        $this->assertSame(1, $forecast['total_appointments_count']);
        $this->assertSame(1, $forecast['total_payables_count']);
    }

    public function test_payable_can_be_paid_once_with_a_payment_date_and_method(): void
    {
        $barbershop = Barbershop::query()->create([
            'name' => 'Barbearia',
            'slug' => 'barbearia',
        ]);
        $payable = Payable::query()->create([
            'barbershop_id' => $barbershop->id,
            'description' => 'Internet',
            'amount' => 99.90,
            'due_date' => Carbon::today()->addDays(3),
        ]);

        $payable->markPaid(Carbon::today(), 'pix');

        $this->assertSame('paid', $payable->fresh()->status);
        $this->assertSame(Carbon::today()->toDateString(), $payable->fresh()->payment_date->toDateString());
        $this->assertSame('pix', $payable->fresh()->payment_method);

        $this->expectException(DomainException::class);
        $payable->markPaid(Carbon::today(), 'pix');
    }

    public function test_future_dated_payment_is_rejected(): void
    {
        $barbershop = Barbershop::query()->create([
            'name' => 'Barbearia',
            'slug' => 'barbearia',
        ]);
        $payable = Payable::query()->create([
            'barbershop_id' => $barbershop->id,
            'description' => 'Internet',
            'amount' => 99.90,
            'due_date' => Carbon::today()->addDays(3),
        ]);

        $this->expectException(DomainException::class);
        $payable->markPaid(Carbon::today()->addDay(), 'pix');
    }

    public function test_pending_payable_can_be_cancelled_but_a_paid_payable_cannot(): void
    {
        $barbershop = Barbershop::query()->create([
            'name' => 'Barbearia',
            'slug' => 'barbearia',
        ]);
        $payable = Payable::query()->create([
            'barbershop_id' => $barbershop->id,
            'description' => 'Assinatura cancelada',
            'amount' => 25,
            'due_date' => Carbon::today()->addDays(3),
        ]);

        $payable->cancel();

        $this->assertSame('cancelled', $payable->fresh()->status);

        $paidPayable = Payable::query()->create([
            'barbershop_id' => $barbershop->id,
            'description' => 'Assinatura paga',
            'amount' => 25,
            'due_date' => Carbon::today()->addDays(3),
        ]);
        $paidPayable->markPaid(Carbon::today());

        $this->expectException(DomainException::class);
        $paidPayable->cancel();
    }

    public function test_forecast_requires_a_barbershop_and_valid_date_order(): void
    {
        $service = app(CashFlowForecastService::class);

        try {
            $service->generate(Carbon::today(), Carbon::today()->addDay());
            $this->fail('A barbershop must be required.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('barbearia', $exception->getMessage());
        }

        $barbershop = Barbershop::query()->create([
            'name' => 'Barbearia',
            'slug' => 'barbearia',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $service->generate(Carbon::today()->addDay(), Carbon::today(), $barbershop->id);
    }

    public function test_cash_register_counts_only_paid_completed_appointments_including_products(): void
    {
        $barbershop = Barbershop::query()->create([
            'name' => 'Barbearia',
            'slug' => 'barbearia',
        ]);
        $paid = $this->createAppointment($barbershop, 'completed', 'paid', Carbon::today(), 40, 15);
        $this->createAppointment($barbershop, 'completed', 'pending', Carbon::today(), 70);

        Filament::setTenant($barbershop, isQuiet: true);

        $page = app(CashRegisterPage::class);
        $page->selectedDate = Carbon::today()->toDateString();
        $transactions = $page->getDailyTransactionsProperty();

        $this->assertCount(1, $transactions);
        $this->assertSame((int) $paid->id, (int) $transactions->first()->id);
        $this->assertSame(55.0, (float) $transactions->sum('total_amount'));
    }

    private function createAppointment(
        Barbershop $barbershop,
        string $status,
        string $paymentStatus,
        Carbon $scheduledAt,
        float $servicePrice,
        float $productsTotal = 0,
    ): Appointment {
        $professional = Professional::query()->create([
            'barbershop_id' => $barbershop->id,
            'name' => 'Barbeiro Teste',
        ]);
        $service = Service::query()->create([
            'barbershop_id' => $barbershop->id,
            'name' => 'Corte',
            'price' => $servicePrice,
        ]);

        return Appointment::query()->create([
            'barbershop_id' => $barbershop->id,
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'client_name' => 'Cliente Teste',
            'client_phone' => '0',
            'scheduled_at' => $scheduledAt,
            'ends_at' => $scheduledAt->copy()->addMinutes(30),
            'status' => $status,
            'service_price' => $servicePrice,
            'products_total' => $productsTotal,
            'commission_rate' => 50,
            'commission_amount' => $servicePrice / 2,
            'payment_status' => $paymentStatus,
        ]);
    }
}
