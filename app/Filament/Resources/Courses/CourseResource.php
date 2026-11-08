<?php

namespace App\Filament\Resources\Courses;

use App\Filament\Resources\Courses\Pages\CreateCourse;
use App\Filament\Resources\Courses\Pages\EditCourse;
use App\Filament\Resources\Courses\Pages\ListCourses;
use App\Filament\Resources\Courses\Pages\ViewCourse;
use App\Filament\Resources\Courses\Schemas\CourseForm;
use App\Filament\Resources\Courses\Schemas\CourseInfolist;
use App\Filament\Resources\Courses\Tables\CoursesTable;
use App\Models\Course;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CourseResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = Course::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Pedagógico';

    protected static ?string $modelLabel = 'curso';

    protected static ?string $pluralModelLabel = 'cursos';

    protected static ?string $slug = 'course';

    protected static ?int $navigationSort = 50;

    protected static ?string $navigationLabel = 'Cursos';

    public static function form(Schema $schema): Schema
    {
        return CourseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CourseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CoursesTable::configure($table);
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
            'index' => ListCourses::route('/'),
            'create' => CreateCourse::route('/create'),
            'view' => ViewCourse::route('/{record}'),
            'edit' => EditCourse::route('/{record}/edit'),
        ];
    }
}
