<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do curso')
                    ->description('Identificação, carga horária e situação do curso.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome do Curso')
                            ->required()
                            ->maxLength(255)
                            ->disabled(fn ($record) => (bool) $record?->ouro_moderno_id)
                            ->helperText(fn ($record) => $record?->ouro_moderno_id ? 'Título oficial sincronizado da Ouro Moderno.' : null),

                        TextInput::make('workload')
                            ->label('Carga Horária (horas)')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->disabled(fn ($record) => (bool) $record?->ouro_moderno_id)
                            ->helperText(fn ($record) => $record?->ouro_moderno_id ? 'Carga horária oficial da Ouro Moderno.' : null),

                        Toggle::make('active')
                            ->label('Curso ativo')
                            ->default(true)
                            ->required(),

                        Textarea::make('description')
                            ->label('Descrição / Ementa para a Vitrine')
                            ->placeholder('Ex: Neste curso você aprenderá as ferramentas essenciais de formatação, tabelas, fórmulas e relatórios práticos...')
                            ->rows(4)
                            ->maxLength(2000)
                            ->helperText('Opcional. Se preenchido, esta descrição aparecerá na Landing Page e na vitrine da escola para os futuros alunos.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
