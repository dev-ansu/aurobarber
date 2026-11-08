<?php

namespace App\Services;

use App\Models\CommercialLead;
use App\Models\CommercialVisit;
use App\Models\Contract;
use Illuminate\Support\Carbon;

class CommercialPerformanceReport
{
    public function generate(Carbon $from, Carbon $until, float $investment = 0.0): array
    {
        $from = $from->copy()->startOfDay();
        $until = $until->copy()->endOfDay();

        $cohort = CommercialLead::query()->whereBetween('commercial_leads.created_at', [$from, $until]);
        $leadCount = (clone $cohort)->count();
        $contactedCount = (clone $cohort)->whereIn('status', ['contacted', 'visit_scheduled', 'visited', 'proposal', 'enrolled'])->count();
        $visitsCount = CommercialVisit::query()->whereBetween('visited_at', [$from, $until])->count();
        $proposalsCount = (clone $cohort)->whereIn('status', ['proposal', 'enrolled'])->count();
        $enrolledCount = (clone $cohort)->where('status', 'enrolled')->count();
        $overdueFollowUps = CommercialLead::query()
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', now())
            ->whereNotIn('status', ['lost', 'enrolled'])
            ->count();

        $cac = ($enrolledCount > 0 && $investment > 0)
            ? round($investment / $enrolledCount, 2)
            : 0.0;

        $funnel = [
            [
                'stage' => 'Novos contatos / Leads',
                'count' => $leadCount,
                'rate' => 100.0,
                'conversion_from_total' => 100.0,
            ],
            [
                'stage' => 'Contatados',
                'count' => $contactedCount,
                'rate' => $leadCount > 0 ? round(($contactedCount / $leadCount) * 100, 1) : 0.0,
                'conversion_from_total' => $leadCount > 0 ? round(($contactedCount / $leadCount) * 100, 1) : 0.0,
            ],
            [
                'stage' => 'Visitas agendadas / realizadas',
                'count' => $visitsCount,
                'rate' => $contactedCount > 0 ? round(($visitsCount / $contactedCount) * 100, 1) : ($leadCount > 0 ? round(($visitsCount / $leadCount) * 100, 1) : 0.0),
                'conversion_from_total' => $leadCount > 0 ? round(($visitsCount / $leadCount) * 100, 1) : 0.0,
            ],
            [
                'stage' => 'Propostas apresentadas',
                'count' => $proposalsCount,
                'rate' => $visitsCount > 0 ? round(($proposalsCount / $visitsCount) * 100, 1) : 0.0,
                'conversion_from_total' => $leadCount > 0 ? round(($proposalsCount / $leadCount) * 100, 1) : 0.0,
            ],
            [
                'stage' => 'Matrículas / Contratos',
                'count' => $enrolledCount,
                'rate' => $proposalsCount > 0 ? round(($enrolledCount / $proposalsCount) * 100, 1) : 0.0,
                'conversion_from_total' => $leadCount > 0 ? round(($enrolledCount / $leadCount) * 100, 1) : 0.0,
            ],
        ];

        // Objeções e Motivos de Perda/Cancelamento
        $reasonsMap = [];
        $totalLosses = 0;

        $lostLeads = (clone $cohort)
            ->where('status', 'lost')
            ->get(['notes']);

        foreach ($lostLeads as $lead) {
            $totalLosses++;
            $raw = trim((string) $lead->notes);
            $reason = $raw === '' ? 'Desistência / Sem motivo especificado' : (mb_strlen($raw) > 60 ? mb_substr($raw, 0, 57).'...' : $raw);
            $reasonsMap[$reason] = ($reasonsMap[$reason] ?? 0) + 1;
        }

        $cancelledContracts = Contract::query()
            ->where(function ($q) use ($from, $until): void {
                $q->whereBetween('cancelled_at', [$from, $until])
                    ->orWhere(function ($sub) use ($from, $until): void {
                        $sub->whereBetween('created_at', [$from, $until])
                            ->where('status', 'cancelled');
                    });
            })
            ->whereNotNull('cancellation_reason')
            ->where('cancellation_reason', '!=', '')
            ->get(['cancellation_reason']);

        foreach ($cancelledContracts as $contract) {
            $totalLosses++;
            $raw = trim((string) $contract->cancellation_reason);
            $reason = mb_strlen($raw) > 60 ? mb_substr($raw, 0, 57).'...' : $raw;
            $reasonsMap[$reason] = ($reasonsMap[$reason] ?? 0) + 1;
        }

        arsort($reasonsMap);
        $lossReasons = [];
        foreach ($reasonsMap as $reason => $count) {
            $lossReasons[] = [
                'reason' => $reason,
                'count' => $count,
                'percentage' => $totalLosses > 0 ? round(($count / $totalLosses) * 100, 1) : 0.0,
            ];
        }

        $sourceLabels = [
            'referral' => 'Indicação',
            'social_media' => 'Rede social',
            'website' => 'Site',
            'walk_in' => 'Visita espontânea',
            'phone' => 'Telefone/WhatsApp',
            'event' => 'Evento/ação externa',
            'other' => 'Outro',
        ];

        $bySource = (clone $cohort)
            ->selectRaw('source, COUNT(*) AS lead_count, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS enrolled_count', ['enrolled'])
            ->groupBy('source')
            ->orderByDesc('lead_count')
            ->get()
            ->map(fn ($row): array => $this->formatRow(
                $sourceLabels[$row->source] ?? 'Não informada',
                (int) $row->lead_count,
                (int) $row->enrolled_count,
            ))
            ->all();

        $byCollaborator = (clone $cohort)
            ->leftJoin('collaborators', 'collaborators.id', '=', 'commercial_leads.collaborator_id')
            ->leftJoin('people', 'people.id', '=', 'collaborators.person_id')
            ->selectRaw('collaborators.id AS collaborator_id, people.name AS collaborator_name, COUNT(*) AS lead_count, SUM(CASE WHEN commercial_leads.status = ? THEN 1 ELSE 0 END) AS enrolled_count', ['enrolled'])
            ->groupBy('collaborators.id', 'people.name')
            ->orderByDesc('lead_count')
            ->get()
            ->map(fn ($row): array => $this->formatRow(
                $row->collaborator_name ?: 'Sem responsável',
                (int) $row->lead_count,
                (int) $row->enrolled_count,
            ))
            ->all();

        $byState = [];
        $byCity = [];
        $byNeighborhood = [];
        $regionalLeads = (clone $cohort)
            ->with(['city.state', 'person.addresses' => fn ($query) => $query->where('is_primary', true)->with('city.state')])
            ->get();

        foreach ($regionalLeads as $lead) {
            $primaryAddress = $lead->person?->addresses->first();
            $city = $lead->city ?? $primaryAddress?->city;
            $neighborhood = trim((string) ($lead->neighborhood ?: $primaryAddress?->neighborhood));
            $state = $city?->state;

            $stateKey = $state ? (string) $state->getKey() : 'unknown';
            $stateLabel = $state ? ($state->acronym.' — '.$state->name) : 'Estado não informado';
            $cityKey = $city ? (string) $city->getKey() : 'unknown';
            $cityLabel = $city ? ($city->name.($state?->acronym ? ' — '.$state->acronym : '')) : 'Cidade não informada';
            $neighborhoodKey = $cityKey.'|'.mb_strtolower($neighborhood ?: 'não informado');
            $neighborhoodLabel = $neighborhood !== ''
                ? $neighborhood.' — '.$cityLabel
                : 'Bairro não informado — '.$cityLabel;

            $this->incrementRegion($byState, $stateKey, $stateLabel, $lead->status === 'enrolled');
            $this->incrementRegion($byCity, $cityKey, $cityLabel, $lead->status === 'enrolled');
            $this->incrementRegion($byNeighborhood, $neighborhoodKey, $neighborhoodLabel, $lead->status === 'enrolled');
        }

        $byState = $this->sortedRegions($byState);
        $byCity = $this->sortedRegions($byCity);
        $byNeighborhood = $this->sortedRegions($byNeighborhood);

        $open = CommercialLead::query()->whereNotIn('status', ['lost', 'enrolled']);
        $aging = [
            ['label' => 'Até 7 dias', 'count' => (clone $open)->where('created_at', '>=', now()->subDays(7))->count()],
            ['label' => '8 a 30 dias', 'count' => (clone $open)->where('created_at', '<', now()->subDays(7))->where('created_at', '>=', now()->subDays(30))->count()],
            ['label' => 'Mais de 30 dias', 'count' => (clone $open)->where('created_at', '<', now()->subDays(30))->count()],
        ];

        return [
            'lead_count' => $leadCount,
            'enrolled_count' => $enrolledCount,
            'conversion_rate' => $leadCount > 0 ? round(($enrolledCount / $leadCount) * 100, 1) : 0,
            'visits_count' => $visitsCount,
            'overdue_follow_ups' => $overdueFollowUps,
            'investment' => $investment,
            'cac' => $cac,
            'funnel' => $funnel,
            'loss_reasons' => $lossReasons,
            'by_source' => $bySource,
            'by_collaborator' => $byCollaborator,
            'by_state' => $byState,
            'by_city' => $byCity,
            'by_neighborhood' => $byNeighborhood,
            'aging' => $aging,
        ];
    }

    private function formatRow(string $label, int $leads, int $enrolled): array
    {
        return [
            'label' => $label,
            'leads' => $leads,
            'enrolled' => $enrolled,
            'conversion_rate' => $leads > 0 ? round(($enrolled / $leads) * 100, 1) : 0,
        ];
    }

    private function incrementRegion(array &$regions, string $key, string $label, bool $enrolled): void
    {
        $regions[$key] ??= ['label' => $label, 'leads' => 0, 'enrolled' => 0];
        $regions[$key]['leads']++;

        if ($enrolled) {
            $regions[$key]['enrolled']++;
        }
    }

    private function sortedRegions(array $regions): array
    {
        $rows = array_map(fn (array $row): array => $this->formatRow($row['label'], $row['leads'], $row['enrolled']), array_values($regions));

        usort($rows, fn (array $a, array $b): int => $b['leads'] <=> $a['leads'] ?: strcmp($a['label'], $b['label']));

        return $rows;
    }
}
