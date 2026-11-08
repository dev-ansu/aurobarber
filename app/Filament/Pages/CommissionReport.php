<?php

namespace App\Filament\Pages;

use App\Models\Collaborator;
use App\Models\Contract;
use App\Models\User;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

class CommissionReport extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?string $navigationParentItem = 'Comercial';

    protected static ?string $navigationLabel = 'Comissões de Vendas';

    protected static ?string $title = 'Relatório e Fechamento de Comissões';

    protected static ?string $slug = 'commission-report';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected string $view = 'filament.pages.commission-report';

    public string $fromDate = '';

    public string $untilDate = '';

    public string $collaboratorId = '';

    public string $commissionType = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('View:CommissionReport'));
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
    public function commissionTypes(): array
    {
        return [
            Collaborator::COMMISSION_TYPE_FIXED_PER_CONTRACT => 'Fixo por Matrícula (R$)',
            Collaborator::COMMISSION_TYPE_PERCENTAGE_CONTRACT => 'Percentual sobre Vendas (%)',
            Collaborator::COMMISSION_TYPE_PERCENTAGE_RECEIVED => 'Percentual sobre Recebido (%)',
            Collaborator::COMMISSION_TYPE_HOURLY_RATE => 'Hora-Aula (R$)',
            Collaborator::COMMISSION_TYPE_NONE => 'Sem comissão configurada',
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function reportData(): Collection
    {
        $from = Carbon::parse($this->fromDate)->toDateString();
        $until = Carbon::parse($this->untilDate)->toDateString();

        $tenantId = Filament::getTenant()?->id;

        $collaboratorsQuery = Collaborator::query()
            ->when($tenantId, function (Builder $q) use ($tenantId): void {
                $q->where(function (Builder $sub) use ($tenantId): void {
                    $sub->whereHas('person.schools', fn (Builder $sq) => $sq->where('schools.id', $tenantId))
                        ->orWhereHas('user.schools', fn (Builder $sq) => $sq->where('schools.id', $tenantId));
                });
            })
            ->with('person')
            ->when(filled($this->collaboratorId), fn (Builder $q) => $q->where('id', (int) $this->collaboratorId))
            ->when(filled($this->commissionType), fn (Builder $q) => $q->where('commission_type', $this->commissionType));

        $collaborators = $collaboratorsQuery->get();

        $data = collect();

        foreach ($collaborators as $collab) {
            // Contratos fechados no período
            $contracts = Contract::query()
                ->where('collaborator_id', $collab->id)
                ->whereBetween('created_at', [$from.' 00:00:00', $until.' 23:59:59'])
                ->whereNotIn('status', ['canceled', 'draft'])
                ->with([
                    'student',
                    'installments',
                    'items.formation',
                    'items.contractCourses.course',
                ])
                ->get();

            $contractsCount = $contracts->count();
            $totalSales = (float) $contracts->sum('total_value');

            $totalPaid = (float) $contracts->sum(function (Contract $contract): float {
                return (float) $contract->installments
                    ->whereIn('status', ['paid', 'partial'])
                    ->sum(fn ($i) => (float) ($i->amount_paid ?: $i->amount));
            });

            // Detalhamento dos contratos
            $contractItems = [];
            foreach ($contracts as $contract) {
                $contractValue = (float) $contract->total_value;
                $contractPaid = (float) $contract->installments
                    ->whereIn('status', ['paid', 'partial'])
                    ->sum(fn ($i) => (float) ($i->amount_paid ?: $i->amount));

                $rate = (float) $collab->commission_rate;
                $itemCommission = match ($collab->commission_type) {
                    Collaborator::COMMISSION_TYPE_FIXED_PER_CONTRACT => $rate,
                    Collaborator::COMMISSION_TYPE_PERCENTAGE_CONTRACT => round($contractValue * ($rate / 100), 2),
                    Collaborator::COMMISSION_TYPE_PERCENTAGE_RECEIVED => round($contractPaid * ($rate / 100), 2),
                    default => 0.0,
                };

                $course = $contract->items->first()?->formation?->name
                    ?? $contract->items->first()?->contractCourses?->first()?->course?->name
                    ?? 'Curso Regular';

                $contractItems[] = [
                    'id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                    'student_name' => $contract->student?->name ?? 'Aluno não identificado',
                    'course' => $course,
                    'date' => $contract->created_at?->format('d/m/Y') ?? '—',
                    'total_value' => $contractValue,
                    'paid_value' => $contractPaid,
                    'commission_amount' => $itemCommission,
                    'status' => $contract->status,
                ];
            }

            $totalCommission = $collab->calculateCommission($contractsCount, $totalSales, $totalPaid);

            $data->push([
                'collaborator_id' => $collab->id,
                'name' => $collab->person?->name ?? 'Colaborador #'.$collab->id,
                'role' => $collab->role ?? 'Colaborador',
                'pix' => $collab->pix,
                'commission_type' => $collab->commission_type,
                'rule_label' => $collab->formatted_commission_rule,
                'commission_rate' => (float) $collab->commission_rate,
                'hourly_rate' => (float) $collab->hourly_rate,
                'contracts_count' => $contractsCount,
                'total_sales' => $totalSales,
                'total_paid' => $totalPaid,
                'total_commission' => $totalCommission,
                'contracts' => $contractItems,
            ]);
        }

        return $data->sortByDesc('total_commission')->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function totals(): array
    {
        $data = $this->reportData();

        return [
            'collaborators_count' => $data->where('total_commission', '>', 0)->count(),
            'contracts_count' => $data->sum('contracts_count'),
            'total_sales' => $data->sum('total_sales'),
            'total_paid' => $data->sum('total_paid'),
            'total_commission' => $data->sum('total_commission'),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $user = auth()->user();
        abort_unless(
            $user instanceof User && ($user->hasRole('super_admin') || $user->can('Export:CommissionReport')),
            403
        );

        $rows = $this->reportData();
        $filename = 'comissoes-'.Carbon::parse($this->fromDate)->format('Ymd').'-a-'.Carbon::parse($this->untilDate)->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM para Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Colaborador',
                'Cargo',
                'Regra de Comissão',
                'Matrículas Realizadas',
                'Faturamento Total (R$)',
                'Valor Recebido (R$)',
                'Comissão Calculada (R$)',
                'Chave PIX',
            ], ';');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['name'],
                    $row['role'],
                    $row['rule_label'],
                    $row['contracts_count'],
                    number_format((float) $row['total_sales'], 2, ',', '.'),
                    number_format((float) $row['total_paid'], 2, ',', '.'),
                    number_format((float) $row['total_commission'], 2, ',', '.'),
                    $row['pix'] ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
