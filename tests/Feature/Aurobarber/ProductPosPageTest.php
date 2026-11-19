<?php

declare(strict_types=1);

use App\Filament\Pages\CashRegisterPage;
use App\Filament\Pages\ProductPosPage;
use App\Filament\Resources\ProductResource;
use App\Models\Barbershop;
use App\Models\CashMovement;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\Professional;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createPosTenantContext(): array
{
    $tenant = Barbershop::create([
        'name' => 'Barbearia PDV',
        'slug' => 'barbearia-pdv-'.uniqid(),
        'is_active' => true,
    ]);

    $owner = User::create([
        'name' => 'Dono PDV',
        'email' => 'dono-pdv-'.uniqid().'@example.test',
        'password' => 'password',
        'role' => 'owner',
        'barbershop_id' => $tenant->id,
        'is_active' => true,
    ]);
    $owner->barbershops()->attach($tenant->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    return [$tenant, $owner];
}

test('pos sells multiple products with different quantities and split payment methods', function () {
    [$tenant, $owner] = createPosTenantContext();

    $pomada = Product::create([
        'barbershop_id' => $tenant->id,
        'name' => 'Pomada Matte',
        'category' => 'cosmetico',
        'price' => 35.00,
        'cost_price' => 15.00,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    $cerveja = Product::create([
        'barbershop_id' => $tenant->id,
        'name' => 'Cerveja Long Neck',
        'category' => 'bar',
        'price' => 12.50,
        'cost_price' => 6.00,
        'stock_quantity' => 8,
        'is_active' => true,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant, isQuiet: true);
    $this->actingAs($owner);

    // 2x Pomada (70.00) + 3x Cerveja (37.50) = 107.50
    // Paid: 40.00 PIX + 30.00 Débito + 50.00 Dinheiro (Troco: 12.50 -> net Dinheiro: 37.50)
    Livewire::test(ProductPosPage::class)
        ->call('addToCart', $pomada->id)
        ->call('addToCart', $pomada->id)
        ->call('addToCart', $cerveja->id)
        ->call('setQuantity', $cerveja->id, 3)
        ->assertSet('cartTotal', 107.5)
        ->set('clientName', 'Carlos Silva')
        ->call('enableSplitPayment')
        ->set('payments', [
            ['method' => 'pix', 'amount' => '40,00'],
            ['method' => 'debito', 'amount' => '30,00'],
            ['method' => 'dinheiro', 'amount' => '50,00'],
        ])
        ->call('finalizeSale')
        ->assertSet('cart', [])
        ->assertNotified();

    expect($pomada->fresh()->stock_quantity)->toBe(8)
        ->and($cerveja->fresh()->stock_quantity)->toBe(5)
        ->and(ProductSale::count())->toBe(2)
        ->and(CashMovement::count())->toBe(1)
        ->and((float) CashMovement::first()->amount)->toBe(107.5);

    $sales = ProductSale::orderBy('id')->get();
    expect($sales[0]->sale_group_id)->not->toBeNull()
        ->and($sales[0]->sale_group_id)->toBe($sales[1]->sale_group_id)
        ->and($sales[0]->payment_method)->toBe('misto')
        ->and($sales[0]->payment_details)->toEqual([
            ['method' => 'pix', 'amount' => 40.0],
            ['method' => 'debito', 'amount' => 30.0],
            ['method' => 'dinheiro', 'amount' => 37.5],
        ]);

    // Verify CashRegisterPage totals and drawer balance (no double-counting as suprimento)
    $cashPage = new CashRegisterPage;
    $cashPage->mount();
    $stats = $cashPage->cashStats;

    expect($stats['pix'])->toBe(40.0)
        ->and($stats['debito'])->toBe(30.0)
        ->and($stats['dinheiro'])->toBe(37.5)
        ->and($stats['total'])->toBe(107.5)
        ->and($stats['suprimentos'])->toBe(0.0)
        ->and($stats['saldo_dinheiro_gaveta'])->toBe(37.5);
});

test('pos prevents selling above stock and prevents underpayment', function () {
    [$tenant, $owner] = createPosTenantContext();

    $agua = Product::create([
        'barbershop_id' => $tenant->id,
        'name' => 'Água Mineral',
        'category' => 'bar',
        'price' => 6.00,
        'cost_price' => 2.00,
        'stock_quantity' => 2,
        'is_active' => true,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant, isQuiet: true);
    $this->actingAs($owner);

    Livewire::test(ProductPosPage::class)
        ->call('setQuantity', $agua->id, 5)
        ->assertSet("cart.{$agua->id}", 2)
        ->assertSet('cartTotal', 12.0)
        ->call('enableSplitPayment')
        ->set('payments', [
            ['method' => 'pix', 'amount' => '5,00'],
            ['method' => 'dinheiro', 'amount' => '2,00'],
        ])
        ->call('finalizeSale')
        ->assertNotified();

    expect(ProductSale::count())->toBe(0)
        ->and($agua->fresh()->stock_quantity)->toBe(2);
});

test('barber with Sell:Product permission can sell in POS with commission but cannot create products', function () {
    [$tenant] = createPosTenantContext();

    $barberRole = Role::findOrCreate('barber', 'web');
    $sellPermission = Permission::findOrCreate('Sell:Product', 'web');
    $barberRole->givePermissionTo($sellPermission);

    $barber = User::create([
        'name' => 'Barbeiro Vendedor',
        'email' => 'barbeiro-vendedor-'.uniqid().'@example.test',
        'password' => 'password',
        'role' => 'barber',
        'barbershop_id' => $tenant->id,
        'is_active' => true,
    ]);
    $barber->barbershops()->attach($tenant->id, [
        'role' => 'barber',
        'is_primary' => true,
    ]);
    $barber->assignRole($barberRole);

    $professional = Professional::create([
        'barbershop_id' => $tenant->id,
        'user_id' => $barber->id,
        'name' => 'Barbeiro Vendedor',
        'commission_rate' => 20.0,
        'is_active' => true,
    ]);

    $oleo = Product::create([
        'barbershop_id' => $tenant->id,
        'name' => 'Óleo de Barba',
        'category' => 'cosmetico',
        'price' => 50.00,
        'cost_price' => 20.00,
        'stock_quantity' => 5,
        'commission_rate' => 15.0,
        'is_active' => true,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant, isQuiet: true);
    $this->actingAs($barber);

    expect(ProductPosPage::canAccess())->toBeTrue()
        ->and(ProductResource::canViewAny())->toBeFalse()
        ->and(ProductResource::canCreate())->toBeFalse();

    Livewire::test(ProductPosPage::class)
        ->assertSet('professionalId', $professional->id)
        ->call('addToCart', $oleo->id)
        ->call('selectSinglePaymentMethod', 'credito')
        ->call('finalizeSale')
        ->assertNotified();

    $sale = ProductSale::first();
    expect($sale)->not->toBeNull()
        ->and($sale->professional_id)->toBe($professional->id)
        ->and((float) $sale->commission_rate)->toBe(15.0)
        ->and((float) $sale->commission_amount)->toBe(7.5)
        ->and($sale->commission_status)->toBe('pending')
        ->and($oleo->fresh()->stock_quantity)->toBe(4);
});

test('barber without Sell:Product permission cannot access POS', function () {
    [$tenant] = createPosTenantContext();

    $barber = User::create([
        'name' => 'Barbeiro Sem PDV',
        'email' => 'barbeiro-sem-pdv-'.uniqid().'@example.test',
        'password' => 'password',
        'role' => 'barber',
        'barbershop_id' => $tenant->id,
        'is_active' => true,
    ]);
    $barber->barbershops()->attach($tenant->id, [
        'role' => 'barber',
        'is_primary' => true,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant, isQuiet: true);
    $this->actingAs($barber);

    expect(ProductPosPage::canAccess())->toBeFalse()
        ->and(ProductResource::canViewAny())->toBeFalse();

    Livewire::test(ProductPosPage::class)->assertForbidden();
});

test('pos isolates products by active tenant', function () {
    [$tenant, $owner] = createPosTenantContext();
    $otherTenant = Barbershop::create([
        'name' => 'Outra Barbearia',
        'slug' => 'outra-barbearia-'.uniqid(),
        'is_active' => true,
    ]);

    $myProduct = Product::create([
        'barbershop_id' => $tenant->id,
        'name' => 'Meu Shampoo',
        'price' => 30.00,
        'stock_quantity' => 5,
        'is_active' => true,
    ]);

    $otherProduct = Product::create([
        'barbershop_id' => $otherTenant->id,
        'name' => 'Shampoo Secreto Outro Tenant',
        'price' => 99.00,
        'stock_quantity' => 5,
        'is_active' => true,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant, isQuiet: true);
    $this->actingAs($owner);

    Livewire::test(ProductPosPage::class)
        ->assertSee('Meu Shampoo')
        ->assertDontSee('Shampoo Secreto Outro Tenant')
        ->call('addToCart', $otherProduct->id)
        ->assertSet('cart', []);
});
