<?php

declare(strict_types=1);

namespace App\Filament\Resources\TypingExercises;

use App\Filament\Resources\TypingExercises\Pages\CreateTypingExercise;
use App\Filament\Resources\TypingExercises\Pages\EditTypingExercise;
use App\Filament\Resources\TypingExercises\Pages\ListTypingExercises;
use App\Filament\Resources\TypingExercises\Schemas\TypingExerciseForm;
use App\Filament\Resources\TypingExercises\Tables\TypingExercisesTable;
use App\Models\TypingExercise;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class TypingExerciseResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = TypingExercise::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|UnitEnum|null $navigationGroup = 'Pedagógico';

    protected static ?string $modelLabel = 'exercício de digitação';

    protected static ?string $pluralModelLabel = 'exercícios de digitação';

    protected static ?string $slug = 'exercicios-digitacao';

    protected static ?int $navigationSort = 55;

    protected static ?string $navigationLabel = 'Treino de Digitação';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && (
            $user->hasRole('super_admin')
            || $user->can('ViewAny:TypingExercise')
            || $user->can('view_any_typing_exercise')
            || $user->can('View:TypingExercise')
        );
    }

    public static function form(Schema $schema): Schema
    {
        return TypingExerciseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TypingExercisesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTypingExercises::route('/'),
            'create' => CreateTypingExercise::route('/create'),
            'edit' => EditTypingExercise::route('/{record}/edit'),
        ];
    }
}
