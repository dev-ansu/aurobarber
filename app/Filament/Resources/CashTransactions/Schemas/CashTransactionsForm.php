<?php

namespace App\Filament\Resources\CashTransactions\Schemas;

use App\Models\CashRegister;
use App\Models\PaymentMethod;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CashTransactionsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Movimentação de caixa')
                    ->description('Registre uma entrada ou saída vinculada a um caixa em operação.')
                    ->schema([
                        Select::make('cash_register_id')->label('Caixa aberto')
                            ->options(fn () => CashRegister::where('status', 'open')
                                ->when(Filament::getTenant()?->id, fn ($q, $tid) => $q->where('school_id', $tid))
                                ->pluck('name', 'id'))
                            ->placeholder('Selecione o caixa ativo')->searchable()->required(),
                        Select::make('type')->label('Tipo de movimentação')->options([
                            'input' => 'Entrada — suprimento de troco',
                            'output' => 'Saída — sangria ou despesa',
                        ])->native(false)->required(),
                        TextInput::make('amount')->label('Valor')->numeric()->prefix('R$')->minValue(0.01)->required(),
                        Select::make('payment_method_id')->label('Forma de pagamento')
                            ->relationship('paymentMethod', 'name')
                            ->options(fn () => PaymentMethod::where('is_active', true)->pluck('name', 'id'))
                            ->searchable()->preload()->placeholder('Selecione a forma de pagamento')
                            ->required(fn (string $operation) => $operation === 'edit'),
                        Textarea::make('description')->label('Histórico / justificativa')
                            ->placeholder('Descreva o motivo da movimentação.')->rows(3)->required()->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
