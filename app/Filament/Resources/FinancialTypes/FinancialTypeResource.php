<?php

declare(strict_types=1);

namespace App\Filament\Resources\FinancialTypes;

use App\Filament\Resources\FinancialTypes\Pages\ManageFinancialTypes;
use App\Models\FinancialType;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use UnitEnum;

class FinancialTypeResource extends Resource
{
    protected static ?string $model = FinancialType::class;

    protected static bool $isScopedToTenant = false;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Plano de Contas';

    protected static ?string $modelLabel = 'conta contábil';

    protected static ?string $pluralModelLabel = 'plano de contas gerencial';

    protected static string|UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return Filament::getTenant() !== null
            && ! auth()->user()?->isRestrictedBarber();
    }

    public static function getEloquentQuery(): Builder
    {
        $tenantId = Filament::getTenant()?->getKey();

        return parent::getEloquentQuery()
            ->forBarbershop($tenantId ? (int) $tenantId : null)
            ->orderByRaw("COALESCE(code, '999'), name");
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Estrutura do Plano de Contas')
                ->description('Classificação contábil para receitas, custos diretos de bancada e despesas operacionais da barbearia.')
                ->schema([
                    Select::make('parent_id')
                        ->label('Conta Superior (Grupo Pai)')
                        ->options(function (?FinancialType $record): array {
                            $tenantId = Filament::getTenant()?->getKey();

                            return FinancialType::query()
                                ->forBarbershop($tenantId ? (int) $tenantId : null)
                                ->when($record?->id, fn ($q) => $q->where('id', '!=', $record->id))
                                ->where('is_synthetic', true)
                                ->orderBy('code')
                                ->get()
                                ->mapWithKeys(fn (FinancialType $item) => [
                                    $item->id => ($item->code ? "{$item->code} - " : '').$item->name,
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->nullable()
                        ->placeholder('Nenhuma (Conta de Nível Principal)')
                        ->helperText('Contas analíticas (lançáveis) devem pertencer a um grupo sintético.')
                        ->columnSpanFull(),

                    TextInput::make('code')
                        ->label('Código Contábil')
                        ->placeholder('Ex: 3.1.1 ou 1.2')
                        ->maxLength(20)
                        ->helperText('Código hierárquico na árvore do plano de contas (ex: 3, 3.1, 3.1.1).'),

                    TextInput::make('name')
                        ->label('Descrição da Conta')
                        ->placeholder('Ex: Aluguel do Ponto, Insumos de Lavatório')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state, ?FinancialType $record): void {
                            if (! $record?->slug) {
                                $set('slug', Str::slug($state ?? '', '_'));
                            }
                        }),

                    Select::make('type')
                        ->label('Natureza da Conta')
                        ->options(FinancialType::getTypeOptions())
                        ->default(FinancialType::TYPE_EXPENSE)
                        ->required(),

                    TextInput::make('slug')
                        ->label('Identificador Interno (Slug)')
                        ->maxLength(255)
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Gerado automaticamente.'),

                    Toggle::make('is_synthetic')
                        ->label('Conta Sintética (Apenas Agrupadora)')
                        ->helperText('Contas sintéticas apenas agrupam subcontas e não recebem lançamentos diretos.')
                        ->default(false),

                    Toggle::make('is_active')
                        ->label('Conta Ativa')
                        ->default(true)
                        ->required(),

                    Textarea::make('description')
                        ->label('Instruções e Orientações de Lançamento')
                        ->placeholder('Orientações para a equipe saber quando usar esta conta...')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('code')
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->sortable()
                    ->searchable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                TextColumn::make('name')
                    ->label('Nome da Conta')
                    ->searchable()
                    ->sortable()
                    ->description(fn (FinancialType $record): ?string => $record->description),

                TextColumn::make('type')
                    ->label('Natureza')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => FinancialType::getTypeOptions()[$state] ?? ucfirst($state))
                    ->color(fn (string $state): string => FinancialType::getTypeColors()[$state] ?? 'gray'),

                TextColumn::make('parent.name')
                    ->label('Grupo Superior')
                    ->placeholder('Nível Principal')
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_synthetic')
                    ->label('Tipo')
                    ->boolean()
                    ->trueIcon('heroicon-o-folder')
                    ->falseIcon('heroicon-o-document-text')
                    ->trueColor('warning')
                    ->falseColor('success')
                    ->tooltip(fn (FinancialType $record): string => $record->is_synthetic ? 'Grupo Sintético (Agrupador)' : 'Conta Analítica (Recebe Lançamentos)'),

                IconColumn::make('is_active')
                    ->label('Ativa')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Natureza')
                    ->options(FinancialType::getTypeOptions()),

                TernaryFilter::make('is_synthetic')
                    ->label('Classificação')
                    ->placeholder('Todas as contas')
                    ->trueLabel('Apenas grupos sintéticos')
                    ->falseLabel('Apenas analíticas (lançáveis)'),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Todas')
                    ->trueLabel('Ativas')
                    ->falseLabel('Inativas'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFinancialTypes::route('/'),
        ];
    }
}
