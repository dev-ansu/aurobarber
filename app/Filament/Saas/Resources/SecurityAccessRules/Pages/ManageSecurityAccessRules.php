<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\SecurityAccessRules\Pages;

use App\Filament\Saas\Resources\SecurityAccessRules\SecurityAccessRuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageSecurityAccessRules extends ManageRecords
{
    protected static string $resource = SecurityAccessRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Adicionar Regra de Acesso')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
