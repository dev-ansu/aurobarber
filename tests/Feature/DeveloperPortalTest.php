<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\LicenseService;
use App\Services\SystemDiagnosticService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

it('creates and updates developer users via artisan command', function () {
    $this->artisan('aurosoft:developer', [
        '--email' => 'dev@aurosoft.internal',
        '--password' => 'SuperDev1234!',
    ])->assertSuccessful();

    $user = User::where('email', 'dev@aurosoft.internal')->first();

    expect($user)->not->toBeNull()
        ->and($user->is_developer)->toBeTrue()
        ->and($user->panel_access)->toBeTrue();

    // Testa listagem
    $this->artisan('aurosoft:developer', ['--list' => true])
        ->assertSuccessful();
});

it('strictly hides developer user from scopeWithoutTechnicalAdmin', function () {
    $personDev = \App\Models\Person::create(['name' => 'Desenvolvedor Oculto', 'email' => 'oculto@aurosoft.internal']);
    $dev = User::factory()->create([
        'person_id' => $personDev->id,
        'email' => 'oculto@aurosoft.internal',
        'is_developer' => true,
    ]);

    $personReg = \App\Models\Person::create(['name' => 'Professor Silva', 'email' => 'professor@escola.com']);
    $regularUser = User::factory()->create([
        'person_id' => $personReg->id,
        'email' => 'professor@escola.com',
        'is_developer' => false,
    ]);

    $visibleUsers = User::withoutTechnicalAdmin()->pluck('id');

    expect($visibleUsers)->toContain($regularUser->id)
        ->and($visibleUsers)->not->toContain($dev->id);
});

it('enforces panel access boundary: developer panel allowed only for developers', function () {
    $personDev = \App\Models\Person::create(['name' => 'Dev Teste', 'email' => 'dev@teste.com']);
    $dev = User::factory()->create([
        'person_id' => $personDev->id,
        'email' => 'dev@teste.com',
        'is_developer' => true,
    ]);

    $personReg = \App\Models\Person::create(['name' => 'Admin Escola', 'email' => 'admin@escola.com']);
    $regularUser = User::factory()->create([
        'person_id' => $personReg->id,
        'email' => 'admin@escola.com',
        'is_developer' => false,
    ]);

    $devPanel = Filament::getPanel('developer');
    $adminPanel = Filament::getPanel('admin');
    $studioPanel = Filament::getPanel('studio');
    $kioskPanel = Filament::getPanel('kiosk');

    // Desenvolvedor acessa apenas developer panel
    expect($dev->canAccessPanel($devPanel))->toBeTrue()
        ->and($dev->canAccessPanel($adminPanel))->toBeFalse()
        ->and($dev->canAccessPanel($studioPanel))->toBeFalse()
        ->and($dev->canAccessPanel($kioskPanel))->toBeFalse();

    // Usuário da escola não pode acessar developer panel
    expect($regularUser->canAccessPanel($devPanel))->toBeFalse();
});

it('returns server and database health metrics via SystemDiagnosticService', function () {
    $service = app(SystemDiagnosticService::class);

    $serverHealth = $service->getServerHealth();
    expect($serverHealth)->toBeArray()
        ->and($serverHealth)->toHaveKey('php_version')
        ->and($serverHealth)->toHaveKey('memory_usage_mb')
        ->and($serverHealth)->toHaveKey('extensions');

    $dbHealth = $service->getDatabaseHealth();
    expect($dbHealth)->toBeArray()
        ->and($dbHealth)->toHaveKey('status')
        ->and($dbHealth['status'])->toBe('ok')
        ->and($dbHealth)->toHaveKey('driver');
});

it('inspects code integrity and git status via SystemDiagnosticService', function () {
    $service = app(SystemDiagnosticService::class);

    $integrity = $service->getCodeIntegrity();
    expect($integrity)->toBeArray()
        ->and($integrity)->toHaveKey('is_clean')
        ->and($integrity)->toHaveKey('branch')
        ->and($integrity)->toHaveKey('commit_hash')
        ->and($integrity)->toHaveKey('modified_files')
        ->and($integrity)->toHaveKey('untracked_files');
});

it('reads, filters, and clears application logs via SystemDiagnosticService', function () {
    $service = app(SystemDiagnosticService::class);
    $logPath = storage_path('logs/laravel.log');

    File::ensureDirectoryExists(dirname($logPath));

    $dummyLog = "[2026-10-31 18:00:00] local.ERROR: Test error for diagnostic {:exception:}\n[2026-10-31 18:01:00] local.INFO: Informative log event\n";
    File::put($logPath, $dummyLog);

    $logs = $service->getRecentLogs(50);
    expect($logs)->not->toBeEmpty();

    $errorLogs = $service->getRecentLogs(50, 'ERROR');
    expect($errorLogs)->not->toBeEmpty()
        ->and($errorLogs[0]['level'])->toBe('ERROR');

    $cleared = $service->clearLogs();
    expect($cleared)->toBeTrue();

    $logsAfterClear = $service->getRecentLogs(50);
    expect(count($logsAfterClear))->toBeLessThanOrEqual(1); // Pode ter o log gerado pelo clearLogs()
});

it('redirects unauthenticated users from developer panel to developer login', function () {
    $this->get('/developer')
        ->assertRedirect('/developer/login');
});

it('forbids regular users from entering developer panel', function () {
    $person = \App\Models\Person::create(['name' => 'Secretaria', 'email' => 'sec@escola.com']);
    $user = User::factory()->create([
        'person_id' => $person->id,
        'email' => 'sec@escola.com',
        'is_developer' => false,
    ]);

    $this->actingAs($user)
        ->get('/developer/developer-dashboard')
        ->assertForbidden();
});

it('allows developer user to access developer dashboard and diagnostic pages', function () {
    $person = \App\Models\Person::create(['name' => 'Desenvolvedor Tech', 'email' => 'devtech@aurosoft.internal']);
    $dev = User::factory()->create([
        'person_id' => $person->id,
        'email' => 'devtech@aurosoft.internal',
        'is_developer' => true,
    ]);

    $this->actingAs($dev)
        ->get('/developer/developer-dashboard')
        ->assertSuccessful();

    $this->actingAs($dev)
        ->get('/developer/code-integrity')
        ->assertSuccessful();

    $this->actingAs($dev)
        ->get('/developer/api-diagnostics')
        ->assertSuccessful();

    $this->actingAs($dev)
        ->get('/developer/license-inspection')
        ->assertSuccessful();

    $this->actingAs($dev)
        ->get('/developer/system-logs')
        ->assertSuccessful();
});

it('strictly blocks developer user from accessing client admin panel', function () {
    $person = \App\Models\Person::create(['name' => 'Dev Tech', 'email' => 'dev@aurosoft.com']);
    $dev = User::factory()->create([
        'person_id' => $person->id,
        'email' => 'dev@aurosoft.com',
        'is_developer' => true,
    ]);

    $this->actingAs($dev)
        ->get('/admin')
        ->assertForbidden();
});

