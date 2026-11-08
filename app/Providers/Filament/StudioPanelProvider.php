<?php

namespace App\Providers\Filament;

use App\Filament\Studio\Pages\LmsQuestionBank;
use App\Filament\Studio\Pages\LmsStudio;
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

class StudioPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('studio')
            ->path('estudio')
            ->brandName('Aurosoft Estúdio LMS')
            ->brandLogo(fn () => view('filament.components.studio-brand-logo'))
            ->brandLogoHeight('2.6rem')
            ->favicon(fn () => asset('favicon.ico'))
            ->topNavigation()
            ->maxContentWidth('full')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->userMenuItems([
                'admin' => MenuItem::make()
                    ->label('Painel Administrativo')
                    ->icon('heroicon-o-arrow-left-on-rectangle')
                    ->url(function (): string {
                        $schoolId = session('filament_tenant_id')
                            ?? \App\Models\School::getActiveSchool()?->id
                            ?? \App\Models\School::getHeadquarters()?->id
                            ?? 1;

                        return url('/' . $schoolId);
                    }),
                'student_portal' => MenuItem::make()
                    ->label('Portal do Aluno (Visualizar)')
                    ->icon('heroicon-o-academic-cap')
                    ->url(fn (): string => route('student.portal')),
            ])
            ->discoverPages(in: app_path('Filament/Studio/Pages'), for: 'App\\Filament\\Studio\\Pages')
            ->pages([
                LmsStudio::class,
                LmsQuestionBank::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('@include("components.pwa-head")'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('@include("components.desktop-footer") @include("components.global-error-notifier")'),
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
