<?php

namespace App\Filament\Resources\Contracts\RelationManagers;

use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Formation;
use App\Services\AurosoftLmsProgressSyncService;
use App\Services\OuroModernoContractSyncService;
use App\Services\OuroModernoProgressSyncService;
use App\Services\OuroModernoService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsRelationManager extends RelationManager
{
    private const LEGACY_STANDALONE_COURSE_FORMATION_DESCRIPTION = 'Formação de compatibilidade criada durante a migração de cursos avulsos.';

    protected static string $relationship = 'items';

    protected static ?string $title = 'Formações contratadas';

    protected static ?string $modelLabel = 'formação contratada';

    protected static ?string $pluralModelLabel = 'formações contratadas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Formação contratada')
                    ->tabs([
                        Tab::make('Formação')
                            ->icon('heroicon-o-academic-cap')
                            ->schema([
                                Section::make('Escolha a formação')
                                    ->description('Selecione a formação que será incluída no contrato.')
                                    ->schema([
                                        Select::make('formation_id')
                                            ->label('Formação')
                                            ->relationship(
                                                'formation',
                                                'name',
                                                modifyQueryUsing: fn (Builder $query): Builder => $query->where('active', true),
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->disabled(fn (?ContractItem $record): bool => $record?->exists ?? false)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set): void {
                                                $courses = Formation::query()
                                                    ->with('courses')
                                                    ->find($state)
                                                    ?->courses
                                                    ->values()
                                                    ->map(fn (Course $course, int $index): array => [
                                                        'course_id' => $course->id,
                                                        'order' => $index + 1,
                                                        'status' => 'not_started',
                                                    ])
                                                    ->all() ?? [];

                                                $set('contractCourses', $courses);
                                            }),
                                    ]),
                            ]),
                        Tab::make('Cursos do aluno')
                            ->icon('heroicon-o-book-open')
                            ->schema([
                                Section::make('Cursos vinculados')
                                    ->description('Os cursos são vinculados individualmente. Arraste ou use as setas para definir a sequência pedagógica.')
                                    ->schema([
                                        Repeater::make('contractCourses')
                                            ->relationship()
                                            ->label('Cursos')
                                            ->orderColumn('order')
                                            ->reorderableWithButtons()
                                            ->reorderableWithDragAndDrop(true)
                                            ->itemNumbers(true)
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): string => filled($state['course_id'] ?? null)
                                                ? (Course::query()->whereKey($state['course_id'])->value('name') ?? 'Curso sem nome')
                                                : 'Novo curso')
                                            ->schema([
                                                Select::make('course_id')
                                                    ->label('Curso')
                                                    ->relationship(
                                                        'course',
                                                        'name',
                                                        modifyQueryUsing: fn (Builder $query): Builder => $query->where('active', true)->orderBy('name'),
                                                    )
                                                    ->getOptionLabelUsing(fn ($value): ?string => filled($value)
                                                        ? Course::query()->whereKey($value)->value('name')
                                                        : null)
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->required()
                                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                                Select::make('status')
                                                    ->label('Situação')
                                                    ->options(function (): array {
                                                        $canComplete = auth()->user()?->hasRole('super_admin') || auth()->user()?->can('Complete:ContractCourseManually');

                                                        return [
                                                            'not_started' => 'Não iniciado',
                                                            'in_progress' => 'Em andamento',
                                                            'completed' => $canComplete ? 'Concluído' : 'Concluído (requer permissão especial)',
                                                        ];
                                                    })
                                                    ->disableOptionWhen(fn (string $value): bool => $value === 'completed' && ! (auth()->user()?->hasRole('super_admin') || auth()->user()?->can('Complete:ContractCourseManually')))
                                                    ->default('not_started')
                                                    ->required()
                                                    ->live()
                                                    ->helperText(fn (Get $get): ?string => $get('status') === 'completed'
                                                        ? '⚠️ Atenção: A conclusão manual consolida notas e gera histórico de formatura para boletim e certificado.'
                                                        : null),
                                                Toggle::make('is_unlocked')
                                                    ->label('Liberar acesso (ignorar pré-requisitos)')
                                                    ->helperText('Permite ao aluno cursar no LMS Aurosoft sem depender da conclusão de cursos anteriores (ex: Ouro Moderno).')
                                                    ->default(false),
                                                TextInput::make('grade')
                                                    ->label('Nota')
                                                    ->numeric()
                                                    ->minValue(0),
                                                Textarea::make('ouro_moderno_status')
                                                    ->label('Última situação retornada pelo LMS')
                                                    ->formatStateUsing(fn ($state): ?string => is_array($state)
                                                        ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                                        : $state)
                                                    ->rows(4)
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->columnSpanFull(),
                                                TextInput::make('ouro_moderno_status_checked_at')
                                                    ->label('Consulta ao LMS em')
                                                    ->disabled()
                                                    ->dehydrated(false),
                                            ])
                                            ->columns(2)
                                            ->addActionLabel('Vincular curso')
                                            ->deleteAction(fn (Action $action): Action => $action->label('Desvincular')),
                                    ]),
                            ]),
                    ])
                    ->contained(false)
                    ->columnSpanFull(),
            ]);
    }

    private function getItemDisplayName(ContractItem $record): string
    {
        $formation = $record->formation;

        if (! $formation) {
            return 'Formação removida';
        }

        if ($formation->description === self::LEGACY_STANDALONE_COURSE_FORMATION_DESCRIPTION) {
            return $formation->courses->first()?->name ?? $formation->name;
        }

        return $formation->name;
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('order')
            ->defaultSort('order', 'asc')
            ->striped()
            ->columns([
                TextColumn::make('order')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('formation.name')
                    ->label('Formação / curso')
                    ->formatStateUsing(fn (?string $state, ContractItem $record): string => $this->getItemDisplayName($record))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contract_courses_count')
                    ->label('Cursos vinculados')
                    ->counts('contractCourses')
                    ->badge()
                    ->color('gray'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Adicionar formação'),
                Action::make('importOuroModernoCourses')
                    ->label('Importar Cursos do LMS Ouro Moderno')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->form([
                        TextInput::make('cod_int')
                            ->label('Código do aluno na Ouro Moderno (cod_int)')
                            ->numeric()
                            ->required()
                            ->default(fn (self $livewire): ?int => $livewire->getOwnerRecord()?->student?->ouro_moderno_cod_int)
                            ->helperText('Informe o código de integração do aluno na Ouro Moderno para buscar os cursos já cadastrados.'),
                    ])
                    ->modalHeading('Importar Cursos do LMS Ouro Moderno')
                    ->modalDescription('Os cursos vinculados ao aluno no LMS Ouro Moderno serão consultados e vinculados automaticamente a este contrato.')
                    ->modalSubmitActionLabel('Importar Cursos')
                    ->action(function (self $livewire, array $data): void {
                        $contract = $livewire->getOwnerRecord();

                        if (! $contract instanceof Contract) {
                            return;
                        }

                        if (! app(OuroModernoService::class)->forSchool($contract->school_id)->isConfigured()) {
                            Notification::make()
                                ->title('Integração não configurada')
                                ->body('A URL e o Token da Ouro Moderno precisam ser preenchidos em Configurações > Ouro Moderno.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $codInt = ! empty($data['cod_int']) ? (int) $data['cod_int'] : null;
                        if ($codInt && $contract->student) {
                            $contract->student->update(['ouro_moderno_cod_int' => $codInt]);
                        }

                        try {
                            $count = app(OuroModernoContractSyncService::class)->importStudentCoursesFromLms($contract);

                            if ($count > 0) {
                                Notification::make()
                                    ->title('Cursos importados com sucesso!')
                                    ->body("{$count} curso(s) da Ouro Moderno foram vinculados ao contrato.")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Nenhum curso novo encontrado')
                                    ->body('O aluno não possui novos cursos na Ouro Moderno ou todos já estão vinculados.')
                                    ->info()
                                    ->send();
                            }
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Falha ao comunicar com o servidor da Ouro Moderno')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                Action::make('refreshOuroModernoStatus')
                    ->label('Sincronizar progresso com o LMS')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(fn (): bool => auth()->user()?->can('Sync:OuroModernoStudentStatus') ?? false)
                    ->action(function (self $livewire, OuroModernoService $ouroModerno, OuroModernoProgressSyncService $progressSync): void {
                        $contract = $livewire->getOwnerRecord();

                        if (! $contract instanceof Contract) {
                            return;
                        }

                        $ouroModerno = $ouroModerno->forSchool($contract->school_id);

                        $allCourses = $contract->items()
                            ->with(['contractCourses.course.lmsModules.lessons.steps'])
                            ->get()
                            ->flatMap(fn ($item) => $item->contractCourses);

                        if ($allCourses->isEmpty()) {
                            Notification::make()
                                ->title('Nenhum curso vinculado ao contrato')
                                ->body('Vincule formações e cursos antes de sincronizar o progresso.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $aurosoftUpdated = 0;
                        $omUpdated = 0;
                        $aurosoftSync = app(AurosoftLmsProgressSyncService::class);
                        $errors = [];
                        $warnings = [];

                        foreach ($allCourses as $link) {
                            try {
                                if ($link->course?->isAurosoftLms()) {
                                    $aurosoftSync->sync($link);
                                    $aurosoftUpdated++;
                                } elseif ($link->course?->isFromOuroModerno()) {
                                    if (! $ouroModerno->isConfigured()) {
                                        $warnings[] = 'A integração com a Ouro Moderno não está configurada para esta escola.';
                                    } elseif (! $contract->student?->ouro_moderno_cod_int) {
                                        $warnings[] = 'O aluno não possui código de integração (cod_int) cadastrado. Importe ou informe o cod_int primeiro.';
                                    } else {
                                        $progressSync->sync($link, (int) $contract->student->ouro_moderno_cod_int, $ouroModerno);
                                        $omUpdated++;
                                    }
                                }
                            } catch (\Throwable $e) {
                                $courseName = $link->course?->name ?? 'Curso';
                                $errors[] = "{$courseName}: " . $e->getMessage();
                            }
                        }

                        $warnings = array_unique($warnings);
                        $totalUpdated = $aurosoftUpdated + $omUpdated;

                        if ($totalUpdated > 0) {
                            $parts = [];
                            if ($aurosoftUpdated > 0) {
                                $parts[] = "{$aurosoftUpdated} curso(s) Aurosoft LMS";
                            }
                            if ($omUpdated > 0) {
                                $parts[] = "{$omUpdated} curso(s) Ouro Moderno";
                            }
                            $detail = implode(' e ', $parts);

                            Notification::make()
                                ->title('Progresso LMS sincronizado')
                                ->body("{$detail} atualizado(s) no Aurosoft. Médias, notas e atividades foram consolidadas.")
                                ->success()
                                ->send();
                        } elseif (empty($warnings) && empty($errors)) {
                            Notification::make()
                                ->title('Nenhum curso pendente de sincronização')
                                ->body('Não há cursos ativos ou configurados para sincronizar.')
                                ->info()
                                ->send();
                        }

                        if (! empty($warnings)) {
                            Notification::make()
                                ->title('Atenção na sincronização')
                                ->body(implode(' | ', $warnings))
                                ->warning()
                                ->persistent()
                                ->send();
                        }

                        if (! empty($errors)) {
                            Notification::make()
                                ->title('Falha na comunicação com o LMS')
                                ->body(implode(' | ', $errors))
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('showCourses')
                    ->label('Ver cursos')
                    ->icon('heroicon-o-chevron-down')
                    ->color('gray')
                    ->modalHeading(fn (?ContractItem $record): string => $record ? 'Cursos de '.$this->getItemDisplayName($record) : 'Cursos da formação')
                    ->modalContent(function (?ContractItem $record) {
                        if (! $record) {
                            return null;
                        }

                        $record->loadMissing(['contract', 'formation.courses', 'contractCourses.course']);

                        return view('filament.resources.contracts.formation-courses-modal', [
                            'item' => $record,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),
                EditAction::make()
                    ->label('Editar formação'),
                DeleteAction::make()
                    ->label('Remover formação'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
