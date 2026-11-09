<?php

use App\Models\Barbershop;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin manifest is served with AuroBarber branding and installable settings', function () {
    $response = $this->getJson('/manifest.json');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/manifest+json');

    expect($response->json('name'))->toBe('AuroBarber • Gestão')
        ->and($response->json('start_url'))->toBe('/admin')
        ->and($response->json('display'))->toBe('standalone')
        ->and($response->json('icons'))->not->toBeEmpty();
});

test('barbershop manifest keeps its own brand and start URL', function () {
    $barbershop = Barbershop::create([
        'name' => 'Barbearia Teste',
        'slug' => 'barbearia-teste',
        'brand_color' => '#123456',
    ]);

    $response = $this->getJson('/barbearia-teste/manifest.json');

    $response->assertOk();

    expect($response->json('name'))->toBe('Barbearia Teste • Agendamento Online')
        ->and($response->json('start_url'))->toBe('/barbearia-teste')
        ->and($response->json('scope'))->toBe('/barbearia-teste')
        ->and($response->json('theme_color'))->toBe('#123456')
        ->and($response->json('icons'))->not->toBeEmpty();
});

test('service worker and AuroBarber icons are available', function () {
    $serviceWorker = file_get_contents(public_path('sw.js'));

    expect($serviceWorker)->toContain("addEventListener('install'")
        ->and($serviceWorker)->toContain("addEventListener('fetch'")
        ->and(file_exists(public_path('pwa-icon.svg')))->toBeTrue()
        ->and(file_exists(public_path('apple-touch-icon.png')))->toBeTrue();
});
