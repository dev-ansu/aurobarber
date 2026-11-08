<?php

namespace App\Filament\Resources\People\RelationManagers;

use App\Enums\ContactTypeEnum;
use App\Models\Contact;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    protected static ?string $recordTitleAttribute = 'value';

    protected static ?string $title = 'Contatos';

    protected static ?string $label = 'Contato';

    protected static ?string $pluralLabel = 'Contatos';

    protected static ?string $modelLabel = 'contato';

    protected static ?string $pluralModelLabel = 'contatos';

    protected static ?string $recordTitle = 'value';

    protected static ?string $slug = 'contacts';

    protected static BackedEnum|string|null $icon = 'heroicon-o-phone';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipo')
                    ->required()
                    ->live()
                    ->options(ContactTypeEnum::class),
                TextInput::make('value')
                    ->label('Contato')
                    ->mask(function (Get $get): ?string {
                        $type = $get('type');
                        $val = $type instanceof ContactTypeEnum ? $type->value : $type;

                        return match ($val) {
                            'phone' => '(99) 9999-9999',
                            'cellphone', 'whatsapp' => '(99) 99999-9999',
                            default => null,
                        };
                    })
                    ->placeholder(function (Get $get): string {
                        $type = $get('type');
                        $val = $type instanceof ContactTypeEnum ? $type->value : $type;

                        return match ($val) {
                            'phone' => '(11) 3456-7890',
                            'cellphone', 'whatsapp' => '(11) 98765-4321',
                            'email' => 'exemplo@dominio.com',
                            'instagram' => '@perfil',
                            'telegram' => '@usuario',
                            default => 'Digite o contato',
                        };
                    })
                    ->rules(function (Get $get) {
                        $type = $get('type');

                        if ($type instanceof ContactTypeEnum) {
                            return $type->getValidationRules();
                        }

                        return ContactTypeEnum::tryFrom($type)?->getValidationRules() ?? ['required', 'string', 'max:255'];
                    })
                    ->validationMessages([
                        'regex' => 'Informe um número de telefone válido com DDD, ex: (11) 98765-4321.',
                        'email' => 'Informe um endereço de e-mail válido.',
                    ])
                    ->required(),
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
                    ->description(fn (Contact $record) => $record->is_primary ? 'Principal' : null)
                    ->badge()
                    ->searchable(),
                TextColumn::make('value')
                    ->label('Contato')
                    ->formatStateUsing(function (string $state, Contact $record): string {
                        $type = $record->type instanceof ContactTypeEnum ? $record->type->value : (string) $record->type;
                        if (in_array($type, ['phone', 'whatsapp', 'cellphone'], true)) {
                            return \App\Services\DataMaskingService::maskPhone($state);
                        }
                        if ($type === 'email') {
                            return \App\Services\DataMaskingService::maskEmail($state);
                        }
                        return $state;
                    })
                    ->searchable(),
                IconColumn::make('is_primary')
                    ->label('Principal')
                    ->hidden()
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
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
                \App\Services\DataMaskingService::makeRevealModalAction('revealContact', 'Contato', fn (Contact $record): string => (string) $record->value),
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
