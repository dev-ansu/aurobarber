<?php

namespace App\Filament\Resources\People\RelationManagers;

use App\Actions\FillAddressFromZipCode;
use App\Enums\AddressTypeEnum;
use App\Models\Address;
use App\Services\CityService;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $recordTitleAttribute = 'value';

    protected static ?string $title = 'Endereços';

    protected static ?string $label = 'Endereço';

    protected static ?string $pluralLabel = 'Endereços';

    protected static ?string $modelLabel = 'endereço';

    protected static ?string $pluralModelLabel = 'endereços';

    protected static ?string $recordTitle = 'value';

    protected static ?string $slug = 'addresses';

    protected static BackedEnum|string|null $icon = 'heroicon-o-map-pin';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([

                    Select::make('type')
                        ->label('Tipo')
                        ->options(AddressTypeEnum::class)
                        ->required(),
                    Grid::make(1)->schema([

                        TextInput::make('zip_code')
                            ->label('CEP')
                            ->mask('99999-999')
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                function (?string $state, Set $set) {
                                    if (blank($state)) {
                                        return;
                                    }

                                    $address = app(FillAddressFromZipCode::class)->execute($state);
                                    if (! $address) {
                                        Notification::make()
                                            ->title('CEP não encontrado.')
                                            ->danger()
                                            ->send();

                                        return;
                                    }

                                    foreach ($address as $field => $value) {
                                        $set($field, $value);
                                    }
                                })
                            ->required(fn (Get $get): bool => ! (bool) $get('without_zip_code'))
                            ->disabled(fn (Get $get): bool => (bool) $get('without_zip_code'))
                            ->dehydrated(fn () => true),

                        Checkbox::make('without_zip_code')
                            ->label('Não tenho o CEP')
                            ->default(false)
                            ->afterStateHydrated(function (Checkbox $component, ?Address $record): void {
                                if ($record && blank($record->zip_code)) {
                                    $component->state(true);
                                }
                            })
                            ->afterStateUpdated(function (bool $state, Set $set) {
                                if (! $state) {
                                    return;
                                }
                                $set('zip_code', null);
                                $set('street', null);
                                $set('neighborhood', null);
                                $set('city_id', null);
                                $set('number', null);
                                $set('complement', null);
                            })
                            ->live()
                            ->dehydrated(false),
                    ]),

                ])->columnSpanFull(),

                Grid::make(2)->schema([

                    TextInput::make('street')
                        ->label('Logradouro')
                        ->disabled(fn (Get $get) => ! $get('without_zip_code'))
                        ->dehydrated(fn () => true)
                        ->required(),
                    TextInput::make('number')
                        ->label('Número')
                        ->integer()
                        ->required(),

                    Grid::make(2)->schema([
                        Select::make('city_id')
                            ->label('Cidade')
                            ->searchable()
                            ->disabled(fn (Get $get) => ! $get('without_zip_code'))
                            ->dehydrated(fn () => true)
                            ->placeholder(function (Get $get) {
                                if (! $get('without_zip_code')) {
                                    return 'Preencha o CEP ou marque a opção "Não tenho o CEP".';
                                }
                            })
                            ->getSearchResultsUsing(
                                fn (string $search) => app(CityService::class)->search($search)
                            )->getOptionLabelUsing(
                                fn ($value) => app(CityService::class)->label($value)
                            )
                            ->helperText(
                                'Caso não saiba o CEP, escolha a cidade manualmente.'
                            )
                            ->required(),

                        TextInput::make('neighborhood')
                            ->label('Bairro')
                            ->disabled(fn (Get $get) => ! $get('without_zip_code')
                            )->dehydrated(fn () => true)->required(),

                    ])->columnSpanFull(),

                ])->columnSpanFull(),

                Textarea::make('complement')
                    ->label('Complemento')
                    ->columnSpanFull(),

                Toggle::make('is_primary')
                    ->label('Principal')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('value')
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->description(fn (Address $record) => $record->is_primary ? 'Principal' : null)
                    ->badge()
                    ->searchable(),

                TextColumn::make('zip_code')
                    ->label('CEP')
                    ->searchable(),

                TextColumn::make('street')
                    ->label('Rua')
                    ->searchable(),

                TextColumn::make('number')
                    ->label('Número')
                    ->searchable(),

                TextColumn::make('city.state.name')
                    ->label('Cidade')
                    ->searchable(),

                TextColumn::make('city.name')
                    ->label('Cidade')
                    ->searchable(),

                TextColumn::make('city.state.name')
                    ->label('Cidade')
                    ->searchable(),

                TextColumn::make('neighborhood')
                    ->label('Bairro')
                    ->searchable(),

                TextColumn::make('complement')
                    ->label('Complemento')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                IconColumn::make('is_primary')
                    ->label('Principal')
                    ->hidden()
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('is_primary')
                    ->label('Principal')
                    ->query(fn ($query) => $query->where('is_primary', true)),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
