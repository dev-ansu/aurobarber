<?php

namespace App\Providers\Filament;

use App\Filament\Auth\EditOwnProfile;
use App\Filament\Pages\Tenancy\EditBarbershopProfile;
use App\Filament\Pages\Tenancy\RegisterBarbershop;
use App\Models\Barbershop;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->passwordReset()
            ->profile(EditOwnProfile::class, isSimple: false)
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable(),
                EmailAuthentication::make(),
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Meu Perfil & Segurança')
                    ->icon('heroicon-o-user-circle')
                    ->url(fn (): string => EditOwnProfile::getUrl(filament()->getTenant() ? ['tenant' => filament()->getTenant()->slug] : []))
                    ->visible(fn (): bool => auth()->check()),
            ])
            ->brandName(fn (): string => filament()->getTenant()?->name ?? 'AuroBarber')
            ->brandLogo(fn (): ?string => filament()->getTenant()?->logo_url ? asset('storage/' . filament()->getTenant()->logo_url) : null)
            ->brandLogoHeight('2.25rem')
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Slate,
            ])
            ->darkMode(true)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->sidebarCollapsibleOnDesktop()
            ->tenant(Barbershop::class, slugAttribute: 'slug')
            ->tenantProfile(EditBarbershopProfile::class)
            ->tenantRegistration(RegisterBarbershop::class)
            ->tenantMenuItems([
                MenuItem::make()
                    ->label('Ver Página Pública ↗')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (): string => filament()->getTenant() ? url(filament()->getTenant()->slug) : url('/'))
                    ->openUrlInNewTab(),
                MenuItem::make()
                    ->label('Voltar ao SaaS Master ⚡')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->url('/saas')
                    ->visible(fn (): bool => auth()->user()?->role === 'master_admin'),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new \Illuminate\Support\HtmlString(
                    '<link rel="manifest" href="' . url('/manifest.json' . (filament()->getTenant() ? '?tenant=' . filament()->getTenant()->slug : '')) . '">' .
                    '<meta name="theme-color" content="' . (filament()->getTenant()?->brand_color ?: '#020617') . '">' .
                    '<meta name="apple-mobile-web-app-capable" content="yes">' .
                    '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">' .
                    '<meta name="apple-mobile-web-app-title" content="' . e(filament()->getTenant()?->name ?? 'AuroBarber') . '">' .
                    '<link rel="apple-touch-icon" href="' . (filament()->getTenant()?->logo_url ? asset('storage/' . filament()->getTenant()->logo_url) : asset('pwa-icon.svg')) . '">' .
                    '<script>
                        window.deferredPwaPrompt = null;
                        window.addEventListener("beforeinstallprompt", function(e) {
                            e.preventDefault();
                            window.deferredPwaPrompt = e;
                            window.dispatchEvent(new CustomEvent("pwa-ready"));
                        });
                        if ("serviceWorker" in navigator) {
                            window.addEventListener("load", function() {
                                navigator.serviceWorker.register("/sw.js").catch(function() {});
                            });
                        }
                    </script>'
                )
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn () => view('filament.components.public-page-button')
            )
            ->navigationGroups([
                NavigationGroup::make('Atendimento')->collapsible(),
                NavigationGroup::make('Equipe')->collapsible(),
                NavigationGroup::make('Serviços')->collapsible(),
                NavigationGroup::make('Financeiro')->collapsed(),
                NavigationGroup::make('SaaS Master')->collapsed(),
            ])
            ->navigationItems([
                NavigationItem::make('Configurações da Barbearia')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->sort(99)
                    ->url(fn (): string => filament()->getTenant() ? EditBarbershopProfile::getUrl() : '#')
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.tenant.profile'))
                    ->visible(fn (): bool => filament()->getTenant() !== null && (auth()->user()?->isOwner() || (method_exists(auth()->user(), 'hasRole') && auth()->user()?->hasRole('super_admin')))),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make()
                    ->navigationGroup('Equipe')
                    ->navigationLabel('Cargos & Permissões')
                    ->navigationIcon('heroicon-o-shield-check')
                    ->navigationSort(30)
                    ->modelLabel('Cargo')
                    ->pluralModelLabel('Cargos & Permissões'),
            ])
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
                \App\Http\Middleware\EnsurePasswordIsChanged::class,
            ])
            ->tenantMiddleware([
                \App\Http\Middleware\ResolveTenantSchema::class,
                \App\Http\Middleware\EnsureBarbershopLicenseIsValid::class,
            ], isPersistent: true);
    }
}
