<?php

namespace App\Filament\Resources\People\RelationManagers;

use App\Enums\DocumentTypeEnum;
use App\Models\Document;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $recordTitleAttribute = 'value';

    protected static ?string $title = 'Documentos';

    protected static ?string $label = 'documento';

    protected static ?string $pluralLabel = 'Documentos';

    protected static ?string $modelLabel = 'documento';

    protected static ?string $pluralModelLabel = 'documentos';

    protected static ?string $recordTitle = 'value';

    protected static ?string $slug = 'documents';

    protected static ?string $navigationLabel = 'Documentos';

    protected static BackedEnum|string|null $icon = 'heroicon-o-document-text';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Tipo')
                    ->live()
                    ->options(DocumentTypeEnum::class)
                    ->required(),
                TextInput::make('value')
                    ->label('Documento')
                    ->rules(function (Get $get) {
                        $type = $get('type');

                        if ($type instanceof DocumentTypeEnum) {
                            return $type->getValidationRules();
                        }

                        return DocumentTypeEnum::tryFrom($type)?->getValidationRules();
                    })
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
                    ->badge()
                    ->description(fn (Document $record) => $record->is_primary ? 'Principal' : null)
                    ->searchable(),
                TextColumn::make('value')
                    ->label('Documento')
                    ->formatStateUsing(function (string $state, Document $record): string {
                        $type = $record->type instanceof DocumentTypeEnum ? $record->type->value : (string) $record->type;
                        return \App\Services\DataMaskingService::maskDocument($state, $type);
                    })
                    ->searchable(),
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
                \App\Services\DataMaskingService::makeRevealModalAction('revealDocument', 'Documento', fn (Document $record): string => (string) $record->value),
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
