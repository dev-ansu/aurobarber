<?php

declare(strict_types=1);

namespace App\Filament\Resources\SatisfactionSurveys\Pages;

use App\Filament\Resources\SatisfactionSurveys\SatisfactionSurveyResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSatisfactionSurvey extends ViewRecord
{
    protected static string $resource = SatisfactionSurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_report')
                ->label('Ver Resultados e Gráficos')
                ->icon('heroicon-o-chart-bar')
                ->color('success')
                ->url(fn (): string => route('filament.admin.pages.satisfaction-survey-report', ['surveyId' => $this->record->id])),
            EditAction::make(),
        ];
    }
}
