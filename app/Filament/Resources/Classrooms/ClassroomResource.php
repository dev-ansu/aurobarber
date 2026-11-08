<?php

namespace App\Filament\Resources\Classrooms;

use App\Actions\ToggleStatusAction;
use App\Filament\Exports\ClassroomExporter;
use App\Filament\Resources\Classrooms\Pages\ManageClassrooms;
use App\Filters\ActiveStatusFilter;
use App\Models\Classroom;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ClassroomResource extends Resource
{
    protected static ?string $model = Classroom::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Administrativo';

    protected static ?string $navigationLabel = 'Salas de aula';

    protected static ?string $pluralModelLabel = 'Salas de aula';

    protected static ?string $modelLabel = 'sala de aula';

    protected static ?string $slug = 'classrooms';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação e capacidade')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')->label('Nome da sala')->maxLength(100)->required(),
                            TextInput::make('capacity')->label('Capacidade de alunos')->numeric()->minValue(1)->required(),
                            Textarea::make('description')->label('Observações')->rows(3)->columnSpanFull(),
                            Toggle::make('active')->label('Sala ativa')->default(true)->required(),
                        ]),
                    ])
                    ->columnSpanFull(),
                Section::make('Funcionamento da sala')
                    ->description('Defina o intervalo semanal de funcionamento. Os horários devem começar em horas cheias.')
                    ->schema([
                        Grid::make(2)->schema([
                            TimePicker::make('opening_time')
                                ->label('Abre às')
                                ->default('08:00')
                                ->native(false)
                                ->hoursStep(1)
                                ->minutesStep(60)
                                ->seconds(false)
                                ->required(),
                            TimePicker::make('closing_time')
                                ->label('Fecha às')
                                ->default('23:00')
                                ->native(false)
                                ->hoursStep(1)
                                ->minutesStep(60)
                                ->seconds(false)
                                ->after('opening_time')
                                ->required(),
                        ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->tooltip(fn (Classroom $record) => $record->description ?? '')
                    ->searchable(),
                TextColumn::make('capacity')
                    ->label('Capacidade')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('operating_hours')
                    ->label('Funcionamento')
                    ->state(fn (Classroom $record): string => sprintf(
                        '%s às %s',
                        Carbon::parse($record->opening_time)->format('H:i'),
                        Carbon::parse($record->closing_time)->format('H:i'),
                    )),
                IconColumn::make('active')
                    ->tooltip('Ativo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                ActiveStatusFilter::make('active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                ToggleStatusAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->label('Ativar selecionados')
                        ->action(fn (Collection $records) => $records->each->update(['active' => true]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->label('Desativar selecionados')
                        ->action(fn (Collection $records) => $records->each->update(['active' => false]))
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar salas')->exporter(ClassroomExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Classroom') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Classroom') ?? false),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClassrooms::route('/'),
        ];
    }
}
