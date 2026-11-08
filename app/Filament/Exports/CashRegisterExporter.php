<?php

namespace App\Filament\Exports;

use App\Models\CashRegister;

class CashRegisterExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = CashRegister::class;

    protected static array $columns = [
        'name' => 'Caixa', 'status' => 'Situação', 'operator.name' => 'Operador',
        'opening_balance' => 'Saldo inicial', 'current_balance' => 'Saldo atual',
        'opened_at' => 'Aberto em', 'closed_at' => 'Fechado em',
    ];
}
