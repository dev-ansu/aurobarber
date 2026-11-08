<?php

namespace App\Filament\Resources\ContractHistoryTypes;

use App\Filament\Resources\ContractHistoryTypes\Pages\ManageContractHistoryTypes;
use App\Models\ContractHistoryType;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ContractHistoryTypeResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    protected static ?string $model = ContractHistoryType::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Comercial';

    protected static ?string $navigationLabel = 'Tipos de acompanhamento';

    protected static ?string $modelLabel = 'tipo de acompanhamento';

    protected static ?string $pluralModelLabel = 'tipos de acompanhamento';

    protected static ?string $slug = 'contract-history-types';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tipo de acompanhamento')->schema([
                TextInput::make('name')->label('Nome')->required()->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (?string $state, callable $set, ?ContractHistoryType $record): void {
                        if (! $record) {
                            $set('slug', Str::slug($state ?? ''));
                        }
                    }),
                TextInput::make('slug')->label('Identificador')->required()->alphaDash()->maxLength(40)
                    ->unique(ignoreRecord: true)
                    ->helperText('Identificador interno estável dos registros. Evite alterá-lo após cadastrar históricos.'),
                TextInput::make('sort_order')->label('Ordem de exibição')->integer()->default(0)->required(),
                Toggle::make('is_active')->label('Disponível para novos registros')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort_order')->columns([
            TextColumn::make('name')->label('Tipo')->searchable()->sortable(),
            TextColumn::make('slug')->label('Identificador')->copyable(),
            TextColumn::make('sort_order')->label('Ordem')->sortable(),
            IconColumn::make('is_active')->label('Ativo')->boolean(),
        ])->filters([
            TernaryFilter::make('is_active')->label('Disponibilidade')->boolean()->trueLabel('Ativos')->falseLabel('Inativos'),
        ])->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageContractHistoryTypes::route('/')];
    }
}
