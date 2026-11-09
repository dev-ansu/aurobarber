<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Auth\EditOwnProfile;
use App\Filament\Pages\Auth\ForcePasswordChange;
use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\BarbershopResource;
use App\Filament\Saas\Pages\SaasDashboard;
use App\Http\Middleware\EnsurePasswordIsChanged;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SaasPanelProvider extends PanelProvider
{
    /**
     * O Painel SaaS Master só é registrado se o servidor estiver explicitamente em modo SaaS.
     */
    public function register(): void
    {
        if (! (bool) env('SAAS_ENABLED', true)) {
            return;
        }

        parent::register();
    }

    public function panel(Panel $panel): Panel
    {
        $saasDomain = env('SAAS_PANEL_DOMAIN');

        $panel = $panel
            ->id('saas')
            ->path('saas');

        if (!empty($saasDomain) && $saasDomain !== 'master.aurosoft.com.br') {
            $panel = $panel->domain($saasDomain);
        }

        return $panel
            ->login(Login::class)
            ->passwordReset()
            ->profile(EditOwnProfile::class, isSimple: false)
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Meu Perfil & Segurança')
                    ->icon('heroicon-o-user-circle')
                    ->url(fn (): string => EditOwnProfile::getUrl())
                    ->visible(fn (): bool => auth()->check()),
            ])
            ->brandName('AuroBarber SaaS Master')
            ->favicon(fn () => asset('favicon.ico'))
            ->sidebarCollapsibleOnDesktop()
            ->darkMode(true)
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->pages([
                SaasDashboard::class,
                ForcePasswordChange::class,
            ])
            ->resources([
                BarbershopResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Saas/Resources'), for: 'App\\Filament\\Saas\\Resources')
            ->discoverPages(in: app_path('Filament/Saas/Pages'), for: 'App\\Filament\\Saas\\Pages')
            ->discoverWidgets(in: app_path('Filament/Saas/Widgets'), for: 'App\\Filament\\Saas\\Widgets')
            ->middleware([
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
                EnsurePasswordIsChanged::class,
            ]);
    }
}
