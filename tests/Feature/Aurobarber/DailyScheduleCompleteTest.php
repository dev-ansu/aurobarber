<?php

use App\Filament\Pages\DailySchedule;
use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\CashMovement;
use App\Models\Product;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('daily schedule complete appointment action supports split payments without repeater', function () {
    $tenant = Barbershop::create([
        'name' => 'Barbearia Split',
        'slug' => 'barbearia-split',
        'is_active' => true,
    ]);

    $owner = User::create([
        'name' => 'Dono Barbearia',
        'email' => 'dono-split@example.test',
        'password' => 'password',
        'role' => 'owner',
        'barbershop_id' => $tenant->id,
    ]);

    $professional = Professional::create([
        'barbershop_id' => $tenant->id,
        'name' => 'Barbeiro 1',
        'commission_rate' => 50,
        'is_active' => true,
    ]);

    $service = Service::create([
        'barbershop_id' => $tenant->id,
        'name' => 'Corte Degradê',
        'price' => 50,
        'duration_minutes' => 30,
        'is_active' => true,
    ]);

    $product = Product::create([
        'barbershop_id' => $tenant->id,
        'name' => 'Pomada Modeladora',
        'price' => 30,
        'stock_quantity' => 5,
        'is_active' => true,
    ]);

    $appointment = Appointment::create([
        'barbershop_id' => $tenant->id,
        'professional_id' => $professional->id,
        'service_id' => $service->id,
        'client_name' => 'Cliente Split',
        'client_phone' => '11999999999',
        'scheduled_at' => now()->startOfDay()->addHours(10),
        'ends_at' => now()->startOfDay()->addHours(10)->addMinutes(30),
        'status' => 'in_progress',
        'payment_status' => 'pending',
        'service_price' => 50,
        'commission_rate' => 50,
        'commission_amount' => 25,
        'services_summary' => 'Corte Degradê',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant, isQuiet: true);
    $this->actingAs($owner);

    Livewire::test(DailySchedule::class)
        ->mountAction('completeAppointment', ['appointmentId' => $appointment->id])
        ->setActionData([
            'appointment_id' => $appointment->id,
            'service_price' => 50,
            'payment_method' => 'pix',
            'additional_dinheiro_amount' => 30, // Total = 50 + 30 = 80. PIX = 50, Dinheiro = 30
            'additional_pix_amount' => 0,
            'additional_debito_amount' => 0,
            'additional_credito_amount' => 0,
            'consumed_product_ids' => [$product->id],
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    $appointment->refresh();
    expect($appointment->status)->toBe('completed')
        ->and($appointment->payment_status)->toBe('paid')
        ->and((float) $appointment->service_price)->toBe(50.0)
        ->and((float) $appointment->products_total)->toBe(30.0)
        ->and($product->fresh()->stock_quantity)->toBe(4)
        ->and($appointment->payment_details)->toBeArray()
        ->and(count($appointment->payment_details))->toBe(2)
        ->and(CashMovement::count())->toBe(1)
        ->and((float) CashMovement::first()->amount)->toBe(80.0);
});
