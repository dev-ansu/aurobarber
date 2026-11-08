<?php

namespace App\Filament\Resources\Contracts;

use App\Filament\Resources\Contracts\Pages\CreateContracts;
use App\Filament\Resources\Contracts\Pages\EditContracts;
use App\Filament\Resources\Contracts\Pages\ListContracts;
use App\Filament\Resources\Contracts\Pages\ViewContracts;
use App\Filament\Resources\Contracts\RelationManagers\AttendancesRelationManager;
use App\Filament\Resources\Contracts\RelationManagers\ContractHistoriesRelationManager;
use App\Filament\Resources\Contracts\RelationManagers\DebtAgreementsRelationManager;
use App\Filament\Resources\Contracts\RelationManagers\InstallmentsRelationManager;
use App\Filament\Resources\Contracts\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Contracts\RelationManagers\OuroModernoLogsRelationManager;
use App\Filament\Resources\Contracts\RelationManagers\StudentSchedulesRelationManager;
use App\Filament\Resources\Contracts\Schemas\ContractsForm;
use App\Filament\Resources\Contracts\Schemas\ContractsInfolist;
use App\Filament\Resources\Contracts\Tables\ContractsTable;
use App\Models\Contract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ContractsResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?string $modelLabel = 'Contrato';

    protected static ?string $pluralModelLabel = 'Contratos';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'student.name';

    public static function form(Schema $schema): Schema
    {
        return ContractsForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContractsInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContractsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InstallmentsRelationManager::class,
            ItemsRelationManager::class,
            StudentSchedulesRelationManager::class,
            AttendancesRelationManager::class,
            ContractHistoriesRelationManager::class,
            DebtAgreementsRelationManager::class,
            OuroModernoLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContracts::route('/'),
            'create' => CreateContracts::route('/create'),
            'view' => ViewContracts::route('/{record}'),
            'edit' => EditContracts::route('/{record}/edit'),
        ];
    }
}
