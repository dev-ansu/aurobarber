<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payables;

use App\Filament\Resources\Payables\Pages\ManagePayables;
use App\Models\FinancialType;
use App\Models\Payable;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
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

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return Filament::getTenant() !== null
            && ! auth()->user()?->isRestrictedBarber();
    }

    public static function getEloquentQuery(): Builder
    {
        $tenantId = Filament::getTenant()?->getKey();

        return parent::getEloquentQuery()
            ->where('barbershop_id', $tenantId ?? 0);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Dados da despesa')
                ->schema([
                    TextInput::make('description')
                        ->label('Descrição da despesa')
                        ->placeholder('Ex: Aluguel da barbearia, Conta de energia, Fornecedor de lâminas...')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('supplier')
                        ->label('Fornecedor / Credor')
                        ->placeholder('Ex: Imobiliária Central, Enel, Distribuidora Barber...')
                        ->maxLength(255),

                    Select::make('financial_type_id')
                        ->label('Conta Contábil / Plano de Contas')
                        ->options(function (): array {
                            $tenantId = Filament::getTenant()?->getKey();

                            return FinancialType::query()
                                ->forBarbershop($tenantId ? (int) $tenantId : null)
                                ->where('is_active', true)
                                ->where('is_synthetic', false)
                                ->orderByRaw("COALESCE(code, '999'), name")
                                ->get()
                                ->mapWithKeys(fn (FinancialType $item) => [
                                    $item->id => ($item->code ? "{$item->code} - " : '').$item->name,
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->helperText('Classificação contábil da despesa.'),

                    TextInput::make('amount')
                        ->label('Valor da conta')
                        ->numeric()
                        ->prefix('R$')
                        ->minValue(0.01)
                        ->required(),

                    DatePicker::make('due_date')
                        ->label('Data de vencimento')
                        ->default(today())
                        ->required(),

                    Textarea::make('notes')
                        ->label('Observações')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Recorrência e Repetição Automática')
                ->description('Configure para despesas fixas recorrentes (ex: aluguel, internet, sistemas).')
                ->schema([
                    Toggle::make('is_recurring')
                        ->label('Conta recorrente (repetir lançamento)')
                        ->reactive()
                        ->columnSpanFull(),

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
                        ->helperText('Ex: 12 irá gerar a conta inicial e mais 11 parcelas futuras.')
                        ->visible(fn (Get $get): bool => (bool) $get('is_recurring'))
                        ->required(fn (Get $get): bool => (bool) $get('is_recurring')),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Comprovante / Anexo da Despesa')
                ->description('Anexe boletos, faturas ou notas fiscais (PDF ou imagem até 2MB).')
                ->schema([
                    FileUpload::make('receipt_path')
                        ->label('Boleto ou comprovante')
                        ->disk('public')
                        ->directory('payables/receipts')
                        ->maxSize(2048)
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                        ->openable()
                        ->downloadable()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'asc')
            ->columns([
                TextColumn::make('description')
                    ->label('Despesa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('supplier')
                    ->label('Fornecedor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('financialType.name')
                    ->label('Categoria Contábil')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('amount')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(function (Payable $record): string {
                        if ($record->status === 'pending' && $record->isOverdue()) {
                            return 'danger';
                        }
                        if ($record->status === 'pending' && $record->due_date?->isToday()) {
                            return 'warning';
                        }

                        return 'gray';
                    }),

                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(function (string $state, Payable $record): string {
                        if ($state === 'pending' && $record->isOverdue()) {
                            return 'Vencida';
                        }

                        return match ($state) {
                            'paid' => 'Paga',
                            'cancelled' => 'Cancelada',
                            default => 'Pendente',
                        };
                    })
                    ->color(function (string $state, Payable $record): string {
                        if ($state === 'pending' && $record->isOverdue()) {
                            return 'danger';
                        }

                        return match ($state) {
                            'paid' => 'success',
                            'cancelled' => 'gray',
                            default => 'warning',
                        };
                    }),

                TextColumn::make('payment_date')
                    ->label('Data Pagto')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('amount_paid')
                    ->label('Valor Pago')
                    ->money('BRL')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

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

                        if (preg_match('/\((\d+\/\d+)\)/', $record->description, $matches)) {
                            return "{$periodLabel} ({$matches[1]})";
                        }

                        return $periodLabel;
                    })
                    ->color(fn (bool $state): string => $state ? 'info' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Situação')
                    ->options([
                        'pending' => 'Pendente',
                        'paid' => 'Paga',
                        'cancelled' => 'Cancelada',
                    ]),

                SelectFilter::make('financial_type_id')
                    ->label('Conta Contábil')
                    ->relationship('financialType', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_recurring')
                    ->label('Recorrência')
                    ->placeholder('Todas')
                    ->trueLabel('Apenas recorrentes')
                    ->falseLabel('Apenas únicas'),
            ])
            ->recordActions([
                Action::make('markPaid')
                    ->label('Baixar Pagamento')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Payable $record): bool => $record->status === 'pending')
                    ->form(fn (Payable $record): array => [
                        DatePicker::make('payment_date')
                            ->label('Data do pagamento')
                            ->default(today())
                            ->required(),

                        TextInput::make('amount_paid')
                            ->label('Valor pago')
                            ->numeric()
                            ->prefix('R$')
                            ->default($record->amount)
                            ->required()
                            ->helperText('Se o valor pago for menor que o valor original, o saldo restante gerará automaticamente uma nova conta pendente.'),

                        DatePicker::make('remaining_due_date')
                            ->label('Vencimento do saldo restante (se parcial)')
                            ->default($record->due_date?->copy()->addMonth() ?? today()->addMonth())
                            ->visible(fn (Get $get): bool => (float) ($get('amount_paid') ?? 0) < (float) $record->amount),

                        Select::make('payment_method')
                            ->label('Forma de pagamento')
                            ->options([
                                'pix' => 'Pix',
                                'dinheiro' => 'Dinheiro',
                                'debito' => 'Cartão de débito',
                                'credito' => 'Cartão de crédito',
                                'transferencia' => 'Transferência bancária',
                                'outro' => 'Outro',
                            ])
                            ->default('pix')
                            ->native(false),

                        FileUpload::make('receipt_path')
                            ->label('Comprovante de pagamento')
                            ->disk('public')
                            ->directory('payables/receipts')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->maxSize(2048),
                    ])
                    ->action(function (Payable $record, array $data): void {
                        $remaining = $record->pay($data);

                        $msg = 'Pagamento registrado com sucesso!';
                        if ($remaining) {
                            $restanteFmt = number_format((float) $remaining->amount, 2, ',', '.');
                            $msg .= " Gerada nova conta de saldo restante no valor de R$ {$restanteFmt}.";
                        }

                        Notification::make()
                            ->title('Pagamento baixado')
                            ->body($msg)
                            ->success()
                            ->send();
                    }),

                Action::make('viewReceipt')
                    ->label('Comprovante')
                    ->icon('heroicon-o-paper-clip')
                    ->color('gray')
                    ->visible(fn (Payable $record): bool => ! empty($record->receipt_path))
                    ->url(fn (Payable $record): string => Storage::disk('public')->url($record->receipt_path))
                    ->openUrlInNewTab(),

                EditAction::make()
                    ->visible(fn (Payable $record): bool => $record->status === 'pending'),

                DeleteAction::make()
                    ->visible(fn (Payable $record): bool => $record->status === 'pending'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePayables::route('/'),
        ];
    }
}
