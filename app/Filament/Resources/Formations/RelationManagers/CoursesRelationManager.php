<?php

namespace App\Filament\Resources\Formations\RelationManagers;

use BackedEnum;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CoursesRelationManager extends RelationManager
{
    protected static string $relationship = 'courses';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $title = 'Cursos';

    protected static ?string $label = 'Curso';

    protected static ?string $pluralLabel = 'Cursos';

    protected static ?string $modelLabel = 'curso';

    protected static ?string $pluralModelLabel = 'cursos';

    protected static ?string $recordTitle = 'name';

    protected static ?string $slug = 'courses';

    protected static BackedEnum|string|null $icon = Heroicon::OutlinedBookOpen;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->label('Curso')
                    ->relationship('courses', 'name')
                    ->searchable()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('formation_course.order')
            ->columns([
                TextColumn::make('pivot.order')
                    ->label('Ordem')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Curso')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('workload')
                    ->label('Carga Horária')
                    ->suffix('h')
                    ->alignCenter(),
                IconColumn::make('active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        \Filament\Forms\Components\TextInput::make('order')
                            ->label('Ordem na Formação')
                            ->numeric()
                            ->default(function (RelationManager $livewire): int {
                                $formation = $livewire->getOwnerRecord();

                                return (int) (\Illuminate\Support\Facades\DB::table('formation_course')->where('formation_id', $formation->id)->max('order') ?? 0) + 1;
                            })
                            ->required(),
                    ]),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('moveUp')
                    ->label('Subir')
                    ->icon('heroicon-m-arrow-up')
                    ->color('gray')
                    ->action(function (\App\Models\Course $record, RelationManager $livewire): void {
                        $formation = $livewire->getOwnerRecord();
                        $currentOrder = (int) ($record->pivot?->order ?? 1);

                        // Localiza o curso imediatamente anterior
                        $previous = \Illuminate\Support\Facades\DB::table('formation_course')
                            ->where('formation_id', $formation->id)
                            ->where('order', '<', $currentOrder)
                            ->orderByDesc('order')
                            ->first();

                        if ($previous) {
                            \Illuminate\Support\Facades\DB::table('formation_course')
                                ->where('formation_id', $formation->id)
                                ->where('course_id', $record->id)
                                ->update(['order' => $previous->order]);

                            \Illuminate\Support\Facades\DB::table('formation_course')
                                ->where('formation_id', $formation->id)
                                ->where('course_id', $previous->course_id)
                                ->update(['order' => $currentOrder]);
                        }
                    }),

                \Filament\Actions\Action::make('moveDown')
                    ->label('Descer')
                    ->icon('heroicon-m-arrow-down')
                    ->color('gray')
                    ->action(function (\App\Models\Course $record, RelationManager $livewire): void {
                        $formation = $livewire->getOwnerRecord();
                        $currentOrder = (int) ($record->pivot?->order ?? 1);

                        // Localiza o curso imediatamente posterior
                        $next = \Illuminate\Support\Facades\DB::table('formation_course')
                            ->where('formation_id', $formation->id)
                            ->where('order', '>', $currentOrder)
                            ->orderBy('order')
                            ->first();

                        if ($next) {
                            \Illuminate\Support\Facades\DB::table('formation_course')
                                ->where('formation_id', $formation->id)
                                ->where('course_id', $record->id)
                                ->update(['order' => $next->order]);

                            \Illuminate\Support\Facades\DB::table('formation_course')
                                ->where('formation_id', $formation->id)
                                ->where('course_id', $next->course_id)
                                ->update(['order' => $currentOrder]);
                        }
                    }),

                \Filament\Actions\Action::make('setOrder')
                    ->label('Definir Ordem')
                    ->icon('heroicon-m-numbered-list')
                    ->color('gray')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('order')
                            ->label('Posição da Trilha (1, 2, 3...)')
                            ->numeric()
                            ->required(),
                    ])
                    ->fillForm(fn (\App\Models\Course $record): array => [
                        'order' => $record->pivot?->order ?? 1,
                    ])
                    ->action(function (\App\Models\Course $record, array $data, RelationManager $livewire): void {
                        $formation = $livewire->getOwnerRecord();
                        \Illuminate\Support\Facades\DB::table('formation_course')
                            ->where('formation_id', $formation->id)
                            ->where('course_id', $record->id)
                            ->update(['order' => (int) $data['order']]);
                    }),

                \Filament\Actions\Action::make('editDescription')
                    ->label('Ementa')
                    ->tooltip('Editar descrição/ementa do módulo para a vitrine')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Textarea::make('description')
                            ->label('Descrição / Ementa do Módulo')
                            ->placeholder('Ex: Neste módulo o aluno aprende a dominar fórmulas, tabelas dinâmicas e relatórios práticos...')
                            ->rows(4)
                            ->maxLength(2000)
                            ->helperText('Opcional. Se preenchido, esta descrição aparecerá na Landing Page da formação para os futuros alunos.'),
                    ])
                    ->fillForm(fn (\App\Models\Course $record): array => [
                        'description' => $record->description,
                    ])
                    ->action(function (\App\Models\Course $record, array $data): void {
                        $record->update(['description' => $data['description']]);
                        \Filament\Notifications\Notification::make()
                            ->title('Ementa do módulo atualizada!')
                            ->success()
                            ->send();
                    }),

                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
