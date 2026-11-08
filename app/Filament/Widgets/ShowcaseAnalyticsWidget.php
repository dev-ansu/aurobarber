<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Formation;
use App\Models\PortalTrafficEvent;
use App\Models\School;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShowcaseAnalyticsWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.showcase-analytics-widget';

    public static function canView(): bool
    {
        if (! Auth::check()) {
            return false;
        }

        // Apenas exibe se a unidade escolar tiver vitrine ativa
        $school = School::getActiveSchool() ?? School::getHeadquarters() ?? School::first();

        return $school && ($school->showcase_enabled ?? true);
    }

    protected function getViewData(): array
    {
        $school = School::getActiveSchool() ?? School::getHeadquarters() ?? School::first();
        if (! $school) {
            return [
                'hasSchool' => false,
            ];
        }

        $visitors30d = 0;
        $visitorsToday = 0;
        $whatsappClicks = 0;
        $leadsCount = 0;
        $conversionRate = 0.0;
        $topFormations = [];
        $topSources = [];

        try {
            $schoolId = (int) $school->id;

            $visitors30d = PortalTrafficEvent::school($schoolId)
                ->lastDays(30)
                ->distinct('visitor_hash')
                ->count('visitor_hash');

            $visitorsToday = PortalTrafficEvent::school($schoolId)
                ->today()
                ->distinct('visitor_hash')
                ->count('visitor_hash');

            $whatsappClicks = PortalTrafficEvent::school($schoolId)
                ->lastDays(30)
                ->where('event_type', 'cta_whatsapp')
                ->count();

            $leadsCount = PortalTrafficEvent::school($schoolId)
                ->lastDays(30)
                ->where('event_type', 'lead_submit')
                ->count();

            if ($visitors30d > 0) {
                $conversionRate = round((($whatsappClicks + $leadsCount) / $visitors30d) * 100, 1);
            }

            // Top formações mais acessadas
            $formationStats = PortalTrafficEvent::school($schoolId)
                ->lastDays(30)
                ->whereNotNull('formation_id')
                ->select('formation_id', DB::raw('count(*) as total_views'))
                ->groupBy('formation_id')
                ->orderByDesc('total_views')
                ->take(4)
                ->get();

            $formationIds = $formationStats->pluck('formation_id')->filter()->all();
            $formationsMap = ! empty($formationIds)
                ? Formation::whereIn('id', $formationIds)->pluck('name', 'id')
                : collect();

            foreach ($formationStats as $stat) {
                $name = $formationsMap[$stat->formation_id] ?? 'Formação #' . $stat->formation_id;
                $topFormations[] = [
                    'name' => $name,
                    'views' => (int) $stat->total_views,
                ];
            }

            // Top origens
            $topSources = PortalTrafficEvent::school($schoolId)
                ->lastDays(30)
                ->select('utm_source', DB::raw('count(*) as count'))
                ->groupBy('utm_source')
                ->orderByDesc('count')
                ->take(4)
                ->get()
                ->map(fn ($item) => ['source' => $item->utm_source ?: 'direto', 'count' => (int) $item->count])
                ->toArray();
        } catch (\Throwable $e) {
            Log::debug('Erro ao carregar dados do ShowcaseAnalyticsWidget: ' . $e->getMessage());
        }

        // Monta o link da vitrine pública da escola
        $saasDomain = config('app.saas_domain', env('SAAS_DOMAIN', 'aurosoft.com.br'));
        $showcaseUrl = $school->slug
            ? "https://{$school->slug}.{$saasDomain}/vitrine"
            : url('/vitrine/' . $school->id);

        return [
            'hasSchool' => true,
            'school' => $school,
            'showcaseUrl' => $showcaseUrl,
            'visitors30d' => $visitors30d,
            'visitorsToday' => $visitorsToday,
            'whatsappClicks' => $whatsappClicks,
            'leadsCount' => $leadsCount,
            'conversionRate' => $conversionRate,
            'topFormations' => $topFormations,
            'topSources' => $topSources,
        ];
    }
}
