<?php

namespace App\Filament\Resources\CashTransactions\Pages;

use App\Filament\Resources\CashTransactions\CashTransactionsResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCashTransactions extends EditRecord
{
    protected static string $resource = CashTransactionsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', [
            'record' => $this->record,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
