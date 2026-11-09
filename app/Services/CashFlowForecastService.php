<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Models\CashMovement;
use App\Models\Payable;
use Carbon\Carbon;
use Filament\Facades\Filament;
use InvalidArgumentException;

class CashFlowForecastService
{
    public function generate(Carbon $startDate, Carbon $endDate, ?int $barbershopId = null): array
    {
        $barbershopId ??= Filament::getTenant()?->getKey();

        if ($barbershopId === null) {
            throw new InvalidArgumentException('A projeção exige uma barbearia selecionada.');
        }

        if ($endDate->lt($startDate)) {
            throw new InvalidArgumentException('A data final deve ser igual ou posterior à inicial.');
        }

        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->endOfDay();
        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        $receivedAppointments = Appointment::query()
            ->where('barbershop_id', $barbershopId)
            ->where('status', 'completed')
            ->where('payment_status', 'paid');

        $historicalRevenue = (float) $receivedAppointments
            ->selectRaw('COALESCE(SUM(service_price + products_total), 0) as total')
            ->value('total');

        $openingBalance = (float) (CashMovement::query()
            ->where('barbershop_id', $barbershopId)
            ->where('type', 'opening')
            ->orderBy('date')
            ->orderBy('id')
            ->value('amount') ?? 0);

        $manualMovements = CashMovement::query()
            ->where('barbershop_id', $barbershopId)
            ->whereIn('type', ['inflow', 'outflow'])
            ->selectRaw("COALESCE(SUM(CASE WHEN type = 'inflow' THEN amount WHEN type = 'outflow' THEN -amount ELSE 0 END), 0) as balance")
            ->value('balance');

        $paidExpenses = (float) Payable::query()
            ->where('barbershop_id', $barbershopId)
            ->where('status', 'paid')
            ->sum('amount');

        $currentBalance = round($historicalRevenue + $openingBalance + (float) $manualMovements - $paidExpenses, 2);

        $appointments = Appointment::query()
            ->where('barbershop_id', $barbershopId)
            ->whereIn('status', ['scheduled', 'confirmed', 'in_progress'])
            ->where('payment_status', 'pending')
            ->whereBetween('scheduled_at', [$start, $end])
            ->orderBy('scheduled_at')
            ->get();

        $payables = Payable::query()
            ->where('barbershop_id', $barbershopId)
            ->where('status', 'pending')
            ->whereBetween('due_date', [$startStr, $endStr])
            ->orderBy('due_date')
            ->get();

        $appointmentsByDate = $appointments->groupBy(
            fn (Appointment $appointment): string => $appointment->scheduled_at->toDateString(),
        );
        $payablesByDate = $payables->groupBy(
            fn (Payable $payable): string => $payable->due_date->toDateString(),
        );
        $allDates = $appointmentsByDate->keys()
            ->merge($payablesByDate->keys())
            ->unique()
            ->sort()
            ->values();

        $totalInflow = 0.0;
        $totalOutflow = 0.0;
        $runningBalance = $currentBalance;
        $timeline = [];

        foreach ($allDates as $dateStr) {
            $dayAppointments = $appointmentsByDate->get($dateStr, collect());
            $dayPayables = $payablesByDate->get($dateStr, collect());

            $dayInflow = (float) $dayAppointments->sum(
                fn (Appointment $appointment): float => (float) $appointment->service_price + (float) $appointment->products_total,
            );

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
                'appointments_count' => $dayAppointments->count(),
                'payables_count' => $dayPayables->count(),
                'appointments_details' => $dayAppointments->map(fn (Appointment $appointment): array => [
                    'client' => $appointment->client_name,
                    'amount' => (float) $appointment->service_price + (float) $appointment->products_total,
                ])->all(),
                'payables_details' => $dayPayables->map(fn (Payable $payable): array => [
                    'title' => $payable->description,
                    'supplier' => $payable->supplier,
                    'amount' => (float) $payable->amount,
                ])->all(),
            ];
        }

        $totalInflow = round($totalInflow, 2);
        $totalOutflow = round($totalOutflow, 2);
        $projectedNetResult = $totalInflow - $totalOutflow;
        $projectedFinalBalance = round($currentBalance + $projectedNetResult, 2);

        return [
            'start_date' => $startStr,
            'end_date' => $endStr,
            'current_liquid_balance' => $currentBalance,
            'total_projected_inflow' => $totalInflow,
            'total_projected_outflow' => $totalOutflow,
            'projected_net_result' => round($projectedNetResult, 2),
            'projected_final_balance' => $projectedFinalBalance,
            'total_appointments_count' => $appointments->count(),
            'total_payables_count' => $payables->count(),
            'timeline' => $timeline,
        ];
    }
}
