<?php

namespace App\Filament\Resources\ContractTypes;

use App\Filament\Resources\ContractTypes\Pages\CreateContractType;
use App\Filament\Resources\ContractTypes\Pages\EditContractType;
use App\Filament\Resources\ContractTypes\Pages\ListContractTypes;
use App\Filament\Resources\ContractTypes\Schemas\ContractTypeForm;
use App\Filament\Resources\ContractTypes\Tables\ContractTypesTable;
use App\Models\ContractType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ContractTypeResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = ContractType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Modelo de Documento';

    protected static ?string $pluralModelLabel = 'Modelos de Documentos';

    protected static ?string $navigationLabel = 'Modelos de Documentos';

    protected static string|UnitEnum|null $navigationGroup = 'Escola';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return ContractTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContractTypesTable::configure($table);
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
            'index' => ListContractTypes::route('/'),
            'create' => CreateContractType::route('/create'),
            'edit' => EditContractType::route('/{record}/edit'),
        ];
    }
}
