<?php

namespace App\Filament\Exports;

use Spatie\Permission\Models\Role;

class RoleExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = Role::class;

    protected static array $columns = ['name' => 'Perfil', 'guard_name' => 'Guard', 'created_at' => 'Criado em'];
}
