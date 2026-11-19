<?php

use App\Filament\Pages\CommissionsPage;
use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\Professional;
use App\Models\Service;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('commissions page accurately calculates 50 percent commission over 130 gross total of services and products', function () {
    $barbershop = Barbershop::create([
        'name' => 'Barbearia Precisão',
        'slug' => 'barbearia-precisao',
        'is_active' => true,
    ]);

    $owner = User::create([
        'name' => 'Proprietário',
        'email' => 'proprietario@barbearia.test',
        'password' => 'password',
        'role' => 'owner',
        'barbershop_id' => $barbershop->id,
    ]);

    $barberUser = User::create([
        'name' => 'Barbeiro Teste',
        'email' => 'barbeiro@barbearia.test',
        'password' => 'password',
        'role' => 'barber',
        'barbershop_id' => $barbershop->id,
    ]);

    $professional = Professional::create([
        'barbershop_id' => $barbershop->id,
        'user_id' => $barberUser->id,
        'name' => 'Barbeiro Teste',
        'commission_rate' => 50.00,
        'is_active' => true,
    ]);

    $service = Service::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Corte Simples',
        'price' => 15.00,
        'duration_minutes' => 30,
        'is_active' => true,
    ]);

    $product = Product::create([
        'barbershop_id' => $barbershop->id,
        'name' => 'Água Mineral',
        'price' => 5.00,
        'cost_price' => 2.00,
        'commission_rate' => 50.00,
        'stock_quantity' => 50,
        'is_active' => true,
    ]);

    // 8 Atendimentos de R$ 15,00 cada = R$ 120,00 de serviços
    for ($i = 1; $i <= 8; $i++) {
        Appointment::create([
            'barbershop_id' => $barbershop->id,
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'client_name' => "Cliente {$i}",
            'client_phone' => "8599900000{$i}",
            'scheduled_at' => now()->startOfWeek()->addHours($i),
            'ends_at' => now()->startOfWeek()->addHours($i)->addMinutes(30),
            'status' => 'completed',
            'payment_status' => 'paid',
            'service_price' => 15.00,
            'commission_rate' => 50.00,
            'commission_amount' => 7.50,
            'products_total' => $i === 1 ? 5.00 : 0.00,
            // Simulando um caso legado no appointment 1
            'products_commission_amount' => $i === 1 ? 27.50 : 0.00,
            'commission_status' => 'pending',
        ]);
    }

    // 2 Vendas de produto de R$ 5,00 cada = R$ 10,00 de produtos
    for ($j = 1; $j <= 2; $j++) {
        ProductSale::create([
            'barbershop_id' => $barbershop->id,
            'product_id' => $product->id,
            'professional_id' => $professional->id,
            'quantity' => 1,
            'unit_price' => 5.00,
            'total_price' => 5.00,
            'cost_price' => 2.00,
            'commission_rate' => 50.00,
            'commission_amount' => 2.50,
            'commission_status' => 'pending',
            'created_at' => now()->startOfWeek()->addHours($j),
        ]);
    }

    $this->actingAs($owner);
    Filament::setTenant($barbershop);

    $page = new CommissionsPage;
    $page->selectedProfessionalId = (string) $professional->id;
    $page->applyPreset('week');

    $stats = $page->getSummaryStatsProperty();

    // Verificação matemática absoluta:
    // Total bruto: 120 (serviços) + 10 (produtos) = 130
    expect((float) $stats['services_gross'])->toBe(120.0)
        ->and((float) $stats['products_gross'])->toBe(10.0)
        ->and((float) $stats['total_gross'])->toBe(130.0)
        // Comissão: 60 (serviços) + 5 (produtos) = 65 (Exatos 50% de 130)
        ->and((float) $stats['services_commission'])->toBe(60.0)
        ->and((float) $stats['products_commission'])->toBe(5.0)
        ->and((float) $stats['total_commission'])->toBe(65.0)
        ->and((float) $stats['barbershop_cut'])->toBe(65.0);
});
