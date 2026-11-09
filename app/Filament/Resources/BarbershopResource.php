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
        return filament()->getCurrentPanel()?->getId() === 'saas' && auth()->user()?->role === 'master_admin';
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
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Link Público')
                    ->formatStateUsing(fn ($state) => "barber.aurosoft.com.br/{$state}"),

                Tables\Columns\TextColumn::make('whatsapp')
                    ->label('WhatsApp'),

                Tables\Columns\TextColumn::make('city')
                    ->label('Cidade'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativa')
                    ->boolean(),
            ])
            ->actions([
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
