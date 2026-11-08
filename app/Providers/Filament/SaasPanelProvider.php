<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Auth\EditOwnProfile;
use App\Filament\Pages\Auth\Login;
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
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SaasPanelProvider extends PanelProvider
{
    /**
     * O Painel SaaS Master só é registrado se o servidor estiver explicitamente em modo SaaS.
     * Em clientes On-Premise, SAAS_ENABLED é false (padrão), portanto o painel sequer existe.
     */
    public function register(): void
    {
        if (! (bool) env('SAAS_ENABLED', false)) {
            return;
        }

        parent::register();
    }

    public function panel(Panel $panel): Panel
    {
        $saasDomain = (string) env('SAAS_PANEL_DOMAIN', 'master.aurosoft.com.br');

        return $panel
            ->id('saas')
            ->domain($saasDomain)
            ->path('')
            ->homeUrl('/')
            ->routes(function () {
                \Illuminate\Support\Facades\Route::get('/saas-dashboard', fn () => redirect('/'));
            })
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
            ->brandName('Aurosoft SaaS Master')
            ->favicon(fn () => asset('favicon.ico'))
            ->sidebarFullyCollapsibleOnDesktop()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->pages([
                SaasDashboard::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->discoverResources(in: app_path('Filament/Saas/Resources'), for: 'App\\Filament\\Saas\\Resources')
            ->discoverPages(in: app_path('Filament/Saas/Pages'), for: 'App\\Filament\\Saas\\Pages')
            ->discoverWidgets(in: app_path('Filament/Saas/Widgets'), for: 'App\\Filament\\Saas\\Widgets')
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
                EnsurePasswordIsChanged::class,
            ]);
    }
}
