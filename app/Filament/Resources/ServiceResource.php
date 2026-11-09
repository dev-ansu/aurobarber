<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-scissors';

    protected static string | \UnitEnum | null $navigationGroup = 'Serviços';

    protected static ?string $navigationLabel = 'Catálogo de Serviços';

    protected static ?string $modelLabel = 'Serviço';

    protected static ?string $pluralModelLabel = 'Serviços';

    public static function canViewAny(): bool
    {
        return ! auth()->user()?->isRestrictedBarber();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Serviço')
                    ->tabs([
                        Tab::make('Dados do Serviço')
                            ->icon('heroicon-o-scissors')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome do Serviço')
                                    ->required()
                                    ->placeholder('Ex: Corte Degradê, Barboterapia')
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('category')
                                    ->label('Categoria do Serviço')
                                    ->options([
                                        'cabelo' => 'Cabelo',
                                        'barba' => 'Barba',
                                        'combo' => 'Combo / Pacote',
                                        'tratamentos' => 'Tratamentos & Química',
                                    ])
                                    ->default('cabelo')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('description')
                                    ->label('Descrição do Serviço')
                                    ->placeholder('Descreva os detalhes e o que está incluso no atendimento')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Disponível para Agendamento Online')
                                    ->default(true)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Preço & Tempo')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label('Preço do Serviço (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('duration_minutes')
                                    ->label('Duração Estimada (minutos)')
                                    ->numeric()
                                    ->default(30)
                                    ->suffix('minutos')
                                    ->helperText('Usado para calcular a grade de horários disponíveis sem sobreposição.')
                                    ->required()
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
                    ->label('Serviço')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->badge(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Preço')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duração')
                    ->suffix(' min')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
