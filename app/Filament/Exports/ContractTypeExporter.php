<?php

namespace App\Filament\Exports;

use App\Models\ContractType;

class ContractTypeExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = ContractType::class;

    protected static array $columns = ['name' => 'Tipo de contrato', 'version' => 'Versão', 'is_active' => 'Ativo'];
}
