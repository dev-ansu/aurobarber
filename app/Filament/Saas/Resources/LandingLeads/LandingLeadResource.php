<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\LandingLeads;

use App\Filament\Saas\Resources\LandingLeads\Pages\EditLandingLead;
use App\Filament\Saas\Resources\LandingLeads\Pages\ListLandingLeads;
use App\Models\LandingLead;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class LandingLeadResource extends Resource
{
    protected static ?string $model = LandingLead::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Leads & Contatos';

    protected static ?string $modelLabel = 'Lead Comercial';

    protected static ?string $pluralModelLabel = 'Leads da Landing Page';

    protected static string|UnitEnum|null $navigationGroup = 'SaaS Master';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->role === 'master_admin';
    }

    public static function getNavigationBadge(): ?string
    {
        $newCount = LandingLead::where('status', 'novo')->count();

        return $newCount > 0 ? (string) $newCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Informações do Lead')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Contato e Interesse')
                        ->icon('heroicon-m-user')
                        ->schema([
                            Section::make('Dados do Interessado')
                                ->schema([
                                    TextInput::make('name')
                                        ->label('Nome do Responsável')
                                        ->required()
                                        ->maxLength(120)
                                        ->columnSpan(2),
                                    TextInput::make('barbershop_name')
                                        ->label('Nome da Barbearia')
                                        ->required()
                                        ->maxLength(160)
                                        ->columnSpan(2),
                                    TextInput::make('phone')
                                        ->label('WhatsApp / Telefone')
                                        ->required()
                                        ->maxLength(40)
                                        ->columnSpan(2),
                                    TextInput::make('email')
                                        ->label('E-mail')
                                        ->email()
                                        ->required()
                                        ->maxLength(160)
                                        ->columnSpan(2),
                                    TextInput::make('city')
                                        ->label('Cidade / UF')
                                        ->maxLength(100)
                                        ->columnSpan(2),
                                    TextInput::make('chairs_count')
                                        ->label('Número de Barbeiros / Cadeiras')
                                        ->maxLength(60)
                                        ->columnSpan(2),
                                    TextInput::make('selected_plan')
                                        ->label('Plano Selecionado')
                                        ->maxLength(100)
                                        ->columnSpan(2),
                                    Select::make('selected_plan_id')
                                        ->label('Plano SaaS Vinculado')
                                        ->relationship('selectedSaasPlan', 'name')
                                        ->columnSpan(2),
                                    Textarea::make('message')
                                        ->label('Mensagem / Observação do Cliente')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ])
                                ->columns(4),
                        ]),

                    Tab::make('Atendimento Comercial')
                        ->icon('heroicon-m-chat-bubble-left-right')
                        ->schema([
                            Section::make('Status do Pipeline')
                                ->schema([
                                    Select::make('status')
                                        ->label('Status do Lead')
                                        ->options([
                                            'novo' => 'Novo (Não atendido)',
                                            'em_contato' => 'Em Contato no WhatsApp',
                                            'demonstracao' => 'Demonstração Agendada',
                                            'convertido' => 'Convertido em Cliente',
                                            'arquivado' => 'Arquivado / Sem Interesse',
                                        ])
                                        ->required()
                                        ->default('novo')
                                        ->columnSpan(2),
                                    Select::make('converted_barbershop_id')
                                        ->label('Barbearia Criada (Se convertido)')
                                        ->relationship('convertedBarbershop', 'name')
                                        ->searchable()
                                        ->columnSpan(2),
                                    Textarea::make('notes')
                                        ->label('Anotações Internas do Atendimento')
                                        ->rows(4)
                                        ->columnSpanFull(),
                                ])
                                ->columns(4),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Responsável')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('barbershop_name')
                    ->label('Barbearia')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('WhatsApp')
                    ->copyable()
                    ->copyMessage('Telefone copiado')
                    ->icon('heroicon-m-phone'),
                TextColumn::make('city')
                    ->label('Cidade')
                    ->searchable(),
                TextColumn::make('selected_plan')
                    ->label('Plano de Interesse')
                    ->badge()
                    ->color('amber'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'novo' => 'danger',
                        'em_contato' => 'warning',
                        'demonstracao' => 'info',
                        'convertido' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'novo' => 'Novo',
                        'em_contato' => 'Em Contato',
                        'demonstracao' => 'Demonstração',
                        'convertido' => 'Convertido',
                        'arquivado' => 'Arquivado',
                        default => ucfirst($state),
                    }),
                TextColumn::make('created_at')
                    ->label('Recebido em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'novo' => 'Novo',
                        'em_contato' => 'Em Contato',
                        'demonstracao' => 'Demonstração',
                        'convertido' => 'Convertido',
                        'arquivado' => 'Arquivado',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLandingLeads::route('/'),
            'edit' => EditLandingLead::route('/{record}/edit'),
        ];
    }
}
