<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CashRegister;
use App\Models\FinancialAccount;
use App\Models\Installment;
use App\Models\Payable;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Support\Collection;

class CashFlowForecastService
{
    /**
     * Gera a projeção de fluxo de caixa para o período informado.
     *
     * @return array{
     *     start_date: string,
     *     end_date: string,
     *     current_liquid_balance: float,
     *     total_projected_inflow: float,
     *     total_projected_outflow: float,
     *     projected_net_result: float,
     *     projected_final_balance: float,
     *     timeline: array<int, array<string, mixed>>,
     *     summary_by_type: array<string, mixed>
     * }
     */
    public function generate(Carbon $startDate, Carbon $endDate, ?int $schoolId = null): array
    {
        $schoolId ??= Filament::getTenant()?->id
            ?? (session('filament_tenant_id') ? (int) session('filament_tenant_id') : null);

        $startStr = $startDate->copy()->startOfDay()->toDateString();
        $endStr = $endDate->copy()->endOfDay()->toDateString();

        // 1. Saldo Líquido Atual Disponível (Caixas Abertos + Contas Financeiras e Cofres)
        $cashRegisterBalance = (float) CashRegister::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('status', 'open')
            ->sum('current_balance');

        $financialAccountsBalance = (float) FinancialAccount::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->sum('current_balance');

        $currentLiquidBalance = round($cashRegisterBalance + $financialAccountsBalance, 2);

        // 2. Receitas Previstas (Parcelas pendentes/parciais com vencimento no período)
        $installments = Installment::query()
            ->withoutGlobalScopes()
            ->forSchool($schoolId)
            ->with(['contract.student', 'financialType'])
            ->whereIn('status', ['pending', 'partial'])
            ->whereBetween('due_date', [$startStr, $endStr])
            ->orderBy('due_date')
            ->get();

        // 3. Despesas Previstas (Contas a pagar pendentes com vencimento no período)
        $payables = Payable::query()
            ->withoutGlobalScopes()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->with('financialType')
            ->where('status', 'pending')
            ->whereBetween('due_date', [$startStr, $endStr])
            ->orderBy('due_date')
            ->get();

        $totalInflow = 0.0;
        $totalOutflow = 0.0;

        // Agrupamento por dia
        $installmentsByDate = $installments->groupBy(fn (Installment $inst) => Carbon::parse($inst->due_date)->toDateString());
        $payablesByDate = $payables->groupBy(fn (Payable $pay) => Carbon::parse($pay->due_date)->toDateString());

        $allDates = $installmentsByDate->keys()
            ->merge($payablesByDate->keys())
            ->unique()
            ->sort()
            ->values();

        $runningBalance = $currentLiquidBalance;
        $timeline = [];

        foreach ($allDates as $dateStr) {
            $dayInstallments = $installmentsByDate->get($dateStr, collect());
            $dayPayables = $payablesByDate->get($dateStr, collect());

            $dayInflow = (float) $dayInstallments->sum(function (Installment $i) {
                $base = (float) $i->amount;
                $disc = (float) ($i->discount ?? 0);
                $fine = (float) ($i->fine ?? 0);
                $paid = (float) ($i->amount_paid ?? 0);

                return max(0, ($base - $disc + $fine) - $paid);
            });

            $dayOutflow = (float) $dayPayables->sum('amount');

            $totalInflow += $dayInflow;
            $totalOutflow += $dayOutflow;

            $dayNet = $dayInflow - $dayOutflow;
            $runningBalance += $dayNet;

            $carbonDate = Carbon::parse($dateStr);

            $timeline[] = [
                'date' => $dateStr,
                'formatted_date' => $carbonDate->format('d/m/Y'),
                'day_of_week' => ucfirst($carbonDate->translatedFormat('D')),
                'inflow' => round($dayInflow, 2),
                'outflow' => round($dayOutflow, 2),
                'day_net' => round($dayNet, 2),
                'running_balance' => round($runningBalance, 2),
                'installments_count' => $dayInstallments->count(),
                'payables_count' => $dayPayables->count(),
                'installments_details' => $dayInstallments->map(fn (Installment $i) => [
                    'student' => $i->contract?->student?->name ?? 'Aluno',
                    'contract' => $i->contract?->contract_number ?? $i->contract_id,
                    'installment_num' => "{$i->installment_number}/{$i->total_installments}",
                    'type' => $i->financialType?->name ?? 'Mensalidade',
                    'amount' => (float) $i->amount,
                ])->all(),
                'payables_details' => $dayPayables->map(fn (Payable $p) => [
                    'title' => $p->title ?? $p->description ?? 'Despesa',
                    'type' => $p->financialType?->name ?? 'Geral',
                    'amount' => (float) $p->amount,
                ])->all(),
            ];
        }

        $totalInflow = round($totalInflow, 2);
        $totalOutflow = round($totalOutflow, 2);
        $projectedNetResult = round($totalInflow - $totalOutflow, 2);
        $projectedFinalBalance = round($currentLiquidBalance + $projectedNetResult, 2);

        return [
            'start_date' => $startStr,
            'end_date' => $endStr,
            'current_liquid_balance' => $currentLiquidBalance,
            'cash_register_balance' => $cashRegisterBalance,
            'financial_accounts_balance' => $financialAccountsBalance,
            'total_projected_inflow' => $totalInflow,
            'total_projected_outflow' => $totalOutflow,
            'projected_net_result' => $projectedNetResult,
            'projected_final_balance' => $projectedFinalBalance,
            'total_installments_count' => $installments->count(),
            'total_payables_count' => $payables->count(),
            'timeline' => $timeline,
        ];
    }
}

