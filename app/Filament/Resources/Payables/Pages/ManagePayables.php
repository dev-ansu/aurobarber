<?php

declare(strict_types=1);

namespace App\Filament\Resources\Payables\Pages;

use App\Filament\Resources\Payables\PayableResource;
use App\Models\Barbershop;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Auth\Access\AuthorizationException;

class ManagePayables extends ManageRecords
{
    protected static string $resource = PayableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nova conta a pagar')
                ->mutateDataUsing(function (array $data): array {
                    $tenant = filament()->getTenant();
                    $user = auth()->user();

                    if (! $tenant instanceof Barbershop || ! $user?->canAccessTenant($tenant)) {
                        throw new AuthorizationException('Selecione uma barbearia autorizada antes de registrar a conta.');
                    }

                    $data['barbershop_id'] = $tenant->getKey();
                    $data['created_by_id'] = $user->getKey();

                    return $data;
                }),
        ];
    }
}
