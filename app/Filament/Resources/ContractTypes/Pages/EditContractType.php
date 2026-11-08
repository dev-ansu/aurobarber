<?php

namespace App\Filament\Resources\ContractTypes\Pages;

use App\Filament\Resources\ContractTypes\ContractTypeResource;
use App\Models\ContractType;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditContractType extends EditRecord
{
    protected static string $resource = ContractTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, ContractType $record) {
                    if ($record->contracts()->exists()) {
                        Notification::make()
                            ->title('Ação Bloqueada')
                            ->body('Este tipo de contrato possui matrículas vinculadas e não pode ser excluído. Em vez disso, inative-o.')
                            ->danger()
                            ->send();

                        // Interrompe o processo de exclusão
                        $action->halt();
                    }
                }),
        ];
    }
}
