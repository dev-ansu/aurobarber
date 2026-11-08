<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\DeveloperPanelProvider;
use App\Providers\Filament\KioskPanelProvider;
use App\Providers\Filament\SaasPanelProvider;
use App\Providers\Filament\StudioPanelProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    AdminPanelProvider::class,
    DeveloperPanelProvider::class,
    KioskPanelProvider::class,
    SaasPanelProvider::class,
    StudioPanelProvider::class,
];
