<?php

namespace Tests\Feature;

use App\Models\License;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LicenseVerificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_license_returns_active_response(): void
    {
        $license = License::create([
            'client_name' => 'Colégio Exemplo',
            'trade_name' => 'Exemplo',
            'cnpj' => '12.345.678/0001-90',
            'license_key' => 'AURO-TEST-KEY-001',
            'type' => 'cloud_saas',
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->addMonth(),
            'grace_period_days' => 5,
        ]);

        $response = $this->postJson('/api/v1/license/verify', [
            'license_key' => 'AURO-TEST-KEY-001',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'active' => true,
                'status' => 'active',
                'client_name' => 'Colégio Exemplo',
            ]);
    }

    public function test_suspended_license_returns_403(): void
    {
        $license = License::create([
            'client_name' => 'Escola Inadimplente',
            'license_key' => 'AURO-TEST-SUSPENDED',
            'type' => 'cloud_saas',
            'status' => 'suspended',
            'expires_at' => now()->addMonth(),
        ]);

        $response = $this->postJson('/api/v1/license/verify', [
            'license_key' => 'AURO-TEST-SUSPENDED',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'valid' => false,
                'active' => false,
                'status' => 'suspended',
            ]);
    }

    public function test_get_request_supports_backward_compatibility(): void
    {
        $license = License::create([
            'client_name' => 'Escola Legacy',
            'trade_name' => 'Legacy School',
            'license_key' => 'KEY-LEGACY-123',
            'type' => 'on_premise',
            'status' => 'active',
            'expires_at' => now()->addDays(20),
        ]);

        $response = $this->getJson('/api/v1/license/verify?key=KEY-LEGACY-123');

        $response->assertStatus(200)
            ->assertJson([
                'valid' => true,
                'ativo' => true,
                'escola' => 'Legacy School',
            ]);
    }

    public function test_telemetry_is_stored_on_heartbeat(): void
    {
        $license = License::create([
            'client_name' => 'Escola Telemetria',
            'license_key' => 'KEY-TELEMETRY-123',
            'type' => 'on_premise',
            'status' => 'active',
            'expires_at' => now()->addDays(20),
        ]);

        $telemetryData = [
            'hostname' => 'servidorescola',
            'os' => 'Windows Server 2022',
            'php_version' => '8.3.12',
            'disk_free_gb' => 45.2,
            'git_commit' => 'ec8cb9a',
        ];

        $response = $this->postJson('/api/v1/license/verify', [
            'license_key' => 'KEY-TELEMETRY-123',
            'telemetry' => $telemetryData,
        ]);

        $response->assertStatus(200);

        $license->refresh();
        $this->assertNotNull($license->telemetry);
        $this->assertSame('servidorescola', $license->telemetry['hostname']);
        $this->assertSame('ec8cb9a', $license->telemetry['git_commit']);
    }
}

