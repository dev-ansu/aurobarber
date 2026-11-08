<?php

namespace App\Filament\Resources\SchoolGoals;

use App\Filament\Resources\SchoolGoals\Pages\ManageSchoolGoals;
use App\Models\SchoolGoal;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use UnitEnum;

class SchoolGoalResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = SchoolGoal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static ?string $navigationLabel = 'Metas da Escola';

    protected static ?string $modelLabel = 'Meta da Escola';

    protected static ?string $pluralModelLabel = 'Metas da Escola';

    protected static string|UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'school-goals';

    public static function form(Schema $schema): Schema
    {
        $currentYear = (int) Carbon::now()->year;
        $years = range(max(2020, $currentYear - 3), $currentYear + 10);
        $yearOptions = array_combine($years, $years);

        return $schema
            ->components([
                Section::make('Período da Meta')
                    ->description('Defina o mês e ano correspondente ao ciclo de metas.')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('month')
                                ->label('Mês de Referência')
                                ->options([
                                    1 => '01 - Janeiro',
                                    2 => '02 - Fevereiro',
                                    3 => '03 - Março',
                                    4 => '04 - Abril',
                                    5 => '05 - Maio',
                                    6 => '06 - Junho',
                                    7 => '07 - Julho',
                                    8 => '08 - Agosto',
                                    9 => '09 - Setembro',
                                    10 => '10 - Outubro',
                                    11 => '11 - Novembro',
                                    12 => '12 - Dezembro',
                                ])
                                ->default(Carbon::now()->month)
                                ->required(),

                            Select::make('year')
                                ->label('Ano')
                                ->options($yearOptions)
                                ->searchable()
                                ->default($currentYear)
                                ->required(),
                        ]),
                    ])->columnSpanFull(),

                Section::make('Metas Comerciais & Financeiras')
                    ->description('Estabeleça os números alvo para o time de vendas e arrecadação.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('target_enrollments')
                                ->label('Meta de Matrículas (Qtd)')
                                ->numeric()
                                ->minValue(0)
                                ->default(20)
                                ->required()
                                ->helperText('Novos contratos fechados no mês.'),

                            TextInput::make('target_sales_amount')
                                ->label('Meta de Faturamento (Vendas)')
                                ->numeric()
                                ->prefix('R$')
                                ->minValue(0)
                                ->default(0.00)
                                ->required()
                                ->helperText('Valor total em novos contratos assinados.'),

                            TextInput::make('target_revenue_amount')
                                ->label('Meta de Arrecadação (Caixa)')
                                ->numeric()
                                ->prefix('R$')
                                ->minValue(0)
                                ->default(0.00)
                                ->required()
                                ->helperText('Total em parcelas a receber no mês.'),
                        ]),
                    ])->columnSpanFull(),

                Section::make('Metas Pedagógicas & Retenção')
                    ->description('Defina os padrões de qualidade pedagógica, frequência e combate à evasão.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('target_attendance_rate')
                                ->label('Meta de Assiduidade Mínima (%)')
                                ->numeric()
                                ->suffix('%')
                                ->minValue(1)
                                ->maxValue(100)
                                ->default(85.00)
                                ->required()
                                ->helperText('Frequência geral esperada nas chamadas do mês.'),

                            TextInput::make('target_max_cancellations')
                                ->label('Teto Máximo de Cancelamentos')
                                ->numeric()
                                ->suffix('alunos')
                                ->minValue(0)
                                ->default(3)
                                ->required()
                                ->helperText('Limite máximo tolerado de evasão no mês.'),

                            TextInput::make('target_retention_interviews')
                                ->label('Meta de Atendimentos Pedagógicos')
                                ->numeric()
                                ->suffix('atendimentos')
                                ->minValue(0)
                                ->default(10)
                                ->required()
                                ->helperText('Contatos/reuniões com alunos em risco de evasão.'),
                        ]),

                        Textarea::make('notes')
                            ->label('Estratégia e Observações do Mês')
                            ->placeholder('Ex: Foco no curso de Informática, campanha de resgate de faltosos e recuperação de aulas.')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('year', 'desc')
            ->columns([
                TextColumn::make('formatted_period')
                    ->label('Mês / Ano')
                    ->badge()
                    ->color('primary')
                    ->sortable(['year', 'month']),

                TextColumn::make('target_enrollments')
                    ->label('Meta Matrículas')
                    ->badge()
                    ->color('info')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => number_format((int) $state, 0, ',', '.').' alunos'),

                TextColumn::make('target_sales_amount')
                    ->label('Meta Vendas (R$)')
                    ->money('BRL')
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('target_revenue_amount')
                    ->label('Meta Arrecadação (R$)')
                    ->money('BRL')
                    ->alignRight()
                    ->sortable(),

                TextColumn::make('target_attendance_rate')
                    ->label('Meta Frequência')
                    ->badge()
                    ->color('success')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state, 1, ',', '.').'%' : '—'),

                TextColumn::make('target_max_cancellations')
                    ->label('Teto Cancelamentos')
                    ->badge()
                    ->color('danger')
                    ->alignRight()
                    ->formatStateUsing(fn ($state) => $state !== null ? "Máx {$state}" : '—'),

                TextColumn::make('notes')
                    ->label('Observações')
                    ->limit(35)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->label('Filtrar por Ano')
                    ->options(function () {
                        return SchoolGoal::query()
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->all();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSchoolGoals::route('/'),
        ];
    }
}
