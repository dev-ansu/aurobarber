<?php

declare(strict_types=1);

namespace Tests\Feature\Aurobarber;

use App\Models\Barbershop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasLicenseWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected string $secret = 'test-saas-secret-key';

    protected function setUp(): void
    {
        parent::setUp();
        config(['saas.webhook_secret' => $this->secret]);
    }

    public function test_rejects_request_without_valid_secret(): void
    {
        $response = $this->postJson('/api/v1/saas/license-webhook', [
            'action' => 'telemetry',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_can_provision_new_barbershop_via_webhook(): void
    {
        $response = $this->withHeader('X-Auro-Secret', $this->secret)
            ->postJson('/api/v1/saas/license-webhook', [
                'action' => 'provision',
                'name' => 'Barbearia Vintage Club',
                'slug' => 'vintage-club',
                'owner_name' => 'Carlos Barbeiro',
                'owner_email' => 'carlos@vintageclub.com',
                'phone' => '11999998888',
                'license_status' => 'active',
                'license_expires_at' => now()->addDays(30)->toDateString(),
                'monthly_price' => 149.90,
                'grace_period_days' => 5,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Barbearia Vintage Club',
                    'slug' => 'vintage-club',
                    'schema_name' => 'tenant_vintage_club',
                ],
            ]);

        $this->assertDatabaseHas('barbershops', [
            'slug' => 'vintage-club',
            'schema_name' => 'tenant_vintage_club',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'carlos@vintageclub.com',
        ]);
    }

    public function test_can_sync_license_status_and_expiration(): void
    {
        $barbershop = Barbershop::create([
            'name' => 'Barbearia Alfa',
            'slug' => 'alfa',
            'license_status' => 'active',
            'license_expires_at' => now()->addDays(5),
            'is_active' => true,
        ]);

        $newExpiration = now()->addDays(35)->startOfDay();

        $response = $this->withHeader('X-Auro-Secret', $this->secret)
            ->postJson('/api/v1/saas/license-webhook', [
                'action' => 'sync_license',
                'slug' => 'alfa',
                'license_status' => 'active',
                'license_expires_at' => $newExpiration->toIso8601String(),
                'grace_period_days' => 7,
                'monthly_price' => 199.00,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'slug' => 'alfa',
                    'license_status' => 'active',
                    'is_active' => true,
                ],
            ]);

        $barbershop->refresh();
        $this->assertEquals(7, $barbershop->grace_period_days);
        $this->assertEquals(199.00, (float) $barbershop->monthly_price);
    }

    public function test_can_suspend_and_activate_barbershop(): void
    {
        $barbershop = Barbershop::create([
            'name' => 'Barbearia Beta',
            'slug' => 'beta',
            'license_status' => 'active',
            'license_expires_at' => now()->addDays(10),
            'is_active' => true,
        ]);

        // 1. Suspender
        $suspendResponse = $this->withHeader('X-Auro-Secret', $this->secret)
            ->postJson('/api/v1/saas/license-webhook', [
                'action' => 'suspend',
                'slug' => 'beta',
                'reason' => 'Fatura em atraso há mais de 10 dias',
            ]);

        $suspendResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'slug' => 'beta',
                    'license_status' => 'suspended',
                    'is_active' => false,
                ],
            ]);

        $barbershop->refresh();
        $this->assertFalse((bool) $barbershop->is_active);
        $this->assertEquals('suspended', $barbershop->license_status);

        // 2. Reativar
        $activateResponse = $this->withHeader('X-Auro-Secret', $this->secret)
            ->postJson('/api/v1/saas/license-webhook', [
                'action' => 'activate',
                'slug' => 'beta',
                'days' => 30,
            ]);

        $activateResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'slug' => 'beta',
                    'license_status' => 'active',
                    'is_active' => true,
                ],
            ]);

        $barbershop->refresh();
        $this->assertTrue((bool) $barbershop->is_active);
        $this->assertEquals('active', $barbershop->license_status);
    }

    public function test_can_retrieve_telemetry(): void
    {
        Barbershop::create([
            'name' => 'Barbearia Gama',
            'slug' => 'gama',
            'license_status' => 'active',
            'license_expires_at' => now()->addDays(20),
            'is_active' => true,
        ]);

        $response = $this->withHeader('X-Auro-Secret', $this->secret)
            ->postJson('/api/v1/saas/license-webhook', [
                'action' => 'telemetry',
                'slug' => 'gama',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'slug' => 'gama',
                    'license_status' => 'active',
                ],
            ]);
    }

    public function test_can_clear_landing_cache_via_webhook(): void
    {
        \Illuminate\Support\Facades\Cache::put('aurobarber_landing_cms', ['dummy' => true]);
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has('aurobarber_landing_cms'));

        $response = $this->withHeader('X-Auro-Secret', $this->secret)
            ->postJson('/api/v1/saas/license-webhook', [
                'action' => 'clear_landing_cache',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertFalse(\Illuminate\Support\Facades\Cache::has('aurobarber_landing_cms'));
    }
}
