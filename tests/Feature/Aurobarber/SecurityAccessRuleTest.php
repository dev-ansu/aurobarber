<?php

declare(strict_types=1);

namespace Tests\Feature\Aurobarber;

use App\Models\SecurityAccessRule;
use App\Models\User;
use App\Services\Security\SecurityAccessRuleService;
use App\Services\SecurityEventLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAccessRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_blacklist_blocks_matching_ip(): void
    {
        $service = app(SecurityAccessRuleService::class);
        $service->blockIp('203.0.113.50', 'Tentativa de scanner malicioso');

        $match = $service->findBlacklistMatch('203.0.113.50');
        $this->assertNotNull($match);
        $this->assertEquals('blacklist', $match->type);
        $this->assertEquals('203.0.113.50', $match->ip_address);
        $this->assertEquals(1, $match->fresh()->match_count);

        // IP não bloqueado retorna null
        $this->assertNull($service->findBlacklistMatch('198.51.100.1'));
    }

    public function test_whitelist_allows_matching_ip(): void
    {
        SecurityAccessRule::query()->create([
            'type' => 'whitelist',
            'ip_address' => '198.51.100.99',
            'label' => 'IP Seguro do Admin',
            'is_active' => true,
        ]);

        $service = app(SecurityAccessRuleService::class);
        $this->assertTrue($service->isWhitelisted(null, '198.51.100.99'));
        $this->assertFalse($service->isWhitelisted(null, '198.51.100.100'));
    }

    public function test_security_event_logger_records_event(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin_test@aurobarber.com',
        ]);
        $logger = app(SecurityEventLogger::class);

        $logger->record(
            'auth.test_event',
            actor: $user,
            subject: 'admin_test@aurobarber.com',
            severity: 'warning',
            metadata: ['test_key' => 'test_value'],
        );

        $latest = $logger->latest(1)->first();
        $this->assertNotNull($latest);
        $this->assertEquals('auth.test_event', $latest->event_type);
        $this->assertEquals('warning', $latest->severity);
        $this->assertEquals($user->id, $latest->user_id);
    }
}
