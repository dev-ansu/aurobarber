<?php

namespace App\Filament\Resources\Installments\Schemas;

use App\Models\FinancialType;
use App\Models\PaymentMethod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class InstallmentsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da Cobrança')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('contract_id')
                                    ->label('Contrato Origem')
                                    ->relationship('contract', 'contract_number')
                                    ->disabled(),

                                Select::make('financial_type_id')
                                    ->label('Tipo / Conta')
                                    ->relationship('financialType', 'name')
                                    ->options(fn () => FinancialType::where('is_active', true)->where('is_synthetic', false)->orderByRaw("COALESCE(code, '999'), name")->get()->mapWithKeys(fn ($item) => [$item->id => ($item->code ? "{$item->code} - " : '') . $item->name]))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabled(),

                                DatePicker::make('due_date')
                                    ->label('Data de Vencimento')
                                    ->required()
                                    ->disabled(fn ($record) => $record && $record->status === 'paid'),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Valores e Conciliação Manual')
                    ->description('Campos utilizados para manutenção manual da tesouraria.')
                    ->disabled(fn ($record) => $record && $record->status === 'paid')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('amount')
                                    ->label('Valor Base Original')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcularValorPago($get, $set))
                                    ->disabled(),

                                Select::make('status')
                                    ->label('Situação do Pagamento')
                                    ->options([
                                        'pending' => 'Pendente',
                                        'paid' => 'Confirmar Recebimento (Baixa)',
                                        'canceled' => 'Cancelada',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        if ($state === 'paid') {
                                            // Preenche a data de pagamento se estiver vazia
                                            if (! $get('paid_at')) {
                                                $set('paid_at', now()->format('Y-m-d'));
                                            }
                                            // Preenche o valor apenas se estiver vazio, permitindo digitação parcial posterior
                                            if (empty($get('amount_paid'))) {
                                                self::recalcularValorPago($get, $set);
                                            }
                                        }
                                    })
                                    ->required(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('discount')
                                    ->label('Desconto Manual')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0.00)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($get, $set) => self::recalcularValorPago($get, $set)),

                                TextInput::make('fine')
                                    ->label('Multa / Acréscimo')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0.00)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($get, $set) => self::recalcularValorPago($get, $set)),

                                TextInput::make('amount_paid')
                                    ->label('Total Efetivamente Recebido (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->placeholder('Informe o valor pago')
                                    ->helperText('O valor não pode ser maior que o total devido da parcela.')
                                    ->required(fn ($get) => $get('status') === 'paid')
                                    ->rules([
                                        fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $amount = floatval($get('amount'));
                                            $discount = floatval($get('discount'));
                                            $fine = floatval($get('fine'));

                                            $totalDevido = max(0, ($amount - $discount) + $fine);

                                            if (floatval($value) > $totalDevido) {
                                                $fail('O valor recebido não pode ser maior que o total devido de R$ '.number_format($totalDevido, 2, ',', '.'));
                                            }
                                        },
                                    ]),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('paid_at')
                                    ->label('Data do Pagamento')
                                    ->default(now())
                                    ->required(fn ($get) => $get('status') === 'paid'),

                                Select::make('payment_method_id')
                                    ->label('Forma de Pagamento')
                                    ->relationship('paymentMethod', 'name')
                                    ->options(fn () => PaymentMethod::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Selecione como o aluno pagou')
                                    ->required(fn ($get) => $get('status') === 'paid'),
                            ]),

                        DatePicker::make('remaining_due_date')
                            ->label('Vencimento do Saldo Restante')
                            ->helperText('Como o valor recebido é menor que o total devido, uma nova parcela será gerada automaticamente com o saldo faltante nesta data.')
                            ->default(fn ($record) => $record?->due_date)
                            ->visible(function (Get $get): bool {
                                $amount = (float) $get('amount');
                                $discount = (float) $get('discount');
                                $fine = (float) $get('fine');
                                $totalDevido = max(0, ($amount - $discount) + $fine);
                                $paid = (float) $get('amount_paid');

                                return $get('status') === 'paid' && $paid > 0 && $paid < $totalDevido;
                            })
                            ->required(function (Get $get): bool {
                                $amount = (float) $get('amount');
                                $discount = (float) $get('discount');
                                $fine = (float) $get('fine');
                                $totalDevido = max(0, ($amount - $discount) + $fine);
                                $paid = (float) $get('amount_paid');

                                return $get('status') === 'paid' && $paid > 0 && $paid < $totalDevido;
                            }),
                    ])->columnSpanFull(),

                Section::make('Comprovante de Pagamento (Anexo)')
                    ->description('Anexe o comprovante Pix, transferência ou depósito enviado pelo aluno/responsável (PDF ou imagem até 2MB).')
                    ->schema([
                        FileUpload::make('receipt_path')
                            ->label('Comprovante do aluno')
                            ->disk('local')
                            ->visibility('private')
                            ->directory('installments/receipts')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'image/jpg'])
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }

    // Método auxiliar adaptado para preencher apenas por conveniência, sem travar a edição parcial
    private static function recalcularValorPago($get, $set): void
    {
        $amount = floatval($get('amount'));
        $discount = floatval($get('discount'));
        $fine = floatval($get('fine'));

        $totalCalculado = max(0, ($amount - $discount) + $fine);

        // Só define automaticamente se estiver vazio para não sobrescrever caso o usuário queira digitar um valor parcial menor
        if (empty($get('amount_paid'))) {
            $set('amount_paid', number_format($totalCalculado, 2, '.', ''));
        }
    }
}
