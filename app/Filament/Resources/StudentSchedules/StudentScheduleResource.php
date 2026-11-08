<?php

namespace App\Filament\Resources\StudentSchedules;

use App\Filament\Exports\StudentScheduleExporter;
use App\Filament\Resources\StudentSchedules\Pages\ManageStudentSchedules;
use App\Models\StudentSchedule;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class StudentScheduleResource extends Resource
{
    protected static ?string $model = StudentSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Pedagógico';

    protected static ?string $navigationLabel = 'Horários dos alunos';

    protected static ?string $modelLabel = 'horário';

    protected static ?string $pluralModelLabel = 'grade de horários';

    protected static ?string $slug = 'student-schedules';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->getAllPermissions()->contains('name', 'View:Agenda');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('weekday')
            ->striped()
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('weekday')
                    ->label('Dia')
                    ->badge()
                    ->state(fn (StudentSchedule $record): string => $record->weekdayLabel())
                    ->color(fn (StudentSchedule $record): string => match ($record->weekday) {
                        1, 2, 3, 4, 5 => 'info',
                        6 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('time_slot')
                    ->label('Horário & Turno')
                    ->state(function (StudentSchedule $record): string {
                        $start = \Illuminate\Support\Carbon::parse($record->start_hour)->format('H:i');
                        $end = \Illuminate\Support\Carbon::parse($record->end_hour)->format('H:i');
                        $hour = (int) substr($start, 0, 2);
                        $turno = match (true) {
                            $hour < 12 => 'Manhã',
                            $hour < 18 => 'Tarde',
                            default => 'Noite',
                        };

                        return "{$start} às {$end} • {$turno}";
                    })
                    ->badge()
                    ->color(function (StudentSchedule $record): string {
                        $hour = (int) substr($record->start_hour, 0, 2);

                        return match (true) {
                            $hour < 12 => 'warning',
                            $hour < 18 => 'primary',
                            default => 'indigo',
                        };
                    })
                    ->sortable('start_hour'),

                TextColumn::make('classroom.name')
                    ->label('Sala / Laboratório')
                    ->description(fn (StudentSchedule $record): ?string => $record->classroom?->capacity ? "Capacidade: {$record->classroom->capacity} computadores" : null)
                    ->sortable(),

                TextColumn::make('contractItem.contract.student.name')
                    ->label('Aluno')
                    ->searchable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->description(function (StudentSchedule $record): ?string {
                        $student = $record->contractItem?->contract?->student;

                        return $student?->cpf ? "CPF: {$student->cpf}" : ($student?->email ?? null);
                    })
                    ->sortable(),

                TextColumn::make('contractItem.contract.contract_number')
                    ->label('Matrícula')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contractItem.formation.name')
                    ->label('Formação')
                    ->placeholder('Curso Livre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contractItem.contract.status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'Ativo',
                        'draft' => 'Rascunho',
                        'suspended' => 'Suspenso',
                        'finished' => 'Concluído',
                        'canceled' => 'Cancelado',
                        default => $state ?? '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'warning',
                        'canceled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('next_occurrence')
                    ->label('Próxima Aula')
                    ->state(fn (StudentSchedule $record): string => $record->nextOccurrenceDate()->format('d/m/Y'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('weekday')
                    ->label('Dia da semana')
                    ->options([
                        0 => 'Domingo',
                        1 => 'Segunda-feira',
                        2 => 'Terça-feira',
                        3 => 'Quarta-feira',
                        4 => 'Quinta-feira',
                        5 => 'Sexta-feira',
                        6 => 'Sábado',
                    ]),
                SelectFilter::make('classroom_id')
                    ->label('Sala / Laboratório')
                    ->relationship('classroom', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                \Filament\Actions\Action::make('open_contract')
                    ->label('Abrir Contrato')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->tooltip('Ver os dados completos da matrícula e editar os horários deste aluno')
                    ->url(fn (StudentSchedule $record): ?string => $record->contractItem?->contract_id
                        ? \App\Filament\Resources\Contracts\ContractsResource::getUrl('view', ['record' => $record->contractItem->contract_id])
                        : null)
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Nenhum horário de aula encontrado')
            ->emptyStateDescription('Os horários semanais dos alunos são alocados diretamente dentro de cada Contrato de matrícula.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->headerActions([
                ExportAction::make()->label('Exportar horários')->exporter(StudentScheduleExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:StudentSchedule') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:StudentSchedule') ?? false),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('contractItem', fn (Builder $query): Builder => $query
                ->whereNull('contract_items.deleted_at')
                ->whereNull('formation_completed_at')
                ->whereHas('contract', fn (Builder $contractQuery): Builder => $contractQuery
                    ->whereIn('status', ['draft', 'active'])))
            ->with([
                'classroom',
                'contractItem.formation',
                'contractItem.contract.student',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudentSchedules::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('ViewAny:StudentSchedule'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
