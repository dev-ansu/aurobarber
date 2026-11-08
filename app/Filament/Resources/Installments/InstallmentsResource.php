<?php

namespace App\Filament\Resources\Installments;

use App\Filament\Resources\Installments\Pages\CreateInstallments;
use App\Filament\Resources\Installments\Pages\EditInstallments;
use App\Filament\Resources\Installments\Pages\ListInstallments;
use App\Filament\Resources\Installments\Pages\ViewInstallments;
use App\Filament\Resources\Installments\Schemas\InstallmentsForm;
use App\Filament\Resources\Installments\Schemas\InstallmentsInfolist;
use App\Filament\Resources\Installments\Tables\InstallmentsTable;
use App\Models\Installment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class InstallmentsResource extends Resource
{
    protected static ?string $model = Installment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $modelLabel = 'Parcela / Cobrança';

    protected static ?string $pluralModelLabel = 'Parcelas e Cobranças';

    protected static ?string $recordTitleAttribute = 'contract.student.name';

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return $record?->student_name ?? $record?->contract?->student?->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['contract.student.name'];
    }

    public static function form(Schema $schema): Schema
    {
        return InstallmentsForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InstallmentsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstallmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstallments::route('/'),
            'create' => CreateInstallments::route('/create'),
            'view' => ViewInstallments::route('/{record}'),
            'edit' => EditInstallments::route('/{record}/edit'),
        ];
    }
}
