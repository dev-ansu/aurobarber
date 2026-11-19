<?php

declare(strict_types=1);

use App\Filament\Pages\Tenancy\RegisterBarbershop;
use App\Models\Barbershop;
use App\Models\SaasPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('barbershop under standard single-unit plan cannot create a second unit', function () {
    $plan = SaasPlan::create([
        'name' => 'Plano Mensal Básico',
        'slug' => 'plano-mensal-basico',
        'monthly_price' => 89.90,
        'billing_cycle_months' => 1,
        'max_units' => 1,
        'is_active' => true,
    ]);

    $owner = User::create([
        'name' => 'Dono Barbearia',
        'email' => 'dono@teste.com',
        'password' => 'secret123',
        'role' => 'owner',
        'is_active' => true,
    ]);

    $matriz = Barbershop::create([
        'name' => 'Barbearia Alpha Matriz',
        'slug' => 'barbearia-alpha',
        'owner_id' => $owner->id,
        'saas_plan_id' => $plan->id,
        'max_units' => null, // Herda 1 do plano
        'has_unrestricted_access' => false,
        'is_active' => true,
    ]);

    $owner->barbershops()->attach($matriz->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    expect($matriz->getAllowedUnits())->toBe(1)
        ->and($matriz->getCurrentUnitsCount())->toBe(1)
        ->and($matriz->canAddBranch())->toBeFalse()
        ->and($matriz->getRemainingUnits())->toBe(0);

    // Tentativa de submissão do formulário de nova filial
    $this->actingAs($owner);

    $page = new class extends RegisterBarbershop
    {
        public function testRegister(array $data): Model
        {
            return $this->handleRegistration($data);
        }
    };

    expect(fn () => $page->testRegister([
        'name' => 'Barbearia Alpha Filial 1',
        'slug' => 'alpha-filial-1',
    ]))->toThrow(ValidationException::class);
});

test('barbershop with customized max_units override allows up to configured units', function () {
    $plan = SaasPlan::create([
        'name' => 'Plano Base',
        'slug' => 'plano-base',
        'monthly_price' => 89.90,
        'billing_cycle_months' => 1,
        'max_units' => 1,
        'is_active' => true,
    ]);

    $owner = User::create([
        'name' => 'Dono Franquia',
        'email' => 'franquia@teste.com',
        'password' => 'secret123',
        'role' => 'owner',
        'is_active' => true,
    ]);

    // Provisionamento customizado com 3 unidades no SaaS Master
    $matriz = Barbershop::create([
        'name' => 'Rede Beta Matriz',
        'slug' => 'rede-beta',
        'owner_id' => $owner->id,
        'saas_plan_id' => $plan->id,
        'max_units' => 3, // Override customizado
        'has_unrestricted_access' => false,
        'is_active' => true,
    ]);

    $owner->barbershops()->attach($matriz->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    expect($matriz->getAllowedUnits())->toBe(3)
        ->and($matriz->canAddBranch())->toBeTrue()
        ->and($matriz->getRemainingUnits())->toBe(2);

    $this->actingAs($owner);

    $page = new class extends RegisterBarbershop
    {
        public function testRegister(array $data): Model
        {
            return $this->handleRegistration($data);
        }
    };

    // Criação da 2ª unidade (1ª filial) -> Sucesso
    $filial1 = $page->testRegister([
        'name' => 'Rede Beta Filial 1',
        'slug' => 'beta-filial-1',
    ]);

    expect($filial1->parent_id)->toBe($matriz->id)
        ->and($matriz->getCurrentUnitsCount())->toBe(2)
        ->and($matriz->canAddBranch())->toBeTrue()
        ->and($matriz->getRemainingUnits())->toBe(1);

    // Criação da 3ª unidade (2ª filial) -> Sucesso
    $filial2 = $page->testRegister([
        'name' => 'Rede Beta Filial 2',
        'slug' => 'beta-filial-2',
    ]);

    expect($filial2->parent_id)->toBe($matriz->id)
        ->and($matriz->getCurrentUnitsCount())->toBe(3)
        ->and($matriz->canAddBranch())->toBeFalse()
        ->and($matriz->getRemainingUnits())->toBe(0);

    // Tentativa da 4ª unidade -> Deve bloquear com ValidationException
    expect(fn () => $page->testRegister([
        'name' => 'Rede Beta Filial 3',
        'slug' => 'beta-filial-3',
    ]))->toThrow(ValidationException::class);
});

test('cliente 0 (barbearia marinho) with unrestricted access can register unlimited units', function () {
    $ownerMarinho = User::create([
        'name' => 'Marinho Proprietário',
        'email' => 'marinho@barber.com.br',
        'password' => 'marinho123',
        'role' => 'owner',
        'is_active' => true,
    ]);

    $marinhoMatriz = Barbershop::create([
        'name' => 'Barbearia Marinho',
        'slug' => 'marinho',
        'owner_id' => $ownerMarinho->id,
        'has_unrestricted_access' => true,
        'max_units' => null,
        'is_active' => true,
    ]);

    $ownerMarinho->barbershops()->attach($marinhoMatriz->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    expect($marinhoMatriz->hasUnrestrictedAccess())->toBeTrue()
        ->and($marinhoMatriz->getAllowedUnits())->toBe(PHP_INT_MAX)
        ->and($marinhoMatriz->canAddBranch())->toBeTrue();

    $this->actingAs($ownerMarinho);

    $page = new class extends RegisterBarbershop
    {
        public function testRegister(array $data): Model
        {
            return $this->handleRegistration($data);
        }
    };

    // Cria 5 filiais consecutivas sem ser bloqueado
    for ($i = 1; $i <= 5; $i++) {
        $filial = $page->testRegister([
            'name' => "Barbearia Marinho Unidade {$i}",
            'slug' => "marinho-unidade-{$i}",
        ]);

        expect($filial->parent_id)->toBe($marinhoMatriz->id)
            ->and($marinhoMatriz->canAddBranch())->toBeTrue()
            ->and($filial->hasUnrestrictedAccess())->toBeTrue();
    }

    expect($marinhoMatriz->getCurrentUnitsCount())->toBe(6);
});

test('register barbershop page redirects when unit limit is exceeded', function () {
    $plan = SaasPlan::create([
        'name' => 'Plano 1 Unidade',
        'slug' => 'plano-1-unidade',
        'monthly_price' => 89.90,
        'billing_cycle_months' => 1,
        'max_units' => 1,
        'is_active' => true,
    ]);

    $owner = User::create([
        'name' => 'Dono Limite',
        'email' => 'limite@teste.com',
        'password' => 'secret123',
        'role' => 'owner',
        'is_active' => true,
    ]);

    $shop = Barbershop::create([
        'name' => 'Barbearia Limite',
        'slug' => 'barbearia-limite',
        'owner_id' => $owner->id,
        'saas_plan_id' => $plan->id,
        'max_units' => 1,
        'has_unrestricted_access' => false,
        'is_active' => true,
    ]);

    $owner->barbershops()->attach($shop->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    $this->actingAs($owner);

    Livewire::test(RegisterBarbershop::class)
        ->assertRedirect(url("/admin/{$shop->slug}/planos-de-licenciamento"));
});
