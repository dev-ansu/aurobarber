<?php

declare(strict_types=1);

namespace App\Filament\Resources\FinancialAccounts;

use App\Filament\Resources\FinancialAccounts\Pages\ManageFinancialAccounts;
use App\Models\FinancialAccount;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class FinancialAccountResource extends Resource
{
    protected static bool $isScopedToTenant = true;

    protected static ?string $model = FinancialAccount::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Contas e Bancos';

    protected static ?string $modelLabel = 'Conta Financeira';

    protected static ?string $pluralModelLabel = 'Contas Financeiras e Cofres';

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Identificação da Conta ou Cofre')
                    ->description('Cadastre as contas bancárias ou cofres físicos onde o dinheiro da escola fica depositado.')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome da Conta / Identificação')
                                    ->placeholder('Ex: Cofre Principal da Unidade, Banco Cora PJ, Banco do Brasil')
                                    ->required()
                                    ->maxLength(255),

                                Select::make('type')
                                    ->label('Tipo de Conta')
                                    ->options([
                                        'vault' => 'Cofre Físico da Unidade',
                                        'bank' => 'Conta Bancária (Banco)',
                                        'other' => 'Outro / Carteira',
                                    ])
                                    ->default('bank')
                                    ->required()
                                    ->live(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('bank_name')
                                    ->label('Instituição Bancária')
                                    ->placeholder('Ex: Cora, Inter, Itaú, Banco do Brasil')
                                    ->maxLength(100)
                                    ->visible(fn ($get) => $get('type') === 'bank'),

                                TextInput::make('agency_number')
                                    ->label('Agência')
                                    ->placeholder('Ex: 0001')
                                    ->maxLength(30)
                                    ->visible(fn ($get) => $get('type') === 'bank'),

                                TextInput::make('account_number')
                                    ->label('Número da Conta')
                                    ->placeholder('Ex: 123456-7')
                                    ->maxLength(50)
                                    ->visible(fn ($get) => $get('type') === 'bank'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('initial_balance')
                                    ->label('Saldo Inicial (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0.00)
                                    ->required(),

                                TextInput::make('current_balance')
                                    ->label('Saldo Atual (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(0.00)
                                    ->required()
                                    ->helperText('Saldo total disponível acumulado nesta conta ou cofre.'),
                            ]),

                        Toggle::make('is_active')
                            ->label('Conta Ativa')
                            ->default(true),

                        Textarea::make('notes')
                            ->label('Observações Internas')
                            ->placeholder('Ex: Utilizada para transferências de fechamento de caixa e sangrias.')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome da Conta / Identificação')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'vault' => 'Cofre Físico',
                        'bank' => 'Conta Bancária',
                        default => 'Outro',
                    })
                    ->color(fn ($state) => match ($state) {
                        'vault' => 'warning',
                        'bank' => 'info',
                        default => 'gray',
                    }),

                TextColumn::make('bank_name')
                    ->label('Banco')
                    ->placeholder('—'),

                TextColumn::make('account_number')
                    ->label('Nº Conta')
                    ->placeholder('—'),

                TextColumn::make('current_balance')
                    ->label('Saldo Atual')
                    ->money('BRL')
                    ->weight('bold')
                    ->color(fn ($state) => (float) $state >= 0 ? 'success' : 'danger'),

                IconColumn::make('is_active')
                    ->label('Ativa')
                    ->boolean(),
            ])
            ->recordActions([
                Action::make('statement')
                    ->label('Extrato')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->modalHeading(fn (FinancialAccount $record): string => 'Extrato da Conta: '.$record->name)
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->form(fn (FinancialAccount $record): array => [
                        Placeholder::make('extrato')
                            ->hiddenLabel()
                            ->content(fn () => view('filament.resources.financial-accounts.statement-modal', ['record' => $record])),
                    ])
                    ->visible(fn (): bool => Auth::user()?->can('statement', FinancialAccount::class) ?? false),

                Action::make('adjust')
                    ->label('Ajustar Saldo')
                    ->icon('heroicon-o-scale')
                    ->color('warning')
                    ->modalHeading(fn (FinancialAccount $record): string => 'Ajuste Manual de Saldo: '.$record->name)
                    ->modalDescription('Utilize para lançar tarifas, rendimentos ou correções de saldo com justificativa auditada.')
                    ->form(fn (FinancialAccount $record): array => [
                        Placeholder::make('current_balance_info')
                            ->label('Saldo Atual Registrado')
                            ->content(fn (): string => 'R$ '.number_format($record->current_balance, 2, ',', '.')),

                        TextInput::make('new_balance')
                            ->label('Novo Saldo da Conta (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),

                        TextInput::make('justification')
                            ->label('Motivo / Justificativa do Ajuste')
                            ->placeholder('Ex: Tarifa mensal de manutenção de conta, rendimento de CDB...')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function (FinancialAccount $record, array $data): void {
                        $newBalance = (float) $data['new_balance'];
                        $justification = (string) $data['justification'];

                        $record->adjustBalance($newBalance, $justification, Auth::id());

                        Notification::make()
                            ->title('Saldo Ajustado com Sucesso')
                            ->body('O saldo da conta '.$record->name.' foi atualizado para R$ '.number_format($newBalance, 2, ',', '.').'.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool => Auth::user()?->can('adjust', FinancialAccount::class) ?? false),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->headerActions([
                Action::make('transfer')
                    ->label('Transferir entre Contas')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('primary')
                    ->modalHeading('Transferência entre Contas da Escola')
                    ->modalDescription('Transfira valores entre cofres físicos e contas bancárias com atualização simultânea de saldos.')
                    ->form([
                        Select::make('from_account_id')
                            ->label('Conta de Origem (De onde sai o dinheiro)')
                            ->options(fn (): array => FinancialAccount::query()
                                ->where('is_active', true)
                                ->get()
                                ->mapWithKeys(fn (FinancialAccount $acc): array => [
                                    $acc->id => $acc->name.' (Saldo: R$ '.number_format($acc->current_balance, 2, ',', '.').')',
                                ])
                                ->all()
                            )
                            ->required()
                            ->live(),

                        Select::make('to_account_id')
                            ->label('Conta de Destino (Para onde vai o dinheiro)')
                            ->options(fn ($get): array => FinancialAccount::query()
                                ->where('is_active', true)
                                ->when($get('from_account_id'), fn ($q, $fromId) => $q->where('id', '!=', $fromId))
                                ->pluck('name', 'id')
                                ->all()
                            )
                            ->required()
                            ->different('from_account_id'),

                        TextInput::make('amount')
                            ->label('Valor da Transferência (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->minValue(0.01)
                            ->required(),

                        TextInput::make('description')
                            ->label('Descrição / Justificativa')
                            ->placeholder('Ex: Depósito de dinheiro do cofre no Banco Cora')
                            ->maxLength(255),
                    ])
                    ->action(function (array $data): void {
                        $fromAccount = FinancialAccount::findOrFail((int) $data['from_account_id']);
                        $toAccount = FinancialAccount::findOrFail((int) $data['to_account_id']);
                        $amount = (float) $data['amount'];
                        $description = $data['description'] ?? null;

                        if ((float) $fromAccount->current_balance < $amount) {
                            Notification::make()
                                ->title('Saldo Insuficiente na Origem')
                                ->body('A conta '.$fromAccount->name.' possui saldo de R$ '.number_format($fromAccount->current_balance, 2, ',', '.').', insuficiente para transferir R$ '.number_format($amount, 2, ',', '.').'.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $fromAccount->transferTo($toAccount, $amount, $description, Auth::id());

                        Notification::make()
                            ->title('Transferência Concluída')
                            ->body('Valor de R$ '.number_format($amount, 2, ',', '.').' transferido de '.$fromAccount->name.' para '.$toAccount->name.'.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (): bool => Auth::user()?->can('transfer', FinancialAccount::class) ?? false),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFinancialAccounts::route('/'),
        ];
    }
}

