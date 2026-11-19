<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\Payments\Pages;

use App\Filament\Saas\Resources\Payments\SaasPaymentSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListSaasPaymentSubmissions extends ListRecords
{
    protected static string $resource = SaasPaymentSubmissionResource::class;
}
