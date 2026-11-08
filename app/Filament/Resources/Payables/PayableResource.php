<?php

namespace App\Filament\Resources\Payables;

use App\Filament\Resources\Payables\Pages\ManagePayables;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\FinancialType;
use App\Models\Payable;
use App\Models\PaymentMethod;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class PayableResource extends Resource
{
    protected static ?string $model = Payable::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Contas a pagar';

    protected static ?string $modelLabel = 'conta a pagar';

    protected static ?string $pluralModelLabel = 'contas a pagar';

    protected static ?string $slug = 'payables';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dados da conta')->schema([
                TextInput::make('description')
                    ->label('Descrição da despesa')
                    ->placeholder('Ex: Aluguel da sede, Conta de energia, Fornecedor de apostilas...')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),

                TextInput::make('supplier')
                    ->label('Fornecedor / Credor')
                    ->placeholder('Ex: Imobiliária Central, CPFL, Papelaria Brasil...')
                    ->maxLength(255),

                Select::make('financial_type_id')
                    ->label('Conta Contábil / Categoria')
                    ->options(fn (): array => FinancialType::query()
                        ->where('is_active', true)
                        ->where('is_synthetic', false)
                        ->orderByRaw("COALESCE(code, '999'), name")
                        ->get()
                        ->mapWithKeys(fn (FinancialType $item) => [
                            $item->id => ($item->code ? "{$item->code} - " : '') . $item->name,
                        ])
                        ->all())
                    ->searchable()
                    ->preload(),

                TextInput::make('amount')
                    ->label('Valor da conta')
                    ->numeric()
                    ->prefix('R$')
                    ->required(),

                DatePicker::make('due_date')
                    ->label('Data de vencimento')
                    ->required()
                    ->default(today()),

                Textarea::make('notes')
                    ->label('Observações')
                    ->rows(2)
                    ->columnSpanFull(),
            ])->columns(2)->columnSpanFull(),

            Section::make('Recorrência e Repetição Automática')
                ->description('Configure para gerar automaticamente os lançamentos futuros desta despesa (ex: aluguel, internet, assinaturas).')
                ->schema([
                    Toggle::make('is_recurring')
                        ->label('Conta recorrente (repetir lançamento)')
                        ->reactive(),

                    Select::make('recurrence_period')
                        ->label('Frequência de repetição')
                        ->options([
                            'monthly' => 'Mensal',
                            'weekly' => 'Semanal',
                            'bimonthly' => 'Bimestral',
                            'yearly' => 'Anual',
                        ])
                        ->default('monthly')
                        ->visible(fn (Get $get): bool => (bool) $get('is_recurring'))
                        ->required(fn (Get $get): bool => (bool) $get('is_recurring')),

                    TextInput::make('recurrence_limit')
                        ->label('Quantidade total de ocorrências')
                        ->numeric()
                        ->default(12)
                        ->minValue(2)
                        ->maxValue(48)
                        ->helperText('Ex: 12 irá gerar a conta atual e mais 11 contas nos períodos seguintes.')
                        ->visible(fn (Get $get): bool => (bool) $get('is_recurring'))
                        ->required(fn (Get $get): bool => (bool) $get('is_recurring')),
                ])->columns(3)->columnSpanFull(),

            Section::make('Comprovante / Anexo da Despesa')
                ->description('Arquivos leves (PDF ou imagem até 2MB). Armazenados localmente no servidor e incluídos automaticamente no backup.')
                ->schema([
                    FileUpload::make('receipt_path')
                        ->label('Boleto, fatura ou comprovante de pagamento')
                        ->disk('local')
                        ->visibility('private')
                        ->directory('payables/receipts')
                        ->maxSize(2048)
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                        ->openable()
                        ->downloadable()
                        ->columnSpanFull(),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'asc')
            ->columns([
                TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('supplier')
                    ->label('Fornecedor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('financialType.name')
                    ->label('Categoria')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Valor R$')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(function (string $state, Payable $record): string {
                        if ($state === 'pending' && $record->due_date?->isPast()) {
                            return 'Vencida';
                        }

                        return match ($state) {
                            'paid' => 'Paga',
                            'pending' => 'Pendente',
                            'canceled' => 'Cancelada',
                            default => ucfirst($state),
                        };
                    })
                    ->color(function (string $state, Payable $record): string {
                        if ($state === 'pending' && $record->due_date?->isPast()) {
                            return 'danger';
                        }

                        return match ($state) {
                            'paid' => 'success',
                            'pending' => 'warning',
                            'canceled' => 'danger',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('payment_date')
                    ->label('Pago em')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                TextColumn::make('amount_paid')
                    ->label('Valor pago')
                    ->money('BRL')
                    ->placeholder('—'),

                TextColumn::make('cashRegister.name')
                    ->label('Caixa')
                    ->placeholder('Sem caixa')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'info' : 'gray'),

                TextColumn::make('is_recurring')
                    ->label('Recorrência')
                    ->badge()
                    ->formatStateUsing(function (bool $state, Payable $record): string {
                        if (! $state) {
                            return 'Única';
                        }

                        $periodLabel = match ($record->recurrence_period) {
                            'weekly' => 'Semanal',
                            'bimonthly' => 'Bimestral',
                            'yearly' => 'Anual',
                            default => 'Mensal',
                        };

                        return "Recorrente ({$periodLabel})";
                    })
                    ->color(fn (bool $state): string => $state ? 'info' : 'gray'),

                TextColumn::make('receipt_path')
                    ->label('Comprovante')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Ver anexo' : 'Sem anexo')
                    ->color(fn ($state): string => $state ? 'success' : 'gray')
                    ->icon(fn ($state): ?string => $state ? 'heroicon-m-paper-clip' : null)
                    ->url(fn (Payable $record): ?string => $record->receipt_path ? route('payables.attachment', $record) : null, shouldOpenInNewTab: true),
            ])
            ->filters([
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

                Filter::make('payment_date_range')
                    ->label('Período de Pagamento')
                    ->schema([
                        Section::make('Filtrar por data de pagamento')
                            ->description('Selecione um atalho rápido ou informe o intervalo em que a conta foi paga.')
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
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date)
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date)
                            );
                    })->columnSpanFull(),

                SelectFilter::make('status')
                    ->label('Situação')
                    ->options([
                        'pending' => 'Pendente',
                        'paid' => 'Paga',
                        'canceled' => 'Cancelada',
                    ]),

                SelectFilter::make('financial_type_id')
                    ->label('Categoria financeira')
                    ->relationship('financialType', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('payment_method_id')
                    ->label('Forma de Pagamento')
                    ->relationship('paymentMethod', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('is_recurring')
                    ->label('Tipo de despesa')
                    ->options([
                        '1' => 'Apenas recorrentes',
                        '0' => 'Apenas pontuais',
                    ]),
            ])
            ->filtersLayout(FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                Action::make('pay')
                    ->label('Baixar conta')
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->visible(fn (Payable $record): bool => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalWidth('4xl')
                    ->before(function (Action $action, Payable $record): void {
                        $tenantId = Filament::getTenant()?->id ?? $record->school_id;
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
                                ->body('Você precisa ter um caixa aberto nesta escola para registrar o pagamento e a saída da despesa. Abra seu caixa antes de continuar.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    })
                    ->modalHeading('Registrar Pagamento de Conta')
                    ->modalDescription('Confirme os dados do pagamento. Se o valor pago for menor que o valor total da conta, uma nova conta a pagar será gerada automaticamente com o saldo restante.')
                    ->form([
                        DatePicker::make('payment_date')
                            ->label('Data do pagamento')
                            ->default(today())
                            ->required(),

                        TextInput::make('amount_paid')
                            ->label('Valor pago')
                            ->numeric()
                            ->prefix('R$')
                            ->default(fn (Payable $record): float => (float) $record->amount)
                            ->live()
                            ->helperText(function (Get $get, Payable $record): ?string {
                                $paid = (float) $get('amount_paid');
                                $total = (float) $record->amount;
                                if ($paid > 0 && $paid < $total) {
                                    $diff = number_format($total - $paid, 2, ',', '.');

                                    return "⚠️ Baixa parcial: será gerada uma nova conta com o saldo restante de R$ {$diff}.";
                                }

                                return null;
                            })
                            ->required(),

                        DatePicker::make('remaining_due_date')
                            ->label('Vencimento do saldo restante')
                            ->helperText('Informe o vencimento para a nova conta que será criada com o saldo restante.')
                            ->default(fn (Payable $record): string => $record->due_date ? Carbon::parse($record->due_date)->toDateString() : today()->toDateString())
                            ->visible(fn (Get $get, Payable $record): bool => (float) $get('amount_paid') > 0 && (float) $get('amount_paid') < (float) $record->amount)
                            ->required(fn (Get $get, Payable $record): bool => (float) $get('amount_paid') > 0 && (float) $get('amount_paid') < (float) $record->amount),

                        Select::make('payment_method_id')
                            ->label('Forma de pagamento')
                            ->options(fn (): array => PaymentMethod::query()->where('is_active', true)->pluck('name', 'id')->all())
                            ->default(fn (): ?int => PaymentMethod::query()->first()?->id)
                            ->required(),

                        Select::make('cash_register_id')
                            ->label('Debitar do Caixa Aberto')
                            ->options(function (Payable $record): array {
                                $tenantId = Filament::getTenant()?->id ?? $record->school_id;
                                $userId = Auth::id();
                                $isSuperAdmin = Auth::user()?->hasRole('super_admin');

                                return CashRegister::query()
                                    ->where('status', 'open')
                                    ->when($tenantId, fn ($q) => $q->where('school_id', $tenantId))
                                    ->when(! $isSuperAdmin, fn ($q) => $q->where('user_id', $userId))
                                    ->get()
                                    ->mapWithKeys(fn (CashRegister $cr): array => [$cr->id => "{$cr->name} (Operador: {$cr->operator?->person?->name})"])
                                    ->all();
                            })
                            ->default(function (Payable $record) {
                                $tenantId = Filament::getTenant()?->id ?? $record->school_id;
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
                            ->helperText('O valor pago sairá como movimentação de despesa do caixa aberto do operador logado nesta escola.'),

                        FileUpload::make('receipt_path')
                            ->label('Anexar comprovante de pagamento')
                            ->disk('local')
                            ->visibility('private')
                            ->directory('payables/receipts')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->openable()
                            ->downloadable()
                            ->helperText('PDF ou imagem até 2MB. Arquivo leve incluído no backup.'),
                    ])
                    ->action(function (Payable $record, array $data): void {
                        $remainingPayable = $record->pay($data);

                        if ($remainingPayable) {
                            $remainingFormatted = number_format((float) $remainingPayable->amount, 2, ',', '.');
                            $dueFormatted = $remainingPayable->due_date?->format('d/m/Y');
                            Notification::make()
                                ->title('Baixa parcial realizada!')
                                ->body("Pagamento registrado com sucesso. Foi gerada uma nova conta com o saldo restante de R$ {$remainingFormatted} (Vencimento: {$dueFormatted}).")
                                ->warning()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Conta baixada com sucesso!')
                                ->body('O pagamento total foi registrado e a conta foi marcada como Paga.')
                                ->success()
                                ->send();
                        }
                    }),
                Action::make('viewReceipt')
                    ->label('Comprovante')
                    ->icon('heroicon-o-paper-clip')
                    ->color('info')
                    ->tooltip('Visualizar comprovante de pagamento anexado')
                    ->visible(fn (Payable $record): bool => ! empty($record->receipt_path))
                    ->url(fn (Payable $record): string => route('payables.attachment', $record), shouldOpenInNewTab: true),

                Action::make('sendToCashRegister')
                    ->label('Lançar no Caixa')
                    ->icon('heroicon-m-banknotes')
                    ->color('warning')
                    ->visible(fn (Payable $record): bool => $record->status === 'paid' && is_null($record->cash_register_id))
                    ->modalHeading('Lançar Saída desta Despesa no Caixa Aberto')
                    ->modalDescription('Esta conta foi baixada sem vincular a um caixa. Deseja debitar o valor do caixa aberto agora?')
                    ->form([
                        Select::make('cash_register_id')
                            ->label('Caixa para Débito')
                            ->options(function (Payable $record): array {
                                $tenantId = Filament::getTenant()?->id ?? $record->school_id;
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
                            ->default(function (Payable $record) {
                                $tenantId = Filament::getTenant()?->id ?? $record->school_id;
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
                            ->required(),

                        Select::make('payment_method_id')
                            ->label('Forma de Pagamento')
                            ->options(fn (): array => PaymentMethod::query()->where('is_active', true)->pluck('name', 'id')->all())
                            ->default(fn (Payable $record): ?int => $record->payment_method_id ?? PaymentMethod::first()?->id)
                            ->required(),
                    ])
                    ->action(function (Payable $record, array $data): void {
                        DB::transaction(function () use ($record, $data) {
                            $amount = (float) ($record->amount_paid ?: $record->amount);
                            $cashRegisterId = $data['cash_register_id'];
                            $paymentMethodId = $data['payment_method_id'];

                            $record->update([
                                'cash_register_id' => $cashRegisterId,
                                'payment_method_id' => $paymentMethodId,
                            ]);

                            CashTransaction::create([
                                'cash_register_id' => $cashRegisterId,
                                'user_id' => Auth::id() ?? $record->created_by_id,
                                'type' => 'output',
                                'amount' => $amount,
                                'payment_method_id' => $paymentMethodId,
                                'description' => "Pagamento de conta: {$record->description}".($record->supplier ? " (Fornecedor: {$record->supplier})" : ''),
                                'financial_type_id' => $record->financial_type_id,
                            ]);

                            $cashRegister = CashRegister::find($cashRegisterId);
                            $cashRegister?->decrement('current_balance', $amount);
                        });

                        Notification::make()
                            ->title('Saída lançada no caixa!')
                            ->body('A despesa foi debitada e a movimentação registrada no caixa com sucesso.')
                            ->success()
                            ->send();
                    }),

                Action::make('repeat')
                    ->label('Repetir lançamento')
                    ->icon('heroicon-m-arrow-path')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Repetir Conta para Próximo Período')
                    ->modalDescription('Crie uma nova ocorrência desta despesa baseada nos mesmos dados.')
                    ->form([
                        DatePicker::make('due_date')
                            ->label('Novo Vencimento')
                            ->default(fn (Payable $record): string => $record->due_date ? Carbon::parse($record->due_date)->addMonth()->toDateString() : today()->addMonth()->toDateString())
                            ->required(),

                        TextInput::make('amount')
                            ->label('Valor da conta')
                            ->numeric()
                            ->prefix('R$')
                            ->default(fn (Payable $record): float => (float) $record->amount)
                            ->required(),
                    ])
                    ->action(function (Payable $record, array $data): void {
                        Payable::create([
                            'description' => $record->description,
                            'supplier' => $record->supplier,
                            'financial_type_id' => $record->financial_type_id,
                            'amount' => $data['amount'],
                            'due_date' => $data['due_date'],
                            'status' => 'pending',
                            'notes' => $record->notes,
                            'is_recurring' => $record->is_recurring,
                            'recurrence_period' => $record->recurrence_period,
                            'recurrence_group_id' => $record->recurrence_group_id,
                        ]);

                        Notification::make()
                            ->title('Lançamento repetido!')
                            ->body('A nova conta foi cadastrada com sucesso para a data informada.')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePayables::route('/'),
        ];
    }
}
