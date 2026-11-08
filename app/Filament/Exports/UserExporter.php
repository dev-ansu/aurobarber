<?php

namespace App\Filament\Exports;

use App\Models\User;
use App\Services\DataMaskingService;
use Filament\Actions\Exports\ExportColumn;
use Illuminate\Database\Eloquent\Builder;

class UserExporter extends ConfiguredResourceExporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')->label('Nome')->preventFormulaInjection(),
            ExportColumn::make('email')
                ->label('E-mail')
                ->state(function (User $record): string {
                    $email = (string) ($record->email ?? '');
                    if (! DataMaskingService::canViewEmail()) {
                        return DataMaskingService::maskEmail($email);
                    }
                    return $email;
                })
                ->preventFormulaInjection(),
            ExportColumn::make('email_verified_at')->label('E-mail verificado em'),
            ExportColumn::make('created_at')->label('Criado em'),
        ];
    }

    public static function modifyQuery(Builder $query): Builder
    {
        return $query->with('person');
    }
}
