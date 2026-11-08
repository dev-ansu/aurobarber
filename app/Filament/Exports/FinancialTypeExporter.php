<?php

namespace App\Filament\Exports;

use App\Models\FinancialType;

class FinancialTypeExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = FinancialType::class;

    protected static array $columns = ['name' => 'Tipo financeiro', 'slug' => 'Identificador', 'is_active' => 'Ativo'];
}
