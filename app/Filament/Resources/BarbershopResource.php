<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarbershopResource\Pages;
use App\Models\Barbershop;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BarbershopResource extends Resource
{
    protected static ?string $model = Barbershop::class;

    protected static bool $isScopedToTenant = false;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string | \UnitEnum | null $navigationGroup = 'SaaS Master';

    protected static ?string $navigationLabel = 'Todas as Barbearias';

    protected static ?string $modelLabel = 'Barbearia';

    protected static ?string $pluralModelLabel = 'Barbearias';

    public static function canViewAny(): bool
    {
        $panel = filament()->getCurrentPanel();
        if ($panel && $panel->getId() !== 'saas') {
            return false;
        }

        return auth()->user()?->role === 'master_admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Barbearia')
                    ->tabs([
                        Tab::make('Identidade & Marca')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome da Barbearia')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('slug')
                                    ->label('Link Exclusivo (Slug)')
                                    ->prefix('barber.aurosoft.com.br/')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('headline')
                                    ->label('Frase de Destaque / Slogan')
                                    ->placeholder('Ex: Tradição, Estilo e Barboterapia')
                                    ->columnSpanFull(),

                                Forms\Components\ColorPicker::make('brand_color')
                                    ->label('Cor da Marca')
                                    ->default('#d97706')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('description')
                                    ->label('Sobre a Barbearia')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Contato & Localização')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Forms\Components\TextInput::make('whatsapp')
                                    ->label('WhatsApp de Atendimento')
                                    ->tel()
                                    ->placeholder('85999999999')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('city')
                                    ->label('Cidade / UF')
                                    ->placeholder('Fortaleza / CE')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('address')
                                    ->label('Endereço Completo')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Proprietário & Acesso')
                            ->icon('heroicon-o-user-plus')
                            ->schema([
                                Forms\Components\Radio::make('owner_type')
                                    ->label('Definição do Proprietário')
                                    ->options([
                                        'new' => 'Cadastrar Novo Dono de Barbearia (Gera senha temporária e envia por e-mail)',
                                        'existing' => 'Vincular a Usuário Já Cadastrado no Sistema',
                                    ])
                                    ->default('new')
                                    ->live()
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('owner_name')
                                    ->label('Nome Completo do Proprietário')
                                    ->placeholder('Ex: Roberto Carlos da Silva')
                                    ->required(fn ($get) => $get('owner_type') === 'new')
                                    ->visible(fn ($get) => $get('owner_type') === 'new')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('owner_email')
                                    ->label('E-mail de Acesso do Proprietário')
                                    ->email()
                                    ->placeholder('Ex: roberto@minhabarbearia.com.br')
                                    ->required(fn ($get) => $get('owner_type') === 'new')
                                    ->visible(fn ($get) => $get('owner_type') === 'new')
                                    ->unique('users', 'email')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('owner_phone')
                                    ->label('Telefone / WhatsApp do Proprietário')
                                    ->tel()
                                    ->placeholder('85999998888')
                                    ->visible(fn ($get) => $get('owner_type') === 'new')
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('send_welcome_email')
                                    ->label('Enviar E-mail de Boas-Vindas com Senha Temporária e Instruções')
                                    ->helperText('O dono receberá o link do painel e a senha temporária, sendo obrigado a redefinir no primeiro acesso.')
                                    ->default(true)
                                    ->dehydrated()
                                    ->visible(fn ($get) => $get('owner_type') === 'new')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('owner_id')
                                    ->label('Selecione o Usuário Proprietário')
                                    ->relationship('owner', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(fn ($get) => $get('owner_type') === 'existing')
                                    ->visible(fn ($get) => $get('owner_type') === 'existing')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Horários & Funcionamento')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Forms\Components\CheckboxList::make('opening_hours.days')
                                    ->label('Dias de Funcionamento da Barbearia')
                                    ->options([
                                        'mon' => 'Segunda-feira',
                                        'tue' => 'Terça-feira',
                                        'wed' => 'Quarta-feira',
                                        'thu' => 'Quinta-feira',
                                        'fri' => 'Sexta-feira',
                                        'sat' => 'Sábado',
                                        'sun' => 'Domingo',
                                    ])
                                    ->default(['mon', 'tue', 'wed', 'thu', 'fri', 'sat'])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Forms\Components\TimePicker::make('opening_hours.start_time')
                                    ->label('Horário Geral de Abertura')
                                    ->default('08:00')
                                    ->columnSpanFull(),

                                Forms\Components\TimePicker::make('opening_hours.end_time')
                                    ->label('Horário Geral de Fechamento')
                                    ->default('19:30')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('opening_hours.slot_interval')
                                    ->label('Intervalo entre Horários na Agenda')
                                    ->options([
                                        15 => 'A cada 15 minutos',
                                        30 => 'A cada 30 minutos (Padrão recomendável)',
                                        45 => 'A cada 45 minutos',
                                        60 => 'A cada 1 hora',
                                    ])
                                    ->default(30)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Licença & Faturamento')
                            ->icon('heroicon-o-key')
                            ->schema([
                                Forms\Components\Select::make('license_status')
                                    ->label('Status da Licença')
                                    ->options([
                                        'active' => '✅ Ativa (Acesso Liberado)',
                                        'trial' => '⏳ Em Teste / Degustação',
                                        'suspended' => '⛔ Suspensa (Acesso Bloqueado por Inadimplência)',
                                        'cancelled' => '❌ Cancelada / Encerrada',
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->native(false)
                                    ->columnSpanFull(),

                                Forms\Components\DatePicker::make('license_expires_at')
                                    ->label('Data de Vencimento da Licença')
                                    ->default(now()->addDays(30))
                                    ->required()
                                    ->helperText('Após o vencimento, o sistema entra no período de carência antes do bloqueio total.')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('grace_period_days')
                                    ->label('Dias de Carência (Tolerância)')
                                    ->numeric()
                                    ->default(5)
                                    ->minValue(0)
                                    ->helperText('Quantidade de dias que o cliente pode continuar acessando com alerta após vencer.')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('monthly_price')
                                    ->label('Valor da Mensalidade (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(120.00)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('suspension_reason')
                                    ->label('Motivo da Suspensão (Exibido na tela de bloqueio)')
                                    ->placeholder('Ex: Pendência no pagamento da mensalidade de Novembro')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('license_notes')
                                    ->label('Observações Internas Financeiras / Contrato')
                                    ->rows(3)
                                    ->placeholder('Anotações internas sobre negociação, data de pagamento combinada, etc.')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Barbearia')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Barbershop $record): string => "barber.aurosoft.com.br/{$record->slug}"),

                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Proprietário')
                    ->description(fn (Barbershop $record): string => $record->owner?->email ?? '-')
                    ->searchable(),

                Tables\Columns\TextColumn::make('license_status')
                    ->label('Status Licença')
                    ->badge()
                    ->color(fn (Barbershop $record): string => match ($record->license_status) {
                        'active' => $record->isInGracePeriod() ? 'warning' : 'success',
                        'trial' => 'primary',
                        'suspended', 'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(function (Barbershop $record): string {
                        if ($record->isSuspended()) {
                            return 'Suspensa';
                        }
                        if ($record->isInGracePeriod()) {
                            return 'Em Carência';
                        }
                        return match ($record->license_status) {
                            'active' => 'Ativa',
                            'trial' => 'Em Teste',
                            'cancelled' => 'Cancelada',
                            default => $record->license_status ?? 'Ativa',
                        };
                    })
                    ->description(function (Barbershop $record): ?string {
                        if ($record->isInGracePeriod()) {
                            return "Tolerância: {$record->graceDaysRemaining()}d restantes";
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('license_expires_at')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(function (Barbershop $record): string {
                        if (!$record->license_expires_at) {
                            return 'Sem data';
                        }
                        $days = $record->daysRemaining();
                        if ($days < 0) {
                            return abs($days) . ' dias atrasado';
                        }
                        if ($days === 0) {
                            return 'Vence hoje!';
                        }
                        return "Restam {$days} dias";
                    }),

                Tables\Columns\TextColumn::make('monthly_price')
                    ->label('Mensalidade')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('city')
                    ->label('Cidade')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('open_panel')
                    ->label('Painel ↗')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (Barbershop $record): string => url("/admin/{$record->slug}"))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('renew_30')
                    ->label('Renovar +30d')
                    ->icon('heroicon-m-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Renovar Licença por +30 Dias')
                    ->modalDescription(fn (Barbershop $record): string => "Deseja renovar a licença de {$record->name}? O novo vencimento será " . (($record->license_expires_at && $record->license_expires_at->isFuture()) ? $record->license_expires_at->copy()->addDays(30)->format('d/m/Y') : now()->addDays(30)->format('d/m/Y')) . ".")
                    ->action(function (Barbershop $record): void {
                        $record->renew(30);
                        \Filament\Notifications\Notification::make()
                            ->title('Licença renovada por +30 dias!')
                            ->body("Novo vencimento registrado: {$record->license_expires_at->format('d/m/Y')}")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Barbershop $record): string => $record->isSuspended() ? 'Reativar' : 'Suspender')
                    ->icon(fn (Barbershop $record): string => $record->isSuspended() ? 'heroicon-m-check-circle' : 'heroicon-m-no-symbol')
                    ->color(fn (Barbershop $record): string => $record->isSuspended() ? 'success' : 'danger')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Barbershop $record): string => $record->isSuspended() ? "Reativar Sistema: {$record->name}" : "Suspender Sistema: {$record->name}")
                    ->modalDescription(fn (Barbershop $record): string => $record->isSuspended() ? "O acesso da barbearia {$record->name} será liberado imediatamente." : "O acesso da barbearia {$record->name} será bloqueado por inadimplência.")
                    ->action(function (Barbershop $record): void {
                        if ($record->isSuspended()) {
                            $record->activate();
                            \Filament\Notifications\Notification::make()
                                ->title('Barbearia reativada!')
                                ->body('O acesso ao sistema foi restabelecido.')
                                ->success()
                                ->send();
                        } else {
                            $record->suspend('Suspensão manual por inadimplência via Painel SaaS');
                            \Filament\Notifications\Notification::make()
                                ->title('Barbearia suspensa!')
                                ->body('O acesso ao painel foi bloqueado.')
                                ->warning()
                                ->send();
                        }
                    }),

                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBarbershops::route('/'),
            'create' => Pages\CreateBarbershop::route('/create'),
            'edit' => Pages\EditBarbershop::route('/{record}/edit'),
        ];
    }
}
