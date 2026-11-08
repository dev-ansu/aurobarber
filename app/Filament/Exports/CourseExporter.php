<?php

namespace App\Filament\Exports;

use App\Models\Course;

class CourseExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = Course::class;

    protected static array $columns = [
        'name' => 'Curso', 'description' => 'Descrição', 'workload' => 'Carga horária',
        'active' => 'Ativo', 'ouro_moderno_id' => 'ID Ouro Moderno',
    ];
}
