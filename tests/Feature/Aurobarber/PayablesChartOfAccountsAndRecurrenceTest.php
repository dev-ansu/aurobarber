<?php

declare(strict_types=1);

namespace Tests\Feature\Aurobarber;

use App\Models\Barbershop;
use App\Models\FinancialType;
use App\Models\Payable;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create a recurring payable and automatically generate occurrences like rent', function () {
    $barbershop = Barbershop::create([
        'name' => 'Barbearia Modelo',
        'slug' => 'barbearia-modelo',
    ]);

    $owner = User::create([
        'name' => 'Proprietário',
        'email' => 'proprietario@modelo.test',
        'password' => 'password',
        'role' => 'owner',
        'barbershop_id' => $barbershop->id,
        'is_active' => true,
    ]);

    $rentType = FinancialType::create([
        'barbershop_id' => $barbershop->id,
        'code' => '3.1.1',
        'name' => 'Aluguel do Ponto Comercial',
        'type' => FinancialType::TYPE_EXPENSE,
        'is_synthetic' => false,
        'is_active' => true,
    ]);

    $baseDueDate = today()->addDays(5);

    $payable = Payable::create([
        'barbershop_id' => $barbershop->id,
        'created_by_id' => $owner->id,
        'description' => 'Aluguel da Barbearia',
        'supplier' => 'Imobiliária Central',
        'financial_type_id' => $rentType->id,
        'amount' => 1500.00,
        'due_date' => $baseDueDate->toDateString(),
        'status' => 'pending',
        'is_recurring' => true,
        'recurrence_period' => 'monthly',
        'recurrence_limit' => 6,
    ]);

    $occurrences = $payable->generateRecurringOccurrences(6, 'monthly');

    expect($occurrences)->toHaveCount(5);

    $allRentPayables = Payable::where('barbershop_id', $barbershop->id)
        ->where('recurrence_group_id', $payable->recurrence_group_id)
        ->orderBy('due_date')
        ->get();

    expect($allRentPayables)->toHaveCount(6);

    // Verifica a primeira ocorrência
    $first = $allRentPayables->first();
    expect($first->description)->toContain('(1/6)')
        ->and((float) $first->amount)->toBe(1500.00)
        ->and($first->due_date->toDateString())->toBe($baseDueDate->toDateString());

    // Verifica a última ocorrência (5 meses depois)
    $last = $allRentPayables->last();
    expect($last->description)->toContain('(6/6)')
        ->and((float) $last->amount)->toBe(1500.00)
        ->and($last->due_date->toDateString())->toBe($baseDueDate->copy()->addMonthsNoOverflow(5)->toDateString());
});

test('can perform partial payment and automatically desmember remaining balance', function () {
    $barbershop = Barbershop::create([
        'name' => 'Barbearia Navalha de Ouro',
        'slug' => 'navalha-ouro',
    ]);

    $payable = Payable::create([
        'barbershop_id' => $barbershop->id,
        'description' => 'Fornecedor de Toalhas e Insumos',
        'amount' => 1000.00,
        'due_date' => today()->toDateString(),
        'status' => 'pending',
    ]);

    // Baixa parcial: paga R$ 400 de R$ 1000
    $remaining = $payable->pay([
        'amount_paid' => 400.00,
        'payment_date' => today()->toDateString(),
        'payment_method' => 'pix',
        'remaining_due_date' => today()->addDays(15)->toDateString(),
    ]);

    $payable->refresh();

    // A conta original é marcada como paga de R$ 400
    expect($payable->status)->toBe('paid')
        ->and((float) $payable->amount)->toBe(400.00)
        ->and((float) $payable->amount_paid)->toBe(400.00)
        ->and($payable->payment_method)->toBe('pix')
        ->and($payable->notes)->toContain('Baixa parcial');

    // Foi criado o saldo restante de R$ 600
    expect($remaining)->not->toBeNull()
        ->and($remaining->status)->toBe('pending')
        ->and((float) $remaining->amount)->toBe(600.00)
        ->and($remaining->description)->toContain('(Saldo restante)')
        ->and($remaining->due_date->toDateString())->toBe(today()->addDays(15)->toDateString());
});

test('chart of accounts seeder seeds standard accounts hierarchy and ensures legacy data compatibility', function () {
    app(ChartOfAccountsSeeder::class)->run(null);

    // Verifica categorias sintéticas
    $synthetic = FinancialType::where('code', '3')->where('is_synthetic', true)->first();
    expect($synthetic)->not->toBeNull()
        ->and($synthetic->type)->toBe(FinancialType::TYPE_EXPENSE);

    // Verifica categoria analítica de aluguel
    $rent = FinancialType::where('code', '3.1.1')->where('is_synthetic', false)->first();
    expect($rent)->not->toBeNull()
        ->and($rent->name)->toContain('Aluguel')
        ->and($rent->parent_id)->not->toBeNull();

    // Garante compatibilidade retroativa para registros legados que tinham apenas 'category' em texto
    $legacyPayable = new Payable([
        'description' => 'Conta antiga',
        'category' => 'Aluguel',
        'amount' => 500,
    ]);

    // O accessor deve retornar o texto legado sem erros
    expect($legacyPayable->category)->toBe('Aluguel');

    // Ao associar financial_type_id, o accessor prioriza o nome contábil
    $legacyPayable->financial_type_id = $rent->id;
    $legacyPayable->setRelation('financialType', $rent);

    expect($legacyPayable->category)->toBe('Aluguel do Ponto Comercial')
        ->and($legacyPayable->financialType->id)->toBe($rent->id);
});
