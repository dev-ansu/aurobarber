<?php

namespace App\Filament\Resources\Installments\Pages;

use App\Filament\Resources\Installments\InstallmentsResource;
use App\Filament\Resources\Installments\Tables\InstallmentsTable;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewInstallments extends ViewRecord
{
    protected static string $resource = InstallmentsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            InstallmentsTable::makePayAction()
                ->after(fn () => $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]))),
            InstallmentsTable::makeReceiptAction(),
            InstallmentsTable::makeAttachmentAction(),
            InstallmentsTable::makeWhatsappAction(),
            ActionGroup::make([
                InstallmentsTable::makeRefundAction()
                    ->after(fn () => $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]))),
                InstallmentsTable::makeCancelAction()
                    ->after(fn () => $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record]))),
                EditAction::make(),
            ])
                ->label('Mais Ações')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray'),
        ];
    }
}
