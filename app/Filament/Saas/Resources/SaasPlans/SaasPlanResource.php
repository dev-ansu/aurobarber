<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\SaasPlans;

use App\Filament\Saas\Resources\SaasPlans\Pages\CreateSaasPlan;
use App\Filament\Saas\Resources\SaasPlans\Pages\EditSaasPlan;
use App\Filament\Saas\Resources\SaasPlans\Pages\ListSaasPlans;
use App\Models\SaasPlan;
use App\Models\User;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SaasPlanResource extends Resource
{
    protected static ?string $model = SaasPlan::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Planos de Licenciamento';

    protected static ?string $modelLabel = 'Plano';

    protected static ?string $pluralModelLabel = 'Planos de Licenciamento';

    protected static string|UnitEnum|null $navigationGroup = 'SaaS Master';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->role === 'master_admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Configuração do plano')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Apresentação e preço')
                        ->schema([
                            Section::make('Plano comercial')
                                ->description('Nome, selo, descrição e mensalidade exibidos na página Planos de licenciamento das barbearias.')
                                ->schema([
                                    TextInput::make('name')->label('Nome do plano')->required()->maxLength(80)->columnSpanFull(),
                                    TextInput::make('slug')->label('Identificador (slug)')->required()->unique(ignoreRecord: true)->maxLength(90)->columnSpanFull(),
                                    TextInput::make('badge')->label('Selo de destaque (opcional)')->placeholder('Ex: Mais Popular, Maior Economia')->maxLength(50)->columnSpanFull(),
                                    Textarea::make('description')->label('Descrição')->rows(2)->columnSpanFull(),
                                    TextInput::make('monthly_price')->label('Valor mensal equivalente')->numeric()->prefix('R$')->required()->minValue(0)->columnSpanFull(),
                                ])
                                ->columnSpanFull(),
                        ]),
                    Tab::make('Ciclo, recursos e publicação')
                        ->schema([
                            Section::make('Ciclo e itens inclusos')
                                ->schema([
                                    TextInput::make('billing_cycle_months')
                                        ->label('Meses por ciclo')
                                        ->numeric()
                                        ->integer()
                                        ->default(1)
                                        ->minValue(1)
                                        ->required()
                                        ->helperText('Ex: 1 para Mensal, 6 para Semestral, 12 para Anual.')
                                        ->columnSpanFull(),
                                    TextInput::make('renewal_days')
                                        ->label('Dias adicionados na renovação')
                                        ->numeric()
                                        ->integer()
                                        ->default(30)
                                        ->minValue(1)
                                        ->required()
                                        ->helperText('Quantidade de dias adicionados ao vencimento ao aprovar um comprovante (ex: 30, 180, 365).')
                                        ->columnSpanFull(),
                                    TextInput::make('max_units')
                                        ->label('Unidades permitidas')
                                        ->numeric()
                                        ->integer()
                                        ->default(1)
                                        ->minValue(1)
                                        ->required()
                                        ->columnSpanFull(),
                                    TagsInput::make('features')
                                        ->label('Recursos exibidos no cartão do plano')
                                        ->placeholder('Digite um recurso e pressione Enter')
                                        ->columnSpanFull(),
                                    Toggle::make('is_featured')->label('Destacar como plano recomendado')->default(false)->columnSpanFull(),
                                    Toggle::make('is_active')->label('Exibir e aceitar solicitações deste plano')->default(true)->columnSpanFull(),
                                    TextInput::make('sort_order')->label('Ordem de exibição')->numeric()->integer()->default(0)->required()->columnSpanFull(),
                                ])
                                ->columnSpanFull(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Plano')->weight('bold')->searchable()->sortable(),
                TextColumn::make('badge')->label('Selo')->badge()->color('warning')->placeholder('—'),
                TextColumn::make('monthly_price')->label('Mensalidade')->money('BRL')->sortable(),
                TextColumn::make('renewal_days')->label('Renovação')->formatStateUsing(fn (int $state): string => "{$state} dias"),
                TextColumn::make('barbershops_count')->counts('barbershops')->label('Barbearias'),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Publicado' : 'Oculto')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (SaasPlan $record): bool => ! $record->barbershops()->withTrashed()->exists()),
            ])
            ->headerActions([
                CreateAction::make()->label('Novo plano'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSaasPlans::route('/'),
            'create' => CreateSaasPlan::route('/create'),
            'edit' => EditSaasPlan::route('/{record}/edit'),
        ];
    }
}
