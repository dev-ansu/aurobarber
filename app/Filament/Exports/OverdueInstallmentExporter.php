<?php

namespace App\Filament\Exports;

use App\Filament\Pages\OverdueInstallmentReport;
use App\Models\Installment;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

class OverdueInstallmentExporter extends Exporter
{
    protected static ?string $model = Installment::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('contract.contract_number')->label('Contrato')->preventFormulaInjection(),
            ExportColumn::make('contract.student.name')->label('Aluno')->preventFormulaInjection(),
            ExportColumn::make('financialType.name')->label('Tipo financeiro')->preventFormulaInjection(),
            ExportColumn::make('due_date')->label('Vencimento')->formatStateUsing(fn ($state): string => $state?->format('d/m/Y') ?? ''),
            ExportColumn::make('days_overdue')->label('Dias em atraso')->state(fn (Installment $record): int => (int) $record->due_date->diffInDays(today())),
            ExportColumn::make('aging_band')->label('Faixa de atraso')->state(fn (Installment $record): string => OverdueInstallmentReport::agingBand((int) $record->due_date->diffInDays(today()))),
            ExportColumn::make('outstanding_amount')->label('Saldo em aberto')->state(fn (Installment $record): string => number_format(OverdueInstallmentReport::outstandingAmount($record), 2, ',', '.')),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with(['contract.student', 'financialType']);
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "O relatório de inadimplência foi concluído: {$export->successful_rows} parcela(s) exportada(s).";
    }

    public function getFileName(Export $export): string
    {
        return "inadimplencia-{$export->getKey()}";
    }

    public static function getCsvDelimiter(): string
    {
        return ';';
    }
}
