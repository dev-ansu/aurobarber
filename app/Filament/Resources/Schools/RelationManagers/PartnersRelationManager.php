<?php

namespace App\Filament\Resources\Schools\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PartnersRelationManager extends RelationManager
{
    protected static string $relationship = 'partners';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome completo / Razão social')
                    ->required()
                    ->maxLength(255),
                TextInput::make('cpf')
                    ->label('CPF')
                    ->mask('999.999.999-99')
                    ->maxLength(14),
                TextInput::make('role')
                    ->label('Qualificação societária')
                    ->placeholder('Ex.: Sócio-administrador')
                    ->maxLength(255),
                TextInput::make('ownership_percentage')
                    ->label('Participação societária (%)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step('0.01'),
                Toggle::make('legal_representative')
                    ->label('Representante legal')
                    ->default(false)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Sócio')
                    ->searchable(),
                TextColumn::make('cpf')
                    ->label('CPF'),
                TextColumn::make('role')
                    ->label('Qualificação'),
                TextColumn::make('ownership_percentage')
                    ->label('Participação')
                    ->suffix('%'),
                TextColumn::make('legal_representative')
                    ->label('Representante legal')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Sim' : 'Não'),
            ])
            ->filters([])
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
