<?php

namespace App\Filament\Resources\Schools;

use App\Filament\Resources\Schools\Pages\CreateSchool;
use App\Filament\Resources\Schools\Pages\EditSchool;
use App\Filament\Resources\Schools\Pages\ListSchools;
use App\Filament\Resources\Schools\RelationManagers\PartnersRelationManager;
use App\Filament\Resources\Schools\Schemas\SchoolForm;
use App\Filament\Resources\Schools\Tables\SchoolsTable;
use App\Models\School;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class SchoolResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = School::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Escola';

    protected static ?string $navigationLabel = 'Unidades Escolares';

    protected static ?string $modelLabel = 'Unidade Escolar';

    protected static ?string $pluralModelLabel = 'Unidades Escolares';

    protected static ?string $slug = 'escolas';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return auth()->check() && (auth()->user()?->can('Create:School') || auth()->user()?->hasRole('super_admin'));
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return SchoolForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PartnersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchools::route('/'),
            'create' => CreateSchool::route('/create'),
            'edit' => EditSchool::route('/{record}/edit'),
        ];
    }
}
