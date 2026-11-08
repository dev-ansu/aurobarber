<?php

namespace App\Filament\Resources\People;

use App\Filament\Resources\People\Pages\CreatePerson;
use App\Filament\Resources\People\Pages\EditPerson;
use App\Filament\Resources\People\Pages\ListPeople;
use App\Filament\Resources\People\Pages\ViewPerson;
use App\Filament\Resources\People\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\People\RelationManagers\ContactsRelationManager;
use App\Filament\Resources\People\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\People\Schemas\PersonForm;
use App\Filament\Resources\People\Schemas\PersonInfolist;
use App\Filament\Resources\People\Tables\PeopleTable;
use App\Models\Person;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PersonResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = Person::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Administrativo';

    protected static ?string $navigationLabel = 'Pessoas';

    protected static ?string $pluralModelLabel = 'Pessoas';

    protected static ?string $modelLabel = 'pessoa';

    protected static ?string $slug = 'people';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return PersonForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PersonInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PeopleTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $tenant = \Filament\Facades\Filament::getTenant();

        if ($tenant) {
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($tenant) {
                $q->whereHas('schools', fn (\Illuminate\Database\Eloquent\Builder $sq) => $sq->whereKey($tenant->getKey()))
                    ->orWhereHas('contracts', fn (\Illuminate\Database\Eloquent\Builder $cq) => $cq->where('school_id', $tenant->getKey()))
                    ->orWhereHas('responsibles.contracts', fn (\Illuminate\Database\Eloquent\Builder $cq) => $cq->where('school_id', $tenant->getKey()));
            });
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            ContactsRelationManager::class,
            DocumentsRelationManager::class,
            AddressesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPeople::route('/'),
            'create' => CreatePerson::route('/create'),
            'view' => ViewPerson::route('/{record}'),
            'edit' => EditPerson::route('/{record}/edit'),
        ];
    }
}
