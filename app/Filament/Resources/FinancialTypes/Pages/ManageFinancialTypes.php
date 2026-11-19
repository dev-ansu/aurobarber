<?php

declare(strict_types=1);

namespace App\Filament\Resources\FinancialTypes\Pages;

use App\Filament\Resources\FinancialTypes\FinancialTypeResource;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ManageRecords;

class ManageFinancialTypes extends ManageRecords
{
    protected static string $resource = FinancialTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova Conta Contábil')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['barbershop_id'] = Filament::getTenant()?->getKey();

                    return $data;
                }),
        ];
    }
}
