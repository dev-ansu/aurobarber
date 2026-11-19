<?php

use App\Models\LandingLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('terms of service page loads successfully and redirects alias', function () {
    $response = $this->get('/termos');
    $response->assertOk()
        ->assertSee('Termos e Condições de Uso')
        ->assertSee('AuroBarber')
        ->assertSee('SLA');

    $alias = $this->get('/termos-de-uso');
    $alias->assertRedirect('/termos');
});

test('privacy policy page loads successfully and redirects alias', function () {
    $response = $this->get('/privacidade');
    $response->assertOk()
        ->assertSee('Política de Privacidade')
        ->assertSee('LGPD')
        ->assertSee('privacidade@aurosoft.com.br');

    $alias = $this->get('/politica-de-privacidade');
    $alias->assertRedirect('/privacidade');
});

test('landing lead form traps bots using honeypot without saving to database', function () {
    $initialCount = LandingLead::count();

    $response = $this->postJson('/landing/lead', [
        'name' => 'Spam Bot',
        'barbershop_name' => 'Fake Barbershop',
        'phone' => '85999990000',
        'email' => 'bot@spammer.org',
        'website_url' => 'https://spam-bot.online', // Honeypot preenchido!
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect(LandingLead::count())->toBe($initialCount);
});

test('landing lead form blocks submission when turnstile is configured and token is invalid', function () {
    config()->set('services.turnstile.site_key', '1x00000000000000000000AA');
    config()->set('services.turnstile.secret_key', '1x0000000000000000000000000000000AA');

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => false,
        ], 200),
    ]);

    $response = $this->postJson('/landing/lead', [
        'name' => 'Carlos Eduardo',
        'barbershop_name' => 'Barbearia Real',
        'phone' => '85999887766',
        'email' => 'carlos@barbearia.com.br',
        'turnstile_token' => 'invalid-token',
    ]);

    expect($response->status())->toBe(422);
    expect($response->json('errors.turnstile_token'))->not->toBeEmpty();
});

test('landing lead form accepts submission when turnstile passes', function () {
    config()->set('services.turnstile.site_key', '1x00000000000000000000AA');
    config()->set('services.turnstile.secret_key', '1x0000000000000000000000000000000AA');

    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
            'action' => 'landing_lead',
        ], 200),
    ]);

    $response = $this->postJson('/landing/lead', [
        'name' => 'Carlos Barbeiro',
        'barbershop_name' => 'Barbearia Alfa',
        'phone' => '85999887766',
        'email' => 'carlos@alfa.com.br',
        'turnstile_token' => 'valid-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect(LandingLead::where('email', 'carlos@alfa.com.br')->exists())->toBeTrue();
});
