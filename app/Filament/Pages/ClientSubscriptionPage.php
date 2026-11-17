<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Barbershop;
use App\Services\SaasSupportContact;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class ClientSubscriptionPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Minha Assinatura';

    protected static ?string $title = 'Plano & Licenciamento da Barbearia';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.client-subscription-page';

    public static function canAccess(): bool
    {
        return ! auth()->user()?->isRestrictedBarber();
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::FiveExtraLarge;
    }

    public function getBarbershopProperty(): ?Barbershop
    {
        $tenant = filament()->getTenant();

        return $tenant instanceof Barbershop ? $tenant : null;
    }

    public function getSupportPhoneProperty(): string
    {
        return app(SaasSupportContact::class)->phone() ?: '5585999728450';
    }
}
