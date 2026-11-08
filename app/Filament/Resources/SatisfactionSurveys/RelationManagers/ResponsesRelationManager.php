<?php

declare(strict_types=1);

namespace App\Filament\Resources\SatisfactionSurveys\RelationManagers;

use App\Models\SatisfactionSurveyAnswer;
use App\Models\SatisfactionSurveyResponse;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ResponsesRelationManager extends RelationManager
{
    protected static string $relationship = 'responses';

    protected static ?string $title = 'Respostas dos alunos';

    protected static ?string $modelLabel = 'resposta';

    protected static ?string $pluralModelLabel = 'respostas dos alunos';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereHas('contract'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('student.name')
                    ->label('Aluno')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('contract.contract_number')
                    ->label('Contrato')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('created_at')
                    ->label('Respondida em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('view_answers')
                    ->label('Ver respostas')
                    ->icon('heroicon-o-eye')
                    ->infolist(fn (SatisfactionSurveyResponse $record): array => [
                        RepeatableEntry::make('answers')
                            ->label('Respostas registradas')
                            ->schema([
                                TextEntry::make('question.question')
                                    ->label('Pergunta')
                                    ->weight('bold'),
                                TextEntry::make('answer_display')
                                    ->label('Resposta do aluno')
                                    ->state(function (SatisfactionSurveyAnswer $record): string {
                                        if ($record->rating_value !== null) {
                                            return $record->question?->type === 'rating_5'
                                                ? str_repeat('⭐', $record->rating_value)." ({$record->rating_value}/5)"
                                                : "Nota: {$record->rating_value}/10";
                                        }

                                        return $record->text_value ?: '—';
                                    }),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fechar'),
            ]);
    }
}
