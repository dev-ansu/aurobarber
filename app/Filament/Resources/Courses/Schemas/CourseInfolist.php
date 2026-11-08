<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do curso')->schema([
                    TextEntry::make('name')->label('Nome')->weight('bold'),
                    TextEntry::make('workload')->label('Carga horária')->suffix(' horas'),
                    IconEntry::make('active')->label('Ativo')->boolean(),
                    TextEntry::make('ouro_moderno_id')->label('Código Ouro Moderno')->placeholder('Não vinculado'),
                    TextEntry::make('description')->label('Descrição')->placeholder('Sem descrição')->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
                Section::make('Registro')->schema([
                    TextEntry::make('created_at')->label('Cadastrado em')->dateTime('d/m/Y H:i'),
                    TextEntry::make('updated_at')->label('Atualizado em')->dateTime('d/m/Y H:i'),
                ])->columns(2)->collapsed()->columnSpanFull(),
            ]);
    }
}
