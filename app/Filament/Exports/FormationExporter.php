<?php

namespace App\Filament\Exports;

use App\Models\Formation;

class FormationExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = Formation::class;

    protected static array $columns = ['name' => 'Formação', 'description' => 'Descrição', 'active' => 'Ativa'];
}
