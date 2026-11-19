<?php

use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Professional;
use App\Models\Service;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->barbershop = Barbershop::create([
        'name' => 'Barbearia Blindada',
        'slug' => 'barbearia-blindada',
        'is_active' => true,
        'opening_hours' => [
            'days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
            'start_time' => '08:00',
            'end_time' => '20:00',
            'slot_interval' => 30,
        ],
    ]);

    $this->professional = Professional::create([
        'barbershop_id' => $this->barbershop->id,
        'name' => 'Marcos Barbeiro',
        'is_active' => true,
        'commission_rate' => 50,
        'working_days' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'],
        'work_start_time' => '08:00',
        'work_end_time' => '20:00',
    ]);

    $this->service = Service::create([
        'barbershop_id' => $this->barbershop->id,
        'name' => 'Corte Moderno',
        'price' => 50.00,
        'duration_minutes' => 30,
        'is_active' => true,
    ]);

    $tenantService = new class extends TenantService
    {
        public function switchToTenant(string $name): void {}

        public function switchToPublic(): void {}
    };

    app()->instance(TenantService::class, $tenantService);
    app()->forgetInstance('current_tenant_barbershop');
});

test('honeypot traps bots in booking and does not persist appointment', function () {
    $initialCount = Appointment::count();

    $response = $this->postJson('/barbearia-blindada/book', [
        'service_ids' => [$this->service->id],
        'professional_id' => (string) $this->professional->id,
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => '14:00',
        'client_name' => 'Spam Bot',
        'client_phone' => '11999998888',
        'website_url' => 'https://spam-bot.online', // Honeypot preenchido pelo bot!
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    // O banco de dados não deve conter nenhum agendamento gravado
    expect(Appointment::count())->toBe($initialCount);
});

test('turnstile blocks booking when cloudflare secret is set and token is invalid', function () {
    config()->set('services.turnstile.site_key', '1x00000000000000000000AA');
    config()->set('services.turnstile.secret_key', '1x0000000000000000000000000000000AA');

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => false,
        ], 200),
    ]);

    $response = $this->postJson('/barbearia-blindada/book', [
        'service_ids' => [$this->service->id],
        'professional_id' => (string) $this->professional->id,
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => '15:00',
        'client_name' => 'Cliente Teste',
        'client_phone' => '85999887766',
        'turnstile_token' => 'invalid-token',
    ]);

    // dump($response->status(), $response->getContent());
    expect($response->status())->toBe(422);
    expect($response->json('errors.turnstile_token'))->not->toBeEmpty();
});

test('turnstile approves booking when token is validated by cloudflare', function () {
    config()->set('services.turnstile.site_key', '1x00000000000000000000AA');
    config()->set('services.turnstile.secret_key', '1x0000000000000000000000000000000AA');

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
            'action' => 'booking',
        ], 200),
    ]);

    $response = $this->postJson('/barbearia-blindada/book', [
        'service_ids' => [$this->service->id],
        'professional_id' => (string) $this->professional->id,
        'date' => now()->addDay()->format('Y-m-d'),
        'time' => '16:00',
        'client_name' => 'Cliente Legítimo',
        'client_phone' => '85999887766',
        'turnstile_token' => 'legitimate-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect(Appointment::where('client_name', 'Cliente Legítimo')->exists())->toBeTrue();
});

test('rating route rejects repeated rating if appointment already rated', function () {
    $appointment = Appointment::create([
        'barbershop_id' => $this->barbershop->id,
        'professional_id' => $this->professional->id,
        'service_id' => $this->service->id,
        'service_price' => 50,
        'commission_rate' => 50,
        'commission_amount' => 25,
        'scheduled_at' => now()->subHours(2),
        'ends_at' => now()->subHours(1),
        'client_name' => 'Cliente Avaliador',
        'client_phone' => '85988776655',
        'status' => 'completed',
        'rating' => 5,
        'rated_at' => now()->subHour(),
    ]);

    $response = $this->post("/avaliar/{$appointment->id}", [
        'rating' => 1,
        'rating_comment' => 'Tentativa de sobrescrever nota',
    ]);

    $response->assertSessionHas('already_rated', true);

    // O rating original de 5 estrelas deve permanecer intacto
    expect($appointment->fresh()->rating)->toBe(5);
});
