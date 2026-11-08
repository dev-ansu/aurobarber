<?php

namespace App\Services;

use App\Models\Installment;
use App\Models\Payable;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

class DreService
{
    /**
     * Gera o Demonstrativo do Resultado do Exercício (DRE) Gerencial.
     *
     * @param  string  $regime  'caixa' ou 'competencia'
     * @return array<string, mixed>
     */
    public function generate(Carbon $startDate, Carbon $endDate, string $regime = 'caixa', ?int $schoolId = null): array
    {
        $schoolId ??= Filament::getTenant()?->id
            ?? (session('filament_tenant_id') ? (int) session('filament_tenant_id') : null);

        $startDateStr = $startDate->copy()->startOfDay()->toDateString();
        $endDateStr = $endDate->copy()->endOfDay()->toDateString();

        // 1. Receitas (Installments)
        $installmentsQuery = Installment::query()
            ->withoutGlobalScopes()
            ->forSchool($schoolId)
            ->with('financialType');

        if ($regime === 'caixa') {
            $installmentsQuery
                ->whereIn('status', ['paid', 'partial'])
                ->whereBetween('paid_at', [$startDateStr, $endDateStr]);
        } else {
            $installmentsQuery
                ->where('status', '!=', 'cancelled')
                ->whereBetween('due_date', [$startDateStr, $endDateStr]);
        }

        $installments = $installmentsQuery->get();

        $grossInstallments = (float) $installments->sum('amount');
        $fines = (float) $installments->sum('fine');
        $discounts = (float) $installments->sum('discount');
        $netInstallmentsPaid = (float) $installments->sum('amount_paid');

        // Receita Bruta = valor nominal + juros/multas
        $grossRevenue = $grossInstallments + $fines;
        // Deduções = descontos
        $revenueDeductions = $discounts;

        // Receita Líquida
        if ($regime === 'caixa') {
            $netRevenue = $netInstallmentsPaid > 0 ? $netInstallmentsPaid : max(0, $grossRevenue - $revenueDeductions);
        } else {
            $netRevenue = max(0, $grossRevenue - $revenueDeductions);
        }

        // Agrupamento de Receitas por Tipo Financeiro
        $revenueByType = $installments
            ->groupBy(function (Installment $item) {
                if (! $item->financialType) {
                    return '1.1.1 - Mensalidades / Geral';
                }

                return $item->financialType->code
                    ? "{$item->financialType->code} - {$item->financialType->name}"
                    : $item->financialType->name;
            })
            ->map(function (Collection $group, string $typeName) use ($regime, $netRevenue) {
                $total = (float) ($regime === 'caixa' ? $group->sum('amount_paid') : $group->sum('amount'));
                $pct = $netRevenue > 0 ? ($total / $netRevenue) * 100 : 0.0;

                return [
                    'name' => $typeName,
                    'count' => $group->count(),
                    'total' => $total,
                    'percentage' => round($pct, 1),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        // 2. Despesas Operacionais (Payables / Contas a Pagar)
        $payablesQuery = Payable::query()
            ->withoutGlobalScopes()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('financialType');

        if ($regime === 'caixa') {
            $payablesQuery
                ->where('status', 'paid')
                ->whereBetween('payment_date', [$startDateStr, $endDateStr]);
        } else {
            $payablesQuery
                ->where('status', '!=', 'cancelled')
                ->whereBetween('due_date', [$startDateStr, $endDateStr]);
        }

        $payables = $payablesQuery->get();

        $totalExpenses = (float) ($regime === 'caixa' ? $payables->sum('amount_paid') : $payables->sum('amount'));

        // Agrupamento de Despesas por Tipo Financeiro / Categoria
        $expensesByType = $payables
            ->groupBy(function (Payable $item) {
                if (! $item->financialType) {
                    return 'Despesas Operacionais Gerais';
                }

                return $item->financialType->code
                    ? "{$item->financialType->code} - {$item->financialType->name}"
                    : $item->financialType->name;
            })
            ->map(function (Collection $group, string $typeName) use ($regime, $netRevenue, $totalExpenses) {
                $total = (float) ($regime === 'caixa' ? $group->sum('amount_paid') : $group->sum('amount'));
                $pctRevenue = $netRevenue > 0 ? ($total / $netRevenue) * 100 : 0.0;
                $pctExpenses = $totalExpenses > 0 ? ($total / $totalExpenses) * 100 : 0.0;

                return [
                    'name' => $typeName,
                    'count' => $group->count(),
                    'total' => $total,
                    'pct_revenue' => round($pctRevenue, 1),
                    'pct_expenses' => round($pctExpenses, 1),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();

        // 3. Resultado Operacional Líquido (Lucro ou Prejuízo)
        $netResult = $netRevenue - $totalExpenses;
        $operatingMargin = $netRevenue > 0 ? ($netResult / $netRevenue) * 100 : 0.0;

        return [
            'period' => [
                'start' => $startDate->format('d/m/Y'),
                'end' => $endDate->format('d/m/Y'),
                'regime' => $regime,
                'regime_label' => $regime === 'caixa' ? 'Regime de Caixa (Efetivamente Pago/Recebido)' : 'Regime de Competência (Vencimentos no Período)',
            ],
            'revenue' => [
                'gross' => $grossRevenue,
                'installments_amount' => $grossInstallments,
                'fines' => $fines,
                'deductions' => $revenueDeductions,
                'net' => $netRevenue,
                'by_type' => $revenueByType,
                'count' => $installments->count(),
            ],
            'expenses' => [
                'total' => $totalExpenses,
                'by_type' => $expensesByType,
                'count' => $payables->count(),
            ],
            'result' => [
                'net_amount' => $netResult,
                'is_profit' => $netResult >= 0,
                'operating_margin' => round($operatingMargin, 2),
            ],
        ];
    }
}
