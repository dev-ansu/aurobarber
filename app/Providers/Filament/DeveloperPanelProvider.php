<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Developer\Pages\DeveloperDashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DeveloperPanelProvider extends PanelProvider
{
    /**
     * Em ambiente SaaS Cloud (VPS), o painel /developer é desativado por padrão para máxima segurança,
     * a menos que DEVELOPER_PANEL_ENABLED=true seja definido no .env.
     * Em clientes On-Premise, SAAS_ENABLED é false (padrão), portanto o painel funciona normalmente.
     */
    public function register(): void
    {
        if ((bool) env('SAAS_ENABLED', false) && ! (bool) env('DEVELOPER_PANEL_ENABLED', false)) {
            return;
        }

        parent::register();
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('developer')
            ->path('developer')
            ->login()
            ->brandName('Aurosoft Developer Console')
            ->favicon(fn () => asset('favicon.ico'))
            ->colors([
                'primary' => Color::Indigo,
                'gray' => Color::Zinc,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->pages([
                DeveloperDashboard::class,
            ])
            ->discoverPages(in: app_path('Filament/Developer/Pages'), for: 'App\\Filament\\Developer\\Pages')
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('@include("components.pwa-head")'),
            )
            ->middleware([
                \App\Http\Middleware\ResolveTenantSchema::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

