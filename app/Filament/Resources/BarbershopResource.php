<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BarbershopResource\Pages;
use App\Models\Barbershop;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
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
        return auth()->user()?->role === 'master_admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identidade & Marca')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome da Barbearia')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('slug')
                            ->label('Link Exclusivo (Slug)')
                            ->helperText('Ex: marinho -> barber.aurosoft.com.br/marinho')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('headline')
                            ->label('Frase de Destaque / Slogan')
                            ->placeholder('Ex: Tradição, Estilo e Barboterapia')
                            ->columnSpanFull(),

                        Forms\Components\ColorPicker::make('brand_color')
                            ->label('Cor da Marca')
                            ->default('#d97706'),

                        Forms\Components\TextInput::make('whatsapp')
                            ->label('WhatsApp de Atendimento')
                            ->tel()
                            ->placeholder('85999999999'),

                        Forms\Components\TextInput::make('city')
                            ->label('Cidade / UF')
                            ->placeholder('Fortaleza / CE'),

                        Forms\Components\TextInput::make('address')
                            ->label('Endereço Completo')
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('description')
                            ->label('Sobre a Barbearia')
                            ->columnSpanFull(),
                    ])->columns(2),
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
                Tables\Actions\EditAction::make(),
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
