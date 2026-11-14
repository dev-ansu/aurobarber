<?php

use App\Models\Barbershop;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('sanitizes schema names correctly', function () {
    $service = new TenantService();

    expect($service->sanitizeSchemaName('Barbearia Marinho'))
        ->toBe('barbearia_marinho')
        ->and($service->sanitizeSchemaName('123_corte'))
        ->toBe('tenant_123_corte')
        ->and($service->sanitizeSchemaName('  @BARBEARIA-VIP!  '))
        ->toBe('barbearia_vip');
});

it('resolves tenant barbershop from subdomain host', function () {
    $service = new TenantService();

    $shop = Barbershop::create([
        'name' => 'Barbearia Alvorada',
        'slug' => 'alvorada',
        'schema_name' => 'tenant_alvorada',
    ]);

    expect($service->resolveTenantFromHost('alvorada.localhost:8085'))
        ->not->toBeNull()
        ->id->toBe($shop->id)
        ->and($service->resolveTenantFromHost('admin.localhost'))
        ->toBeNull();
});

it('resolves tenant barbershop from custom domain host', function () {
    $service = new TenantService();

    $shop = Barbershop::create([
        'name' => 'Navalha de Ouro',
        'slug' => 'navalha',
        'custom_domain' => 'navalha.com.br',
        'schema_name' => 'tenant_navalha',
        'is_active' => true,
    ]);

    expect($service->resolveTenantFromHost('navalha.com.br:443'))
        ->not->toBeNull()
        ->id->toBe($shop->id);
});
