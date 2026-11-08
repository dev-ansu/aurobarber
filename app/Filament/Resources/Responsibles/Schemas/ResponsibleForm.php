<?php

namespace App\Filament\Resources\Responsibles\Schemas;

use App\Actions\PersonSelect;
use App\Enums\RelationshipEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ResponsibleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Vínculo de responsabilidade')->schema([
                    PersonSelect::make('person_id')->label('Pessoa')->searchable()
                        ->hidden(fn (string $operation): bool => $operation === 'edit')->required(),
                    TextEntry::make('person_name')->label('Pessoa vinculada')
                        ->formatStateUsing(fn ($record) => $record?->person?->name)
                        ->visible(fn (string $operation): bool => $operation === 'edit'),
                    Select::make('relationship')->label('Parentesco')->options(RelationshipEnum::class)->native(false)->required(),
                    Toggle::make('financial_responsible')->label('Responsável financeiro')->required(),
                ])->columns(2)->columnSpanFull(),
            ]);
    }
}
