<?php

namespace App\Filament\Exports;

use App\Models\Contract;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

/**
 * Metadata adapter for report CSVs stored in Filament's export history.
 */
class CompletionReportExporter extends Exporter
{
    protected static ?string $model = Contract::class;

    public static function getColumns(): array
    {
        return [];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "A exportação do relatório de conclusão foi concluída: {$export->successful_rows} registro(s).";
    }

    public static function getCsvDelimiter(): string
    {
        return ';';
    }
}
