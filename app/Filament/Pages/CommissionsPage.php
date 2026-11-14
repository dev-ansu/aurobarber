<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\CommissionPayout;
use App\Models\ProductSale;
use App\Models\Professional;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommissionsPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static string | \UnitEnum | null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Comissões da Equipe';

    protected static ?string $title = 'Fechamento de Comissões por Barbeiro';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.commissions-page';

    public function getMaxContentWidth(): Width | string | null
    {
        return 'full';
    }

    // Filtros
    public ?int $selectedProfessionalId = null;
    public string $periodPreset = 'week'; // today, week, month, custom
    public string $startDate = '';
    public string $endDate = '';
    public string $statusFilter = 'pending'; // pending, paid, all
    public string $activeTab = 'appointments'; // appointments, history

    // Modal de Liquidação / Pagamento de Comissão
    public bool $payoutModalOpen = false;
    public string $payoutMethod = 'pix'; // pix, dinheiro, transferencia
    public string $payoutNotes = '';

    // Modal de Impressão do Recibo de Comissão
    public bool $printPayoutModalOpen = false;
    public ?CommissionPayout $selectedPayoutToPrint = null;

    public function mount(): void
    {
        $this->applyPreset('week');
        $tenant = filament()->getTenant();
        if ($tenant) {
            if (auth()->user()?->isRestrictedBarber()) {
                $this->selectedProfessionalId = auth()->user()->resolveProfessionalId($tenant->id);
            } else {
                $this->selectedProfessionalId = $tenant->professionals()->where('is_active', true)->first()?->id;
            }
        }
    }

    public function applyPreset(string $preset): void
    {
        $this->periodPreset = $preset;

        match ($preset) {
            'today' => [
                $this->startDate = now()->format('Y-m-d'),
                $this->endDate = now()->format('Y-m-d'),
            ],
            'week' => [
                $this->startDate = now()->startOfWeek()->format('Y-m-d'),
                $this->endDate = now()->endOfWeek()->format('Y-m-d'),
            ],
            'month' => [
                $this->startDate = now()->startOfMonth()->format('Y-m-d'),
                $this->endDate = now()->endOfMonth()->format('Y-m-d'),
            ],
            default => null,
        };
    }

    public function getProfessionalsProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (!$tenant) {
            return collect();
        }

        $query = $tenant->professionals()->where('is_active', true)->orderBy('display_order');

        if (auth()->user()?->isRestrictedBarber()) {
            $proId = auth()->user()->resolveProfessionalId($tenant->id) ?? 0;
            $query->where('id', $proId);
        }

        return $query->get();
    }

    public function getSelectedProfessionalProperty(): ?Professional
    {
        if (auth()->user()?->isRestrictedBarber()) {
            $proId = auth()->user()->resolveProfessionalId();
            return $proId ? Professional::find($proId) : null;
        }

        if (!$this->selectedProfessionalId) {
            return null;
        }

        return Professional::find($this->selectedProfessionalId);
    }

    public function getAppointmentsProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (!$tenant) {
            return collect();
        }

        $query = Appointment::where('barbershop_id', $tenant->id)
            ->where('status', 'completed')
            ->whereDate('scheduled_at', '>=', $this->startDate)
            ->whereDate('scheduled_at', '<=', $this->endDate)
            ->with(['professional', 'service', 'services', 'commissionPayout'])
            ->orderBy('scheduled_at', 'desc');

        if (auth()->user()?->isRestrictedBarber()) {
            $proId = auth()->user()->resolveProfessionalId($tenant->id) ?? 0;
            $query->where('professional_id', $proId);
        } elseif ($this->selectedProfessionalId) {
            $query->where('professional_id', $this->selectedProfessionalId);
        }

        if ($this->statusFilter === 'pending') {
            $query->where(function ($q) {
                $q->whereNull('commission_status')->orWhere('commission_status', 'pending');
            });
        } elseif ($this->statusFilter === 'paid') {
            $query->where('commission_status', 'paid');
        }

        return $query->get();
    }

    public function getProductSalesProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (!$tenant) {
            return collect();
        }

        $query = ProductSale::where('barbershop_id', $tenant->id)
            ->whereDate('created_at', '>=', $this->startDate)
            ->whereDate('created_at', '<=', $this->endDate)
            ->with(['product', 'professional', 'commissionPayout'])
            ->orderBy('created_at', 'desc');

        if (auth()->user()?->isRestrictedBarber()) {
            $proId = auth()->user()->resolveProfessionalId($tenant->id) ?? 0;
            $query->where('professional_id', $proId);
        } elseif ($this->selectedProfessionalId) {
            $query->where('professional_id', $this->selectedProfessionalId);
        } else {
            $query->whereNotNull('professional_id');
        }

        if ($this->statusFilter === 'pending') {
            $query->where('commission_status', 'pending');
        } elseif ($this->statusFilter === 'paid') {
            $query->where('commission_status', 'paid');
        }

        return $query->get();
    }

    public function getSummaryStatsProperty(): array
    {
        $appts = $this->appointments;
        $prodSales = $this->productSales;

        $servicesGross = (float) $appts->sum('service_price');
        $servicesCommission = 0.0;
        foreach ($appts as $a) {
            $serviceComm = (float) $a->commission_amount - (float) $a->products_commission_amount;
            $servicesCommission += max(0, $serviceComm);
        }

        $productsGross = (float) $prodSales->sum('total_price');
        $productsCommission = (float) $prodSales->sum('commission_amount');

        $totalGross = $servicesGross + $productsGross;
        $totalCommission = $servicesCommission + $productsCommission;
        $barbershopCut = $totalGross - $totalCommission;
        $count = $appts->count();
        $productsCount = (int) $prodSales->sum('quantity');

        return [
            'count' => $count,
            'products_count' => $productsCount,
            'services_gross' => $servicesGross,
            'services_commission' => $servicesCommission,
            'products_gross' => $productsGross,
            'products_commission' => $productsCommission,
            'total_gross' => $totalGross,
            'total_commission' => $totalCommission,
            'barbershop_cut' => $barbershopCut,
            'avg_ticket' => $count > 0 ? $servicesGross / $count : 0,
        ];
    }

    public function getPayoutsHistoryProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (!$tenant) {
            return collect();
        }

        $query = CommissionPayout::where('barbershop_id', $tenant->id)
            ->with(['professional'])
            ->orderBy('created_at', 'desc');

        if (auth()->user()?->isRestrictedBarber()) {
            $proId = auth()->user()->resolveProfessionalId($tenant->id) ?? 0;
            $query->where('professional_id', $proId);
        } elseif ($this->selectedProfessionalId) {
            $query->where('professional_id', $this->selectedProfessionalId);
        }

        return $query->get();
    }

    public function openPayoutModal(): void
    {
        if (auth()->user()?->isRestrictedBarber()) {
            return;
        }
        if (!$this->selectedProfessionalId) {
            Notification::make()
                ->title('Selecione um barbeiro específico para realizar o fechamento.')
                ->warning()
                ->send();
            return;
        }

        $pendingApptsCount = $this->appointments->where('commission_status', '!=', 'paid')->count();
        $pendingProdSalesCount = $this->productSales->where('commission_status', '!=', 'paid')->count();

        if ($pendingApptsCount === 0 && $pendingProdSalesCount === 0) {
            Notification::make()
                ->title('Não há comissões em aberto (cortes ou produtos) para liquidar no período selecionado.')
                ->warning()
                ->send();
            return;
        }

        $this->payoutMethod = 'pix';
        $this->payoutNotes = '';
        $this->payoutModalOpen = true;
    }

    public function confirmPayout(): void
    {
        $tenant = filament()->getTenant();
        $prof = $this->selectedProfessional;

        if (!$tenant || !$prof) {
            return;
        }

        $pendingAppts = $this->appointments->where('commission_status', '!=', 'paid');
        $pendingProdSales = $this->productSales->where('commission_status', '!=', 'paid');

        if ($pendingAppts->isEmpty() && $pendingProdSales->isEmpty()) {
            $this->payoutModalOpen = false;
            return;
        }

        DB::transaction(function () use ($tenant, $prof, $pendingAppts, $pendingProdSales) {
            $servicesGross = (float) $pendingAppts->sum('service_price');
            $servicesCommission = 0.0;
            foreach ($pendingAppts as $a) {
                $serviceComm = (float) $a->commission_amount - (float) $a->products_commission_amount;
                $servicesCommission += max(0, $serviceComm);
            }

            $productsGross = (float) $pendingProdSales->sum('total_price');
            $productsCommission = (float) $pendingProdSales->sum('commission_amount');

            $totalGross = $servicesGross + $productsGross;
            $totalCommission = $servicesCommission + $productsCommission;
            $barbershopCut = $totalGross - $totalCommission;

            $payout = CommissionPayout::create([
                'barbershop_id' => $tenant->id,
                'professional_id' => $prof->id,
                'period_start' => $this->startDate,
                'period_end' => $this->endDate,
                'appointments_count' => $pendingAppts->count(),
                'services_gross' => $servicesGross,
                'services_commission' => $servicesCommission,
                'products_gross' => $productsGross,
                'products_commission' => $productsCommission,
                'products_count' => (int) $pendingProdSales->sum('quantity'),
                'total_gross' => $totalGross,
                'total_commission' => $totalCommission,
                'barbershop_cut' => $barbershopCut,
                'payment_method' => $this->payoutMethod,
                'notes' => $this->payoutNotes ?: null,
                'paid_at' => now(),
            ]);

            if ($pendingAppts->isNotEmpty()) {
                Appointment::whereIn('id', $pendingAppts->pluck('id'))->update([
                    'commission_status' => 'paid',
                    'commission_payout_id' => $payout->id,
                    'commission_paid_at' => now(),
                ]);
            }

            if ($pendingProdSales->isNotEmpty()) {
                ProductSale::whereIn('id', $pendingProdSales->pluck('id'))->update([
                    'commission_status' => 'paid',
                    'commission_payout_id' => $payout->id,
                    'commission_paid_at' => now(),
                ]);
            }
        });

        $this->payoutModalOpen = false;

        Notification::make()
            ->title('Comissão liquidada com sucesso!')
            ->body('Recibo gerado e comissões de serviços e produtos marcadas como pagas.')
            ->success()
            ->send();
    }

    public function openPrintPayoutModal(int $payoutId): void
    {
        $this->selectedPayoutToPrint = CommissionPayout::with(['professional', 'barbershop', 'appointments.service', 'productSales.product'])->findOrFail($payoutId);
        $this->printPayoutModalOpen = true;
    }
}
