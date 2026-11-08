<?php

declare(strict_types=1);

namespace App\Filament\Resources\SatisfactionSurveys;

use App\Filament\Resources\SatisfactionSurveys\Pages\CreateSatisfactionSurvey;
use App\Filament\Resources\SatisfactionSurveys\Pages\EditSatisfactionSurvey;
use App\Filament\Resources\SatisfactionSurveys\Pages\ListSatisfactionSurveys;
use App\Filament\Resources\SatisfactionSurveys\Pages\ViewSatisfactionSurvey;
use App\Filament\Resources\SatisfactionSurveys\RelationManagers\ResponsesRelationManager;
use App\Models\SatisfactionSurvey;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class SatisfactionSurveyResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = SatisfactionSurvey::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleBottomCenterText;

    protected static string|UnitEnum|null $navigationGroup = 'Pedagógico';

    protected static ?string $navigationLabel = 'Pesquisas de satisfação';

    protected static ?string $modelLabel = 'pesquisa de satisfação';

    protected static ?string $pluralModelLabel = 'pesquisas de satisfação';

    protected static ?string $slug = 'satisfaction-surveys';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Configurações da pesquisa')
                ->description('Defina o título, orientações e se esta pesquisa deve ser exibida no Kiosk de presença.')
                ->schema([
                    TextInput::make('title')
                        ->label('Título da pesquisa')
                        ->placeholder('Ex.: Avaliação de Aulas e Estrutura - Outubro 2026')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),
                    Select::make('school_id')
                        ->label('Unidade Escolar')
                        ->relationship('school', 'trade_name')
                        ->placeholder('Todas as Unidades (Pesquisa Global da Rede)')
                        ->searchable()
                        ->preload()
                        ->helperText('Selecione uma unidade específica ou deixe em branco para aplicar a pesquisa a todas as escolas.')
                        ->columnSpan(2),
                    Textarea::make('description')
                        ->label('Orientação ao aluno')
                        ->placeholder('Ex.: Sua opinião nos ajuda a melhorar a qualidade do curso e dos laboratórios.')
                        ->rows(2)
                        ->maxLength(1000)
                        ->columnSpan(2),
                    Toggle::make('is_active')
                        ->label('Pesquisa ativa no Kiosk')
                        ->helperText('Quando ativada, os alunos que ainda não responderam a esta pesquisa específica serão convidados a responder no Kiosk.')
                        ->default(true),
                ])->columns(2),

            Section::make('Perguntas da pesquisa')
                ->description('Adicione as perguntas que o aluno responderá na tela do Kiosk.')
                ->schema([
                    Repeater::make('questions')
                        ->relationship('questions')
                        ->label('Lista de perguntas')
                        ->orderColumn('sort_order')
                        ->defaultItems(1)
                        ->schema([
                            TextInput::make('question')
                                ->label('Enunciado da pergunta')
                                ->placeholder('Ex.: Como você avalia a atenção e suporte dos instrutores?')
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Select::make('type')
                                ->label('Tipo de resposta')
                                ->options([
                                    'rating_5' => 'Avaliação (1 a 5 estrelas)',
                                    'nps_10' => 'NPS (Escala de 0 a 10)',
                                    'multiple_choice' => 'Múltipla escolha',
                                    'text' => 'Texto livre / Sugestão aberta',
                                ])
                                ->default('rating_5')
                                ->required()
                                ->live(),
                            Toggle::make('is_required')
                                ->label('Resposta obrigatória')
                                ->default(true),
                            TagsInput::make('options')
                                ->label('Opções de resposta (pressione Enter para adicionar cada opção)')
                                ->placeholder('Adicionar opção...')
                                ->visible(fn (Get $get): bool => $get('type') === 'multiple_choice')
                                ->required(fn (Get $get): bool => $get('type') === 'multiple_choice')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? 'Nova pergunta'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Título da pesquisa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('school.trade_name')
                    ->label('Unidade')
                    ->placeholder('Todas (Global)')
                    ->badge()
                    ->sortable(),
                TextColumn::make('questions_count')
                    ->counts('questions')
                    ->label('Perguntas')
                    ->badge()
                    ->color('info'),
                TextColumn::make('responses_count')
                    ->counts('responses')
                    ->label('Respostas')
                    ->badge()
                    ->color('success'),
                TextColumn::make('average_rating')
                    ->label('Média Geral')
                    ->state(fn (SatisfactionSurvey $record): string => $record->average_rating !== null ? '⭐ '.number_format($record->average_rating, 1, ',', '.') : '—')
                    ->badge()
                    ->color(fn (SatisfactionSurvey $record): string => ($record->average_rating ?? 0) >= 4.0 ? 'success' : (($record->average_rating ?? 0) >= 3.0 ? 'warning' : 'gray')),
                ToggleColumn::make('is_active')
                    ->label('Ativa no Kiosk'),
                TextColumn::make('created_at')
                    ->label('Criada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('school_id')
                    ->label('Unidade Escolar')
                    ->relationship('school', 'trade_name')
                    ->placeholder('Todas as Unidades (Global)'),
                TernaryFilter::make('is_active')
                    ->label('Situação')
                    ->trueLabel('Ativas no Kiosk')
                    ->falseLabel('Inativas')
                    ->placeholder('Todas'),
            ])
            ->recordActions([
                Action::make('results')
                    ->label('Resultados & Gráficos')
                    ->icon('heroicon-o-chart-bar')
                    ->color('primary')
                    ->url(fn (SatisfactionSurvey $record): string => route('filament.admin.pages.satisfaction-survey-report', ['surveyId' => $record->id])),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSatisfactionSurveys::route('/'),
            'create' => CreateSatisfactionSurvey::route('/create'),
            'view' => ViewSatisfactionSurvey::route('/{record}'),
            'edit' => EditSatisfactionSurvey::route('/{record}/edit'),
        ];
    }
}
