<?php

namespace App\Filament\Exports;

use App\Models\Classroom;

class ClassroomExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = Classroom::class;

    protected static array $columns = [
        'name' => 'Sala', 'description' => 'Descrição', 'capacity' => 'Capacidade',
        'opening_time' => 'Abre às', 'closing_time' => 'Fecha às', 'active' => 'Ativa',
    ];
}
