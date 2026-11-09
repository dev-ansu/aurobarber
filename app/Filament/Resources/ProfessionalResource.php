<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfessionalResource\Pages;
use App\Models\Professional;
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

class ProfessionalResource extends Resource
{
    protected static ?string $model = Professional::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'Equipe';

    protected static ?string $navigationLabel = 'Barbeiros / Equipe';

    protected static ?string $modelLabel = 'Barbeiro';

    protected static ?string $pluralModelLabel = 'Barbeiros';

    public static function canViewAny(): bool
    {
        return ! auth()->user()?->isRestrictedBarber();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Profissional')
                    ->tabs([
                        Tab::make('Perfil do Barbeiro')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nome Completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('nickname')
                                    ->label('Apelido / Nome de Atendimento')
                                    ->placeholder('Ex: Zé do Corte, Marinho')
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('phone')
                                    ->label('WhatsApp / Telefone')
                                    ->tel()
                                    ->placeholder('85999999999')
                                    ->columnSpanFull(),

                                Forms\Components\Select::make('user_id')
                                    ->label('Login do Barbeiro (Colaborador Vinculado)')
                                    ->options(function () {
                                        $tenant = filament()->getTenant();
                                        if (! $tenant) {
                                            return [];
                                        }

                                        return $tenant->users()
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(fn ($u) => [$u->id => "{$u->name} ({$u->email})"]);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->helperText('Vincule o login deste barbeiro (criado em Colaboradores / Acessos) para que ele veja apenas a própria agenda e as próprias comissões.')
                                    ->columnSpanFull(),

                                Forms\Components\Textarea::make('bio')
                                    ->label('Biografia / Especialidades')
                                    ->placeholder('Especialista em degradê navalhado, barboterapia...')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Ativo para Atendimentos')
                                    ->default(true)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Comissão & Horários')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Forms\Components\TextInput::make('commission_rate')
                                    ->label('Comissão do Profissional (%)')
                                    ->numeric()
                                    ->default(50.00)
                                    ->suffix('%')
                                    ->required()
                                    ->columnSpanFull(),

                                Forms\Components\CheckboxList::make('working_days')
                                    ->label('Dias de Atendimento na Semana')
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

                                Forms\Components\TimePicker::make('work_start_time')
                                    ->label('Início do Expediente')
                                    ->default('08:00:00')
                                    ->columnSpanFull(),

                                Forms\Components\TimePicker::make('work_end_time')
                                    ->label('Fim do Expediente')
                                    ->default('19:00:00')
                                    ->columnSpanFull(),

                                Forms\Components\Toggle::make('has_lunch_break')
                                    ->label('Possui Intervalo de Almoço / Pausa Diária')
                                    ->helperText('Bloqueia automaticamente os horários de almoço na página pública de agendamento.')
                                    ->default(false)
                                    ->live()
                                    ->columnSpanFull(),

                                Forms\Components\TimePicker::make('lunch_start_time')
                                    ->label('Início do Almoço / Pausa')
                                    ->default('12:00:00')
                                    ->visible(fn ($get): bool => (bool) $get('has_lunch_break'))
                                    ->columnSpanFull(),

                                Forms\Components\TimePicker::make('lunch_end_time')
                                    ->label('Fim do Almoço / Retorno')
                                    ->default('13:00:00')
                                    ->visible(fn ($get): bool => (bool) $get('has_lunch_break'))
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
