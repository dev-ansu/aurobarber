<?php

namespace App\Services;

use App\Models\ContractStatusHistory;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;

class ContractCancellationReport
{
    public function generate(Carbon $from, Carbon $until, ?int $formationId = null, ?int $schoolId = null): array
    {
        $schoolId ??= Filament::getTenant()?->id;

        $histories = ContractStatusHistory::query()
            ->with(['contract' => fn ($q) => $q->withoutGlobalScopes()->with(['student', 'items.formation'])])
            ->where('to_status', 'canceled')
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $until->copy()->endOfDay()])
            ->whereHas('contract', fn ($q) => $q->withoutGlobalScopes()->when($schoolId, fn ($sq) => $sq->where('school_id', $schoolId)))
            ->when($formationId, fn ($query) => $query->whereHas(
                'contract.items',
                fn ($items) => $items->where('formation_id', $formationId),
            ))
            ->latest('created_at')
            ->get();

        $rows = $histories->map(function (ContractStatusHistory $history): array {
            $contract = $history->contract;
            $startDate = $contract?->start_date;
            $duration = $startDate
                ? Carbon::parse($startDate)->diffInDays($history->created_at)
                : null;

            return [
                'date' => $history->created_at?->format('d/m/Y H:i') ?? '—',
                'student' => $contract?->student?->name ?? 'Aluno não disponível',
                'contract_number' => $contract?->contract_number ?? '—',
                'formations' => $contract?->items->pluck('formation.name')->filter()->unique()->implode(', ') ?: '—',
                'reason' => filled($history->reason) ? $history->reason : 'Não informado',
                'duration_days' => $duration,
            ];
        })->all();

        $byReason = $histories
            ->groupBy(fn (ContractStatusHistory $history): string => filled($history->reason) ? trim($history->reason) : 'Não informado')
            ->map(fn ($group, string $reason): array => [
                'label' => $reason,
                'count' => $group->count(),
                'percentage' => $histories->isNotEmpty() ? round(($group->count() / $histories->count()) * 100, 1) : 0,
            ])
            ->sortByDesc('count')
            ->values()
            ->all();

        $byMonth = $histories
            ->groupBy(fn (ContractStatusHistory $history): string => $history->created_at?->format('Y-m') ?? 'Sem data')
            ->map(fn ($group, string $month): array => [
                'label' => $month === 'Sem data' ? $month : Carbon::createFromFormat('Y-m', $month)->translatedFormat('M/Y'),
                'count' => $group->count(),
            ])
            ->sortKeys()
            ->values()
            ->all();

        $durations = $histories->map(function (ContractStatusHistory $history): ?float {
            if (! $history->contract?->start_date || ! $history->created_at) {
                return null;
            }

            return (float) Carbon::parse($history->contract->start_date)->diffInDays($history->created_at);
        })->filter();

        return [
            'cancellation_events' => $histories->count(),
            'contracts' => $histories->pluck('contract_id')->unique()->count(),
            'students' => $histories->map(fn (ContractStatusHistory $history) => $history->contract?->student_id)->filter()->unique()->count(),
            'without_reason' => $histories->filter(fn (ContractStatusHistory $history): bool => blank($history->reason))->count(),
            'average_duration_days' => $durations->isNotEmpty() ? round($durations->avg(), 1) : null,
            'by_reason' => $byReason,
            'by_month' => $byMonth,
            'rows' => $rows,
        ];
    }
}
