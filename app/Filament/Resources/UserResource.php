<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static string | \UnitEnum | null $navigationGroup = 'Equipe';

    protected static ?string $navigationLabel = 'Colaboradores / Acessos';

    protected static ?string $modelLabel = 'Colaborador';

    protected static ?string $pluralModelLabel = 'Colaboradores';

    protected static ?int $navigationSort = 10;

    protected static ?string $tenantRelationshipName = 'users';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Colaborador')
                    ->tabs([
                        Tab::make('Dados do Colaborador')
                            ->icon('heroicon-o-user')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome Completo')
                                    ->placeholder('Ex: Carlos Eduardo')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('email')
                                    ->label('E-mail de Acesso')
                                    ->placeholder('carlos@barbearia.com')
                                    ->email()
                                    ->required()
                                    ->unique(User::class, 'email', ignoreRecord: true)
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('phone')
                                    ->label('WhatsApp / Telefone')
                                    ->placeholder('85999998888')
                                    ->tel()
                                    ->maxLength(20)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Cargos & Permissões')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Select::make('roles')
                                    ->label('Cargo / Papel no Sistema')
                                    ->relationship('roles', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->helperText('Selecione os cargos definidos em Cargos & Permissões que determinam o que este colaborador pode acessar e operar.')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Credenciais & Acesso')
                            ->icon('heroicon-o-key')
                            ->schema([
                                TextInput::make('password')
                                    ->label('Senha de Acesso')
                                    ->password()
                                    ->revealable()
                                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->helperText('Defina a senha inicial. Deixe em branco ao editar para manter a senha atual.')
                                    ->columnSpanFull(),

                                Toggle::make('must_change_password')
                                    ->label('Exigir alteração de senha no primeiro login')
                                    ->default(true)
                                    ->columnSpanFull(),

                                Toggle::make('is_active')
                                    ->label('Acesso Ativo ao Sistema')
                                    ->default(true)
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
                TextColumn::make('name')
                    ->label('Nome')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->searchable(),

                TextColumn::make('roles.name')
                    ->label('Cargo(s)')
                    ->badge()
                    ->separator(', ')
                    ->colors([
                        'primary' => 'super_admin',
                        'warning' => 'barbershop_owner',
                        'info' => 'barbershop_manager',
                        'success' => 'barber',
                        'gray' => 'receptionist',
                    ]),

                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Cadastrado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
