<?php

namespace App\Filament\Pages;

use App\Models\Collaborator;
use App\Models\CommercialLead;
use App\Models\Contract;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class SalesBySellerReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Comercial';

    protected static ?string $navigationLabel = 'Vendas por Vendedor';

    protected static ?string $title = 'Relatório de Vendas e Matrículas por Vendedor';

    protected static ?string $slug = 'sales-by-seller-report';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.sales-by-seller-report';

    public string $fromDate = '';

    public string $untilDate = '';

    public string $collaboratorId = '';

    public string $contractStatus = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('View:SalesBySellerReport'));
    }

    public function mount(): void
    {
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->untilDate = today()->toDateString();
    }

    /**
     * @return array<int, string>
     */
    public function collaborators(): array
    {
        $tenantId = Filament::getTenant()?->id;

        return Collaborator::query()
            ->when($tenantId, function (Builder $q) use ($tenantId): void {
                $q->where(function (Builder $sub) use ($tenantId): void {
                    $sub->whereHas('person.schools', fn (Builder $sq) => $sq->where('schools.id', $tenantId))
                        ->orWhereHas('user.schools', fn (Builder $sq) => $sq->where('schools.id', $tenantId));
                });
            })
            ->with('person')
            ->get()
            ->filter(fn (Collaborator $c): bool => (bool) $c->person?->name)
            ->mapWithKeys(fn (Collaborator $c): array => [
                $c->id => $c->person->name.($c->role ? " ({$c->role})" : ''),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function contractStatuses(): array
    {
        return [
            'active' => 'Ativo',
            'finished' => 'Concluído',
            'canceled' => 'Cancelado',
            'suspended' => 'Trancado / Suspenso',
        ];
    }

    /**
     * Consolida as métricas agrupadas por vendedor
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function sellersReport(): Collection
    {
        $from = Carbon::parse($this->fromDate)->toDateString();
        $until = Carbon::parse($this->untilDate)->toDateString();

        $contractsQuery = Contract::query()
            ->where(function (Builder $q) use ($from, $until): void {
                $q->whereBetween('start_date', [$from, $until])
                    ->orWhereBetween('created_at', [$from.' 00:00:00', $until.' 23:59:59']);
            })
            ->when(filled($this->collaboratorId), fn (Builder $q) => $q->where('collaborator_id', (int) $this->collaboratorId))
            ->when(filled($this->contractStatus), fn (Builder $q) => $q->where('status', $this->contractStatus))
            ->with([
                'collaborator.person',
                'student',
                'items.formation',
                'items.contractCourses.course',
                'installments',
            ]);

        $contracts = $contractsQuery->get();

        $leadsQuery = CommercialLead::query()
            ->whereBetween('created_at', [$from.' 00:00:00', $until.' 23:59:59'])
            ->when(filled($this->collaboratorId), fn (Builder $q) => $q->where('collaborator_id', (int) $this->collaboratorId));

        $leads = $leadsQuery->get();

        $tenantId = Filament::getTenant()?->id;

        $allCollaborators = Collaborator::query()
            ->when($tenantId, function (Builder $q) use ($tenantId): void {
                $q->where(function (Builder $sub) use ($tenantId): void {
                    $sub->whereHas('person.schools', fn (Builder $sq) => $sq->where('schools.id', $tenantId))
                        ->orWhereHas('user.schools', fn (Builder $sq) => $sq->where('schools.id', $tenantId));
                });
            })
            ->when(filled($this->collaboratorId), fn (Builder $q) => $q->where('id', (int) $this->collaboratorId))
            ->with('person')
            ->get();

        $grouped = [];

        // Inicializa colaboradores cadastrados
        foreach ($allCollaborators as $collab) {
            $grouped[$collab->id] = [
                'collaborator_id' => $collab->id,
                'name' => $collab->person?->name ?? 'Colaborador #'.$collab->id,
                'role' => $collab->role ?? 'Vendedor / Comercial',
                'leads_count' => 0,
                'contracts_count' => 0,
                'total_sales' => 0.0,
                'total_discounts' => 0.0,
                'total_paid' => 0.0,
                'total_pending' => 0.0,
                'contracts' => [],
            ];
        }

        // Slot para vendas diretas / sem vendedor atribuído
        $unassignedKey = 'unassigned';
        $grouped[$unassignedKey] = [
            'collaborator_id' => null,
            'name' => 'Venda Direta / Não atribuído',
            'role' => 'Secretaria / Balcão',
            'leads_count' => 0,
            'contracts_count' => 0,
            'total_sales' => 0.0,
            'total_discounts' => 0.0,
            'total_paid' => 0.0,
            'total_pending' => 0.0,
            'contracts' => [],
        ];

        // Mapeia leads por colaborador
        foreach ($leads as $lead) {
            $key = $lead->collaborator_id ?: $unassignedKey;
            if (isset($grouped[$key])) {
                $grouped[$key]['leads_count']++;
            }
        }

        // Mapeia contratos e valores
        foreach ($contracts as $contract) {
            $key = $contract->collaborator_id ?: $unassignedKey;
            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'collaborator_id' => $contract->collaborator_id,
                    'name' => $contract->collaborator?->person?->name ?? 'Colaborador #'.$contract->collaborator_id,
                    'role' => $contract->collaborator?->role ?? 'Comercial',
                    'leads_count' => 0,
                    'contracts_count' => 0,
                    'total_sales' => 0.0,
                    'total_discounts' => 0.0,
                    'total_paid' => 0.0,
                    'total_pending' => 0.0,
                    'contracts' => [],
                ];
            }

            $totalValue = (float) $contract->total_value;
            $discountValue = (float) ($contract->discount_value ?? 0.0);

            $paid = (float) $contract->installments->whereIn('status', ['paid', 'partial'])->sum(fn ($i) => (float) ($i->amount_paid ?: $i->amount));
            $pending = (float) $contract->installments->whereIn('status', ['pending', 'overdue'])->sum(fn ($i) => (float) $i->amount);

            $grouped[$key]['contracts_count']++;
            $grouped[$key]['total_sales'] += $totalValue;
            $grouped[$key]['total_discounts'] += $discountValue;
            $grouped[$key]['total_paid'] += $paid;
            $grouped[$key]['total_pending'] += $pending;

            $course = $contract->items->first()?->formation?->name
                ?? $contract->items->first()?->contractCourses?->first()?->course?->name
                ?? 'Curso Regular';

            $grouped[$key]['contracts'][] = [
                'id' => $contract->id,
                'number' => $contract->contract_number,
                'student_name' => $contract->student?->name ?? 'Aluno não identificado',
                'course' => $course,
                'date' => $contract->start_date?->format('d/m/Y') ?? $contract->created_at?->format('d/m/Y'),
                'total_value' => $totalValue,
                'status' => $contract->status,
                'paid_amount' => $paid,
            ];
        }

        // Remove slot não atribuído se não tiver movimentação
        if ($grouped[$unassignedKey]['contracts_count'] === 0 && $grouped[$unassignedKey]['leads_count'] === 0) {
            unset($grouped[$unassignedKey]);
        }

        // Calcula taxa de conversão e ticket médio
        return collect($grouped)
            ->map(function (array $seller): array {
                $leads = $seller['leads_count'];
                $contracts = $seller['contracts_count'];

                $seller['conversion_rate'] = $leads > 0
                    ? round(($contracts / $leads) * 100, 1)
                    : ($contracts > 0 ? 100.0 : 0.0);

                $seller['average_ticket'] = $contracts > 0
                    ? round($seller['total_sales'] / $contracts, 2)
                    : 0.0;

                return $seller;
            })
            ->sortByDesc('total_sales')
            ->values();
    }

    /**
     * Retorna os cartões consolidados de indicadores do topo
     *
     * @return array<string, mixed>
     */
    public function summaryMetrics(): array
    {
        $rows = $this->sellersReport();

        $totalContracts = $rows->sum('contracts_count');
        $totalLeads = $rows->sum('leads_count');
        $totalSales = $rows->sum('total_sales');
        $totalDiscounts = $rows->sum('total_discounts');
        $totalPaid = $rows->sum('total_paid');
        $totalPending = $rows->sum('total_pending');

        $activeSellers = $rows->filter(fn (array $r): bool => $r['contracts_count'] > 0 || $r['leads_count'] > 0)->count();

        $overallConversion = $totalLeads > 0
            ? round(($totalContracts / $totalLeads) * 100, 1)
            : ($totalContracts > 0 ? 100.0 : 0.0);

        $averageTicket = $totalContracts > 0
            ? round($totalSales / $totalContracts, 2)
            : 0.0;

        return [
            'active_sellers' => $activeSellers,
            'total_contracts' => $totalContracts,
            'total_leads' => $totalLeads,
            'overall_conversion' => $overallConversion,
            'total_sales' => $totalSales,
            'total_discounts' => $totalDiscounts,
            'average_ticket' => $averageTicket,
            'total_paid' => $totalPaid,
            'total_pending' => $totalPending,
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $user = auth()->user();
        abort_unless(
            $user instanceof User && ($user->hasRole('super_admin') || $user->can('Export:SalesBySellerReport')),
            403
        );

        $rows = $this->sellersReport();
        $filename = 'vendas-por-vendedor-'.Carbon::parse($this->fromDate)->format('Ymd').'-a-'.Carbon::parse($this->untilDate)->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM para abertura correta no Excel brasileiro
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Vendedor / Captador',
                'Cargo / Função',
                'Leads Atendidos',
                'Matrículas Fechadas',
                'Taxa de Conversão (%)',
                'Faturamento Total (R$)',
                'Descontos Concedidos (R$)',
                'Ticket Médio (R$)',
                'Total Recebido/Baixado (R$)',
                'Saldo a Receber (R$)',
            ], ';');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['name'],
                    $row['role'],
                    $row['leads_count'],
                    $row['contracts_count'],
                    number_format((float) $row['conversion_rate'], 1, ',', '').'%',
                    number_format((float) $row['total_sales'], 2, ',', '.'),
                    number_format((float) $row['total_discounts'], 2, ',', '.'),
                    number_format((float) $row['average_ticket'], 2, ',', '.'),
                    number_format((float) $row['total_paid'], 2, ',', '.'),
                    number_format((float) $row['total_pending'], 2, ',', '.'),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
