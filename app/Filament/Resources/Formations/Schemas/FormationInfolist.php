<?php

namespace App\Filament\Resources\Formations\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FormationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da formação')->schema([
                    TextEntry::make('name')->label('Nome')->weight('bold'),
                    IconEntry::make('active')->label('Ativa')->boolean(),
                    TextEntry::make('description')->label('Descrição')->placeholder('Sem descrição')->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
                Section::make('Registro')->schema([
                    TextEntry::make('created_at')->label('Cadastrada em')->dateTime('d/m/Y H:i'),
                    TextEntry::make('updated_at')->label('Atualizada em')->dateTime('d/m/Y H:i'),
                ])->columns(2)->collapsed()->columnSpanFull(),
            ]);
    }
}
