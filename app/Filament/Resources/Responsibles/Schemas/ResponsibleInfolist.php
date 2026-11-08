<?php

namespace App\Filament\Resources\Responsibles\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ResponsibleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Responsável')->schema([
                    TextEntry::make('person.name')->label('Nome')->weight('bold'),
                    TextEntry::make('relationship')->label('Parentesco')->badge(),
                    TextEntry::make('financial_responsible')->label('Responsável financeiro')
                        ->formatStateUsing(fn ($state): string => $state ? 'Sim' : 'Não')
                        ->badge()
                        ->color(fn ($state): string => $state ? 'success' : 'gray'),
                ])->columns(2)->columnSpanFull(),
                Section::make('Registro')->schema([
                    TextEntry::make('created_at')->label('Cadastrado em')->dateTime('d/m/Y H:i'),
                    TextEntry::make('updated_at')->label('Atualizado em')->dateTime('d/m/Y H:i'),
                ])->columns(2)->collapsed()->columnSpanFull(),
            ]);
    }
}
