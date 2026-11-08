<?php

namespace App\Filament\Resources\CashTransactions;

use App\Filament\Resources\CashTransactions\Pages\CreateCashTransactions;
use App\Filament\Resources\CashTransactions\Pages\EditCashTransactions;
use App\Filament\Resources\CashTransactions\Pages\ListCashTransactions;
use App\Filament\Resources\CashTransactions\RelationManagers\AuditLogsRelationManager;
use App\Filament\Resources\CashTransactions\Schemas\CashTransactionsForm;
use App\Filament\Resources\CashTransactions\Tables\CashTransactionsTable;
use App\Models\CashTransaction;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CashTransactionsResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        $tenantId = Filament::getTenant()?->id;

        return parent::getEloquentQuery()
            ->when($tenantId, fn (Builder $query) => $query->whereHas(
                'register',
                fn (Builder $cq) => $cq->withoutGlobalScopes()->where('school_id', $tenantId)
            ));
    }

    protected static ?string $model = CashTransaction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    // Organiza este recurso dentro do mesmo menu que o Caixa Diário
    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    // Tradução dos rótulos internos do recurso
    protected static ?string $modelLabel = 'Movimentação de Caixa';

    protected static ?string $pluralModelLabel = 'Movimentações de Caixa';

    protected static ?string $recordTitleAttribute = 'description';

    public static function form(Schema $schema): Schema
    {
        return CashTransactionsForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashTransactionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AuditLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashTransactions::route('/'),
            'create' => CreateCashTransactions::route('/create'),
            'edit' => EditCashTransactions::route('/{record}/edit'),
        ];
    }
}
