<?php

namespace App\Filament\Resources\Contracts\RelationManagers;

use App\Models\Contract;
use App\Models\ContractHistory;
use App\Models\ContractHistoryType;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContractHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static ?string $title = 'Histórico de acompanhamento';

    protected static ?string $modelLabel = 'registro';

    protected static ?string $pluralModelLabel = 'registros';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Contract
            && (auth()->user()?->can('ViewAny:ContractHistory') ?? false);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Registro de acompanhamento')
                ->columnSpanFull()
                ->schema([
                    Select::make('type')
                        ->label('Tipo de contato')
                        ->options(fn (): array => ContractHistoryType::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->orderBy('name')
                            ->pluck('name', 'slug')
                            ->all())
                        ->default(fn (): ?string => ContractHistoryType::query()
                            ->where('is_active', true)
                            ->orderByRaw("CASE WHEN slug = 'general' THEN 0 ELSE 1 END")
                            ->orderBy('sort_order')
                            ->value('slug'))
                        ->required(),
                    DateTimePicker::make('occurred_at')
                        ->label('Quando ocorreu')
                        ->default(now())
                        ->native(false)
                        ->seconds(false)
                        ->required(),
                    DateTimePicker::make('next_follow_up_at')
                        ->label('Próximo acompanhamento')
                        ->native(false)
                        ->seconds(false),
                    Textarea::make('description')
                        ->label('O que foi feito ou combinado?')
                        ->required()
                        ->rows(5)
                        ->maxLength(5000)
                        ->columnSpanFull(),
                ])->columns(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user.person', 'typeDefinition']))
            ->defaultSort('occurred_at', 'desc')
            ->recordAction('viewDetail')
            ->columns([
                TextColumn::make('occurred_at')->label('Data do contato')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('type_label')->label('Tipo')->badge(),
                TextColumn::make('description')
                    ->label('Registro')
                    ->limit(75)
                    ->tooltip('Clique na linha para visualizar os detalhes completos deste histórico')
                    ->searchable(),
                TextColumn::make('user.name')->label('Registrado por')->placeholder('Sistema')->toggleable(),
                TextColumn::make('next_follow_up_at')->label('Próximo retorno')->dateTime('d/m/Y H:i')->placeholder('Não definido')->sortable(),
            ])
            ->recordActions([
                Action::make('viewDetail')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->tooltip('Ver histórico completo')
                    ->modalHeading('Detalhes do Registro de Acompanhamento')
                    ->modalWidth('3xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar')
                    ->infolist(fn (ContractHistory $record): array => [
                        Section::make('Informações Gerais')
                            ->schema([
                                TextEntry::make('occurred_at')
                                    ->label('Data e Hora do Registro')
                                    ->dateTime('d/m/Y H:i'),
                                TextEntry::make('type_label')
                                    ->label('Tipo de Contato')
                                    ->badge(),
                                TextEntry::make('user.name')
                                    ->label('Cadastrado por')
                                    ->placeholder('Sistema Automático'),
                                TextEntry::make('next_follow_up_at')
                                    ->label('Próximo Acompanhamento Agendado')
                                    ->dateTime('d/m/Y H:i')
                                    ->placeholder('Nenhum retorno agendado'),
                            ])->columns(2),
                        Section::make('Relato / Ocorrência Completa')
                            ->schema([
                                TextEntry::make('description')
                                    ->label('')
                                    ->extraAttributes(['class' => 'whitespace-pre-line text-sm leading-relaxed text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-gray-800/60 p-4 rounded-2xl ring-1 ring-gray-950/5 dark:ring-white/10 select-text'])
                                    ->columnSpanFull(),
                            ]),
                    ]),
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Editar registro')
                    ->modalWidth('3xl'),
                DeleteAction::make()->iconButton()->tooltip('Excluir registro'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar acompanhamento')
                    ->modalWidth('3xl')
                    ->mutateDataUsing(fn (array $data): array => [
                        ...$data,
                        'user_id' => filament()->auth()->id(),
                    ]),
            ]);
    }
}
