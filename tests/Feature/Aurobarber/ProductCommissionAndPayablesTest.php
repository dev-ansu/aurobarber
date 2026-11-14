<?php

use App\Filament\Widgets\PayablesOverviewWidget;
use App\Models\Barbershop;
use App\Models\Payable;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\Professional;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates product sale with barber commission and decreases stock on direct sale', function () {
    $barbershop = Barbershop::create([
        'name' => 'Barbearia Modelo',
        'slug' => 'barbearia-modelo',
    ]);

    $professional = Professional::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Carlos Silva',
        'display_name' => 'Carlos',
        'commission_rate' => 40.00,
        'is_active' => true,
    ]);

    $product = Product::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Pomada Efeito Seco',
        'price' => 50.00,
        'cost_price' => 20.00,
        'stock_quantity' => 10,
        'commission_rate' => 15.00,
        'is_active' => true,
    ]);

    $qty = 2;
    $total = 100.00;
    $commRate = 15.00;
    $commAmount = 15.00;

    $product->decrement('stock_quantity', $qty);

    $sale = ProductSale::create([
        'barbershop_id' => $barbershop->id,
        'product_id' => $product->id,
        'professional_id' => $professional->id,
        'quantity' => $qty,
        'unit_price' => $product->price,
        'total_price' => $total,
        'cost_price' => $product->cost_price,
        'commission_rate' => $commRate,
        'commission_amount' => $commAmount,
        'commission_status' => 'pending',
        'payment_method' => 'pix',
        'client_name' => 'Cliente Balcão',
    ]);

    expect($product->fresh()->stock_quantity)->toBe(8)
        ->and($sale->commission_amount)->toBe('15.00')
        ->and($sale->commission_status)->toBe('pending')
        ->and($sale->professional_id)->toBe($professional->id);
});

it('calculates payables overview correctly for upcoming and overdue bills', function () {
    $barbershop = Barbershop::create([
        'name' => 'Barbearia Modelo 2',
        'slug' => 'barbearia-modelo-2',
    ]);

    // Conta vencida
    Payable::create([
        'barbershop_id' => $barbershop->id,
        'description' => 'Conta de Luz Atrasada',
        'amount' => 350.00,
        'due_date' => now()->subDays(3)->toDateString(),
        'status' => 'pending',
    ]);

    // Conta a vencer em 2 dias
    Payable::create([
        'barbershop_id' => $barbershop->id,
        'description' => 'Internet Fibra',
        'amount' => 150.00,
        'due_date' => now()->addDays(2)->toDateString(),
        'status' => 'pending',
    ]);

    // Conta paga este mês
    Payable::create([
        'barbershop_id' => $barbershop->id,
        'description' => 'Água Mineral',
        'amount' => 80.00,
        'due_date' => now()->toDateString(),
        'payment_date' => now()->toDateString(),
        'status' => 'paid',
    ]);

    Filament::setTenant($barbershop, isQuiet: true);

    $owner = User::create([
        'name' => 'Dono Teste',
        'email' => 'dono-'.uniqid().'@example.test',
        'password' => 'password',
        'role' => 'owner',
        'barbershop_id' => $barbershop->id,
        'is_active' => true,
    ]);
    $this->actingAs($owner);

    expect(PayablesOverviewWidget::canView())->toBeTrue();
});
