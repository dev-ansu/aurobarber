<?php

namespace App\Filament\Exports;

use App\Models\CashTransaction;

class CashTransactionExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = CashTransaction::class;

    protected static array $columns = [
        'register.name' => 'Caixa', 'user.name' => 'Operador', 'type' => 'Tipo',
        'amount' => 'Valor', 'paymentMethod.name' => 'Forma de pagamento',
        'financialType.name' => 'Tipo financeiro', 'description' => 'Descrição', 'created_at' => 'Realizado em',
    ];
}
