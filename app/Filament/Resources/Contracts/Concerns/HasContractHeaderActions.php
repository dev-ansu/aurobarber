<?php

namespace App\Filament\Resources\Contracts\Concerns;

use App\Filament\Resources\Contracts\Actions\AdjustContractFinancialAction;
use App\Filament\Resources\Contracts\Actions\CancelContractWithFeeAction;
use App\Filament\Resources\Contracts\Actions\FinishContractAction;
use App\Filament\Resources\Contracts\Actions\ReactivateContractAction;
use App\Filament\Resources\Contracts\Actions\SuspendContractAction;
use App\Models\Contract;
use App\Models\FinancialType;
use App\Models\PaymentMethod;
use App\Services\DebtAgreementService;
use App\Services\OuroModernoContractSyncService;
use App\Services\OuroModernoService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Carbon;

trait HasContractHeaderActions
{
    protected function getUnifiedContractHeaderActions(bool $isView = true): array
    {
        return [
            // Botão principal de navegação direto do lado de fora
            $isView ? EditAction::make() : ViewAction::make(),

            // GRUPO FINANCEIRO: Ajustes de parcelas e Renegociações
            ActionGroup::make([
                AdjustContractFinancialAction::make(),
                $this->makeRenegotiateDebtAction(),
            ])
                ->label('Financeiro')
                ->icon('heroicon-m-banknotes')
                ->color('warning')
                ->button(),

            // GRUPO 1: Todos os Documentos & Emissões da Escola
            ActionGroup::make([
                Action::make('print')
                    ->label('Imprimir Contrato')
                    ->icon('heroicon-m-printer')
                    ->color('success')
                    ->url(fn (Contract $record): string => route('contracts.print', $record))
                    ->openUrlInNewTab(),

                Action::make('printCarne')
                    ->label('Imprimir Carnê')
                    ->icon('heroicon-m-receipt-percent')
                    ->color('warning')
                    ->url(fn (Contract $record): string => route('contracts.carne', $record))
                    ->openUrlInNewTab(),

                Action::make('declaracaoMatricula')
                    ->label('Declaração de Matrícula')
                    ->icon('heroicon-m-academic-cap')
                    ->color('info')
                    ->url(fn (Contract $record): string => route('contracts.declaracao-matricula', $record))
                    ->openUrlInNewTab(),

                Action::make('declaracaoComparecimento')
                    ->label('Declaração de Comparecimento')
                    ->icon('heroicon-m-calendar-days')
                    ->color('info')
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

                Action::make('declaracaoQuitacao')
                    ->label('Declaração de Quitação Anual (Lei 12.007/09)')
                    ->icon('heroicon-m-shield-check')
                    ->color('success')
                    ->url(fn (Contract $record): string => route('contracts.declaracao-quitacao', $record))
                    ->openUrlInNewTab(),

                Action::make('termoCancelamento')
                    ->label('Termo de Rescisão / Distrato')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->url(fn (Contract $record): string => route('contracts.termo-cancelamento', $record))
                    ->openUrlInNewTab(),

                Action::make('termoTrancamento')
                    ->label('Termo de Trancamento Provisório')
                    ->icon('heroicon-m-pause-circle')
                    ->color('warning')
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
                    ->color('gray')
                    ->url(fn (Contract $record): string => route('contracts.autorizacao-imagem', $record))
                    ->openUrlInNewTab(),

                Action::make('carteirinhaEstudantil')
                    ->label('Carteirinha Estudantil (Crachá)')
                    ->icon('heroicon-m-identification')
                    ->color('primary')
                    ->url(fn (Contract $record): string => route('contracts.carteirinha-estudantil', $record))
                    ->openUrlInNewTab(),

                Action::make('fichaMatricula')
                    ->label('Ficha Cadastral (Dossiê do Aluno)')
                    ->icon('heroicon-m-identification')
                    ->color('gray')
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
                    ->color('primary')
                    ->url(fn (Contract $record): string => route('contracts.attendance-report', $record))
                    ->openUrlInNewTab(),

                Action::make('termoAcordo')
                    ->label('Termo de Acordo / Confissão')
                    ->icon('heroicon-m-scale')
                    ->color('warning')
                    ->visible(fn (Contract $record): bool => $record->debtAgreements()->exists())
                    ->url(fn (Contract $record): string => route('contracts.termo-acordo', $record))
                    ->openUrlInNewTab(),

                Action::make('certificate')
                    ->label('Certificado (Frente e Verso)')
                    ->icon('heroicon-m-trophy')
                    ->color('info')
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
                            'start_date' => $startDate ? Carbon::parse($startDate)->format('Y-m-d') : null,
                            'completion_date' => $completionDate ? Carbon::parse($completionDate)->format('Y-m-d') : null,
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
                        Grid::make(3)->schema([
                            TextInput::make('total_workload')
                                ->label('Carga Horária (h)')
                                ->numeric(),
                            TextInput::make('average_grade')
                                ->label('Média Final (%)')
                                ->numeric(),
                            TextInput::make('school_representative')
                                ->label('Diretor(a) / Assinante'),
                        ]),
                        Grid::make(2)->schema([
                            DatePicker::make('start_date')
                                ->label('Data de Início'),
                            DatePicker::make('completion_date')
                                ->label('Data de Conclusão / Formatura'),
                        ]),
                        Grid::make(3)->schema([
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
                        $livewire->js('window.open(' . json_encode($url) . ", '_blank');");
                    }),

                Action::make('ouroModernoTranscript')
                    ->label('Boletim do aluno (LMS)')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->visible(fn (): bool => auth()->user()?->can('View:OuroModernoTranscript') ?? false)
                    ->url(fn (Contract $record): string => route('contracts.ouro-moderno-transcript', $record))
                    ->openUrlInNewTab(),
            ])
                ->label('Documentos')
                ->icon('heroicon-m-document-text')
                ->color('gray')
                ->button(),

            // GRUPO 2: Ações Operacionais e Pedagógicas do Contrato
            ActionGroup::make([
                FinishContractAction::make(),
                SuspendContractAction::make(),
                ReactivateContractAction::make(),
                CancelContractWithFeeAction::make(),

                $this->makeImportOuroModernoAction(),

                // Integrações LMS
                Action::make('sendOuroModerno')
                    ->label('Enviar contrato ao LMS')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->color('primary')
                    ->visible(fn (Contract $record): bool => ! $record->is_ead
                        && $record->status === 'active'
                        && (auth()->user()?->can('Sync:OuroModernoContract') ?? false))
                    ->schema([
                        Select::make('mode')
                            ->label('Cadastro do aluno na Ouro Moderno')
                            ->options([
                                'create' => 'Cadastrar aluno novo',
                                'update' => 'Aluno já existe: atualizar cadastro',
                            ])
                            ->default(fn (Contract $record): string => $record->student?->ouro_moderno_registered_at ? 'update' : 'create')
                            ->required()
                            ->live(),
                        TextInput::make('username_preview')
                            ->label('Usuário de acesso ao LMS')
                            ->default(fn (Contract $record): string => (string) $record->contract_number)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('O número deste contrato será enviado como usuário do aluno.'),
                        TextInput::make('password')
                            ->label('Senha de acesso ao LMS')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->default(fn (Contract $record): ?string => $record->student?->ouro_moderno_senha)
                            ->required(fn (Get $get, Contract $record): bool => $get('mode') === 'create' || blank($record->student?->ouro_moderno_senha))
                            ->helperText(fn (Get $get, Contract $record): string => $get('mode') === 'update' && filled($record->student?->ouro_moderno_senha)
                                ? 'Deixe em branco para manter a senha já cadastrada no Aurosoft.'
                                : 'Esta senha será gravada no cadastro do aluno no Aurosoft e usada no login automático.'),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Sincronizar contrato com a Ouro Moderno?')
                    ->modalDescription('Os dados pessoais e os cursos vinculados serão enviados ao LMS.')
                    ->modalSubmitActionLabel('Enviar ao LMS')
                    ->action(function (Contract $record, array $data): void {
                        abort_unless(auth()->user()?->can('Sync:OuroModernoContract') ?? false, 403);
                        try {
                            $password = filled($data['password'] ?? null)
                                ? (string) $data['password']
                                : $record->student?->ouro_moderno_senha;

                            $result = app(OuroModernoContractSyncService::class)->sync($record, $data['mode'], $password);
                            $msg = "Código do aluno: {$result['student_code']}. Cursos Ouro Moderno matriculados nesta operação: {$result['courses_enrolled']}.";
                            Notification::make()->title('Contrato sincronizado com a Ouro Moderno')->body($msg)->success()->send();
                        } catch (\Throwable $exception) {
                            report($exception);
                            Notification::make()->title('Não foi possível sincronizar o contrato')->body($exception->getMessage())->danger()->persistent()->send();
                        }
                    }),

                Action::make('migrateToEad')
                    ->label('Migrar para o EAD')
                    ->icon('heroicon-o-academic-cap')
                    ->color('warning')
                    ->visible(fn (Contract $record): bool => ! $record->is_ead
                        && $record->status === 'active'
                        && ($record->school?->ouro_moderno_ead_enabled ?? false))
                    ->requiresConfirmation()
                    ->modalHeading('Migrar contrato para o EAD (Ouro Moderno Online)?')
                    ->modalDescription('Esta ação transferirá o aluno exclusivamente para a modalidade EAD.')
                    ->modalSubmitActionLabel('Confirmar Migração para EAD')
                    ->action(function (Contract $record): void {
                        try {
                            $service = app(\App\Services\OuroModernoEadService::class);
                            $res = $service->migrateContractToEad($record);
                            Notification::make()->title('Migração para EAD Concluída!')->body($res['message'])->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Erro ao Migrar para EAD')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('migrateToLocalLms')
                    ->label('Migrar para Presencial (LMS Local)')
                    ->icon('heroicon-o-building-library')
                    ->color('success')
                    ->visible(fn (Contract $record): bool => (bool) $record->is_ead && $record->status === 'active')
                    ->requiresConfirmation()
                    ->modalHeading('Transferir aluno do EAD para Presencial?')
                    ->modalDescription('Esta ação transferirá o contrato para o ensino Presencial.')
                    ->modalSubmitActionLabel('Confirmar Transferência para Presencial')
                    ->action(function (Contract $record): void {
                        try {
                            $service = app(\App\Services\OuroModernoEadService::class);
                            $res = $service->migrateContractToLocalLms($record);
                            Notification::make()->title('Transferido para Presencial!')->body($res['message'])->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Erro na Transferência')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Action::make('syncEad')
                    ->label('Sincronizar EAD')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(fn (Contract $record): bool => (bool) $record->is_ead)
                    ->action(function (Contract $record): void {
                        $service = app(\App\Services\OuroModernoEadService::class);
                        $res = $service->syncStudentProgress($record);
                        if ($res['success']) {
                            Notification::make()->title('Progresso EAD Atualizado')->body($res['message'])->success()->send();
                        } else {
                            Notification::make()->title('Falha ao Sincronizar')->body($res['message'])->danger()->send();
                        }
                    }),

                Action::make('openEadPortal')
                    ->label('Acesso Portal EAD')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('primary')
                    ->visible(fn (Contract $record): bool => (bool) $record->is_ead && filled($record->ead_student_id))
                    ->action(function (Contract $record, \Livewire\Component $livewire): void {
                        $service = app(\App\Services\OuroModernoEadService::class);
                        $url = $service->getStudentAutoLoginUrl($record);
                        if ($url) {
                            $livewire->js("window.open('{$url}', '_blank');");
                        } else {
                            Notification::make()->title('Não foi possível gerar auto-login')->warning()->send();
                        }
                    }),

                Action::make('whatsappEad')
                    ->label('WhatsApp EAD')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->color('success')
                    ->visible(fn (Contract $record): bool => (bool) $record->is_ead)
                    ->action(function (Contract $record, \Livewire\Component $livewire): void {
                        $calcService = app(\App\Services\FinancialCalculationService::class);
                        $phone = $calcService->resolveContractContactPhone($record);
                        if (! $phone) {
                            Notification::make()->title('Telefone não encontrado')->warning()->send();
                            return;
                        }
                        $name = $record->student?->name ?? 'Aluno';
                        $prog = number_format((float) ($record->ead_last_progress_percentage ?? 0), 0);
                        $schoolName = $record->school?->trade_name
                            ?: ($record->school?->legal_name
                                ?: (School::getActiveSchool()?->trade_name ?: 'nossa escola'));
                        $msg = "Olá, {$name}! 👋 Estamos acompanhando seu curso online no EAD da {$schoolName}. Você já completou {$prog}% das suas aulas! Parabéns e bons estudos!";
                        $cleanPhone = $calcService->formatInternationalPhone($phone);
                        $livewire->js("window.open('https://wa.me/{$cleanPhone}?text='.rawurlencode(\$msg), '_blank');");
                    }),

                DeleteAction::make()
                    ->modalDescription('Tem certeza que deseja excluir este contrato? Todas as parcelas em aberto vinculadas a este contrato serão excluídas automaticamente.')
                    ->before(function (Contract $record, DeleteAction $action): void {
                        $hasPaid = $record->installments()
                            ->where(function ($q): void {
                                $q->where('status', 'paid')
                                    ->orWhereNotNull('paid_at')
                                    ->orWhere('amount_paid', '>', 0);
                            })
                            ->exists();

                        if ($hasPaid) {
                            Notification::make()
                                ->title('Exclusão não permitida')
                                ->body('Este contrato possui parcelas já pagas no caixa/financeiro. Para encerrar o vínculo, utilize a opção Cancelar Contrato.')
                                ->danger()
                                ->persistent()
                                ->send();

                            $action->halt();
                        }
                    }),
                RestoreAction::make()
                    ->authorize(fn (Contract $record): bool => (auth()->user()?->can('Reactivate:Contract') ?? false)
                        || (auth()->user()?->can('Restore:Contract') ?? false)
                        || (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('super_admin'))),
                ForceDeleteAction::make()
                    ->authorize(fn (Contract $record): bool => (auth()->user()?->can('ForceDelete:Contract') ?? false)
                        || (method_exists(auth()->user(), 'hasRole') && auth()->user()->hasRole('super_admin'))),
            ])
                ->label('Ações do Contrato')
                ->icon('heroicon-m-cog-6-tooth')
                ->color('primary')
                ->button(),
        ];
    }

    protected function makeRenegotiateDebtAction(): Action
    {
        return Action::make('renegotiateDebt')
            ->label('Renegociar Dívidas')
            ->icon('heroicon-m-banknotes')
            ->color('warning')
            ->visible(fn (Contract $record): bool => ! in_array($record->status, ['canceled', 'finished'])
                && $record->installments()->whereIn('status', ['pending', 'partial'])->exists())
            ->modalHeading('Renegociação e Acordo de Dívidas')
            ->modalDescription('Selecione as parcelas em atraso ou pendentes para cancelar, aplicar desconto, registrar entrada e reparcelar o saldo restante.')
            ->modalSubmitActionLabel('Formalizar Acordo')
            ->modalWidth('4xl')
            ->schema([
                Section::make('Parcelas a Renegociar')->schema([
                    CheckboxList::make('installment_ids')
                        ->label('Selecione as parcelas para o acordo')
                        ->options(function (Contract $record): array {
                            return $record->installments()
                                ->whereIn('status', ['pending', 'partial'])
                                ->orderBy('due_date')
                                ->get()
                                ->mapWithKeys(function ($inst): array {
                                    $num = $inst->installment_number ? "Parcela {$inst->installment_number}/{$inst->total_installments}" : 'Taxa Avulsa';
                                    $venc = $inst->due_date ? Carbon::parse($inst->due_date)->format('d/m/Y') : '-';
                                    $val = number_format($inst->amount, 2, ',', '.');
                                    $tipo = $inst->financialType?->name ?? 'Mensalidade';
                                    $atraso = $inst->due_date && Carbon::parse($inst->due_date)->isPast() ? ' (ATRASADA)' : '';

                                    return [$inst->id => "{$num} [{$tipo}] — R$ {$val} — Vencimento: {$venc}{$atraso}"];
                                })
                                ->all();
                        })
                        ->default(function (Contract $record): array {
                            return $record->installments()
                                ->whereIn('status', ['pending', 'partial'])
                                ->pluck('id')
                                ->all();
                        })
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, Contract $record): void {
                            $selectedIds = (array) ($get('installment_ids') ?? []);
                            $total = $record->installments()->whereIn('id', $selectedIds)->sum('amount');
                            $set('total_original_amount', number_format($total, 2, '.', ''));
                            $discount = floatval($get('discount_amount') ?? 0);
                            $agreed = max(0, $total - $discount);
                            $set('total_agreed_amount', number_format($agreed, 2, '.', ''));
                        }),
                ]),

                Section::make('Valores e Condições')->schema([
                    Grid::make(3)->schema([
                        TextInput::make('total_original_amount')
                            ->label('Total Original')
                            ->prefix('R$')
                            ->readOnly()
                            ->dehydrated()
                            ->default(fn (Contract $record): string => number_format($record->installments()->whereIn('status', ['pending', 'partial'])->sum('amount'), 2, '.', '')),

                        TextInput::make('discount_amount')
                            ->label('Desconto Concedido')
                            ->prefix('R$')
                            ->numeric()
                            ->default('0.00')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                $total = floatval($get('total_original_amount') ?? 0);
                                $discount = floatval($get('discount_amount') ?? 0);
                                $agreed = max(0, $total - $discount);
                                $set('total_agreed_amount', number_format($agreed, 2, '.', ''));
                            }),

                        TextInput::make('total_agreed_amount')
                            ->label('Total Líquido do Acordo')
                            ->prefix('R$')
                            ->readOnly()
                            ->dehydrated()
                            ->default(fn (Contract $record): string => number_format($record->installments()->whereIn('status', ['pending', 'partial'])->sum('amount'), 2, '.', '')),
                    ]),

                    Grid::make(3)->schema([
                        TextInput::make('down_payment_amount')
                            ->label('Valor da Entrada')
                            ->prefix('R$')
                            ->numeric()
                            ->default('0.00')
                            ->live(onBlur: true),

                        Toggle::make('down_payment_paid')
                            ->label('Entrada paga no ato')
                            ->helperText('Registra recebimento no caixa aberto.')
                            ->default(false)
                            ->live(),

                        Select::make('payment_method_id')
                            ->label('Forma de Pgto da Entrada')
                            ->options(fn (): array => PaymentMethod::pluck('name', 'id')->all())
                            ->visible(fn (Get $get): bool => (bool) $get('down_payment_paid') && floatval($get('down_payment_amount') ?? 0) > 0)
                            ->required(fn (Get $get): bool => (bool) $get('down_payment_paid') && floatval($get('down_payment_amount') ?? 0) > 0),
                    ]),

                    Grid::make(3)->schema([
                        TextInput::make('installments_count')
                            ->label('Qtd. Parcelas Saldo Restante')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(36)
                            ->default(1)
                            ->required(),

                        DatePicker::make('first_due_date')
                            ->label('1º Vencimento do Saldo')
                            ->default(now()->addMonth()->startOfDay())
                            ->required(),

                        Select::make('financial_type_id')
                            ->label('Classificação Financeira')
                            ->options(fn (): array => FinancialType::pluck('name', 'id')->all())
                            ->default(function (): ?int {
                                $likeOp = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

                                return FinancialType::query()->where('name', $likeOp, '%acordo%')->value('id') ?? FinancialType::first()?->id;
                            }),
                    ]),

                    Textarea::make('notes')
                        ->label('Observações do Acordo')
                        ->placeholder('Motivo da renegociação, condições combinadas com o responsável...')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),
            ])
            ->action(function (Contract $record, array $data): void {
                try {
                    $service = app(DebtAgreementService::class);
                    $agreement = $service->createAgreement($record, $data, auth()->user());

                    Notification::make()
                        ->title('Acordo de Dívida formalizado!')
                        ->body("Acordo nº {$agreement->agreement_number} gerado com sucesso.")
                        ->success()
                        ->actions([
                            Action::make('printTermo')
                                ->label('Imprimir Termo de Confissão')
                                ->icon('heroicon-m-printer')
                                ->url(route('contracts.termo-acordo', [$record, $agreement]), shouldOpenInNewTab: true),
                        ])
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Erro ao processar acordo')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected function makeImportOuroModernoAction(): Action
    {
        return Action::make('importOuroModernoCourses')
            ->label('Importar Cursos do LMS Ouro Moderno')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('primary')
            ->form([
                TextInput::make('cod_int')
                    ->label('Código do aluno na Ouro Moderno (cod_int)')
                    ->numeric()
                    ->required()
                    ->default(fn (Contract $record): ?int => $record->student?->ouro_moderno_cod_int)
                    ->helperText('Informe o código de integração do aluno na Ouro Moderno para buscar os cursos já cadastrados.'),
            ])
            ->modalHeading('Importar Cursos do LMS Ouro Moderno')
            ->modalDescription('Os cursos vinculados ao aluno no LMS Ouro Moderno serão consultados e vinculados automaticamente a este contrato.')
            ->modalSubmitActionLabel('Importar Cursos')
            ->action(function (Contract $record, array $data): void {
                if (! app(OuroModernoService::class)->forSchool($record->school_id)->isConfigured()) {
                    Notification::make()
                        ->title('Integração não configurada')
                        ->body('A URL e o Token da Ouro Moderno precisam ser preenchidos em Configurações > Ouro Moderno.')
                        ->warning()
                        ->send();

                    return;
                }

                $codInt = ! empty($data['cod_int']) ? (int) $data['cod_int'] : null;
                if ($codInt && $record->student) {
                    $record->student->update(['ouro_moderno_cod_int' => $codInt]);
                }

                $count = app(OuroModernoContractSyncService::class)->importStudentCoursesFromLms($record);

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
            });
    }
}
