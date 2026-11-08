<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use App\Models\Installment;
use App\Models\School;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SchoolWeeklyDigestService
{
    /**
     * Coleta as métricas consolidadas dos últimos 7 dias e projeções para os próximos 7 dias.
     *
     * @return array<string, mixed>
     */
    public function collectMetrics(School $school, ?Carbon $endDate = null): array
    {
        $end = $endDate ?? Carbon::now();
        $start = $end->copy()->subDays(7)->startOfDay();
        $nextWeekEnd = $end->copy()->addDays(7)->endOfDay();

        // 1. Receita Realizada nos Últimos 7 dias (Parcelas e taxas quitadas)
        $paidInstallments = Installment::query()
            ->withoutGlobalScopes()
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('paid_at', [$start, $end])
                  ->orWhere(function ($sub) use ($start, $end) {
                      $sub->whereNull('paid_at')
                          ->whereIn('status', ['paid', 'partial'])
                          ->whereBetween('updated_at', [$start, $end]);
                  });
            })
            ->whereIn('status', ['paid', 'partial'])
            ->get();

        $totalReceived = (float) $paidInstallments->sum(function ($inst) {
            return (float) ($inst->amount_paid ?: $inst->amount);
        });
        $receivedCount = $paidInstallments->count();

        // 2. Novas Matrículas fechadas nos últimos 7 dias
        $newContracts = Contract::query()
            ->withoutGlobalScopes()
            ->whereNotIn('status', ['canceled', 'draft'])
            ->whereBetween('created_at', [$start, $end])
            ->with(['items.formation', 'items.contractCourses.course'])
            ->get();

        $newContractsCount = $newContracts->count();
        $newContractsValue = (float) $newContracts->sum('total_value');

        // Cursos mais vendidos no período
        $courseCounts = [];
        foreach ($newContracts as $contract) {
            foreach ($contract->items as $item) {
                $name = $item->formation?->name 
                    ?? $item->contractCourses->first()?->course?->name 
                    ?? 'Curso Profissionalizante';
                $courseCounts[$name] = ($courseCounts[$name] ?? 0) + 1;
            }
        }
        arsort($courseCounts);
        $topCourses = [];
        foreach (array_slice($courseCounts, 0, 4, true) as $name => $count) {
            $topCourses[] = ['name' => $name, 'count' => $count];
        }

        // 3. Previsão de Contas a Receber na Próxima Semana
        $upcomingInstallments = Installment::query()
            ->withoutGlobalScopes()
            ->whereNotIn('status', ['paid', 'canceled'])
            ->whereBetween('due_date', [$end->copy()->startOfDay(), $nextWeekEnd])
            ->get();

        $upcomingDueCount = $upcomingInstallments->count();
        $upcomingDueValue = (float) $upcomingInstallments->sum('amount');

        // 4. Retornos de Trancamentos Previstos na Próxima Semana
        $pendingSuspensionsCount = Contract::query()
            ->withoutGlobalScopes()
            ->where('status', 'suspended')
            ->whereBetween('suspension_expected_return_date', [$end->copy()->startOfDay(), $nextWeekEnd])
            ->count();

        return [
            'period_label' => $start->format('d/m') . ' a ' . $end->format('d/m/Y'),
            'total_received' => $totalReceived,
            'received_count' => $receivedCount,
            'new_contracts_count' => $newContractsCount,
            'new_contracts_value' => $newContractsValue,
            'top_courses' => $topCourses,
            'upcoming_due_count' => $upcomingDueCount,
            'upcoming_due_value' => $upcomingDueValue,
            'pending_suspensions_count' => $pendingSuspensionsCount,
            'panel_url' => url('/admin'),
        ];
    }
}
