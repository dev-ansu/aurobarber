<?php

namespace App\Providers\Filament;

use App\Filament\Auth\EditOwnProfile;
use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\UserGuide;
use App\Filament\Studio\Pages\LmsQuestionBank;
use App\Filament\Studio\Pages\LmsStudio;
use App\Filament\Widgets\AttendanceRateChart;
use App\Filament\Widgets\BirthdaysTableWidget;
use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\DropoutRiskRadarWidget;
use App\Filament\Widgets\OverdueReceivablesChart;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\SchoolGoalsProgressWidget;
use App\Filament\Widgets\SchoolOnboardingWidget;
use App\Filament\Widgets\ShowcaseAnalyticsWidget;
use App\Http\Middleware\ApplyTenantUrlDefaults;
use App\Http\Middleware\CheckLicenseStatus;
use App\Http\Middleware\EnsurePasswordIsChanged;
use App\Models\School;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use App\Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->navigationGroups([
                NavigationGroup::make('Escola')->collapsed(),
                NavigationGroup::make('Administrativo')->collapsible(),
                NavigationGroup::make('Pedagógico')->collapsed(),
                NavigationGroup::make('Comercial')->collapsed(),
                NavigationGroup::make('Financeiro')->collapsed(),
                NavigationGroup::make('Relatórios')->collapsed(),
                NavigationGroup::make('Integrações')->collapsed(),
                NavigationGroup::make('Configurações Financeiras')->collapsed(),
                NavigationGroup::make('Ajuda e suporte')->collapsed(),
            ])
            ->default()
            ->sidebarFullyCollapsibleOnDesktop()
            ->id('admin')
            ->path('')
            ->tenant(School::class)
            ->resolveTenantUsing(function (string $key): School {
                if (ctype_digit($key) || is_numeric($key)) {
                    $school = School::query()->withoutGlobalScopes()->find((int) $key);
                    if ($school instanceof School) {
                        return $school;
                    }

                    $fallback = School::getHeadquarters() ?? School::query()->withoutGlobalScopes()->orderBy('id')->first();
                    if ($fallback instanceof School) {
                        return $fallback;
                    }
                }

                throw (new ModelNotFoundException)->setModel(School::class, [$key]);
            })
            ->tenantMiddleware([
                ApplyTenantUrlDefaults::class,
            ], isPersistent: true)
            ->brandName('Aurosoft')
            ->brandLogo(fn () => view('filament.components.brand-logo'))
            ->brandLogoHeight(fn () => (request()->routeIs('filament.*.auth.*') || request()->is('*login*') || request()->is('*password-reset*')) ? '3rem' : '2.6rem')
            ->favicon(fn () => asset('favicon.ico'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login(Login::class)
            ->passwordReset()
            ->profile(EditOwnProfile::class, isSimple: false)
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label('Perfil')
                    ->icon('heroicon-o-user')
                    ->url(fn (): string => EditOwnProfile::getUrl())
                    ->visible(fn (): bool => auth()->check()),
                'student_portal' => MenuItem::make()
                    ->label('Portal do Aluno (Educador)')
                    ->icon('heroicon-o-academic-cap')
                    ->url(fn (): string => route('student.portal'))
                    ->visible(fn (): bool => auth()->check() && (auth()->user()?->can('View:StudentPortal') || auth()->user()?->hasRole('super_admin'))),
                'help' => MenuItem::make()
                    ->label('Ajuda e tutoriais')
                    ->icon('heroicon-o-book-open')
                    ->url(fn (): string => UserGuide::getUrl()),
            ])
            ->navigationItems([
                NavigationItem::make('Financeiro')
                    ->group('Relatórios')
                    ->icon('heroicon-o-banknotes')
                    ->sort(1),
                NavigationItem::make('Pedagógico')
                    ->group('Relatórios')
                    ->icon('heroicon-o-academic-cap')
                    ->sort(2),
                NavigationItem::make('Comercial')
                    ->group('Relatórios')
                    ->icon('heroicon-o-presentation-chart-line')
                    ->sort(3),
                NavigationItem::make('Estúdio LMS')
                    ->url(fn (): string => url('/estudio'))
                    ->icon('heroicon-o-pencil-square')
                    ->group('Pedagógico')
                    ->sort(15)
                    ->visible(fn (): bool => LmsStudio::canAccess() || LmsQuestionBank::canAccess()),
                NavigationItem::make('Portal do Aluno (Educador)')
                    ->url(fn (): string => route('student.portal'))
                    ->icon('heroicon-o-academic-cap')
                    ->group('Pedagógico')
                    ->sort(99)
                    ->visible(fn (): bool => auth()->check() && (auth()->user()?->can('View:StudentPortal') || auth()->user()?->hasRole('super_admin'))),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('@include("components.pwa-head")'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => Blade::render("@livewire('filament.schedule-board-modal', ['mode' => 'topbar'])"),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn (): string => Blade::render("@livewire('filament.schedule-board-modal', ['mode' => 'sidebar'])"),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): string => Blade::render('@include("filament.auth.login-shortcuts")'),
            )
            ->renderHook(
                PanelsRenderHook::PAGE_START,
                fn (): string => Blade::render("@livewire('system-update-alert')"),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('@include("components.desktop-footer") @include("components.global-error-notifier") @include("components.pwa-install-button")'),
            )
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                SchoolOnboardingWidget::class,
                QuickActionsWidget::class,
                DashboardStatsOverview::class,
                SchoolGoalsProgressWidget::class,
                ShowcaseAnalyticsWidget::class,
                AttendanceRateChart::class,
                OverdueReceivablesChart::class,
                DropoutRiskRadarWidget::class,
                BirthdaysTableWidget::class,
            ])
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
                CheckLicenseStatus::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                ApplyTenantUrlDefaults::class,
                EnsurePasswordIsChanged::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('Administrativo')
                    ->navigationLabel('Papéis/Funções')
                    ->navigationIcon('heroicon-o-shield-check')
                    ->navigationSort(50),
            ]);
    }
}
