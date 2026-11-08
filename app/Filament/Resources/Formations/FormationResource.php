<?php

namespace App\Filament\Resources\Formations;

use App\Filament\Resources\Formations\Pages\CreateFormation;
use App\Filament\Resources\Formations\Pages\EditFormation;
use App\Filament\Resources\Formations\Pages\ListFormations;
use App\Filament\Resources\Formations\Pages\ViewFormation;
use App\Filament\Resources\Formations\RelationManagers\CoursesRelationManager;
use App\Filament\Resources\Formations\Schemas\FormationForm;
use App\Filament\Resources\Formations\Schemas\FormationInfolist;
use App\Filament\Resources\Formations\Tables\FormationsTable;
use App\Models\Formation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FormationResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = Formation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Pedagógico';

    protected static ?string $modelLabel = 'formação';

    protected static ?string $pluralModelLabel = 'formações';

    protected static ?string $slug = 'formation';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Formações';

    public static function form(Schema $schema): Schema
    {
        return FormationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FormationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FormationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CoursesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormations::route('/'),
            'create' => CreateFormation::route('/create'),
            'view' => ViewFormation::route('/{record}'),
            'edit' => EditFormation::route('/{record}/edit'),
        ];
    }
}
