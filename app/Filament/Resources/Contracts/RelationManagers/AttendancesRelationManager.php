<?php

namespace App\Filament\Resources\Contracts\RelationManagers;

use App\Filament\Exports\StudentAttendanceExporter;
use App\Models\Contract;
use App\Models\StudentAttendance;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Presenças e faltas';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Contract && auth()->check();
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        if (! $ownerRecord instanceof Contract) {
            return null;
        }

        $count = StudentAttendance::query()
            ->whereHas(
                'studentSchedule.contractItem',
                fn (Builder $query): Builder => $query->where('contract_id', $ownerRecord->getKey()),
            )
            ->count();

        return (string) $count;
    }

    public function getRelationship(): Relation|Builder
    {
        return $this->getOwnerRecord()->items();
    }

    protected function getContractAttendancesQuery(): Builder
    {
        return StudentAttendance::query()
            ->whereHas(
                'studentSchedule.contractItem',
                fn (Builder $query): Builder => $query->where('contract_id', $this->getOwnerRecord()->getKey()),
            )
            ->with([
                'session.classroom',
                'studentSchedule.contractItem.contract.student',
                'studentSchedule.contractItem.formation',
                'markedBy.person',
            ]);
    }

    protected function getAttendanceSummaryDescription(): string
    {
        $baseQuery = StudentAttendance::query()
            ->whereHas(
                'studentSchedule.contractItem',
                fn (Builder $query): Builder => $query->where('contract_id', $this->getOwnerRecord()->getKey()),
            );

        $total = (clone $baseQuery)->count();
        if ($total === 0) {
            return 'Nenhum registro de chamada para este contrato até o momento.';
        }

        $present = (clone $baseQuery)->where('status', 'present')->count();
        $absent = $total - $present;
        $rate = round(($present / $total) * 100, 1);

        return "Total de aulas: {$total} • Presenças: {$present} • Faltas: {$absent} • Taxa de frequência: {$rate}%";
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Histórico de Presenças e Faltas')
            ->description(fn (): string => $this->getAttendanceSummaryDescription())
            ->defaultSort('attendance_date', 'desc')
            ->striped()
            ->query(fn (): Builder => $this->getContractAttendancesQuery())
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
                TextColumn::make('studentSchedule.contractItem.formation.name')
                    ->label('Formação')
                    ->searchable(),
                TextColumn::make('session.classroom.name')
                    ->label('Sala')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Presença')
                    ->formatStateUsing(fn (string $state): string => $state === 'present' ? 'Presente' : 'Falta')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'present' ? 'success' : 'danger'),
                TextColumn::make('markedBy.name')
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
                TextColumn::make('note')
                    ->label('Observação')
                    ->placeholder('—')
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Situação')
                    ->options([
                        'present' => 'Presente',
                        'absent' => 'Falta',
                    ]),
                Filter::make('attendance_period')
                    ->label('Período')
                    ->schema([
                        DatePicker::make('from')->label('Data inicial'),
                        DatePicker::make('until')->label('Data final'),
                    ])
                    ->columns(2)
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('attendance_date', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('attendance_date', '<=', $date))),
            ])
            ->headerActions([
                ExportAction::make('export')
                    ->label('Exportar Excel / CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->exporter(StudentAttendanceExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereHas(
                        'studentSchedule.contractItem',
                        fn (Builder $q): Builder => $q->where('contract_id', $this->getOwnerRecord()->getKey()),
                    )),
                Action::make('printAttendanceReport')
                    ->label('Imprimir PDF / Relatório')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->url(fn (): string => route('contracts.attendance-report', $this->getOwnerRecord()))
                    ->openUrlInNewTab(),
            ])
            ->recordActions([
                Action::make('change_status')
                    ->label('Alterar frequência')
                    ->icon(Heroicon::OutlinedArrowsRightLeft)
                    ->color('warning')
                    ->authorize(fn (StudentAttendance $record): bool => auth()->user()?->hasRole('super_admin') || auth()->user()?->can('ChangeStatus:StudentAttendance'))
                    ->modalHeading(fn (StudentAttendance $record): string => 'Alterar Frequência — '.($record->studentSchedule?->contractItem?->contract?->student?->name ?? 'Aluno'))
                    ->modalDescription('Alterne entre Presença e Falta com registro de auditoria e justificativa obrigatória.')
                    ->modalSubmitActionLabel('Salvar alteração')
                    ->form([
                        Radio::make('status')
                            ->label('Nova situação')
                            ->options([
                                'present' => 'Presença (P)',
                                'absent' => 'Falta (F)',
                            ])
                            ->default(fn (StudentAttendance $record): string => $record->status === 'present' ? 'absent' : 'present')
                            ->required(),
                        Textarea::make('reason')
                            ->label('Justificativa / Motivo da alteração')
                            ->placeholder('Ex: Lançamento incorreto, reagendamento de aula, início efetivo postergado, etc.')
                            ->required()
                            ->minLength(3)
                            ->maxLength(1000),
                    ])
                    ->action(function (StudentAttendance $record, array $data): void {
                        $user = auth()->user();
                        if (! $user instanceof User) {
                            throw new \LogicException('É necessário estar autenticado para alterar a frequência.');
                        }

                        $record->changeStatus($data['status'], $user, $data['reason']);

                        Notification::make()
                            ->title('Frequência alterada com sucesso!')
                            ->body('A nova situação foi registrada e auditada no sistema.')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
