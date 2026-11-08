<?php

namespace App\Filament\Resources\CashRegisters;

use App\Filament\Resources\CashRegisters\Pages\CreateCashRegisters;
use App\Filament\Resources\CashRegisters\Pages\EditCashRegisters;
use App\Filament\Resources\CashRegisters\Pages\ListCashRegisters;
use App\Filament\Resources\CashRegisters\Schemas\CashRegistersForm;
use App\Filament\Resources\CashRegisters\Tables\CashRegistersTable;
use App\Models\CashRegister;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CashRegistersResource extends Resource
{
    protected static ?string $model = CashRegister::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $modelLabel = 'Caixa Diário';

    protected static ?string $pluralModelLabel = 'Caixas Diários';

    public static function form(Schema $schema): Schema
    {
        return CashRegistersForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashRegistersTable::configure($table);
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
            'index' => ListCashRegisters::route('/'),
            'create' => CreateCashRegisters::route('/create'),
            'edit' => EditCashRegisters::route('/{record}/edit'),
        ];
    }
}
