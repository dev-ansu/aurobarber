<?php

declare(strict_types=1);

namespace App\Filament\Resources\SatisfactionSurveys\Pages;

use App\Filament\Resources\SatisfactionSurveys\SatisfactionSurveyResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSatisfactionSurveys extends ListRecords
{
    protected static string $resource = SatisfactionSurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_report')
                ->label('Relatório & Gráficos de Satisfação')
                ->icon('heroicon-o-chart-pie')
                ->color('info')
                ->url(route('filament.admin.pages.satisfaction-survey-report')),
            CreateAction::make()->label('Nova pesquisa'),
        ];
    }
}
