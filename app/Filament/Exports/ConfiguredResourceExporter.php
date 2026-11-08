<?php

namespace App\Filament\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Builder;

abstract class ConfiguredResourceExporter extends Exporter
{
    /** @var array<string, string> Column path => label. */
    protected static array $columns = [];

    public static function getColumns(): array
    {
        $columns = [];

        foreach (static::$columns as $attribute => $label) {
            $columns[] = ExportColumn::make($attribute)
                ->label($label)
                ->preventFormulaInjection();
        }

        return $columns;
    }

    public static function modifyQuery(Builder $query): Builder
    {
        $relations = [];

        foreach (array_keys(static::$columns) as $attribute) {
            if (! str_contains($attribute, '.')) {
                continue;
            }

            $segments = explode('.', $attribute);
            array_pop($segments);

            while ($segments !== []) {
                $relations[] = implode('.', $segments);
                array_pop($segments);
            }
        }

        return $query->with(array_values(array_unique($relations)));
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        return "Exportação concluída: {$export->successful_rows} registro(s).";
    }

    public static function getCsvDelimiter(): string
    {
        return ';';
    }
}
