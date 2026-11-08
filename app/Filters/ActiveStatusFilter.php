<?php

namespace App\Filters;

use Filament\Tables\Filters\SelectFilter;

class ActiveStatusFilter extends SelectFilter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Status');

        $this->options([
            'true' => 'Ativo',
            'false' => 'Inativo',
        ]);
    }
}
