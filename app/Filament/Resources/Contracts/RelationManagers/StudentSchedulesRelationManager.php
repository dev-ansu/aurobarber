<?php

namespace App\Filament\Resources\Contracts\RelationManagers;

use App\Models\Classroom;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\StudentSchedule;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StudentSchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'Horários semanais';

    protected static ?string $modelLabel = 'horário';

    protected static ?string $pluralModelLabel = 'horários semanais';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Agendar uma aula')
                    ->description('Cada registro corresponde a um bloco de uma hora. Para aulas consecutivas, adicione um horário para cada bloco.')
                    ->schema([
                        Select::make('contract_item_id')
                            ->label('Formação')
                            ->options(fn (): array => $this->formationOptions())
                            ->rules(fn (): array => $this->contractItemRules())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set): void {
                                $set('weekday', null);
                                $set('classroom_id', null);
                                $set('start_hour', null);
                                $set('end_hour', null);
                            }),
                        Grid::make(2)
                            ->schema([
                                Select::make('weekday')
                                    ->label('Dia da semana')
                                    ->options([
                                        0 => 'Domingo',
                                        1 => 'Segunda-feira',
                                        2 => 'Terça-feira',
                                        3 => 'Quarta-feira',
                                        4 => 'Quinta-feira',
                                        5 => 'Sexta-feira',
                                        6 => 'Sábado',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('classroom_id', null);
                                        $set('start_hour', null);
                                        $set('end_hour', null);
                                    })
                                    ->required(),
                                Select::make('classroom_id')
                                    ->label('Sala com vaga')
                                    ->options(fn (Get $get, ?StudentSchedule $record): array => $this->classroomOptions($get, $record))
                                    ->searchable()
                                    ->disabled(fn (Get $get): bool => blank($get('weekday')))
                                    ->helperText(fn (Get $get, ?StudentSchedule $record): ?string => $this->classroomHelperText($get, $record))
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('start_hour', null);
                                        $set('end_hour', null);
                                    })
                                    ->required(),
                                Select::make('start_hour')
                                    ->label('Bloco de uma hora')
                                    ->options(fn (Get $get, ?StudentSchedule $record): array => $this->slotOptions($get, $record))
                                    ->placeholder('Selecione um horário disponível')
                                    ->helperText('Para adicionar mais uma hora, salve este horário e adicione o próximo bloco.')
                                    ->disabled(fn (Get $get): bool => blank($get('classroom_id')))
                                    ->live()
                                    ->afterStateUpdated(function (?string $state, Set $set): void {
                                        $set(
                                            'end_hour',
                                            $state ? Carbon::parse($state)->addHour()->format('H:i:s') : null,
                                        );
                                    })
                                    ->required(),
                                Hidden::make('end_hour')
                                    ->required(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('weekday')
            ->striped()
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'classroom:id,name',
                'contractItem:id,contract_id,formation_id',
                'contractItem.formation:id,name',
            ]))
            ->columns([
                TextColumn::make('contractItem.formation.name')
                    ->label('Formação')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('weekday')
                    ->label('Dia')
                    ->state(fn (StudentSchedule $record): string => $record->weekdayLabel())
                    ->sortable(),
                TextColumn::make('start_hour')
                    ->label('Horário')
                    ->state(fn (StudentSchedule $record): string => sprintf(
                        '%s–%s',
                        $record->formattedStartHour(),
                        $record->formattedEndHour(),
                    ))
                    ->sortable(),
                TextColumn::make('classroom.name')
                    ->label('Sala')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('batchSchedule')
                    ->label('Agendamentos (Múltiplos Horários)')
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary')
                    ->modalHeading('Agendamentos de Aulas')
                    ->modalDescription('Selecione a formação, o laboratório, os dias da semana e os horários das aulas para gerar a grade semanal do aluno.')
                    ->modalWidth('4xl')
                    ->form([
                        Section::make('Identificação e Laboratório')->schema([
                            Grid::make(2)->schema([
                                Select::make('contract_item_id')
                                    ->label('Formação')
                                    ->options(fn (): array => $this->formationOptions())
                                    ->default(fn (): ?int => count($this->formationOptions()) === 1 ? (int) array_key_first($this->formationOptions()) : null)
                                    ->rules(fn (): array => $this->contractItemRules())
                                    ->searchable()
                                    ->required(),

                                Select::make('classroom_id')
                                    ->label('Laboratório / Sala')
                                    ->options(fn (): array => Classroom::query()->where('active', true)->orderBy('name')->pluck('name', 'id')->all())
                                    ->default(fn (): ?int => Classroom::query()->where('active', true)->orderBy('name')->value('id'))
                                    ->required()
                                    ->live(),
                            ]),
                        ]),

                        Grid::make(12)->schema([
                            Section::make('Dia da semana')
                                ->schema([
                                    CheckboxList::make('weekdays')
                                        ->hiddenLabel()
                                        ->options([
                                            0 => 'Domingo',
                                            1 => 'Segunda-feira',
                                            2 => 'Terça-feira',
                                            3 => 'Quarta-feira',
                                            4 => 'Quinta-feira',
                                            5 => 'Sexta-feira',
                                            6 => 'Sábado',
                                        ])
                                        ->columns(1)
                                        ->required()
                                        ->live(),
                                ])
                                ->columnSpan(['default' => 12, 'md' => 4]),

                            Section::make('Horários:')
                                ->description('Clique para marcar os blocos de 1 hora desejados')
                                ->schema([
                                    ToggleButtons::make('hours')
                                        ->hiddenLabel()
                                        ->multiple()
                                        ->options(function (Get $get): array {
                                            $classroomId = $get('classroom_id');
                                            $openingHour = 8;
                                            $closingHour = 20;

                                            if ($classroomId) {
                                                $classroom = Classroom::find($classroomId);
                                                if ($classroom && $classroom->opening_time && $classroom->closing_time) {
                                                    $openingHour = Carbon::parse($classroom->opening_time)->hour;
                                                    $closingHour = Carbon::parse($classroom->closing_time)->hour;
                                                }
                                            }

                                            $options = [];
                                            for ($h = $openingHour; $h < $closingHour; $h++) {
                                                $timeKey = sprintf('%02d:00:00', $h);
                                                $options[$timeKey] = $timeKey;
                                            }

                                            return $options;
                                        })
                                        ->columns(['default' => 2, 'sm' => 3, 'md' => 4, 'lg' => 6])
                                        ->required()
                                        ->live()
                                        ->helperText(function (Get $get): string {
                                            $classroomId = $get('classroom_id');
                                            if (! $classroomId) {
                                                return 'Selecione uma sala para consultar vagas e funcionamento.';
                                            }
                                            $classroom = Classroom::find($classroomId);

                                            return $classroom
                                                ? "Sala {$classroom->name} • Capacidade: {$classroom->capacity} alunos • Funcionamento: " . Carbon::parse($classroom->opening_time)->format('H:i') . ' às ' . Carbon::parse($classroom->closing_time)->format('H:i')
                                                : '';
                                        }),
                                ])
                                ->columnSpan(['default' => 12, 'md' => 8]),
                        ]),
                    ])
                    ->action(function (array $data): void {
                        $contract = $this->getOwnerRecord();
                        if (! $contract instanceof Contract) {
                            return;
                        }

                        $contractItemId = (int) $data['contract_item_id'];
                        $classroomId = (int) $data['classroom_id'];
                        $weekdays = array_map('intval', (array) ($data['weekdays'] ?? []));
                        $hours = (array) ($data['hours'] ?? []);

                        $classroom = Classroom::find($classroomId);
                        if (! $classroom) {
                            Notification::make()->title('Sala inválida')->danger()->send();

                            return;
                        }

                        $weekdayNames = [
                            0 => 'Domingo',
                            1 => 'Segunda-feira',
                            2 => 'Terça-feira',
                            3 => 'Quarta-feira',
                            4 => 'Quinta-feira',
                            5 => 'Sexta-feira',
                            6 => 'Sábado',
                        ];

                        $createdCount = 0;
                        $skipped = [];

                        foreach ($weekdays as $weekday) {
                            $dayName = $weekdayNames[$weekday] ?? "Dia {$weekday}";
                            $availableSlots = StudentSchedule::availableSlotsByClassroom(
                                $weekday,
                                $contract->student_id,
                                null,
                                $classroom->school_id ?? $contract->school_id,
                            );
                            $roomSlots = $availableSlots[$classroom->id] ?? [];

                            foreach ($hours as $startHour) {
                                $startHourDb = Carbon::parse($startHour)->format('H:i:s');
                                $endHourDb = Carbon::parse($startHour)->addHour()->format('H:i:s');
                                $formattedHour = Carbon::parse($startHour)->format('H:i');

                                // Verifica se o aluno já tem este horário agendado
                                $alreadyHas = StudentSchedule::query()
                                    ->where('weekday', $weekday)
                                    ->where('start_hour', $startHourDb)
                                    ->where('end_hour', $endHourDb)
                                    ->whereHas('contractItem.contract', fn ($q) => $q->where('student_id', $contract->student_id)->whereIn('status', ['draft', 'active']))
                                    ->exists();

                                if ($alreadyHas) {
                                    $skipped[] = "{$dayName} às {$formattedHour} (já agendado)";
                                    continue;
                                }

                                // Verifica disponibilidade de vagas na sala
                                if (! array_key_exists($startHourDb, $roomSlots)) {
                                    $skipped[] = "{$dayName} às {$formattedHour} (sem vagas)";
                                    continue;
                                }

                                try {
                                    StudentSchedule::create([
                                        'contract_item_id' => $contractItemId,
                                        'classroom_id' => $classroomId,
                                        'weekday' => $weekday,
                                        'start_hour' => $startHourDb,
                                        'end_hour' => $endHourDb,
                                    ]);
                                    $createdCount++;
                                } catch (\Throwable $e) {
                                    $skipped[] = "{$dayName} às {$formattedHour} ({$e->getMessage()})";
                                }
                            }
                        }

                        if ($createdCount > 0) {
                            $body = "{$createdCount} horário(s) semanal(is) cadastrado(s) com sucesso na sala {$classroom->name}!";
                            if (! empty($skipped)) {
                                $body .= "\nNão agendados: " . implode(', ', $skipped);
                                Notification::make()
                                    ->title('Agendamentos parciais concluídos')
                                    ->body($body)
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Agendamentos concluídos!')
                                    ->body($body)
                                    ->success()
                                    ->send();
                            }
                        } else {
                            Notification::make()
                                ->title('Nenhum horário foi agendado')
                                ->body(! empty($skipped) ? 'Motivos: ' . implode(', ', $skipped) : 'Verifique conflitos e disponibilidade de vagas.')
                                ->danger()
                                ->send();
                        }
                    }),

                CreateAction::make()
                    ->label('Horário individual')
                    ->modalHeading('Agendar horário semanal')
                    ->modalWidth('3xl')
                    ->color('gray')
                    ->createAnother(false),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar horário')
                    ->modalHeading('Editar horário semanal')
                    ->modalWidth('3xl'),
                DeleteAction::make()
                    ->label('Arquivar horário'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Arquivar selecionados'),
                ]),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private function formationOptions(): array
    {
        $contract = $this->getOwnerRecord();

        if (! $contract instanceof Contract) {
            return [];
        }

        return $contract->items()
            ->with('formation:id,name')
            ->whereNull('formation_completed_at')
            ->get(['id', 'formation_id'])
            ->mapWithKeys(fn ($item): array => [$item->id => $item->formation->name])
            ->all();
    }

    /**
     * @return array<int, Exists|string>
     */
    private function contractItemRules(): array
    {
        $contract = $this->getOwnerRecord();

        if (! $contract instanceof Contract) {
            return ['prohibited'];
        }

        return [
            Rule::exists((new ContractItem)->getTable(), 'id')
                ->where('contract_id', $contract->getKey())
                ->whereNull('deleted_at')
                ->whereNull('formation_completed_at'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function classroomOptions(Get $get, ?StudentSchedule $record): array
    {
        $slotsByClassroom = $this->availableSlotsByClassroom($get, $record);
        $classroomIds = collect($slotsByClassroom)
            ->filter(fn (array $slots): bool => $slots !== [])
            ->keys();

        if ($classroomIds->isEmpty()) {
            return [];
        }

        return Classroom::query()
            ->whereIn('id', $classroomIds)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    private function classroomHelperText(Get $get, ?StudentSchedule $record): ?string
    {
        if (blank($get('weekday'))) {
            return 'Selecione o dia para ver as salas que têm vaga.';
        }

        return $this->classroomOptions($get, $record) === []
            ? 'Não há salas com horários disponíveis neste dia.'
            : 'A lista mostra salas com pelo menos um bloco livre.';
    }

    /**
     * @return array<string, string>
     */
    private function slotOptions(Get $get, ?StudentSchedule $record): array
    {
        $classroomId = $get('classroom_id');

        if (blank($classroomId)) {
            return [];
        }

        return $this->availableSlotsByClassroom($get, $record)[(int) $classroomId] ?? [];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function availableSlotsByClassroom(Get $get, ?StudentSchedule $record): array
    {
        $weekday = $get('weekday');
        $contract = $this->getOwnerRecord();

        if (blank($weekday) || ! $contract instanceof Contract) {
            return [];
        }

        return StudentSchedule::availableSlotsByClassroom(
            (int) $weekday,
            $contract->student_id,
            $record?->getKey(),
            $contract->school_id,
        );
    }
}
