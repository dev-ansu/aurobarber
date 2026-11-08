<?php

declare(strict_types=1);

namespace App\Filament\Resources\FinancialAccounts\Pages;

use App\Filament\Resources\FinancialAccounts\FinancialAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFinancialAccounts extends ManageRecords
{
    protected static string $resource = FinancialAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Cadastrar Conta / Cofre'),
        ];
    }
}

