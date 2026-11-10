<?php

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Barbershop;
use App\Models\Product;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('product catalog only returns products for the authorized active tenant', function () {
    [$tenant, $otherTenant, $owner] = createProductTenantContext();
    $currentProduct = createTenantProduct($tenant);
    createTenantProduct($otherTenant);

    Filament::setTenant($tenant, isQuiet: true);
    $this->actingAs($owner);

    expect(ProductResource::getEloquentQuery()->pluck('id')->all())
        ->toBe([$currentProduct->id]);

    Livewire::test(ListProducts::class)
        ->assertCanSeeTableRecords([$currentProduct])
        ->assertCanNotSeeTableRecords(
            Product::where('barbershop_id', $otherTenant->id)->get(),
        );
});

test('creating a product uses the active tenant and requires tenant authorization', function () {
    [$tenant, , $owner] = createProductTenantContext();

    Filament::setTenant($tenant, isQuiet: true);
    $this->actingAs($owner);

    $method = new ReflectionMethod(CreateProduct::class, 'mutateFormDataBeforeCreate');
    $method->setAccessible(true);

    expect($method->invoke(new CreateProduct, ['name' => 'Pomada'])['barbershop_id'])
        ->toBe($tenant->id);

    Filament::setTenant(null, isQuiet: true);

    expect(fn () => $method->invoke(new CreateProduct, ['name' => 'Produto sem tenant']))
        ->toThrow(AuthorizationException::class);
});

function createProductTenantContext(): array
{
    $tenant = Barbershop::create([
        'name' => 'Barbearia Atual',
        'slug' => 'barbearia-atual',
    ]);
    $otherTenant = Barbershop::create([
        'name' => 'Outra Barbearia',
        'slug' => 'outra-barbearia',
    ]);
    $owner = User::create([
        'name' => 'Dona Teste',
        'email' => 'dona-'.uniqid().'@example.test',
        'password' => 'password',
        'role' => 'owner',
        'barbershop_id' => $tenant->id,
        'is_active' => true,
    ]);
    $owner->barbershops()->attach($tenant->id, [
        'role' => 'owner',
        'is_primary' => true,
    ]);

    return [$tenant, $otherTenant, $owner];
}

function createTenantProduct(Barbershop $barbershop): Product
{
    return Product::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Pomada '.$barbershop->id,
        'price' => 15,
        'stock_quantity' => 5,
    ]);
}
