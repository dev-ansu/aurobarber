<?php

use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    cache()->forget('aurosoft_license_status');
});

it('allows access when license check is disabled', function () {
    config(['license.enabled' => false]);

    $service = app(LicenseService::class);
    $result = $service->checkLicense();

    expect($result['active'])->toBeTrue()
        ->and($result['status'])->toBe('disabled');

    $this->get('/')->assertSuccessful();
});

it('blocks access and redirects to /licenca when remote license is blocked', function () {
    config([
        'license.enabled' => true,
        'license.key' => 'TESTE-ESCOLA-123',
        'license.webhook_url' => 'https://mock-google-sheets.com/exec',
    ]);

    Http::fake([
        'https://mock-google-sheets.com/exec*' => Http::response([
            'ativo' => false,
            'escola' => 'Escola Teste Bloqueada',
            'mensagem' => 'Mensalidade de Outubro vencida.',
            'suporte' => '5511999999999',
        ], 200),
    ]);

    $service = app(LicenseService::class);
    $result = $service->checkLicense(true);

    expect($result['active'])->toBeFalse()
        ->and($result['status'])->toBe('blocked')
        ->and($result['message'])->toBe('Mensalidade de Outubro vencida.');

    $response = $this->get('/');
    $response->assertRedirect(route('license.blocked'));
});

it('allows access when remote license returns active', function () {
    config([
        'license.enabled' => true,
        'license.key' => 'TESTE-ESCOLA-123',
        'license.webhook_url' => 'https://mock-google-sheets.com/exec',
    ]);

    Http::fake([
        'https://mock-google-sheets.com/exec*' => Http::response([
            'ativo' => true,
            'escola' => 'Escola Modelo Ativa',
            'mensagem' => 'Licença regular.',
            'vencimento' => '2026-12-31',
        ], 200),
    ]);

    $service = app(LicenseService::class);
    $result = $service->checkLicense(true);

    expect($result['active'])->toBeTrue()
        ->and($result['status'])->toBe('active')
        ->and($result['school_name'])->toBe('Escola Modelo Ativa');

    $this->get('/')->assertSuccessful();
});

it('allows offline grace period when internet fails but license was verified recently', function () {
    config([
        'license.enabled' => true,
        'license.key' => 'TESTE-ESCOLA-123',
        'license.webhook_url' => 'https://mock-google-sheets.com/exec',
        'license.grace_days' => 5,
    ]);

    // Simula estado salvo há 2 dias atrás
    Storage::disk('local')->put('license_state.json', json_encode([
        'active' => true,
        'status' => 'active',
        'school_name' => 'Escola Offline Grace',
        'last_checked_at' => now()->subDays(2)->toIso8601String(),
    ]));

    // Simula falha total de conexão (sem internet)
    Http::fake([
        'https://mock-google-sheets.com/exec*' => Http::failedConnection(),
    ]);

    $service = app(LicenseService::class);
    $result = $service->checkLicense(true);

    expect($result['active'])->toBeTrue()
        ->and($result['status'])->toBe('offline_grace')
        ->and($result['offline'])->toBeTrue();
});

it('renders the blocked license view correctly', function () {
    config([
        'license.enabled' => true,
        'license.key' => 'TESTE-ESCOLA-123',
        'license.webhook_url' => 'https://mock-google-sheets.com/exec',
    ]);

    Http::fake([
        'https://mock-google-sheets.com/exec*' => Http::response([
            'ativo' => false,
            'escola' => 'Escola Bloqueada Exemplo',
            'mensagem' => 'Mensalidade em aberto, contate o financeiro.',
            'suporte' => '5511999998888',
        ], 200),
    ]);

    $this->get('/licenca')
        ->assertSuccessful()
        ->assertSee('Acesso Temporariamente Suspenso')
        ->assertSee('Escola Bloqueada Exemplo')
        ->assertSee('Mensalidade em aberto, contate o financeiro.')
        ->assertSee('Verificar Pagamento Agora')
        ->assertSee('Falar com o Suporte no WhatsApp');
});

it('redirects /admin to /licenca when license is blocked', function () {
    config([
        'license.enabled' => true,
        'license.key' => 'TESTE-ESCOLA-123',
        'license.webhook_url' => 'https://mock-google-sheets.com/exec',
    ]);

    Http::fake([
        'https://mock-google-sheets.com/exec*' => Http::response([
            'ativo' => false,
            'mensagem' => 'Mensalidade vencida.',
        ], 200),
    ]);

    $person = \App\Models\Person::create(['name' => 'Admin Teste', 'email' => 'admin@teste.com']);
    $user = \App\Models\User::create(['person_id' => $person->id, 'email' => 'admin@teste.com', 'password' => 'secret']);

    $this->actingAs($user)->get('/admin')->assertRedirect(route('license.blocked'));
});

it('blocks access and returns unlicensed when key is empty or missing', function () {
    config([
        'license.enabled' => true,
        'license.key' => '',
        'license.webhook_url' => 'https://mock-google-sheets.com/exec',
    ]);

    $service = app(LicenseService::class);
    $result = $service->checkLicense(true);

    expect($result['active'])->toBeFalse()
        ->and($result['status'])->toBe('unlicensed');

    $this->get('/')->assertRedirect(route('license.blocked'));
});

it('blocks access automatically when due date has passed even if status was active in spreadsheet', function () {
    config([
        'license.enabled' => true,
        'license.key' => 'TESTE-ESCOLA-123',
        'license.webhook_url' => 'https://mock-google-sheets.com/exec',
    ]);

    Http::fake([
        'https://mock-google-sheets.com/exec*' => Http::response([
            'ativo' => true,
            'escola' => 'Escola Inadimplente Automática',
            'status' => 'ativo',
            'vencimento' => now()->subDay()->format('Y-m-d'),
            'mensagem' => 'Licença regular.',
        ], 200),
    ]);

    $service = app(LicenseService::class);
    $result = $service->checkLicense(true);

    expect($result['active'])->toBeFalse()
        ->and($result['status'])->toBe('expired')
        ->and($result['message'])->toContain('venceu');

    $this->get('/')->assertRedirect(route('license.blocked'));
});

it('blocks access when remote sheet reports unlicensed', function () {
    config([
        'license.enabled' => true,
        'license.key' => 'CHAVE-INEXISTENTE',
        'license.webhook_url' => 'https://mock-google-sheets.com/exec',
    ]);

    Http::fake([
        'https://mock-google-sheets.com/exec*' => Http::response([
            'ativo' => false,
            'status' => 'unlicensed',
            'mensagem' => 'Chave de licença não localizada.',
        ], 200),
    ]);

    $service = app(LicenseService::class);
    $result = $service->checkLicense(true);

    expect($result['active'])->toBeFalse()
        ->and($result['status'])->toBe('unlicensed');

    $this->get('/')->assertRedirect(route('license.blocked'));
});

