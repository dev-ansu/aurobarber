<?php

namespace App\Filament\Resources\Contracts\Tables;

use App\Filament\Exports\ContractExporter;
use App\Filament\Resources\Contracts\Actions\AdjustContractFinancialAction;
use App\Filament\Resources\Contracts\Actions\ReactivateContractAction;
use App\Models\Collaborator;
use App\Models\Contract;
use App\Models\School;
use App\Services\SchoolTransferService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['student', 'items']))
            ->columns([
                TextColumn::make('contract_number')
                    ->label('Nº Contrato')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('student.name')
                    ->label('Aluno (Estudante)')
                    ->searchable()
                    ->sortable()
                    ->description(function (Contract $record): ?string {
                        if (! $record->student?->email && ! $record->student?->cpf) {
                            return null;
                        }
                        $email = $record->student->email ? \App\Services\DataMaskingService::maskEmail($record->student->email) : '';
                        $cpf = $record->student->cpf ? 'CPF: ' . \App\Services\DataMaskingService::maskCpf($record->student->cpf) : '';

                        return trim("{$email} • {$cpf}", ' •');
                    }),

                TextColumn::make('student.email')
                    ->label('E-mail')
                    ->formatStateUsing(fn ($state) => \App\Services\DataMaskingService::maskEmail($state))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('student.birth_date')
                    ->label('Nascimento (Senha)')
                    ->date('d/m/Y')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('total_value')
                    ->label('Valor Total')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('modalities')
                    ->label('Modalidade')
                    ->badge()
                    ->state(function (Contract $record): array {
                        $tags = [];
                        if ($record->is_flex) {
                            $tags[] = 'Flex';
                        }
                        if ($record->is_ead) {
                            $tags[] = 'EAD';
                        }
                        if ($record->isFree()) {
                            $tags[] = 'Gratuito';
                        }

                        return $tags ?: ['Regular'];
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Flex' => 'primary',
                        'EAD' => 'info',
                        'Gratuito' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'draft' => 'gray',
                        'suspended' => 'warning',
                        'canceled' => 'danger',
                        'finished' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => self::statusLabel($state))
                    ->description(function (Contract $record): ?string {
                        if ($record->status === 'active' && $record->items->isNotEmpty() && $record->items->every(fn ($item) => $item->formation_completed_at !== null)) {
                            return '🎓 Formação Concluída';
                        }

                        return null;
                    }),

                TextColumn::make('start_date')
                    ->label('Data de Início')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('start_date_range')
                    ->label('Período de Início')
                    ->schema([
                        Section::make('Filtrar por início do contrato')
                            ->icon('heroicon-m-calendar')
                            ->schema([
                                Grid::make(2)->schema([
                                    DatePicker::make('from')
                                        ->label('Data Inicial')
                                        ->live(),
                                    DatePicker::make('until')
                                        ->label('Data Final')
                                        ->live(),
                                ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->indicateUsing(fn (array $data): array => self::dateRangeIndicators($data, 'Início'))
                    ->query(fn (Builder $query, array $data): Builder => self::applyDateRange($query, 'start_date', $data)),

                Filter::make('created_at_range')
                    ->label('Período de Cadastro')
                    ->schema([
                        Section::make('Filtrar por data de cadastro')
                            ->icon('heroicon-m-calendar')
                            ->schema([
                                Grid::make(2)->schema([
                                    DatePicker::make('from')
                                        ->label('Data Inicial')
                                        ->live(),
                                    DatePicker::make('until')
                                        ->label('Data Final')
                                        ->live(),
                                ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->indicateUsing(fn (array $data): array => self::dateRangeIndicators($data, 'Cadastro'))
                    ->query(fn (Builder $query, array $data): Builder => self::applyDateRange($query, 'created_at', $data)),

                SelectFilter::make('status')
                    ->label('Status do Contrato')
                    ->options([
                        'draft' => 'Rascunho',
                        'active' => 'Ativo',
                        'suspended' => 'Suspenso',
                        'canceled' => 'Cancelado',
                        'finished' => 'Finalizado',
                    ]),

                SelectFilter::make('contract_type_id')
                    ->label('Modelo de Minuta Contratual')
                    ->relationship('contractType', 'name', fn ($query) => $query->where('document_type', \App\Models\ContractType::TYPE_CONTRACT))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('collaborator_id')
                    ->label('Colaborador responsável')
                    ->options(fn (): array => Collaborator::query()
                        ->with('person')
                        ->get()
                        ->mapWithKeys(fn (Collaborator $collaborator): array => [
                            $collaborator->getKey() => $collaborator->person?->name ?? 'Colaborador sem pessoa vinculada',
                        ])
                        ->all())
                    ->searchable(),

                Filter::make('is_flex')
                    ->label('Apenas Alunos Flex')
                    ->query(fn (Builder $query): Builder => $query->where('is_flex', true)),

                Filter::make('is_ead')
                    ->label('Apenas Alunos EAD')
                    ->query(fn (Builder $query): Builder => $query->where('is_ead', true)),

                Filter::make('is_free')
                    ->label('Apenas Cursos Gratuitos / Bolsistas')
                    ->query(fn (Builder $query): Builder => $query->where(fn ($q) => $q->where('is_free', true)
                        ->orWhere(fn ($sq) => $sq->where('total_value', '<=', 0)->doesntHave('installments')))),

                Filter::make('pending_completion')
                    ->label('🎓 Formação Concluída (Pendente Secretaria)')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'active')
                        ->whereHas('items')
                        ->whereDoesntHave('items', fn ($q) => $q->whereNull('formation_completed_at'))
                    ),

                TrashedFilter::make(),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('Visualizar contrato'),
                EditAction::make()->iconButton()->tooltip('Editar contrato'),
                Action::make('viewStudentPerson')
                    ->label('')
                    ->tooltip('Ver dados da pessoa / aluno')
                    ->icon('heroicon-m-user')
                    ->iconButton()
                    ->color('gray')
                    ->modalHeading(fn (Contract $record): string => 'Dados da Pessoa — '.($record->student?->name ?? 'Aluno'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->infolist(fn (Contract $record): array => [
                        Section::make('Identificação e Contato')
                            ->schema([
                                TextEntry::make('student.name')->label('Nome Completo')->weight('bold'),
                                TextEntry::make('student.email')
                                    ->label('E-mail Principal')
                                    ->formatStateUsing(fn ($state) => \App\Services\DataMaskingService::maskEmail($state))
                                    ->placeholder('—'),
                                TextEntry::make('student.cpf')
                                    ->label('CPF')
                                    ->formatStateUsing(fn ($state) => \App\Services\DataMaskingService::maskCpf($state))
                                    ->placeholder('—')
                                    ->suffixAction(
                                        \App\Services\DataMaskingService::makeRevealModalAction('revealStudentCpfModal', 'CPF', fn (Contract $record): string => (string) ($record->student?->cpf ?? ''))
                                    ),
                                TextEntry::make('student.birth_date')->label('Data de Nascimento')->date('d/m/Y')->placeholder('—'),
                                TextEntry::make('student.ouro_moderno_usuario')->label('Usuário Totem / LMS')->copyable()->placeholder('—'),
                                TextEntry::make('student.gender')->label('Gênero')->placeholder('—'),
                            ])->columns(2),
                    ]),
                ActionGroup::make([
                    AdjustContractFinancialAction::make(),
                    Action::make('finishContract')
                        ->label('Concluir')
                        ->tooltip('Concluir contrato manualmente')
                        ->icon('heroicon-m-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Conclusão manual do contrato')
                        ->modalDescription('A secretaria confirma o encerramento do contrato. Esta ação grava o histórico de formatura.')
                        ->schema([
                            Textarea::make('reason')
                                ->label('Observação de Conclusão')
                                ->placeholder('Ex: Aluno cumpriu toda a carga horária e aprovou nos exames finais.')
                                ->rows(2)
                                ->nullable(),
                        ])
                        ->action(function (Contract $record, array $data): void {
                            $record->changeStatus('finished', $data['reason'] ?? 'Conclusão de curso regular');

                            Notification::make()
                                ->title('Contrato concluído!')
                                ->body("O contrato {$record->contract_number} foi finalizado com sucesso.")
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Contract $record): bool => $record->status === 'active')
                        ->authorize(fn (Contract $record): bool => auth()->user()?->can('Update:Contract') ?? false),
                    ReactivateContractAction::make(),
                    RestoreAction::make()
                        ->authorize(fn (Contract $record): bool => (auth()->user()?->can('Reactivate:Contract') ?? false)
                            || (auth()->user()?->can('Restore:Contract') ?? false)
                            || (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('super_admin'))),
                    ForceDeleteAction::make()
                        ->authorize(fn (Contract $record): bool => (auth()->user()?->can('ForceDelete:Contract') ?? false)
                            || (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('super_admin'))),
                    Action::make('printCarne')
                        ->label('Carnê de Pagamento')
                        ->icon('heroicon-m-receipt-percent')
                        ->url(fn (Contract $record): string => route('contracts.carne', $record))
                        ->openUrlInNewTab(),
                    Action::make('declaracaoMatricula')
                        ->label('Declaração de Matrícula')
                        ->icon('heroicon-m-academic-cap')
                        ->url(fn (Contract $record): string => route('contracts.declaracao-matricula', $record))
                        ->openUrlInNewTab(),
                    Action::make('declaracaoQuitacao')
                        ->label('Declaração de Quitação (Lei 12.007/09)')
                        ->icon('heroicon-m-shield-check')
                        ->url(fn (Contract $record): string => route('contracts.declaracao-quitacao', $record))
                        ->openUrlInNewTab(),
                    Action::make('declaracaoComparecimento')
                        ->label('Declaração de Comparecimento')
                        ->icon('heroicon-m-calendar-days')
                        ->modalHeading('Emissão de Declaração de Comparecimento')
                        ->modalDescription('Preencha a data e o horário para emissão do comprovante de comparecimento do aluno.')
                        ->modalSubmitActionLabel('Gerar e Imprimir')
                        ->form([
                            DatePicker::make('date')
                                ->label('Data do Comparecimento')
                                ->default(today())
                                ->required(),
                            TextInput::make('start_time')
                                ->label('Horário Inicial')
                                ->default('14:00')
                                ->required(),
                            TextInput::make('end_time')
                                ->label('Horário Final')
                                ->default('17:00')
                                ->required(),
                            TextInput::make('purpose')
                                ->label('Finalidade / Atividade')
                                ->default('atividades pedagógicas, aulas regulares e avaliações')
                                ->required(),
                        ])
                        ->action(function (Contract $record, array $data, $livewire): void {
                            $params = array_filter($data, fn ($v) => ! is_null($v) && $v !== '');
                            $url = route('contracts.declaracao-comparecimento', array_merge(['contract' => $record], $params));
                            $livewire->js('window.open(' . json_encode($url) . ", '_blank');");
                        }),
                    Action::make('termoCancelamento')
                        ->label('Termo de Rescisão / Distrato')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->url(fn (Contract $record): string => route('contracts.termo-cancelamento', $record))
                        ->openUrlInNewTab(),
                    Action::make('termoTrancamento')
                        ->label('Termo de Trancamento Provisório')
                        ->icon('heroicon-m-pause-circle')
                        ->modalHeading('Emissão de Termo de Trancamento de Matrícula')
                        ->modalDescription('Defina os parâmetros do trancamento temporário da matrícula.')
                        ->modalSubmitActionLabel('Gerar e Imprimir')
                        ->form([
                            DatePicker::make('start_date')
                                ->label('Data de Início')
                                ->default(today())
                                ->required(),
                            Select::make('days')
                                ->label('Prazo de Trancamento')
                                ->options([
                                    15 => '15 dias',
                                    30 => '30 dias (1 mês)',
                                    60 => '60 dias (2 meses)',
                                    90 => '90 dias (3 meses)',
                                ])
                                ->default(30)
                                ->required(),
                            TextInput::make('reason')
                                ->label('Justificativa')
                                ->default('motivos de ordem particular e temporária')
                                ->required(),
                        ])
                        ->action(function (Contract $record, array $data, $livewire): void {
                            $params = array_filter($data, fn ($v) => ! is_null($v) && $v !== '');
                            $url = route('contracts.termo-trancamento', array_merge(['contract' => $record], $params));
                            $livewire->js('window.open(' . json_encode($url) . ", '_blank');");
                        }),
                    Action::make('autorizacaoImagem')
                        ->label('Autorização de Imagem e Voz (LGPD)')
                        ->icon('heroicon-m-camera')
                        ->url(fn (Contract $record): string => route('contracts.autorizacao-imagem', $record))
                        ->openUrlInNewTab(),
                    Action::make('carteirinhaEstudantil')
                        ->label('Carteirinha Estudantil (Crachá)')
                        ->icon('heroicon-m-identification')
                        ->url(fn (Contract $record): string => route('contracts.carteirinha-estudantil', $record))
                        ->openUrlInNewTab(),
                    Action::make('fichaMatricula')
                        ->label('Ficha Cadastral (Dossiê)')
                        ->icon('heroicon-m-identification')
                        ->url(fn (Contract $record): string => route('contracts.ficha-matricula', $record))
                        ->openUrlInNewTab(),
                    Action::make('fichaFiscal')
                        ->label('Ficha Fiscal (ISS Fortaleza)')
                        ->icon('heroicon-m-document-currency-dollar')
                        ->color('info')
                        ->modalHeading(fn (Contract $record): string => "Ficha de Emissão Fiscal (ISS Fortaleza) - Contrato #{$record->contract_number}")
                        ->modalDescription('Dados completos do aluno, responsável financeiro e discriminação para preenchimento da Nota Fiscal de Serviços (NFS-e).')
                        ->modalContent(fn (Contract $record) => view('filament.pages.actions.installment-fiscal-sheet', [
                            'contract' => $record,
                            'student' => $record->student,
                            'responsible' => $record->responsible,
                            'installment' => $record->installments()->where('status', 'paid')->latest('paid_at')->first() ?? $record->installments()->first(),
                            'downPayment' => \App\Filament\Pages\ReceivedInstallmentsReport::calculateContractDownPayment($record),
                            'totalPaid' => \App\Filament\Pages\ReceivedInstallmentsReport::calculateContractTotalPaid($record),
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Fechar'),
                    Action::make('attendanceReport')
                        ->label('Relatório de Presenças e Faltas')
                        ->icon('heroicon-m-clipboard-document-check')
                        ->url(fn (Contract $record): string => route('contracts.attendance-report', $record))
                        ->openUrlInNewTab(),
                    Action::make('certificate')
                        ->label('Certificado (Frente e Verso)')
                        ->icon('heroicon-m-trophy')
                        ->modalHeading('Emissão e Personalização do Certificado')
                        ->modalDescription('Verifique e personalize os dados impressos na frente e verso do certificado antes de imprimir.')
                        ->modalSubmitActionLabel('Visualizar e Imprimir Certificado')
                        ->fillForm(function (Contract $record): array {
                            $record->loadMissing(['student', 'items.formation', 'items.contractCourses.course']);
                            $school = $record->resolved_school;
                            $courses = $record->items->flatMap(fn ($i) => $i->contractCourses)->filter(fn ($cc) => $cc->course !== null);
                            $formations = $record->items->map(fn ($i) => $i->formation?->name)->filter()->unique()->join(', ');
                            $courseTitle = $formations ?: ($courses->map(fn ($cc) => $cc->course->name)->unique()->join(', ') ?: 'Curso Profissionalizante');
                            $totalWorkload = $courses->sum(fn ($cc) => (int) ($cc->course->workload ?? 0));
                            $grades = $courses->map(fn ($cc) => is_numeric($cc->grade) ? (float) $cc->grade : null)->filter();
                            $averageGrade = $grades->isNotEmpty() ? round($grades->avg(), 1) : null;
                            $startDate = $record->start_date ?? $courses->min('created_at') ?? now();
                            $completionDate = $record->finished_at ?? $courses->max('completed_at') ?? $record->end_date ?? now();
                            $schoolRep = collect($school['partners'] ?? [])->first(fn ($p) => (bool) ($p['legal_representative'] ?? false))['name'] ?? null;

                            return [
                                'course_title' => $courseTitle,
                                'total_workload' => $totalWorkload ?: null,
                                'average_grade' => $averageGrade,
                                'start_date' => $startDate ? \Carbon\Carbon::parse($startDate)->format('Y-m-d') : null,
                                'completion_date' => $completionDate ? \Carbon\Carbon::parse($completionDate)->format('Y-m-d') : null,
                                'book_number' => '01',
                                'sheet_number' => str_pad((string) (($record->id % 99) + 1), 3, '0', STR_PAD_LEFT),
                                'registration_number' => str_pad((string) $record->id, 4, '0', STR_PAD_LEFT),
                                'school_representative' => $schoolRep,
                            ];
                        })
                        ->form([
                            TextInput::make('course_title')
                                ->label('Título do Curso / Formação')
                                ->required()
                                ->columnSpanFull(),
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('total_workload')
                                        ->label('Carga Horária (h)')
                                        ->numeric(),
                                    TextInput::make('average_grade')
                                        ->label('Média Final (%)')
                                        ->numeric(),
                                    TextInput::make('school_representative')
                                        ->label('Diretor(a) / Assinante'),
                                ]),
                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('start_date')
                                        ->label('Data de Início'),
                                    DatePicker::make('completion_date')
                                        ->label('Data de Conclusão / Formatura'),
                                ]),
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('book_number')
                                        ->label('Livro de Registro'),
                                    TextInput::make('sheet_number')
                                        ->label('Folha nº'),
                                    TextInput::make('registration_number')
                                        ->label('Registro nº'),
                                ]),
                            Textarea::make('legal_base')
                                ->label('Texto Legal / Observações no Verso (opcional)')
                                ->placeholder('Deixe em branco para usar o amparo legal padrão da LDB (Art. 39 a 42).')
                                ->rows(2)
                                ->columnSpanFull(),
                        ])
                        ->action(function (Contract $record, array $data, $livewire) {
                            $params = array_filter($data, fn ($v) => ! is_null($v) && $v !== '');
                            $url = route('contracts.certificate', array_merge(['contract' => $record], $params));
                            $livewire->js('window.open('.json_encode($url).", '_blank');");
                        }),
                    Action::make('printContract')
                        ->label('Contrato de Prestação de Serviços')
                        ->icon('heroicon-m-document-text')
                        ->url(fn (Contract $record): string => route('contracts.print', $record))
                        ->openUrlInNewTab(),
                    Action::make('termoAcordo')
                        ->label('Termo de Confissão de Dívida')
                        ->icon('heroicon-m-scale')
                        ->visible(fn (Contract $record): bool => $record->debtAgreements()->exists())
                        ->url(fn (Contract $record): string => route('contracts.termo-acordo', $record))
                        ->openUrlInNewTab(),
                    Action::make('transferSchool')
                        ->label('Transferir de Unidade')
                        ->icon('heroicon-m-arrows-right-left')
                        ->color('info')
                        ->modalHeading(fn (Contract $record): string => "Transferir Matrícula #{$record->contract_number} de Unidade")
                        ->modalDescription('Transfere o contrato, o aluno, seu responsável financeiro, histórico de parcelas e progresso no LMS desta unidade para outra escola da rede, liberando a vaga de sala na unidade atual.')
                        ->modalSubmitActionLabel('Confirmar Transferência')
                        ->visible(fn (): bool => School::query()->withoutGlobalScopes()->count() > 1)
                        ->authorize(fn (): bool => (auth()->user()?->isSuperAdmin() ?? false) || (auth()->user()?->can('Update:Contract') ?? false))
                        ->schema(fn (Contract $record): array => [
                            Select::make('target_school_id')
                                ->label('Unidade de Destino')
                                ->options(fn (): array => School::query()
                                    ->withoutGlobalScopes()
                                    ->where('id', '!=', $record->school_id)
                                    ->orderBy('trade_name')
                                    ->get()
                                    ->mapWithKeys(fn (School $s): array => [$s->id => $s->getFilamentName()])
                                    ->all())
                                ->required()
                                ->native(false)
                                ->searchable(),
                            Textarea::make('reason')
                                ->label('Motivo / Observações para a unidade de destino')
                                ->placeholder('Ex: Aluno mudou de bairro e continuará o curso no turno da tarde na nova unidade.')
                                ->rows(3),
                            Toggle::make('keep_origin_link')
                                ->label('Manter cadastro da pessoa vinculado também nesta unidade de origem')
                                ->default(false),
                            Toggle::make('send_notification')
                                ->label('Notificar automaticamente a unidade de destino na Comunicação da Rede')
                                ->default(true),
                        ])
                        ->action(function (Contract $record, array $data): void {
                            $targetSchool = School::query()->withoutGlobalScopes()->findOrFail($data['target_school_id']);

                            app(SchoolTransferService::class)->transferContract(
                                contract: $record,
                                targetSchool: $targetSchool,
                                reason: $data['reason'] ?? null,
                                keepOriginPersonLink: (bool) ($data['keep_origin_link'] ?? false),
                                sendNotificationMessage: (bool) ($data['send_notification'] ?? true),
                            );

                            Notification::make()
                                ->title('Matrícula, aluno e responsável transferidos!')
                                ->body("O contrato #{$record->contract_number} foi transferido para a unidade {$targetSchool->getFilamentName()}.")
                                ->success()
                                ->send();
                        }),
                ])
                    ->label('Documentos')
                    ->icon('heroicon-m-document-duplicate')
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('transferSelectedContracts')
                        ->label('Transferir Selecionados de Unidade')
                        ->icon('heroicon-m-arrows-right-left')
                        ->color('info')
                        ->visible(fn (): bool => School::query()->withoutGlobalScopes()->count() > 1)
                        ->authorize(fn (): bool => (auth()->user()?->isSuperAdmin() ?? false) || (auth()->user()?->can('Update:Contract') ?? false))
                        ->modalHeading('Transferir Contratos Selecionados em Lote para outra Unidade')
                        ->modalDescription('Todos os contratos selecionados, juntamente com seus alunos, responsáveis financeiros, progresso LMS e parcelas, serão transferidos para a unidade de destino.')
                        ->modalSubmitActionLabel('Confirmar Transferência em Lote')
                        ->deselectRecordsAfterCompletion()
                        ->schema([
                            Select::make('target_school_id')
                                ->label('Unidade de Destino')
                                ->options(function (): array {
                                    $tenantId = Filament::getTenant()?->id;

                                    return School::query()
                                        ->withoutGlobalScopes()
                                        ->when($tenantId, fn ($q) => $q->where('id', '!=', $tenantId))
                                        ->orderBy('trade_name')
                                        ->get()
                                        ->mapWithKeys(fn (School $s): array => [$s->id => $s->getFilamentName()])
                                        ->all();
                                })
                                ->required()
                                ->native(false)
                                ->searchable(),
                            Textarea::make('reason')
                                ->label('Motivo / Observação da transferência em lote')
                                ->rows(2),
                            Toggle::make('keep_origin_link')
                                ->label('Manter cadastro da pessoa vinculado também nesta unidade de origem')
                                ->default(false),
                            Toggle::make('send_notification')
                                ->label('Notificar a unidade de destino na Comunicação da Rede')
                                ->default(true),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $targetSchool = School::query()->withoutGlobalScopes()->findOrFail($data['target_school_id']);
                            $service = app(SchoolTransferService::class);
                            $count = 0;

                            foreach ($records as $record) {
                                if ((int) $record->school_id === (int) $targetSchool->id) {
                                    continue;
                                }

                                $service->transferContract(
                                    contract: $record,
                                    targetSchool: $targetSchool,
                                    reason: $data['reason'] ?? null,
                                    keepOriginPersonLink: (bool) ($data['keep_origin_link'] ?? false),
                                    sendNotificationMessage: (bool) ($data['send_notification'] ?? true),
                                );
                                $count++;
                            }

                            Notification::make()
                                ->title("{$count} contrato(s) transferido(s) com sucesso!")
                                ->body("Os contratos selecionados e seus vínculos foram enviados para {$targetSchool->getFilamentName()}.")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action): void {
                            foreach ($records as $record) {
                                $hasPaid = $record->installments()
                                    ->where(function ($q): void {
                                        $q->where('status', 'paid')
                                            ->orWhereNotNull('paid_at')
                                            ->orWhere('amount_paid', '>', 0);
                                    })
                                    ->exists();

                                if ($hasPaid) {
                                    Notification::make()
                                        ->title('Exclusão em lote bloqueada')
                                        ->body("O contrato #{$record->contract_number} possui parcelas pagas no caixa/financeiro e não pode ser excluído.")
                                        ->danger()
                                        ->persistent()
                                        ->send();

                                    $action->halt();
                                }
                            }
                        }),
                    RestoreBulkAction::make()
                        ->authorize(fn (): bool => (auth()->user()?->can('Reactivate:Contract') ?? false)
                            || (auth()->user()?->can('RestoreAny:Contract') ?? false)
                            || (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('super_admin'))),
                    ForceDeleteBulkAction::make()
                        ->authorize(fn (): bool => (auth()->user()?->can('ForceDeleteAny:Contract') ?? false)
                            || (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('super_admin'))),
                ]),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Exportar contratos')
                    ->authorize(fn (): bool => auth()->user()?->getAllPermissions()->contains('name', 'Export:Contract') ?? false)
                    ->visible(fn (): bool => auth()->user()?->getAllPermissions()->contains('name', 'Export:Contract') ?? false)
                    ->exporter(ContractExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx]),
            ]);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Ativo',
            'draft' => 'Rascunho',
            'suspended' => 'Suspenso',
            'canceled' => 'Cancelado',
            'finished' => 'Finalizado',
            default => $status,
        };
    }

    /**
     * @param  array{from?: string|null, until?: string|null}  $data
     * @return array<int, string>
     */
    private static function dateRangeIndicators(array $data, string $label): array
    {
        $indicators = [];

        if ($data['from'] ?? null) {
            $indicators[] = "{$label} a partir de: ".Carbon::parse($data['from'])->format('d/m/Y');
        }

        if ($data['until'] ?? null) {
            $indicators[] = "{$label} até: ".Carbon::parse($data['until'])->format('d/m/Y');
        }

        return $indicators;
    }

    /**
     * @param  array{from?: string|null, until?: string|null}  $data
     */
    private static function applyDateRange(Builder $query, string $column, array $data): Builder
    {
        return $query
            ->when($data['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($column, '>=', $date))
            ->when($data['until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($column, '<=', $date));
    }
}
