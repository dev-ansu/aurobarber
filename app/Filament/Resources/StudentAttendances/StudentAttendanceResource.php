<?php

namespace App\Filament\Resources\StudentAttendances;

use App\Actions\GenerateStudentAttendancesForDate;
use App\Filament\Exports\StudentAttendanceExporter;
use App\Filament\Resources\StudentAttendances\Pages\ManageStudentAttendances;
use App\Models\StudentAttendance;
use App\Models\User;
use App\Services\LmsProgressionService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use LogicException;
use UnitEnum;

class StudentAttendanceResource extends Resource
{
    protected static ?string $model = StudentAttendance::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Pedagógico';

    protected static ?string $navigationLabel = 'Chamadas';

    protected static ?string $modelLabel = 'chamada';

    protected static ?string $pluralModelLabel = 'chamadas';

    protected static ?string $slug = 'student-attendances';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('attendance_date', 'desc')
            ->striped()
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('attendance_date')
                    ->label('Data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('session.start_hour')
                    ->label('Início')
                    ->time('H:i')
                    ->sortable(),
                TextColumn::make('session.end_hour')
                    ->label('Término')
                    ->time('H:i'),
                TextColumn::make('studentSchedule.contractItem.contract.student.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('studentSchedule.contractItem.formation.name')
                    ->label('Formação')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('session.classroom.name')
                    ->label('Sala')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Presença')
                    ->formatStateUsing(fn (string $state): string => $state === 'present' ? 'Presente' : 'Falta')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'present' ? 'success' : 'danger'),
                TextColumn::make('markedBy.person.name')
                    ->label('Registrado por')
                    ->placeholder(fn (StudentAttendance $record): string => $record->marking_source === 'kiosk'
                        ? 'Kiosk do aluno'
                        : 'Aguardando confirmação')
                    ->toggleable(),
                TextColumn::make('marked_at')
                    ->label('Registrado em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('marking_source')
                    ->label('Origem')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'student' => 'Aluno',
                        'staff' => 'Equipe',
                        'kiosk' => 'Kiosk do aluno',
                        default => '—',
                    })
                    ->toggleable(),
                TextColumn::make('note')
                    ->label('Observação')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('attendance_period')
                    ->label('Período')
                    ->schema([
                        DatePicker::make('from')->label('De'),
                        DatePicker::make('until')->label('Até'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('attendance_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('attendance_date', '<=', $date))),
                SelectFilter::make('status')
                    ->label('Presença')
                    ->options([
                        'absent' => 'Falta',
                        'present' => 'Presente',
                    ]),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar frequência')->exporter(StudentAttendanceExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:StudentAttendance') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:StudentAttendance') ?? false),
                Action::make('generate_today')
                    ->label('Gerar chamadas de hoje')
                    ->icon(Heroicon::OutlinedBolt)
                    ->authorize(fn (): bool => static::canViewAny())
                    ->action(function (GenerateStudentAttendancesForDate $generator): void {
                        $createdCount = $generator->execute(Carbon::today());

                        Notification::make()
                            ->success()
                            ->title('Chamadas de hoje atualizadas')
                            ->body("Novas chamadas geradas: {$createdCount}.")
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('change_status')
                    ->label('Alterar frequência')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->color('warning')
                    ->authorize(fn (StudentAttendance $record): bool => Auth::user()?->hasRole('super_admin') || Auth::user()?->can('ChangeStatus:StudentAttendance'))
                    ->modalHeading(fn (StudentAttendance $record): string => 'Alterar Frequência — '.($record->studentSchedule?->contractItem?->contract?->student?->name ?? 'Aluno'))
                    ->modalDescription('Alterne entre Presença e Falta com registro de auditoria e justificativa obrigatória.')
                    ->modalSubmitActionLabel('Salvar alteração')
                    ->form([
                        \Filament\Forms\Components\Radio::make('status')
                            ->label('Nova situação')
                            ->options([
                                'present' => 'Presença (P)',
                                'absent' => 'Falta (F)',
                            ])
                            ->default(fn (StudentAttendance $record): string => $record->status === 'present' ? 'absent' : 'present')
                            ->required(),
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('Justificativa / Motivo da alteração')
                            ->placeholder('Ex: Lançamento incorreto, reagendamento de aula, início efetivo postergado, etc.')
                            ->required()
                            ->minLength(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (StudentAttendance $record, array $data): void {
                        $user = Auth::user();
                        if (! $user instanceof User) {
                            throw new LogicException('É necessário estar autenticado para alterar a frequência.');
                        }

                        $record->changeStatus($data['status'], $user, $data['reason']);

                        Notification::make()
                            ->title('Frequência alterada com sucesso!')
                            ->body('A nova situação foi registrada e auditada no sistema.')
                            ->success()
                            ->send();
                    }),
                Action::make('mark_present')
                    ->label('Confirmar presença')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (StudentAttendance $record): bool => $record->status !== 'present')
                    ->authorize(fn (): bool => static::canViewAny())
                    ->action(function (StudentAttendance $record): void {
                        $user = Auth::user();

                        if (! $user instanceof User) {
                            throw new LogicException('É necessário estar autenticado para registrar a presença.');
                        }

                        $record->mark('present', $user, 'staff');
                    })
                    ->successNotificationTitle('Presença registrada'),
                Action::make('mark_absent')
                    ->label('Registrar falta')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (StudentAttendance $record): bool => $record->status === 'present')
                    ->authorize(fn (): bool => static::canViewAny())
                    ->requiresConfirmation()
                    ->action(function (StudentAttendance $record): void {
                        $user = Auth::user();

                        if (! $user instanceof User) {
                            throw new LogicException('É necessário estar autenticado para registrar a falta.');
                        }

                        $record->mark('absent', $user, 'staff');
                    })
                    ->successNotificationTitle('Falta registrada'),
                Action::make('unlock_next_lesson')
                    ->label('Liberar Próxima Aula')
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Liberar Próxima Aula do Aluno')
                    ->modalDescription('Deseja liberar a próxima aula do curso e suspender o teto diário de hoje para este aluno?')
                    ->visible(fn (StudentAttendance $record): bool => (bool) $record->studentSchedule?->contractItem?->contract)
                    ->authorize(fn (): bool => static::canViewAny())
                    ->action(function (StudentAttendance $record, LmsProgressionService $service): void {
                        $contract = $record->studentSchedule?->contractItem?->contract;
                        if (! $contract) {
                            return;
                        }

                        $user = Auth::user();
                        $lesson = $service->unlockNextLessonForContract(
                            $contract,
                            $user instanceof User ? $user : null,
                            'Liberado pelo educador no painel de chamadas'
                        );

                        if ($lesson) {
                            Notification::make()
                                ->title('Aula Liberada!')
                                ->body("A aula '{$lesson->title}' e o limite diário foram liberados com sucesso para o aluno.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Atenção')
                                ->body('Nenhuma aula pendente encontrada ou o aluno já completou toda a grade do curso.')
                                ->warning()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'session.classroom',
            'studentSchedule.contractItem.contract.student',
            'studentSchedule.contractItem.formation',
            'markedBy.person',
        ]);
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->getAllPermissions()->contains('name', 'View:Agenda');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageStudentAttendances::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('ViewAny:StudentAttendance'));
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
