<?php

declare(strict_types=1);

use App\Filament\Pages\Tenancy\RegisterBarbershop;
use App\Filament\Saas\Pages\SaasDashboard;
use App\Models\Barbershop;
use App\Models\SaasPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new branch registration assigns parent_id, zeros monthly_price and establishes branch relationship', function () {
    $plan = SaasPlan::create([
        'name' => 'Plano Multi-Unidades',
        'slug' => 'plano-multi-unidades',
        'monthly_price' => 199.90,
        'billing_cycle_months' => 1,
        'max_units' => 3,
        'is_active' => true,
    ]);

    $owner = User::create([
        'name' => 'Marcos Dono',
        'email' => 'marcos@barbearia.com',
        'password' => 'secret123',
        'role' => 'owner',
        'is_active' => true,
    ]);

    $matriz = Barbershop::create([
        'name' => 'Barbearia Navalha de Ouro',
        'slug' => 'navalha-ouro',
        'owner_id' => $owner->id,
        'saas_plan_id' => $plan->id,
        'monthly_price' => 199.90,
        'is_active' => true,
        'license_status' => 'active',
        'license_expires_at' => now()->addDays(30),
    ]);

    $owner->barbershops()->attach($matriz->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    $this->actingAs($owner);

    $page = new class extends RegisterBarbershop
    {
        public function testRegister(array $data): Model
        {
            return $this->handleRegistration($data);
        }
    };

    $filial = $page->testRegister([
        'name' => 'Barbearia Navalha de Ouro - Shopping',
        'slug' => 'navalha-ouro-shopping',
        'city' => 'Fortaleza / CE',
    ]);

    expect($filial->parent_id)->toBe($matriz->id)
        ->and($filial->isBranch())->toBeTrue()
        ->and($filial->isFilial())->toBeTrue()
        ->and($filial->isMatriz())->toBeFalse()
        ->and($matriz->isMatriz())->toBeTrue()
        ->and($matriz->isBranch())->toBeFalse()
        ->and($matriz->hasBranches())->toBeTrue()
        ->and((float) $filial->effectiveMonthlyPrice())->toBe(0.0)
        ->and((float) $matriz->effectiveMonthlyPrice())->toBe(199.90);

    expect(Barbershop::matrizes()->pluck('id')->all())->toContain($matriz->id)
        ->and(Barbershop::matrizes()->pluck('id')->all())->not->toContain($filial->id)
        ->and(Barbershop::filiais()->pluck('id')->all())->toContain($filial->id)
        ->and(Barbershop::onlyBranches()->pluck('id')->all())->toContain($filial->id);
});

test('saas dashboard differentiates between matrizes and filiais in kpis and listings', function () {
    $admin = User::create([
        'name' => 'Master Admin',
        'email' => 'master@aurosoft.com.br',
        'password' => 'secret123',
        'role' => 'master_admin',
        'is_active' => true,
    ]);

    $matriz1 = Barbershop::create([
        'name' => 'Barbearia Alpha',
        'slug' => 'alpha',
        'owner_id' => $admin->id,
        'is_active' => true,
        'license_status' => 'active',
    ]);

    $filial1 = Barbershop::create([
        'name' => 'Barbearia Alpha Filial Sul',
        'slug' => 'alpha-sul',
        'owner_id' => $admin->id,
        'parent_id' => $matriz1->id,
        'is_active' => true,
        'license_status' => 'active',
    ]);

    $matriz2 = Barbershop::create([
        'name' => 'Barbearia Beta',
        'slug' => 'beta',
        'owner_id' => $admin->id,
        'is_active' => true,
        'license_status' => 'active',
    ]);

    $this->actingAs($admin);

    $dashboard = new SaasDashboard;
    $dashboard->loadMetrics();

    expect($dashboard->totalMatrizes)->toBe(2)
        ->and($dashboard->totalBranches)->toBe(1)
        ->and($dashboard->totalUnits)->toBe(3)
        ->and($dashboard->totalBarbershops)->toBe(2);

    $recentFilial = collect($dashboard->recentBarbershops)->firstWhere('id', $filial1->id);
    expect($recentFilial)->not->toBeNull()
        ->and($recentFilial['is_branch'])->toBeTrue()
        ->and($recentFilial['parent_name'])->toBe('Barbearia Alpha');

    $recentMatriz = collect($dashboard->recentBarbershops)->firstWhere('id', $matriz1->id);
    expect($recentMatriz)->not->toBeNull()
        ->and($recentMatriz['is_branch'])->toBeFalse()
        ->and($recentMatriz['branches_count'])->toBe(1);
});

test('landing page and modal group branches under parent barbershop', function () {
    $matriz = Barbershop::create([
        'name' => 'Rede Vintage Barbershop',
        'slug' => 'vintage-matriz',
        'city' => 'Fortaleza / CE',
        'is_active' => true,
        'license_status' => 'active',
    ]);

    $filial = Barbershop::create([
        'name' => 'Rede Vintage - Praia',
        'slug' => 'vintage-praia',
        'city' => 'Fortaleza / CE',
        'parent_id' => $matriz->id,
        'is_active' => true,
        'license_status' => 'active',
    ]);

    $response = $this->get('/');
    $response->assertOk();

    // The modal should contain the parent barbershop with rede badge and the filial under it
    $response->assertSee('Rede Vintage Barbershop');
    $response->assertSee('Rede Vintage - Praia');
    $response->assertSee('Rede • 2 unidades');
    $response->assertSee('Filial');
});
