<?php

namespace App\Filament\Resources\FinancialTypes;

use App\Filament\Exports\FinancialTypeExporter;
use App\Filament\Resources\FinancialTypes\Pages\ManageFinancialTypes;
use App\Models\FinancialType;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;

class FinancialTypeResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = FinancialType::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Plano de Contas';

    protected static ?string $modelLabel = 'Conta Contábil';

    protected static ?string $pluralModelLabel = 'Plano de Contas Gerencial';

    protected static string|UnitEnum|null $navigationGroup = 'Configurações Financeiras';

    protected static ?int $navigationSort = 51;

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Estrutura do Plano de Contas')
                    ->description('Classificação contábil para receitas, custos do ensino e despesas operacionais da escola.')
                    ->schema([
                        Select::make('parent_id')
                            ->label('Conta Superior (Pai)')
                            ->options(function (?FinancialType $record): array {
                                return FinancialType::query()
                                    ->when($record?->id, fn ($q) => $q->where('id', '!=', $record->id))
                                    ->where('is_synthetic', true)
                                    ->orderBy('code')
                                    ->get()
                                    ->mapWithKeys(fn (FinancialType $item) => [
                                        $item->id => ($item->code ? "{$item->code} - " : '') . $item->name,
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->nullable()
                            ->placeholder('Nenhuma (Conta de Nível Principal)')
                            ->helperText('Contas analíticas devem ser filhas de uma conta sintética.'),

                        TextInput::make('code')
                            ->label('Código Contábil')
                            ->placeholder('Ex: 1.1.1 ou 3.1.2')
                            ->maxLength(20)
                            ->helperText('Código hierárquico na árvore do plano de contas (ex: 1, 1.1, 1.1.1).'),

                        TextInput::make('name')
                            ->label('Descrição da Conta')
                            ->placeholder('Ex: Mensalidade de Cursos, Aluguel do Prédio')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state, ?FinancialType $record) {
                                if (! $record?->slug) {
                                    $set('slug', Str::slug($state, '_'));
                                }
                            }),

                        Select::make('type')
                            ->label('Natureza da Conta')
                            ->options(FinancialType::getTypeOptions())
                            ->default(FinancialType::TYPE_EXPENSE)
                            ->required(),

                        Toggle::make('is_synthetic')
                            ->label('Conta Sintética (Apenas Agrupadora)')
                            ->helperText('Contas sintéticas apenas agrupam outras contas e não recebem lançamentos diretos.')
                            ->default(false),

                        Toggle::make('is_active')
                            ->label('Conta Ativa')
                            ->default(true)
                            ->required(),

                        TextInput::make('slug')
                            ->label('Identificador Interno (Slug)')
                            ->maxLength(255)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Gerado automaticamente para integrações.'),

                        Textarea::make('description')
                            ->label('Descrição e Orientações de Lançamento')
                            ->placeholder('Instruções para a equipe saber quando usar esta conta...')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('code', 'asc')
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->placeholder('—'),

                TextColumn::make('name')
                    ->label('Descrição da Conta')
                    ->searchable()
                    ->sortable()
                    ->description(fn (FinancialType $record): ?string => $record->parent ? "Superior: {$record->parent->name}" : null),

                TextColumn::make('type')
                    ->label('Natureza')
                    ->badge()
                    ->color(fn (string $state): string => FinancialType::getTypeColors()[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state): string => FinancialType::getTypeOptions()[$state] ?? $state),

                IconColumn::make('is_synthetic')
                    ->label('Tipo')
                    ->boolean()
                    ->trueIcon('heroicon-o-folder')
                    ->falseIcon('heroicon-o-document-text')
                    ->trueColor('primary')
                    ->falseColor('gray')
                    ->tooltip(fn (FinancialType $record): string => $record->is_synthetic ? 'Sintética (Grupo)' : 'Analítica (Aceita lançamentos)'),

                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Natureza')
                    ->options(FinancialType::getTypeOptions()),

                TernaryFilter::make('is_synthetic')
                    ->label('Classificação')
                    ->trueLabel('Apenas Sintéticas (Grupos)')
                    ->falseLabel('Apenas Analíticas (Lançamentos)')
                    ->placeholder('Todas'),

                TernaryFilter::make('is_active')
                    ->label('Situação')
                    ->trueLabel('Ativas')
                    ->falseLabel('Inativas')
                    ->placeholder('Todas'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar Plano de Contas')->exporter(FinancialTypeExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:FinancialType') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:FinancialType') ?? false),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFinancialTypes::route('/'),
        ];
    }
}
