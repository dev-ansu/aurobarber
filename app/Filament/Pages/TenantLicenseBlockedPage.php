<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Barbershop;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class TenantLicenseBlockedPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $title = 'Licença Suspensa';

    protected static ?string $slug = 'licenca-bloqueada';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.tenant-license-blocked';

    public function getMaxContentWidth(): Width | string | null
    {
        return 'full';
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public function getBarbershop(): ?Barbershop
    {
        $tenant = Filament::getTenant();
        return $tenant instanceof Barbershop ? $tenant : null;
    }
}
