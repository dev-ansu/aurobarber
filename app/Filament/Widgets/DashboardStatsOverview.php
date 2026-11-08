<?php

namespace App\Filament\Widgets;

use App\Models\CommercialLead;
use App\Models\Contract;
use App\Models\Installment;
use App\Models\User;
use App\Services\PaymentMethodReceiptReport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = [
        'default' => 1,
        'sm' => 2,
        'md' => 3,
        'xl' => 5,
    ];

    public static function canView(): bool
    {
        $user = filament()->auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if (self::hasPermission($user, 'View:DashboardStatsOverview')) {
            return true;
        }

        return self::hasPermission($user, 'View:Stat:RecebimentosMes') ||
            self::hasPermission($user, 'View:Stat:ContasReceberHoje') ||
            self::hasPermission($user, 'View:Stat:ContratosAtivos') ||
            self::hasPermission($user, 'View:Stat:AlunosAtivos') ||
            self::hasPermission($user, 'View:Stat:Inadimplencia') ||
            self::hasPermission($user, 'View:Stat:MatriculasMes') ||
            self::hasPermission($user, 'View:Stat:Leads') ||
            self::hasPermission($user, 'View:CommercialOverview');
    }

    protected function getStats(): array
    {
        $user = filament()->auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        $stats = [];

        $hasCombo = self::hasPermission($user, 'View:DashboardStatsOverview');

        // CARD 1: Contratos ativos
        if ($hasCombo || self::hasPermission($user, 'View:Stat:ContratosAtivos')) {
            $activeContracts = Contract::query()
                ->where('status', 'active')
                ->count();

            $stats[] = Stat::make('Contratos ativos', number_format($activeContracts, 0, ',', '.'))
                ->description('Contratos em andamento')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('primary')
                ->url(route('filament.admin.resources.contracts.index'));
        }

        // CARD 2: Matrículas no mês
        if ($hasCombo || self::hasPermission($user, 'View:Stat:MatriculasMes')) {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $monthlyEnrollments = Contract::query()
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->whereNotIn('status', ['canceled', 'draft'])
                ->count();

            $stats[] = Stat::make('Matrículas no mês', number_format($monthlyEnrollments, 0, ',', '.'))
                ->description('Novos contratos no mês')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('success')
                ->url(route('filament.admin.resources.contracts.index'));
        }

        // CARD 3: Contas a receber hoje
        if ($hasCombo || self::hasPermission($user, 'View:Stat:ContasReceberHoje')) {
            $dueTodayQuery = Installment::query()
                ->forSchool()
                ->whereIn('status', ['pending', 'partial'])
                ->whereDate('due_date', Carbon::today());

            $dueTodayAmount = (clone $dueTodayQuery)->sum(DB::raw('amount - discount + fine - COALESCE(amount_paid, 0)'));
            $dueTodayCount = $dueTodayQuery->count();

            $stats[] = Stat::make('Contas a receber hoje', 'R$ '.number_format((float) $dueTodayAmount, 2, ',', '.'))
                ->description("{$dueTodayCount} parcelas com vencimento hoje")
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.installments.index', ['tab' => 'today']));
        }

        // CARD 4: Recebimentos no mês
        if ($hasCombo || self::hasPermission($user, 'View:Stat:RecebimentosMes')) {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $receivedRows = app(PaymentMethodReceiptReport::class)->query($startOfMonth, $endOfMonth)->get();
            $totalReceived = (float) $receivedRows->sum('amount_total');
            $receivedCount = (int) $receivedRows->sum('receipt_count');

            $stats[] = Stat::make('Recebimentos no mês', 'R$ '.number_format($totalReceived, 2, ',', '.'))
                ->description("{$receivedCount} recebimentos registrados")
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->url(route('filament.admin.resources.cash-transactions.index'));
        }

        // CARD 5: Leads em aberto
        if ($hasCombo || self::hasPermission($user, 'View:CommercialOverview') || self::hasPermission($user, 'View:Stat:Leads')) {
            $openLeads = CommercialLead::query()
                ->whereNotIn('status', ['lost', 'enrolled'])
                ->count();

            $overdueFollowUps = CommercialLead::query()
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<', now())
                ->whereNotIn('status', ['lost', 'enrolled'])
                ->count();

            $desc = $overdueFollowUps > 0
                ? "{$overdueFollowUps} retornos atrasados"
                : 'Oportunidades em andamento';

            $stats[] = Stat::make('Leads em aberto', number_format($openLeads, 0, ',', '.'))
                ->description($desc)
                ->descriptionIcon('heroicon-m-user-group')
                ->color($overdueFollowUps > 0 ? 'warning' : 'info')
                ->url(route('filament.admin.resources.commercial-leads.index'));
        }

        // Card avulso por permissão: Alunos ativos (se não tiver combo e não tiver contratos ativos)
        if (! $hasCombo && self::hasPermission($user, 'View:Stat:AlunosAtivos') && ! self::hasPermission($user, 'View:Stat:ContratosAtivos')) {
            $activeStudents = Contract::query()
                ->where('status', 'active')
                ->distinct('student_id')
                ->count('student_id');

            $stats[] = Stat::make('Alunos ativos', number_format($activeStudents, 0, ',', '.'))
                ->description('Alunos com contrato em andamento')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('info')
                ->url(route('filament.admin.resources.contracts.index'));
        }

        // Card avulso por permissão: Saldo vencido (se não tiver combo)
        if (! $hasCombo && self::hasPermission($user, 'View:Stat:Inadimplencia')) {
            $overdue = Installment::query()
                ->forSchool()
                ->whereIn('status', ['pending', 'partial'])
                ->whereDate('due_date', '<', Carbon::today())
                ->whereRaw('(amount - discount + fine - COALESCE(amount_paid, 0)) > 0');

            $overdueAmount = (clone $overdue)->sum(DB::raw('amount - discount + fine - COALESCE(amount_paid, 0)'));
            $overdueCount = $overdue->count();

            $stats[] = Stat::make('Saldo vencido', 'R$ '.number_format((float) $overdueAmount, 2, ',', '.'))
                ->description("{$overdueCount} parcelas vencidas")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->url(route('filament.admin.pages.overdue-installment-report'));
        }

        return $stats;
    }

    private static function hasPermission(User $user, string $permission): bool
    {
        return $user->getAllPermissions()->contains('name', $permission);
    }
}
