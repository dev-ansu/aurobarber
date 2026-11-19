<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\SaasPaymentReceipts\Pages;

use App\Filament\Saas\Resources\SaasPaymentReceipts\SaasPaymentReceiptResource;
use Filament\Resources\Pages\ManageRecords;

class ManageSaasPaymentReceipts extends ManageRecords
{
    protected static string $resource = SaasPaymentReceiptResource::class;
}
