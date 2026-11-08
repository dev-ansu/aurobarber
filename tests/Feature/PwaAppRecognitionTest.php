<?php

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('pwa webmanifest file exists and has valid configuration', function () {
    $manifestPath = public_path('manifest.webmanifest');
    expect(file_exists($manifestPath))->toBeTrue();

    $content = json_decode(file_get_contents($manifestPath), true);
    expect($content)->toBeArray()
        ->and($content['name'])->toBe('Aurosoft - Gestão Escolar')
        ->and($content['display'])->toBe('standalone')
        ->and($content['start_url'])->toBe('/admin')
        ->and($content['icons'])->not->toBeEmpty();
});

test('service worker file exists and contains essential pwa listeners', function () {
    $swPath = public_path('sw.js');
    expect(file_exists($swPath))->toBeTrue();

    $content = file_get_contents($swPath);
    expect($content)->toContain('addEventListener(\'install\'')
        ->and($content)->toContain('addEventListener(\'fetch\'');
});

test('favicon files exist and have content', function () {
    $icoPath = public_path('favicon.ico');
    $svgPath = public_path('favicon.svg');
    expect(file_exists($icoPath))->toBeTrue()
        ->and(filesize($icoPath))->toBeGreaterThan(500)
        ->and(file_exists($svgPath))->toBeTrue()
        ->and(file_get_contents($svgPath))->toContain('<svg');
});

test('admin panel renders pwa manifest link, favicon and meta tags', function () {
    $adminPerson = Person::create([
        'name' => 'Admin PWA',
        'email' => 'adminpwa@teste.com',
    ]);

    $adminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    $user = User::create([
        'person_id' => $adminPerson->id,
        'email' => 'adminpwa@teste.com',
        'password' => 'secret',
        'panel_access' => true,
    ]);
    $user->assignRole($adminRole);

    $this->actingAs($user);

    $response = $this->followingRedirects()->get('/admin');
    $response->assertOk();
    $response->assertSee('rel="manifest"', false);
    $response->assertSee('favicon', false);
    $response->assertSee('mobile-web-app-capable', false);
    $response->assertSee('sw.js', false);
});
