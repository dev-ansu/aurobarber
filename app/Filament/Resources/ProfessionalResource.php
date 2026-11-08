<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfessionalResource\Pages;
use App\Models\Professional;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProfessionalResource extends Resource
{
    protected static ?string $model = Professional::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Equipe';

    protected static ?string $navigationLabel = 'Barbeiros / Profissionais';

    protected static ?string $modelLabel = 'Barbeiro';

    protected static ?string $pluralModelLabel = 'Barbeiros';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Barbeiro')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome Completo')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nickname')
                            ->label('Apelido / Nome de Atendimento')
                            ->placeholder('Ex: Zé do Corte, Marinho')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('WhatsApp / Telefone')
                            ->tel(),

                        Forms\Components\TextInput::make('commission_rate')
                            ->label('Comissão Padrão (%)')
                            ->numeric()
                            ->default(50.00)
                            ->suffix('%')
                            ->required(),

                        Forms\Components\TimePicker::make('work_start_time')
                            ->label('Início do Expediente')
                            ->default('08:00:00'),

                        Forms\Components\TimePicker::make('work_end_time')
                            ->label('Fim do Expediente')
                            ->default('19:00:00'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Ativo na Agenda')
                            ->default(true),

                        Forms\Components\Textarea::make('bio')
                            ->label('Biografia / Especialidades')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('nickname')
                    ->label('Apelido')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefone'),

                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Comissão')
                    ->suffix('%')
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
            'index' => Pages\ListProfessionals::route('/'),
            'create' => Pages\CreateProfessional::route('/create'),
            'edit' => Pages\EditProfessional::route('/{record}/edit'),
        ];
    }
}
