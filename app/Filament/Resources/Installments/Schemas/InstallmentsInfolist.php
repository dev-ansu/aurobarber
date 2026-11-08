<?php

namespace App\Filament\Resources\Installments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InstallmentsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Origem e Identificação da Cobrança')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('contract.contract_number')
                                    ->label('Número do Contrato')
                                    ->weight('bold')
                                    ->copyable(),

                                TextEntry::make('contract.student.name')
                                    ->label('Aluno (Beneficiário)'),

                                TextEntry::make('status')
                                    ->label('Situação Atual')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'paid' => 'success',
                                        'pending' => 'warning',
                                        'partial' => 'info',
                                        'canceled' => 'danger',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'paid' => 'Paga',
                                        'pending' => 'Pendente',
                                        'partial' => 'Parcial (Desmembrada)',
                                        'canceled' => 'Cancelada',
                                    }),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('financialType.name')
                                    ->label('Tipo Financeiro')
                                    ->badge()
                                    ->color('gray'),

                                TextEntry::make('installment_number')
                                    ->label('Número da Parcela')
                                    ->formatStateUsing(fn ($state, $record) => $state ? "{$state} de {$record->total_installments}" : 'Parcela Avulsa'),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Demonstrativo de Valores')
                    ->description('Detalhamento contábil dos valores aplicados nesta cobrança.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('amount')
                                    ->label('Valor Base Original')
                                    ->money('BRL')
                                    ->weight('bold'),

                                TextEntry::make('amount_paid')
                                    ->label('Total Efetivamente Recebido')
                                    ->money('BRL')
                                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                                    ->placeholder('Nenhum pagamento efetuado'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('discount')
                                    ->label('Desconto Concedido')
                                    ->money('BRL'),

                                TextEntry::make('fine')
                                    ->label('Multa / Juros por Atraso')
                                    ->money('BRL'),
                            ]),
                    ])->columnSpanFull(),

                Section::make('Controle de Prazos e Auditoria')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('due_date')
                                    ->label('Data de Vencimento')
                                    ->date('d/m/Y'),

                                TextEntry::make('paid_at')
                                    ->label('Data do Recebimento')
                                    ->date('d/m/Y')
                                    ->placeholder('Aguardando pagamento'),

                                TextEntry::make('paymentMethod.name')
                                    ->label('Forma de Recebimento')
                                    ->badge()
                                    ->color('gray')
                                    ->placeholder('Em aberto'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Gerada no Sistema em')
                                    ->dateTime('d/m/Y H:i'),

                                TextEntry::make('updated_at')
                                    ->label('Última Atualização Cadastral')
                                    ->dateTime('d/m/Y H:i'),

                                TextEntry::make('receipt_path')
                                    ->label('Comprovante Anexado')
                                    ->badge()
                                    ->formatStateUsing(fn ($state): string => $state ? 'Visualizar Arquivo' : 'Sem anexo')
                                    ->color(fn ($state): string => $state ? 'success' : 'gray')
                                    ->icon(fn ($state): ?string => $state ? 'heroicon-m-paper-clip' : null)
                                    ->url(fn ($record): ?string => $record?->receipt_path ? route('installments.attachment', $record) : null, shouldOpenInNewTab: true),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
