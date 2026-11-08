<?php

namespace App\Filament\Exports;

use App\Models\Collaborator;
use App\Services\DataMaskingService;
use Filament\Actions\Exports\ExportColumn;

class CollaboratorExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = Collaborator::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('person.name')->label('Nome')->preventFormulaInjection(),
            ExportColumn::make('role')->label('Cargo')->preventFormulaInjection(),
            ExportColumn::make('admission_date')->label('Admissão'),
            ExportColumn::make('dismissal_date')->label('Desligamento'),
            ExportColumn::make('active')->label('Ativo'),
            ExportColumn::make('pix')
                ->label('Chave Pix')
                ->state(function (Collaborator $record): string {
                    $pix = (string) ($record->pix ?? '');
                    if (! DataMaskingService::canViewFinancial()) {
                        return DataMaskingService::maskPix($pix);
                    }
                    return $pix;
                })
                ->preventFormulaInjection(),
        ];
    }
}
