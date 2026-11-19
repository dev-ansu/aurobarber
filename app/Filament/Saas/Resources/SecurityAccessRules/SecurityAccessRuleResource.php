<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\SecurityAccessRules;

use App\Filament\Saas\Resources\SecurityAccessRules\Pages\ManageSecurityAccessRules;
use App\Models\SecurityAccessRule;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class SecurityAccessRuleResource extends Resource
{
    protected static ?string $model = SecurityAccessRule::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|UnitEnum|null $navigationGroup = 'SaaS Master';

    protected static ?string $navigationLabel = 'Firewall de IPs (White/Blacklist)';

    protected static ?string $modelLabel = 'Regra de Acesso';

    protected static ?string $pluralModelLabel = 'Regras de Acesso (White / Blacklist)';

    protected static ?int $navigationSort = 40;

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->role === 'master_admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipo de Lista')
                    ->options([
                        'whitelist' => '🛡️ Whitelist (IP / Dispositivo Confiável)',
                        'blacklist' => '🚫 Blacklist (IP / Dispositivo Bloqueado)',
                    ])
                    ->required()
                    ->default('whitelist'),

                TextInput::make('label')
                    ->label('Identificação / Nome Amigável')
                    ->placeholder('ex: Meu Notebook Pessoal, Escritório Central, IP Suspeito...')
                    ->required()
                    ->maxLength(255),

                TextInput::make('ip_address')
                    ->label('Endereço IP')
                    ->placeholder('ex: 189.40.10.5 ou 2804:14c:...')
                    ->maxLength(45),

                Select::make('user_id')
                    ->label('Usuário Específico (Opcional)')
                    ->relationship('user', 'email')
                    ->getOptionLabelFromRecordUsing(fn (User $record): string => "{$record->name} ({$record->email})")
                    ->searchable(['name', 'email'])
                    ->preload()
                    ->placeholder('Global (Aplica-se a todos os acessos)'),

                TextInput::make('user_agent')
                    ->label('User-Agent do Navegador (Opcional)')
                    ->maxLength(500),

                Toggle::make('is_active')
                    ->label('Regra Ativa')
                    ->default(true),

                DateTimePicker::make('expires_at')
                    ->label('Expira em (Vazio = Permanente)')
                    ->placeholder('Nunca expira'),

                Textarea::make('notes')
                    ->label('Anotações / Motivo da Regra')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'whitelist' => 'success',
                        'blacklist' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'whitelist' => '🛡️ Whitelist',
                        'blacklist' => '🚫 Blacklist',
                        default => $state,
                    }),

                TextColumn::make('label')
                    ->label('Identificação')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (SecurityAccessRule $record): ?string => $record->notes),

                TextColumn::make('ip_address')
                    ->label('IP')
                    ->fontFamily('mono')
                    ->copyable()
                    ->placeholder('Qualquer IP')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->placeholder('Global (Todos)')
                    ->description(fn (SecurityAccessRule $record): ?string => $record->user?->email),

                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),

                TextColumn::make('match_count')
                    ->label('Acionamentos')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('last_matched_at')
                    ->label('Último Bloqueio/Match')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Nunca')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expira em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Permanente')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'whitelist' => 'Whitelist (Permitidos)',
                        'blacklist' => 'Blacklist (Bloqueados)',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Ativas',
                        '0' => 'Inativas',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSecurityAccessRules::route('/'),
        ];
    }
}
