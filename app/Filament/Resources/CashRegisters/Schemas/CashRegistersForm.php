<?php

namespace App\Filament\Resources\CashRegisters\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CashRegistersForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Controle de Caixa')
                    ->schema([
                        TextInput::make('name')
                            ->label('Identificação do Caixa')
                            ->placeholder('Ex: Caixa Secretaria - Manhã')
                            ->required()
                            ->disabled(fn ($record) => $record && $record->status === 'closed'),

                        Select::make('status')
                            ->label('Situação atual')
                            ->options([
                                'open' => 'Aberto (Em Operação)',
                                'closed' => 'Fechado (Encerrado)',
                            ])
                            ->default('open')
                            ->disabled() // Deixamos desabilitado para o usuário não burlar, o controle será por botões/ações
                            ->required(),

                        TextInput::make('opening_balance')
                            ->label('Saldo Inicial (Troco)')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0.00)
                            ->disabled(fn ($record) => $record !== null) // Bloqueia a edição após criado
                            ->required(),

                        TextInput::make('current_balance')
                            ->label('Saldo Atual em Caixa')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0.00)
                            ->disabled(), // Atualizado automaticamente pelo sistema conforme os recebimentos
                    ])->columns(2)->columnSpanFull(),

                Section::make('Auditoria e Operador')
                    ->schema([
                        DateTimePicker::make('opened_at')
                            ->label('Aberto em')
                            ->disabled(),

                        DateTimePicker::make('closed_at')
                            ->label('Fechado em')
                            ->disabled(),
                    ])->columns(2)->visible(fn ($record) => $record !== null)
                    ->columnSpanFull(),
            ]);
    }
}
