<?php

use App\Filament\Pages\SystemUpdate;
use App\Livewire\SystemUpdateAlert;
use App\Models\Person;
use App\Models\User;
use App\Services\SystemUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('guest cannot access system update page', function () {
    $this->get(SystemUpdate::getUrl())
        ->assertRedirect('/admin/login');
});

test('user without permission cannot access system update page', function () {
    $school = \App\Models\School::first() ?: \App\Models\School::create([
        'legal_name' => 'Escola Matriz',
        'trade_name' => 'Matriz',
    ]);
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id]);
    $user->schools()->attach($school);

    $this->actingAs($user)
        ->get(SystemUpdate::getUrl())
        ->assertForbidden();
});

test('super admin can access system update page and see version status', function () {
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'View:SystemUpdate', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'Apply:SystemUpdate', 'guard_name' => 'web']);

    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id]);
    $user->assignRole($role);

    $this->actingAs($user)
        ->get(SystemUpdate::getUrl())
        ->assertOk()
        ->assertSee('Atualização do Sistema')
        ->assertSee('Canal de Atualização')
        ->assertSee('main');
});

test('system update service returns valid array structure', function () {
    $service = app(SystemUpdateService::class);
    $status = $service->checkForUpdates();

    expect($status)->toBeArray()
        ->toHaveKeys(['has_update', 'current_branch', 'target_branch', 'current_commit', 'commits_behind', 'checked_at']);
});

test('system update alert displays when update is available and user is super_admin', function () {
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'View:SystemUpdate', 'guard_name' => 'web']);

    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id]);
    $user->assignRole($role);

    // Mock do status no cache
    Cache::put('system_update_status', [
        'has_update' => true,
        'current_branch' => 'main',
        'target_branch' => 'main',
        'current_commit' => ['short_hash' => 'abc1234', 'message' => 'old commit', 'date' => '01/01/2026'],
        'remote_commit' => ['short_hash' => 'def5678'],
        'commits_behind' => 3,
        'new_commits' => ['def5678 - nova funcionalidade'],
        'checked_at' => now()->format('d/m/Y H:i:s'),
        'error' => null,
    ], 300);

    Livewire::actingAs($user)
        ->test(SystemUpdateAlert::class)
        ->assertSee('Há uma nova atualização do Aurosoft disponível!')
        ->assertSee('Atualizar Agora')
        ->call('dismiss')
        ->assertDontSee('Há uma nova atualização do Aurosoft disponível!');
});

test('system update service detects encrypted env deployment and finds encryption key', function () {
    $service = app(SystemUpdateService::class);

    // No ambiente de testes padrão, .env existe em disco
    expect($service->isEncryptedEnvDeployment())->toBeFalse();

    putenv('LARAVEL_ENV_ENCRYPTION_KEY=base64:TestKey12345678901234567890123456789012=');
    expect($service->hasEnvEncryptionKey())->toBeTrue();
    expect($service->getEnvEncryptionKey())->toBe('base64:TestKey12345678901234567890123456789012=');
    putenv('LARAVEL_ENV_ENCRYPTION_KEY');
});

test('system update service compileProductionCaches runs successfully', function () {
    $service = app(SystemUpdateService::class);
    $logs = $service->compileProductionCaches();

    expect($logs)->toBeArray()
        ->toContain('Caches anteriores limpos com segurança.');
});


