<?php

namespace App\Filament\Exports;

use App\Models\Responsible;
use App\Services\DataMaskingService;
use Filament\Actions\Exports\ExportColumn;

class ResponsibleExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = Responsible::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('person.name')->label('Nome')->preventFormulaInjection(),
            ExportColumn::make('person.email')
                ->label('E-mail')
                ->state(function (Responsible $record): string {
                    $email = (string) ($record->person?->email ?? '');
                    if (! DataMaskingService::canViewEmail()) {
                        return DataMaskingService::maskEmail($email);
                    }
                    return $email;
                })
                ->preventFormulaInjection(),
            ExportColumn::make('relationship')->label('Parentesco')->preventFormulaInjection(),
            ExportColumn::make('financial_responsible')->label('Responsável financeiro')->preventFormulaInjection(),
        ];
    }
}
