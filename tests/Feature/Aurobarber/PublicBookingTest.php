<?php

use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Professional;
use App\Models\Service;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public booking switches to tenant schema when configured and saves appointment with services', function () {
    $barbershop = Barbershop::create([
        'name' => 'Barbearia Central',
        'slug' => 'barbearia-central',
        'schema_name' => 'tenant_barbearia_central',
        'is_active' => true,
        'opening_hours' => [
            'days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'start_time' => '08:00',
            'end_time' => '20:00',
            'slot_interval' => 30,
        ],
    ]);

    $professional = Professional::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Carlos Barbeiro',
        'is_active' => true,
        'commission_rate' => 50,
        'working_days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
        'work_start_time' => '08:00',
        'work_end_time' => '20:00',
    ]);

    $cut = Service::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Corte Degradê',
        'price' => 45.00,
        'duration_minutes' => 30,
        'is_active' => true,
    ]);

    $beard = Service::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Barba Completa',
        'price' => 35.00,
        'duration_minutes' => 30,
        'is_active' => true,
    ]);

    $switchedSchemas = [];
    $tenantService = new class($switchedSchemas) extends TenantService
    {
        public function __construct(public array &$log) {}

        public function switchToTenant(string $name): void
        {
            $this->log[] = $name;
        }

        public function switchToPublic(): void
        {
            // noop in sqlite test
        }
    };

    app()->instance(TenantService::class, $tenantService);
    app()->forgetInstance('current_tenant_barbershop');

    $bookingDate = now()->addDay()->format('Y-m-d');

    $response = $this->postJson('/barbearia-central/book', [
        'service_ids' => [$cut->id, $beard->id],
        'professional_id' => (string) $professional->id,
        'date' => $bookingDate,
        'time' => '10:00',
        'client_name' => 'João Cliente',
        'client_phone' => '85999887766',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('appointment.services_count', 2);

    expect($switchedSchemas)->toContain('tenant_barbearia_central');

    $appointment = Appointment::query()
        ->with('services')
        ->where('barbershop_id', $barbershop->id)
        ->where('client_name', 'João Cliente')
        ->sole();

    expect((float) $appointment->service_price)->toBe(80.0)
        ->and($appointment->ends_at->format('H:i'))->toBe('11:00')
        ->and($appointment->services)->toHaveCount(2);
});
