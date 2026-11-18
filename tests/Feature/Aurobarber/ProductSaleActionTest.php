<?php

use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Barbershop;
use App\Models\CashMovement;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('selecting products automatically initializes quantities and registers cash movement', function () {
    $tenant = Barbershop::create([
        'name' => 'Barbearia Venda',
        'slug' => 'barbearia-venda',
        'is_active' => true,
    ]);

    $owner = User::create([
        'name' => 'Dona Venda',
        'email' => 'dona-venda@example.test',
        'password' => 'password',
        'role' => 'owner',
        'barbershop_id' => $tenant->id,
    ]);

    $product1 = Product::create([
        'barbershop_id' => $tenant->id,
        'name' => 'Água Mineral',
        'price' => 5.0,
        'cost_price' => 2.0,
        'stock_quantity' => 10,
        'is_active' => true,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Filament::setTenant($tenant, isQuiet: true);
    $this->actingAs($owner);

    Livewire::test(ListProducts::class)
        ->mountAction('registerProductSale')
        ->setActionData([
            'client_name' => 'Cliente Teste',
            'product_ids' => [$product1->id],
            // User did not type quantity
            'payment_method' => 'pix',
        ])
        ->callMountedAction()
        ->assertHasNoActionErrors();

    expect(ProductSale::count())->toBe(1)
        ->and($product1->fresh()->stock_quantity)->toBe(9)
        ->and(CashMovement::count())->toBe(1)
        ->and((float) CashMovement::first()->amount)->toBe(5.0);
});
