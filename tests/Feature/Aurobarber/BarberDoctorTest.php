<?php

declare(strict_types=1);

use App\Filament\Saas\Pages\BarberDoctorPage;
use App\Models\Barbershop;
use App\Models\User;
use App\Services\BarberDoctorService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('barber doctor service runs complete diagnostic report with valid score and checks', function () {
    $service = app(BarberDoctorService::class);
    $report = $service->diagnose();

    expect($report)->toBeArray()
        ->toHaveKeys(['score', 'status', 'status_label', 'summary', 'execution_time_ms', 'checked_at', 'checks'])
        ->and($report['score'])->toBeInt()->toBeBetween(0, 100)
        ->and($report['summary']['total'])->toBeGreaterThanOrEqual(10)
        ->and($report['checks'])->not->toBeEmpty();

    $categories = collect($report['checks'])->pluck('category')->unique()->all();
    expect($categories)->toContain('Infraestrutura')
        ->toContain('Segurança & Perímetro')
        ->toContain('Resiliência & Backups')
        ->toContain('Multi-Barbearias & Licenças');
});

test('barber doctor detects barbershops without saas plan and reports branches correctly', function () {
    $matriz = Barbershop::create([
        'name' => 'Matriz Válida',
        'slug' => 'matriz-valida',
        'monthly_price' => 100,
        'saas_plan_id' => null, // sem plano
        'is_active' => true,
        'license_status' => 'active',
    ]);

    $filial = Barbershop::create([
        'name' => 'Filial 1',
        'slug' => 'filial-1',
        'parent_id' => $matriz->id,
        'monthly_price' => 0,
        'saas_plan_id' => null,
        'is_active' => true,
        'license_status' => 'active',
    ]);

    $service = app(BarberDoctorService::class);
    $report = $service->diagnose();

    $tenancyCheck = collect($report['checks'])->firstWhere('name', 'Integridade de Filiais e Redes');
    expect($tenancyCheck['status'])->toBe('warning')
        ->and($tenancyCheck['details'])->toContain('sem plano SaaS');
});

test('barber doctor page renders in saas panel and allows running diagnostics and filtering', function () {
    $admin = User::create([
        'name' => 'SaaS Master Admin',
        'email' => 'master@aurobarber.com',
        'password' => bcrypt('password123'),
        'role' => 'master_admin',
        'is_active' => true,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('saas'));

    $this->actingAs($admin);

    Livewire::test(BarberDoctorPage::class)
        ->assertSuccessful()
        ->assertSee('BarberDoctor')
        ->assertSee('Robô Clínico de Saúde')
        ->call('setCategory', 'Infraestrutura')
        ->assertSet('activeCategory', 'Infraestrutura')
        ->call('runDiagnostics')
        ->assertHasNoErrors();
});
