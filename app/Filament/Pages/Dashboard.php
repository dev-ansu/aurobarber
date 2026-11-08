<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\AttendanceRateChart;
use App\Filament\Widgets\BirthdaysTableWidget;
use App\Filament\Widgets\CommercialOverview;
use App\Filament\Widgets\ContractsByStatusChart;
use App\Filament\Widgets\DashboardStatsOverview;
use App\Filament\Widgets\DropoutRiskRadarWidget;
use App\Filament\Widgets\OverdueReceivablesChart;
use App\Filament\Widgets\QuickActionsWidget;
use App\Filament\Widgets\ReceiptsByPaymentMethodChart;
use App\Filament\Widgets\SchoolGoalsProgressWidget;
use App\Filament\Widgets\SchoolOnboardingWidget;
use App\Filament\Widgets\ShowcaseAnalyticsWidget;
use App\Models\PortalTrafficEvent;
use App\Models\School;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Painel de Controle';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?int $navigationSort = -2;

    public string $activeTab = 'geral';

    /**
     * Preserva a aba ativa na URL (ex: ?tab=captacao)
     *
     * @var array<string, mixed>
     */
    protected $queryString = [
        'activeTab' => ['except' => 'geral', 'as' => 'tab'],
    ];

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getHeader(): ?View
    {
        $user = Auth::user();
        $school = School::getActiveSchool() ?? School::getHeadquarters() ?? School::first();

        $todayVisitors = 0;
        if ($school) {
            try {
                $todayVisitors = PortalTrafficEvent::school((int) $school->id)->today()->distinct('visitor_hash')->count('visitor_hash');
            } catch (\Throwable) {
            }
        }

        $visibleWidgets = collect($this->getWidgets())
            ->filter(fn ($widget): bool => $this->normalizeWidgetClass($widget)::canView())
            ->map(fn ($widget) => $this->normalizeWidgetClass($widget))
            ->values();

        $allowedTabs = [
            'geral' => $visibleWidgets->contains(fn ($w) => str_contains($this->getWidgetCategories($w), 'geral')),
            'captacao' => $visibleWidgets->contains(fn ($w) => str_contains($this->getWidgetCategories($w), 'captacao')),
            'financeiro' => $visibleWidgets->contains(fn ($w) => str_contains($this->getWidgetCategories($w), 'financeiro')),
            'pedagogico' => $visibleWidgets->contains(fn ($w) => str_contains($this->getWidgetCategories($w), 'pedagogico')),
            'todos' => $visibleWidgets->isNotEmpty(),
        ];

        return view('filament.pages.dashboard-header', [
            'user' => $user,
            'school' => $school,
            'activeTab' => $this->activeTab,
            'todayVisitors' => $todayVisitors,
            'allowedTabs' => $allowedTabs,
        ]);
    }

    /**
     * Retorna todos os widgets da página, que serão filtrados instantaneamente pelas abas executivas via Alpine.
     */
    public function getWidgets(): array
    {
        return [
            SchoolOnboardingWidget::class,
            QuickActionsWidget::class,
            DashboardStatsOverview::class,
            ShowcaseAnalyticsWidget::class,
            CommercialOverview::class,
            SchoolGoalsProgressWidget::class,
            ReceiptsByPaymentMethodChart::class,
            OverdueReceivablesChart::class,
            AttendanceRateChart::class,
            ContractsByStatusChart::class,
            DropoutRiskRadarWidget::class,
            BirthdaysTableWidget::class,
        ];
    }

    /**
     * Adiciona o atributo data-dashboard-category em cada componente de widget para filtro instantâneo via Alpine
     * e garante columnSpan('full') para que os cards ocupem 100% da largura sem esmagamento.
     */
    public function getWidgetsSchemaComponents(array $widgets, array $data = []): array
    {
        return collect($widgets)
            ->values()
            ->filter(fn ($widget): bool => $this->normalizeWidgetClass($widget)::canView())
            ->map(function ($widget, int $widgetKey) use ($data): \Filament\Schemas\Components\Livewire {
                $widgetClass = $this->normalizeWidgetClass($widget);
                $categories = $this->getWidgetCategories($widgetClass);
                $columnSpan = $this->getWidgetColumnSpan($widgetClass);

                return \Filament\Schemas\Components\Livewire::make(
                    $widgetClass,
                    fn (): array => [
                        ...$this->getWidgetData(),
                        ...$data,
                        ...$widgetClass::getDefaultProperties(),
                    ],
                )
                ->key("{$widgetClass}-{$widgetKey}")
                ->columnSpan($columnSpan)
                ->extraAttributes([
                    'data-dashboard-category' => $categories,
                ]);
            })
            ->all();
    }

    protected function getWidgetColumnSpan(string $widgetClass): int|string|array
    {
        return match ($widgetClass) {
            AttendanceRateChart::class,
            ContractsByStatusChart::class,
            ReceiptsByPaymentMethodChart::class,
            OverdueReceivablesChart::class => [
                'default' => 'full',
                'md' => 1,
            ],
            default => 'full',
        };
    }

    protected function getWidgetCategories(string $widgetClass): string
    {
        return match ($widgetClass) {
            ShowcaseAnalyticsWidget::class => 'geral captacao',
            CommercialOverview::class => 'geral captacao',
            QuickActionsWidget::class => 'geral',
            SchoolGoalsProgressWidget::class => 'geral',
            SchoolOnboardingWidget::class => 'geral',
            DashboardStatsOverview::class => 'geral financeiro',
            ReceiptsByPaymentMethodChart::class,
            OverdueReceivablesChart::class => 'financeiro',
            AttendanceRateChart::class,
            ContractsByStatusChart::class,
            DropoutRiskRadarWidget::class,
            BirthdaysTableWidget::class => 'pedagogico',
            default => 'geral',
        };
    }
}
