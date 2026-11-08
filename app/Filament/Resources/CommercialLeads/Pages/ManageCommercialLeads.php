<?php

namespace App\Filament\Resources\CommercialLeads\Pages;

use App\Filament\Pages\CommercialLeadsKanban;
use App\Filament\Resources\CommercialLeads\CommercialLeadResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCommercialLeads extends ManageRecords
{
    protected static string $resource = CommercialLeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openKanban')
                ->label('Ver Funil (Kanban)')
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->visible(fn (): bool => CommercialLeadsKanban::canAccess())
                ->url(fn (): string => CommercialLeadsKanban::getUrl()),
            CreateAction::make()->label('Novo interessado'),
        ];
    }
}
