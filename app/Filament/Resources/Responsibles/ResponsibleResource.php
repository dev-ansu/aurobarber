<?php

namespace App\Filament\Resources\Responsibles;

use App\Filament\Resources\Responsibles\Pages\CreateResponsible;
use App\Filament\Resources\Responsibles\Pages\EditResponsible;
use App\Filament\Resources\Responsibles\Pages\ListResponsibles;
use App\Filament\Resources\Responsibles\Pages\ViewResponsible;
use App\Filament\Resources\Responsibles\Schemas\ResponsibleForm;
use App\Filament\Resources\Responsibles\Schemas\ResponsibleInfolist;
use App\Filament\Resources\Responsibles\Tables\ResponsiblesTable;
use App\Models\Responsible;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class ResponsibleResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    public static function canGloballySearch(): bool
    {
        return false;
    }

    protected static ?string $model = Responsible::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $recordTitleAttribute = 'person.name';

    public static function getRecordTitle(?Model $record): string|Htmlable|null
    {
        return $record?->display_name ?? $record?->person?->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['person.name'];
    }

    protected static string|UnitEnum|null $navigationGroup = 'Administrativo';

    protected static ?string $navigationLabel = 'Responsáveis';

    protected static ?string $pluralModelLabel = 'Responsáveis';

    protected static ?string $modelLabel = 'responsável';

    protected static ?string $slug = 'responsibles';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ResponsibleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ResponsibleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResponsiblesTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $tenant = \Filament\Facades\Filament::getTenant();

        if ($tenant) {
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($tenant) {
                $q->whereHas('contracts', fn (\Illuminate\Database\Eloquent\Builder $cq) => $cq->where('school_id', $tenant->getKey()))
                    ->orWhereHas('person.schools', fn (\Illuminate\Database\Eloquent\Builder $sq) => $sq->whereKey($tenant->getKey()))
                    ->orWhereHas('person.contracts', fn (\Illuminate\Database\Eloquent\Builder $cq) => $cq->where('school_id', $tenant->getKey()));
            });
        }

        return $query;
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
            'index' => ListResponsibles::route('/'),
            'create' => CreateResponsible::route('/create'),
            'view' => ViewResponsible::route('/{record}'),
            'edit' => EditResponsible::route('/{record}/edit'),
        ];
    }
}
