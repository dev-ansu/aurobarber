<?php

declare(strict_types=1);

namespace App\Filament\Resources\TypingExercises\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TypingExercisesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Título do Exercício')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('level')
                    ->label('Nível')
                    ->badge()
                    ->color(fn (int $state): string => match ($state) {
                        1 => 'gray',
                        2 => 'info',
                        3 => 'primary',
                        4 => 'warning',
                        5 => 'purple',
                        6, 7 => 'cyan',
                        8 => 'success',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (int $state): string => "Nível {$state}")
                    ->sortable(),

                TextColumn::make('category')
                    ->label('Categoria')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'home_row' => 'Teclas Guias',
                        'all_letters', 'letters' => 'Todas as Letras (A-Z)',
                        'words' => 'Palavras Completas',
                        'accents' => 'Acentuação e Ç',
                        'symbols' => 'Pontuação & Símbolos',
                        'short_text' => 'Texto Curto',
                        'medium_text' => 'Texto Médio',
                        'long_text', 'texts' => 'Texto Longo',
                        default => $state,
                    }),

                TextColumn::make('min_wpm')
                    ->label('Min PPM')
                    ->suffix(' WPM')
                    ->sortable(),

                TextColumn::make('min_accuracy')
                    ->label('Min Precisão')
                    ->suffix('%')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),

                TextColumn::make('records_count')
                    ->counts('records')
                    ->label('Treinos Realizados')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('level')
                    ->label('Nível de Dificuldade')
                    ->options([
                        1 => 'Nível 1: Teclas Guia e Dedos',
                        2 => 'Nível 2: Alfabeto Completo (A-Z)',
                        3 => 'Nível 3: Palavras Completas',
                        4 => 'Nível 4: Acentuação e Cedilha',
                        5 => 'Nível 5: Pontuação e Símbolos',
                        6 => 'Nível 6: Textos Curtos',
                        7 => 'Nível 7: Textos Médios',
                        8 => 'Nível 8: Textos Longos',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Situação')
                    ->trueLabel('Ativos')
                    ->falseLabel('Inativos')
                    ->placeholder('Todos'),
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
