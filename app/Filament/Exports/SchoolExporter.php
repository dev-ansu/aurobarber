<?php

namespace App\Filament\Exports;

use App\Models\School;

class SchoolExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = School::class;

    protected static array $columns = [
        'legal_name' => 'Razão social', 'trade_name' => 'Nome fantasia', 'cnpj' => 'CNPJ',
        'email' => 'E-mail', 'phone' => 'Telefone', 'street' => 'Endereço', 'number' => 'Número',
        'neighborhood' => 'Bairro', 'city' => 'Município', 'state' => 'Estado', 'postal_code' => 'CEP',
    ];
}
