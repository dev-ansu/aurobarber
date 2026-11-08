<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\SupportTickets\Pages;

use App\Filament\Saas\Resources\SupportTickets\SaasTicketResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSaasTickets extends ManageRecords
{
    protected static string $resource = SaasTicketResource::class;
}
