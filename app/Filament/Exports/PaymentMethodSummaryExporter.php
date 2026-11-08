<?php

namespace App\Filament\Exports;

use App\Models\PaymentMethod;

class PaymentMethodSummaryExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = PaymentMethod::class;

    protected static array $columns = [
        'name' => 'Forma de recebimento',
        'receipt_count' => 'Quantidade de recebimentos',
        'amount_total' => 'Valor recebido',
    ];
}
