<?php

namespace App\Providers\Filament;

use App\Filament\Kiosk\Pages\StudentAttendanceKiosk;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class KioskPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('kiosk')
            ->path('presenca')
            ->topbar(false)
            ->brandName(false)
            ->favicon(fn () => asset('favicon.ico'))
            ->navigation(false)
            ->topNavigation(false)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->pages([
                StudentAttendanceKiosk::class,
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@include("components.pwa-head")'),
            )
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->middleware([
                \App\Http\Middleware\ResolveTenantSchema::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ]);
    }
}
