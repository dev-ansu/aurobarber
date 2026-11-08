<?php

namespace App\Filament\Resources\CashRegisters\Tables;

use App\Filament\Exports\CashRegisterExporter;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashRegistersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Caixa')
                    ->searchable(),

                TextColumn::make('operator.name')
                    ->label('Operador')
                    ->placeholder('Sem operador'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'success',
                        'closed' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Aberto',
                        'closed' => 'Fechado',
                    }),

                TextColumn::make('opening_balance')
                    ->label('Saldo Inicial')
                    ->money('BRL'),

                TextColumn::make('current_balance')
                    ->label('Saldo Atual')
                    ->money('BRL'),

                TextColumn::make('closing_balance')
                    ->label('Saldo Fechamento')
                    ->money('BRL')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('counted_cash')
                    ->label('Dinheiro Contado')
                    ->money('BRL')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('difference')
                    ->label('Diferença / Quebra')
                    ->money('BRL')
                    ->badge()
                    ->color(fn (?float $state): string => match (true) {
                        $state === null => 'gray',
                        $state == 0.0 => 'success',
                        $state > 0.0 => 'info',
                        $state < 0.0 => 'danger',
                    })
                    ->formatStateUsing(fn (?float $state): string => match (true) {
                        $state === null => '—',
                        $state == 0.0 => 'Exato (R$ 0,00)',
                        $state > 0.0 => '+ R$ '.number_format($state, 2, ',', '.').' (Sobra)',
                        $state < 0.0 => '- R$ '.number_format(abs($state), 2, ',', '.').' (Quebra)',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('opened_at')
                    ->label('Abertura')
                    ->dateTime('d/m/Y H:i'),

                TextColumn::make('closed_at')
                    ->label('Fechamento')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Situação')->options(['open' => 'Aberto', 'closed' => 'Fechado']),
                SelectFilter::make('operator_id')
                    ->label('Operador')
                    ->options(fn (): array => User::query()
                        ->withoutTechnicalAdmin()
                        ->with('person')
                        ->orderBy('email')
                        ->limit(250)
                        ->get()
                        ->mapWithKeys(fn (User $user): array => [$user->id => $user->name ?? $user->email])
                        ->all())
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('suprimento')
                    ->label('Suprimento')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->visible(fn (CashRegister $record): bool => $record->status === 'open')
                    ->modalHeading('Adicionar Suprimento (Troco de Caixa)')
                    ->modalDescription('Lançamento de dinheiro físico adicionado à gaveta do caixa.')
                    ->form([
                        TextInput::make('amount')
                            ->label('Valor do Suprimento')
                            ->numeric()
                            ->prefix('R$')
                            ->required()
                            ->minValue(0.01),
                        TextInput::make('description')
                            ->label('Motivo / Justificativa')
                            ->placeholder('Ex: Troco inicial de notas miúdas e moedas')
                            ->required(),
                    ])
                    ->action(function (CashRegister $record, array $data): void {
                        $amount = (float) $data['amount'];
                        $cashMethodId = $record->getCashPaymentMethodId();

                        DB::transaction(function () use ($record, $amount, $cashMethodId, $data) {
                            CashTransaction::create([
                                'cash_register_id' => $record->id,
                                'user_id' => Auth::id(),
                                'type' => 'input',
                                'amount' => $amount,
                                'payment_method_id' => $cashMethodId,
                                'description' => 'Suprimento: '.$data['description'],
                            ]);

                            $record->increment('current_balance', $amount);
                        });

                        Notification::make()
                            ->title('Suprimento Registrado')
                            ->body('Valor de R$ '.number_format($amount, 2, ',', '.').' adicionado à gaveta do caixa.')
                            ->success()
                            ->send();
                    }),

                Action::make('sangria')
                    ->label('Sangria')
                    ->icon('heroicon-o-minus-circle')
                    ->color('warning')
                    ->visible(fn (CashRegister $record): bool => $record->status === 'open')
                    ->modalHeading('Realizar Sangria (Retirada de Dinheiro)')
                    ->modalDescription('Retirada de valores em dinheiro da gaveta para cofre, depósito ou despesa miúda.')
                    ->form([
                        TextInput::make('amount')
                            ->label('Valor da Sangria')
                            ->numeric()
                            ->prefix('R$')
                            ->required()
                            ->minValue(0.01),
                        TextInput::make('description')
                            ->label('Motivo / Destino')
                            ->placeholder('Ex: Retirada para o cofre da diretoria')
                            ->required(),
                        Select::make('destination_account_id')
                            ->label('Conta de Destino / Cofre (Opcional)')
                            ->placeholder('Selecione se for transferir para um cofre ou conta bancária')
                            ->options(fn (CashRegister $record): array => \App\Models\FinancialAccount::query()
                                ->forSchool($record->school_id)
                                ->where('is_active', true)
                                ->pluck('name', 'id')
                                ->all()
                            )
                            ->helperText('Ao selecionar uma conta, o valor retirado será creditado nela automaticamente.'),
                    ])
                    ->action(function (CashRegister $record, array $data): void {
                        $amount = (float) $data['amount'];
                        $cashMethodId = $record->getCashPaymentMethodId();
                        $destinationAccountId = ! empty($data['destination_account_id']) ? (int) $data['destination_account_id'] : null;

                        DB::transaction(function () use ($record, $amount, $cashMethodId, $data, $destinationAccountId) {
                            CashTransaction::create([
                                'cash_register_id' => $record->id,
                                'user_id' => Auth::id(),
                                'type' => 'output',
                                'amount' => $amount,
                                'payment_method_id' => $cashMethodId,
                                'destination_account_id' => $destinationAccountId,
                                'description' => 'Sangria: '.$data['description'],
                            ]);

                            $record->decrement('current_balance', $amount);

                            if ($destinationAccountId) {
                                $destAccount = \App\Models\FinancialAccount::find($destinationAccountId);
                                $destAccount?->deposit(
                                    amount: $amount,
                                    description: 'Sangria do Caixa '.$record->name.': '.$data['description'],
                                    sourceType: 'cash_register_sangria',
                                    sourceId: $record->id,
                                    userId: Auth::id()
                                );
                            }
                        });

                        Notification::make()
                            ->title('Sangria Registrada')
                            ->body('Retirada de R$ '.number_format($amount, 2, ',', '.').' registrada com sucesso.')
                            ->warning()
                            ->send();
                    }),

                Action::make('imprimirRelatorio')
                    ->label('Imprimir Relatório')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (CashRegister $record): string => route('admin.caixa.relatorio', $record))
                    ->openUrlInNewTab(),

                Action::make('fecharCaixa')
                    ->label('Fechar Caixa')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (CashRegister $record): bool => $record->status === 'open')
                    ->modalHeading('Conferência e Fechamento de Caixa')
                    ->modalDescription('Realize a contagem das notas e moedas da gaveta física e informe o total apurado abaixo.')
                    ->form(fn (CashRegister $record): array => [
                        Placeholder::make('resumo')
                            ->label('Valores Calculados pelo Sistema')
                            ->content(fn (): string => sprintf(
                                'Saldo Esperado em Dinheiro: R$ %s | Saldo Geral Acumulado: R$ %s',
                                number_format($record->expectedCashBalance(), 2, ',', '.'),
                                number_format($record->current_balance, 2, ',', '.')
                            )),
                        TextInput::make('counted_cash')
                            ->label('Dinheiro Físico Contado na Gaveta')
                            ->numeric()
                            ->prefix('R$')
                            ->required()
                            ->helperText('Conte todas as cédulas e moedas físicas presentes na gaveta.'),
                        Select::make('destination_account_id')
                            ->label('Transferir Dinheiro para Conta / Cofre (Opcional)')
                            ->placeholder('Selecione para onde o dinheiro contado será enviado')
                            ->options(fn (CashRegister $record): array => \App\Models\FinancialAccount::query()
                                ->forSchool($record->school_id)
                                ->where('is_active', true)
                                ->pluck('name', 'id')
                                ->all()
                            )
                            ->helperText('Se selecionado, o valor em dinheiro apurado é transferido automaticamente para este cofre/banco ao fechar.'),
                        Textarea::make('closing_notes')
                            ->label('Observações do Fechamento')
                            ->placeholder('Opcional. Justificativa de eventuais sobras ou quebras de caixa...'),
                    ])
                    ->action(function (CashRegister $record, array $data): void {
                        $diff = 0.0;
                        $destinationAccountId = ! empty($data['destination_account_id']) ? (int) $data['destination_account_id'] : null;

                        DB::transaction(function () use ($record, $data, $destinationAccountId, &$diff) {
                            $expectedCash = $record->expectedCashBalance();
                            $counted = (float) $data['counted_cash'];
                            $diff = $counted - $expectedCash;

                            if ($destinationAccountId && $counted > 0) {
                                $cashMethodId = $record->getCashPaymentMethodId();
                                $destAccount = \App\Models\FinancialAccount::find($destinationAccountId);

                                CashTransaction::create([
                                    'cash_register_id' => $record->id,
                                    'user_id' => Auth::id(),
                                    'type' => 'output',
                                    'amount' => $counted,
                                    'payment_method_id' => $cashMethodId,
                                    'destination_account_id' => $destinationAccountId,
                                    'description' => 'Transferência de Fechamento de Caixa: '.($destAccount?->name ?? 'Conta Destino'),
                                ]);

                                $record->decrement('current_balance', $counted);
                                $destAccount?->deposit(
                                    amount: $counted,
                                    description: 'Fechamento de Caixa: '.$record->name,
                                    sourceType: 'cash_register_closing',
                                    sourceId: $record->id,
                                    userId: Auth::id()
                                );
                            }

                            $record->update([
                                'status' => 'closed',
                                'closed_at' => now(),
                                'closing_balance' => $record->current_balance,
                                'counted_cash' => $counted,
                                'difference' => $diff,
                                'closing_notes' => $data['closing_notes'] ?? null,
                            ]);
                        });

                        $feedback = match (true) {
                            $diff == 0.0 => 'Caixa fechado com sucesso! Conferência exata sem divergências.',
                            $diff > 0.0 => 'Caixa fechado! Sobra de caixa de R$ '.number_format($diff, 2, ',', '.').' registrada.',
                            default => 'Caixa fechado com quebra! Falta de R$ '.number_format(abs($diff), 2, ',', '.').' registrada.',
                        };

                        Notification::make()
                            ->title('Turno de Caixa Fechado')
                            ->body($feedback)
                            ->color($diff < 0.0 ? 'danger' : 'success')
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar caixas')->exporter(CashRegisterExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:CashRegister') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:CashRegister') ?? false),
            ]);
    }
}
