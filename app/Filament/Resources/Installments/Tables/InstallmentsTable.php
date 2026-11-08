<?php

namespace App\Filament\Resources\Installments\Tables;

use App\Filament\Exports\InstallmentExporter;
use App\Models\CashRegister;
use App\Models\FinancialAccount;
use App\Models\Installment;
use App\Models\PaymentMethod;
use App\Services\FinancialCalculationService;
use App\Services\PixService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class InstallmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where(function ($q) {
                    // 1. Parcelas já pagas nesta unidade (mesmo que o contrato tenha sido concluído ou transferido)
                    $q->where('status', 'paid')
                        // 2. Contratos ativos (mostra todas as parcelas pertencentes a esta unidade)
                        ->orWhereHas('contract', fn ($subQ) => $subQ->withoutGlobalScopes()->where('status', 'active'))
                        // 3. OU Contratos cancelados, mas apenas se a parcela estiver pendente (taxa de cancelamento)
                        ->orWhere(function ($subQ) {
                            $subQ->where('status', 'pending')
                                ->whereHas('contract', fn ($cQ) => $cQ->withoutGlobalScopes()->where('status', 'canceled'));
                        });
                });
            })
            ->columns([
                TextColumn::make('contract.contract_number')
                    ->label('Nº Contrato')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contract.student.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('financialType.name')
                    ->label('Tipo')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('installment_number')
                    ->label('Parcela')
                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} / {$record->total_installments}" : 'Avulsa'),

                TextColumn::make('amount')
                    ->label('Valor Base')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(function ($state, $record) {
                        // Se estiver pendente ou parcial e a data de vencimento já passou de hoje
                        if (in_array($state, ['pending', 'partial']) && $record->due_date && $record->due_date->isPast()) {
                            return $state === 'partial' ? 'Parcial / Atrasado' : 'Atrasado';
                        }

                        return match ($state) {
                            'pending' => 'Pendente',
                            'paid' => 'Pago',
                            'partial' => 'Parcial',
                            'canceled' => 'Cancelado',
                            default => $state,
                        };
                    })
                    ->color(function ($state, $record) {
                        // Vermelho se estiver vencido (atrasado)
                        if (in_array($state, ['pending', 'partial']) && $record->due_date && $record->due_date->isPast()) {
                            return 'danger';
                        }

                        return match ($state) {
                            'pending' => 'warning',
                            'paid' => 'success',
                            'partial' => 'info',
                            'canceled' => 'gray',
                            default => 'gray',
                        };
                    }),

                // COLUNA ADICIONADA: Mostra graficamente como a parcela foi paga no formato badge
                TextColumn::make('paymentMethod.name')
                    ->label('Forma de Pagto')
                    ->badge()
                    ->color('gray')
                    ->placeholder('Em aberto'),

                TextColumn::make('paid_at')
                    ->label('Pago em')
                    ->date('d/m/Y')
                    ->placeholder('Não paga')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('receipt_path')
                    ->label('Anexo')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Anexo' : '—')
                    ->color(fn ($state): string => $state ? 'success' : 'gray')
                    ->icon(fn ($state): ?string => $state ? 'heroicon-m-paper-clip' : null)
                    ->url(fn (Installment $record): ?string => $record->receipt_path ? route('installments.attachment', $record) : null, shouldOpenInNewTab: true),
            ])
            ->filters([
                // Filtro de Período de Vencimento Compacto (lado a lado)
                Filter::make('due_date_range')
                    ->label('Período de Vencimento')
                    ->schema([
                        Section::make('Filtrar por Vencimento')
                            ->description('Selecione um atalho rápido ou informe o intervalo de datas desejado.')
                            ->icon('heroicon-m-calendar')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('quick_period')
                                            ->label('Atalho de vencimento')
                                            ->options([
                                                'this_month' => 'Este mês',
                                                'last_month' => 'Mês passado',
                                                'next_month' => 'Próximo mês',
                                                'custom' => 'Personalizado',
                                                'all' => 'Todo o período',
                                            ])
                                            ->default('this_month')
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set): void {
                                                if ($state === 'this_month') {
                                                    $set('from', now()->startOfMonth()->toDateString());
                                                    $set('until', now()->endOfMonth()->toDateString());
                                                } elseif ($state === 'last_month') {
                                                    $set('from', now()->subMonth()->startOfMonth()->toDateString());
                                                    $set('until', now()->subMonth()->endOfMonth()->toDateString());
                                                } elseif ($state === 'next_month') {
                                                    $set('from', now()->addMonth()->startOfMonth()->toDateString());
                                                    $set('until', now()->addMonth()->endOfMonth()->toDateString());
                                                } elseif ($state === 'all') {
                                                    $set('from', null);
                                                    $set('until', null);
                                                }
                                            }),
                                        DatePicker::make('from')
                                            ->label('Data Inicial')
                                            ->live()
                                            ->default(now()->startOfMonth()->toDateString())
                                            ->afterStateUpdated(fn (Set $set) => $set('quick_period', 'custom')),
                                        DatePicker::make('until')
                                            ->label('Data Final')
                                            ->live()
                                            ->default(now()->endOfMonth()->toDateString())
                                            ->afterStateUpdated(fn (Set $set) => $set('quick_period', 'custom')),
                                    ]),
                            ]),
                    ])->columnSpanFull()
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Vencimento a partir de: '.Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Vencimento até: '.Carbon::parse($data['until'])->format('d/m/Y');
                        }

                        return $indicators;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date)
                            );
                    }),
                Filter::make('paid_date_range')
                    ->label('Período de Pagamento')
                    ->schema([
                        Section::make('Filtrar por data de pagamento')
                            ->description('Selecione um atalho rápido ou informe o intervalo em que o pagamento foi realizado.')
                            ->icon('heroicon-m-calendar')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('quick_period_payment')
                                            ->label('Atalho de pagamento')
                                            ->options([
                                                'this_month' => 'Pago este mês',
                                                'last_month' => 'Pago mês passado',
                                                'custom' => 'Personalizado',
                                                'all' => 'Sem filtro de pagamento',
                                            ])
                                            ->placeholder('Selecione se desejar')
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set): void {
                                                if ($state === 'this_month') {
                                                    $set('from', now()->startOfMonth()->toDateString());
                                                    $set('until', now()->endOfMonth()->toDateString());
                                                } elseif ($state === 'last_month') {
                                                    $set('from', now()->subMonth()->startOfMonth()->toDateString());
                                                    $set('until', now()->subMonth()->endOfMonth()->toDateString());
                                                } elseif ($state === 'all') {
                                                    $set('from', null);
                                                    $set('until', null);
                                                }
                                            }),
                                        DatePicker::make('from')
                                            ->label('Data Inicial')
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set) => $set('quick_period_payment', 'custom')),
                                        DatePicker::make('until')
                                            ->label('Data Final')
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set) => $set('quick_period_payment', 'custom')),
                                    ]),
                            ]),
                    ])
                    // Garante que o Filament saiba redefinir os campos ao limpar
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Pago a partir de: '.Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Pago até: '.Carbon::parse($data['until'])->format('d/m/Y');
                        }

                        return $indicators;
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('paid_at', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('paid_at', '<=', $date)
                            );
                    })->columnSpanFull(),

                SelectFilter::make('financial_type_id')
                    ->label('Tipo de Cobrança')
                    ->relationship('financialType', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Situação')
                    ->options([
                        'pending' => 'Pendente',
                        'partial' => 'Parcial',
                        'paid' => 'Pago',
                        'canceled' => 'Cancelado',
                    ]),

                SelectFilter::make('payment_method_id')
                    ->label('Forma de Pagamento')
                    ->relationship('paymentMethod', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                self::makePayAction(),
                self::makeWhatsappAction(),
                ViewAction::make()->iconButton()->tooltip('Visualizar detalhes da parcela'),
                ActionGroup::make([
                    self::makeReceiptAction(),
                    self::makeAttachmentAction(),
                    self::makeRefundAction(),
                    self::makeCancelAction(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->authorize('delete')
                        ->visible(fn (Installment $record): bool => filament()->auth()->user()?->can('delete', $record) ?? false),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('cancelSelected')
                        ->label('Cancelar Selecionadas')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->authorize(fn (): bool => auth()->user()?->can('cancelAny', Installment::class) ?? false)
                        ->modalHeading('Cancelar Parcelas Selecionadas')
                        ->modalDescription('Todas as parcelas selecionadas que estiverem em aberto serão marcadas como canceladas com o motivo informado.')
                        ->modalSubmitActionLabel('Cancelar Todas')
                        ->form([
                            Textarea::make('cancellation_reason')
                                ->label('Motivo do Cancelamento')
                                ->placeholder('Ex: Acordo coletivo, bolsa institucional, ajuste financeiro da turma...')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            $canceledCount = 0;
                            $reason = (string) $data['cancellation_reason'];
                            $userId = auth()->id();

                            foreach ($records as $record) {
                                if (in_array($record->status, ['pending', 'partial'], true)) {
                                    $record->cancel($reason, $userId);
                                    $canceledCount++;
                                }
                            }

                            Notification::make()
                                ->title('Parcelas Canceladas')
                                ->body("{$canceledCount} parcela(s) foram canceladas com sucesso.")
                                ->warning()
                                ->send();
                        }),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar parcelas')->exporter(InstallmentExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Installment') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Installment') ?? false),
            ]);
    }

    public static function makePayAction(): Action
    {
        return Action::make('pay')
            ->label('Baixar')
            ->tooltip('Baixar Parcela (Recebimento)')
            ->icon('heroicon-m-banknotes')
            ->color('success')
            ->visible(fn (Installment $record): bool => in_array($record->status, ['pending', 'partial']))
            ->requiresConfirmation()
            ->modalWidth('4xl')
            ->before(function (Action $action, Installment $record): void {
                $tenantId = Filament::getTenant()?->id ?? $record->contract?->school_id;
                $userId = Auth::id();
                $isSuperAdmin = Auth::user()?->hasRole('super_admin');

                $query = CashRegister::query()
                    ->where('status', 'open')
                    ->when($tenantId, fn ($q) => $q->where('school_id', $tenantId));

                $hasCashRegister = (clone $query)->where('user_id', $userId)->exists()
                    || ($isSuperAdmin && (clone $query)->exists());

                if (! $hasCashRegister) {
                    Notification::make()
                        ->title('Nenhum caixa aberto nesta escola')
                        ->body('Você precisa ter um caixa aberto nesta escola para registrar o recebimento de parcelas. Abra seu caixa antes de continuar.')
                        ->danger()
                        ->send();

                    $action->halt();
                }
            })
            ->modalHeading(fn (Installment $record): string => "Baixar Parcela — Contrato #{$record->contract?->contract_number} ({$record->contract?->student?->name})")
            ->modalDescription('Confirme os dados do recebimento. Para pagamentos parciais, informe o valor pago e a data de vencimento do saldo restante.')
            ->modalSubmitActionLabel('Confirmar Recebimento')
            ->form([
                Grid::make(3)
                    ->schema([
                        DatePicker::make('paid_at')
                            ->label('Data do Pagamento')
                            ->default(today())
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, Installment $record, $state): void {
                                $calcService = app(FinancialCalculationService::class);
                                $payDate = $state ? Carbon::parse($state) : today();

                                $overdue = $calcService->calculateOverdueCharges($record, $payDate);
                                $canWaiveFine = auth()->user()?->can('waiveFine', $record) ?? false;
                                if (! $canWaiveFine || empty($get('fine'))) {
                                    $set('fine', $overdue['fine_amount'] + $overdue['interest_amount']);
                                }

                                $punctual = $calcService->calculatePunctualityDiscount($record, $payDate);
                                if ($punctual['is_eligible'] && (bool) $get('apply_punctuality_discount')) {
                                    $set('discount', $punctual['discount_amount']);
                                } elseif (! $punctual['is_eligible']) {
                                    $set('apply_punctuality_discount', false);
                                    $set('discount', (float) ($record->discount ?? 0));
                                }

                                if (empty($get('amount_paid'))) {
                                    $base = (float) $record->amount;
                                    $disc = (float) ($get('discount') ?? 0);
                                    $fine = (float) ($get('fine') ?? 0);
                                    $set('amount_paid', max(0, ($base - $disc) + $fine));
                                }
                            }),

                        TextInput::make('base_amount')
                            ->label('Valor Base da Parcela')
                            ->numeric()
                            ->prefix('R$')
                            ->default(fn (Installment $record): float => (float) $record->amount)
                            ->disabled()
                            ->dehydrated(false),

                        Toggle::make('apply_punctuality_discount')
                            ->label('Desconto Pontualidade')
                            ->helperText(function (Get $get, Installment $record): string {
                                $calcService = app(FinancialCalculationService::class);
                                $payDate = $get('paid_at') ? Carbon::parse($get('paid_at')) : today();
                                $info = $calcService->calculatePunctualityDiscount($record, $payDate);

                                return $info['explanation'] ?: 'Desconto de pontualidade da escola.';
                            })
                            ->default(function (Installment $record): bool {
                                $calcService = app(FinancialCalculationService::class);
                                $info = $calcService->calculatePunctualityDiscount($record, today());

                                return $info['is_eligible'];
                            })
                            ->visible(function (Get $get, Installment $record): bool {
                                $calcService = app(FinancialCalculationService::class);
                                $payDate = $get('paid_at') ? Carbon::parse($get('paid_at')) : today();
                                $info = $calcService->calculatePunctualityDiscount($record, $payDate);

                                return $info['is_enabled'];
                            })
                            ->disabled(fn (Installment $record): bool => ! (auth()->user()?->can('applyPunctualityDiscount', $record) ?? false))
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, Installment $record, $state): void {
                                $calcService = app(FinancialCalculationService::class);
                                $payDate = $get('paid_at') ? Carbon::parse($get('paid_at')) : today();
                                $info = $calcService->calculatePunctualityDiscount($record, $payDate);

                                if ($state && $info['is_eligible']) {
                                    $set('discount', $info['discount_amount']);
                                } else {
                                    $set('discount', (float) ($record->discount ?? 0));
                                }

                                $base = (float) $record->amount;
                                $disc = (float) ($get('discount') ?? 0);
                                $fine = (float) ($get('fine') ?? 0);
                                $set('amount_paid', max(0, ($base - $disc) + $fine));
                            }),
                    ]),

                Grid::make(3)
                    ->schema([
                        TextInput::make('discount')
                            ->label('Desconto (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->default(function (Installment $record): float {
                                $calcService = app(FinancialCalculationService::class);
                                $info = $calcService->calculatePunctualityDiscount($record, today());
                                if ($info['is_eligible']) {
                                    return $info['discount_amount'];
                                }

                                return (float) ($record->discount ?? 0);
                            })
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, Installment $record): void {
                                if (empty($get('amount_paid'))) {
                                    $base = (float) $record->amount;
                                    $disc = (float) ($get('discount') ?? 0);
                                    $fine = (float) ($get('fine') ?? 0);
                                    $set('amount_paid', max(0, ($base - $disc) + $fine));
                                }
                            }),

                        TextInput::make('fine')
                            ->label('Multa / Acréscimo (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->default(function (Installment $record): float {
                                $calcService = app(FinancialCalculationService::class);
                                $overdue = $calcService->calculateOverdueCharges($record, today());

                                return (float) ($overdue['total_late_charges'] > 0 ? $overdue['total_late_charges'] : ($record->fine ?? 0));
                            })
                            ->helperText(function (Get $get, Installment $record): ?string {
                                $calcService = app(FinancialCalculationService::class);
                                $payDate = $get('paid_at') ? Carbon::parse($get('paid_at')) : today();
                                $overdue = $calcService->calculateOverdueCharges($record, $payDate);

                                return $overdue['is_overdue'] ? $overdue['explanation'] : 'Nenhum encargo por atraso.';
                            })
                            ->disabled(fn (Installment $record): bool => ! (auth()->user()?->can('waiveFine', $record) ?? false))
                            ->dehydrated(true)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Get $get, Set $set, Installment $record): void {
                                if (empty($get('amount_paid'))) {
                                    $base = (float) $record->amount;
                                    $disc = (float) ($get('discount') ?? 0);
                                    $fine = (float) ($get('fine') ?? 0);
                                    $set('amount_paid', max(0, ($base - $disc) + $fine));
                                }
                            }),

                        TextInput::make('amount_paid')
                            ->label('Valor a Receber (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->default(function (Installment $record): float {
                                $calcService = app(FinancialCalculationService::class);
                                $overdue = $calcService->calculateOverdueCharges($record, today());
                                $punctual = $calcService->calculatePunctualityDiscount($record, today());

                                $base = (float) $record->amount;
                                $disc = $punctual['is_eligible'] ? $punctual['discount_amount'] : (float) ($record->discount ?? 0);
                                $fine = $overdue['total_late_charges'] > 0 ? $overdue['total_late_charges'] : (float) ($record->fine ?? 0);

                                return max(0, ($base - $disc) + $fine);
                            })
                            ->live(onBlur: true)
                            ->helperText(function (Get $get, Installment $record): ?string {
                                $base = (float) $record->amount;
                                $disc = (float) ($get('discount') ?? 0);
                                $fine = (float) ($get('fine') ?? 0);
                                $totalDevido = max(0, ($base - $disc) + $fine);
                                $paid = (float) $get('amount_paid');

                                if ($paid > 0 && $paid < $totalDevido) {
                                    $diff = number_format($totalDevido - $paid, 2, ',', '.');

                                    return "⚠️ Baixa parcial: será gerada automaticamente uma nova parcela com o saldo restante de R$ {$diff}.";
                                }

                                return null;
                            })
                            ->required(),
                    ]),

                Grid::make(2)
                    ->schema([
                        Select::make('payment_method_id')
                            ->label('Forma de Pagamento')
                            ->options(fn (): array => PaymentMethod::query()->where('is_active', true)->pluck('name', 'id')->all())
                            ->default(fn (Installment $record): ?int => $record->payment_method_id ?? PaymentMethod::first()?->id)
                            ->required(),

                        Select::make('cash_register_id')
                            ->label('Lançar no Caixa Aberto')
                            ->options(function (Installment $record): array {
                                $tenantId = Filament::getTenant()?->id ?? $record->contract?->school_id;
                                $userId = Auth::id();
                                $isSuperAdmin = Auth::user()?->hasRole('super_admin');

                                return CashRegister::query()
                                    ->where('status', 'open')
                                    ->when($tenantId, fn ($q) => $q->where('school_id', $tenantId))
                                    ->when(! $isSuperAdmin, fn ($q) => $q->where('user_id', $userId))
                                    ->get()
                                    ->mapWithKeys(fn (CashRegister $cr): array => [$cr->id => "{$cr->name} (Aberto por {$cr->operator?->person?->name})"])
                                    ->all();
                            })
                            ->default(function (Installment $record) {
                                $tenantId = Filament::getTenant()?->id ?? $record->contract?->school_id;
                                $userId = Auth::id();

                                return CashRegister::query()
                                    ->where('status', 'open')
                                    ->when($tenantId, fn ($q) => $q->where('school_id', $tenantId))
                                    ->where('user_id', $userId)
                                    ->value('id')
                                    ?? CashRegister::query()
                                        ->where('status', 'open')
                                        ->when($tenantId, fn ($q) => $q->where('school_id', $tenantId))
                                        ->value('id');
                            })
                            ->required()
                            ->helperText('O valor recebido entrará no caixa aberto do operador logado nesta escola.'),
                    ]),

                DatePicker::make('remaining_due_date')
                    ->label('Vencimento do Saldo Restante')
                    ->helperText('Informe o vencimento para a nova parcela que será criada com o saldo faltante.')
                    ->default(fn (Installment $record): string => $record->due_date ? Carbon::parse($record->due_date)->toDateString() : today()->toDateString())
                    ->visible(function (Get $get, Installment $record): bool {
                        $base = (float) $record->amount;
                        $disc = (float) ($get('discount') ?? 0);
                        $fine = (float) ($get('fine') ?? 0);
                        $totalDevido = max(0, ($base - $disc) + $fine);
                        $paid = (float) $get('amount_paid');

                        return $paid > 0 && $paid < $totalDevido;
                    })
                    ->required(function (Get $get, Installment $record): bool {
                        $base = (float) $record->amount;
                        $disc = (float) ($get('discount') ?? 0);
                        $fine = (float) ($get('fine') ?? 0);
                        $totalDevido = max(0, ($base - $disc) + $fine);
                        $paid = (float) $get('amount_paid');

                        return $paid > 0 && $paid < $totalDevido;
                    }),

                FileUpload::make('receipt_path')
                    ->label('Comprovante de pagamento')
                    ->disk('local')
                    ->visibility('private')
                    ->directory('installments/receipts')
                    ->maxSize(2048)
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                    ->openable()
                    ->downloadable()
                    ->helperText('Anexe o comprovante Pix, transferência ou comprovante bancário (PDF ou imagem até 2MB).'),
            ])
            ->action(function (Installment $record, array $data): void {
                $data['status'] = 'paid';
                if (! empty($data['apply_punctuality_discount'])) {
                    $data['punctuality_discount_applied'] = true;
                }
                $record->processarBaixa($data);
            });
    }

    public static function makeWhatsappAction(): Action
    {
        return Action::make('whatsappCharge')
            ->label('WhatsApp')
            ->icon('heroicon-m-chat-bubble-left-ellipsis')
            ->color('success')
            ->tooltip('Cobrar via WhatsApp com código Pix')
            ->visible(fn (Installment $record): bool => in_array($record->status, ['pending', 'partial'], true) && (auth()->user()?->can('chargeWhatsApp', $record) ?? false))
            ->modalHeading(fn (Installment $record): string => "Cobrança via WhatsApp — Contrato #{$record->contract?->contract_number}")
            ->modalDescription('Confirme o número e a mensagem que será aberta no seu WhatsApp.')
            ->modalSubmitActionLabel('Abrir WhatsApp')
            ->form([
                TextInput::make('phone')
                    ->label('Telefone do Responsável / Aluno')
                    ->placeholder('Ex: 11999998888')
                    ->required()
                    ->default(fn (Installment $record): string => app(FinancialCalculationService::class)->resolveContactPhone($record) ?? '')
                    ->helperText('Número formatado com DDD. Ao confirmar, o WhatsApp Web abrirá para este contato.'),

                Textarea::make('message')
                    ->label('Mensagem da Cobrança')
                    ->rows(8)
                    ->required()
                    ->default(function (Installment $record): string {
                        $studentName = $record->contract?->student?->name ?? 'Aluno(a)';
                        $contractNumber = $record->contract?->contract_number ?? $record->contract_id;
                        $num = $record->installment_number ?: '1';
                        $total = $record->total_installments ?: '1';
                        $dueDate = $record->due_date ? Carbon::parse($record->due_date)->format('d/m/Y') : '-';
                        $amount = number_format((float) ($record->amount_paid > 0 ? $record->amount_paid : $record->amount), 2, ',', '.');

                        $school = $record->school
                            ?? $record->contract?->school
                            ?? (class_exists(Filament::class) ? Filament::getTenant() : null)
                            ?? \App\Models\School::getActiveSchool()
                            ?? \App\Models\School::query()->first();
                        $schoolName = $school?->trade_name ?: ($school?->legal_name ?: 'nossa escola');

                        $pixData = app(PixService::class)->generateForInstallment($record, $school);
                        $pixCode = $pixData['payload'] ?? $pixData['copy_paste'] ?? '';

                        $msg = "Olá, {$studentName}! Tudo bem?\n\n";
                        $msg .= "Passando para lembrar sobre a parcela {$num}/{$total} do seu curso na {$schoolName} (Contrato #{$contractNumber}), com vencimento em {$dueDate}.\n";
                        $msg .= "Valor: R$ {$amount}\n\n";

                        if ($pixCode) {
                            $msg .= "Chave Pix Copia e Cola para pagamento:\n{$pixCode}\n\n";
                        }

                        $msg .= "Se você já realizou o pagamento, por favor desconsidere esta mensagem. Tenha um ótimo dia!";

                        return $msg;
                    }),
            ])
            ->action(function (Installment $record, array $data, $livewire): void {
                $phone = app(FinancialCalculationService::class)->formatInternationalPhone((string) $data['phone']);
                $text = rawurlencode($data['message']);
                $waUrl = "https://wa.me/{$phone}?text={$text}";

                $livewire->js("window.open('{$waUrl}', '_blank');");

                Notification::make()
                    ->title('WhatsApp Aberto!')
                    ->body('A tela de conversa com o responsável foi iniciada no seu navegador.')
                    ->success()
                    ->send();
            });
    }

    public static function makeReceiptAction(): Action
    {
        return Action::make('receipt')
            ->label('Recibo')
            ->icon('heroicon-o-document-text')
            ->color('info')
            ->url(fn (Installment $record): string => route('installments.receipt', $record))
            ->openUrlInNewTab()
            ->visible(fn (Installment $record): bool => in_array($record->status, ['paid', 'partial']));
    }

    public static function makeAttachmentAction(): Action
    {
        return Action::make('viewReceipt')
            ->label('Comprovante Anexado')
            ->icon('heroicon-o-paper-clip')
            ->color('info')
            ->tooltip('Visualizar comprovante anexado')
            ->visible(fn (Installment $record): bool => ! empty($record->receipt_path))
            ->url(fn (Installment $record): string => route('installments.attachment', $record), shouldOpenInNewTab: true);
    }

    public static function makeRefundAction(): Action
    {
        return Action::make('refund')
            ->label('Estornar Baixa')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->tooltip('Estornar baixa desta parcela')
            ->visible(fn (Installment $record): bool => $record->status === 'paid' && (auth()->user()?->can('refund', $record) ?? false))
            ->requiresConfirmation()
            ->modalHeading(fn (Installment $record): string => "Estornar Baixa — Parcela #{$record->installment_number}/{$record->total_installments}")
            ->modalDescription('Esta ação cancelará o recebimento registrado, retornará a parcela para a situação "Pendente" e gerará um lançamento de saída no caixa ou na conta bancária, além de registrar o histórico na auditoria.')
            ->modalSubmitActionLabel('Confirmar Estorno')
            ->form([
                Radio::make('refund_destination')
                    ->label('Onde debitar o valor do estorno?')
                    ->options([
                        'cash_register' => 'Caixa Diário (Lançamento de saída no caixa do operador)',
                        'financial_account' => 'Conta Bancária / Cofre (Saída no extrato da conta bancária)',
                        'none' => 'Apenas Contábil (Não movimentar saldo físico)',
                    ])
                    ->default('cash_register')
                    ->live(),

                Select::make('cash_register_id')
                    ->label('Caixa para Lançamento do Estorno')
                    ->visible(fn (Get $get): bool => ($get('refund_destination') ?? 'cash_register') === 'cash_register')
                    ->options(function (Installment $record): array {
                        $schoolId = $record->contract?->school_id ?? Filament::getTenant()?->id;

                        return CashRegister::query()
                            ->where('status', 'open')
                            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->default(function (Installment $record): ?int {
                        $schoolId = $record->contract?->school_id ?? Filament::getTenant()?->id;

                        return CashRegister::query()
                            ->where('user_id', Auth::id())
                            ->where('status', 'open')
                            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                            ->value('id')
                            ?? CashRegister::query()
                                ->where('status', 'open')
                                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                                ->value('id');
                    })
                    ->helperText('Selecione o caixa aberto onde a saída do estorno será registrada.'),

                Select::make('financial_account_id')
                    ->label('Conta Bancária / Cofre para Saída')
                    ->visible(fn (Get $get): bool => $get('refund_destination') === 'financial_account')
                    ->required(fn (Get $get): bool => $get('refund_destination') === 'financial_account')
                    ->options(function (Installment $record): array {
                        $schoolId = $record->contract?->school_id ?? Filament::getTenant()?->id;

                        return FinancialAccount::query()
                            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->helperText('O valor será debitado do saldo desta conta e registrado no extrato como "Estorno de recebimento".'),

                Textarea::make('refund_reason')
                    ->label('Motivo do Estorno (Obrigatório)')
                    ->placeholder('Ex: Baixa realizada na parcela errada pela secretaria, duplicidade de pagamento, estorno bancário...')
                    ->required()
                    ->minLength(5)
                    ->maxLength(255),
            ])
            ->action(function (Installment $record, array $data): void {
                $destination = $data['refund_destination'] ?? 'cash_register';
                $cashRegisterId = $destination === 'cash_register' && ! empty($data['cash_register_id'])
                    ? (int) $data['cash_register_id']
                    : null;
                $financialAccountId = $destination === 'financial_account' && ! empty($data['financial_account_id'])
                    ? (int) $data['financial_account_id']
                    : null;

                $record->estornarBaixa(
                    reason: $data['refund_reason'],
                    cashRegisterId: $cashRegisterId,
                    financialAccountId: $financialAccountId,
                    userId: Auth::id()
                );

                Notification::make()
                    ->title('Baixa Estornada com Sucesso!')
                    ->body("A parcela #{$record->installment_number} voltou para a situação pendente e o estorno foi registrado na auditoria.")
                    ->warning()
                    ->send();
            });
    }

    public static function makeCancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancelar Parcela')
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->tooltip('Cancelar cobrança desta parcela')
            ->visible(fn (Installment $record): bool => in_array($record->status, ['pending', 'partial'], true) && (auth()->user()?->can('cancel', $record) ?? false))
            ->requiresConfirmation()
            ->modalHeading('Confirmar Cancelamento da Parcela')
            ->modalDescription('Esta ação cancelará a cobrança da parcela mantendo o registro no histórico para auditoria.')
            ->modalSubmitActionLabel('Confirmar Cancelamento')
            ->form([
                Textarea::make('cancellation_reason')
                    ->label('Motivo do Cancelamento')
                    ->placeholder('Ex: Bolsa integral concedida, duplicidade de cobrança, cancelamento contratual...')
                    ->required()
                    ->maxLength(255),
            ])
            ->action(function (Installment $record, array $data): void {
                $record->cancel($data['cancellation_reason'], auth()->id());

                Notification::make()
                    ->title('Parcela Cancelada')
                    ->body("A parcela #{$record->installment_number} foi cancelada com sucesso.")
                    ->warning()
                    ->send();
            });
    }

    private static function getFormOptionsMeses(): array
    {
        $options = [];
        $start = Carbon::now()->subMonths(3);

        for ($i = 0; $i < 16; $i++) {
            $current = $start->copy()->addMonths($i);
            $options[$current->format('Y-m')] = ucfirst($current->translatedFormat('F / Y'));
        }

        return $options;
    }
}
