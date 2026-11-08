<?php

namespace App\Filament\Exports;

use App\Models\PaymentMethod;

class PaymentMethodExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = PaymentMethod::class;

    protected static array $columns = ['name' => 'Forma de pagamento', 'slug' => 'Identificador', 'is_active' => 'Ativa'];
}
