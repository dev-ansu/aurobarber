<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\CommissionPayout;
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
            $this->selectedProfessionalId = $tenant->professionals()->where('is_active', true)->first()?->id;
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

        return $tenant->professionals()->where('is_active', true)->orderBy('display_order')->get();
    }

    public function getSelectedProfessionalProperty(): ?Professional
    {
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

        if ($this->selectedProfessionalId) {
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

    public function getSummaryStatsProperty(): array
    {
        $appts = $this->appointments;

        $totalGross = (float) $appts->sum('service_price');
        $totalCommission = (float) $appts->sum('commission_amount');
        $barbershopCut = $totalGross - $totalCommission;
        $count = $appts->count();

        return [
            'count' => $count,
            'total_gross' => $totalGross,
            'total_commission' => $totalCommission,
            'barbershop_cut' => $barbershopCut,
            'avg_ticket' => $count > 0 ? $totalGross / $count : 0,
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

        if ($this->selectedProfessionalId) {
            $query->where('professional_id', $this->selectedProfessionalId);
        }

        return $query->get();
    }

    public function openPayoutModal(): void
    {
        if (!$this->selectedProfessionalId) {
            Notification::make()
                ->title('Selecione um barbeiro específico para realizar o fechamento.')
                ->warning()
                ->send();
            return;
        }

        $pendingCount = $this->appointments->where('commission_status', '!=', 'paid')->count();
        if ($pendingCount === 0) {
            Notification::make()
                ->title('Não há comissões em aberto para liquidar no período selecionado.')
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
        if ($pendingAppts->isEmpty()) {
            $this->payoutModalOpen = false;
            return;
        }

        DB::transaction(function () use ($tenant, $prof, $pendingAppts) {
            $totalGross = (float) $pendingAppts->sum('service_price');
            $totalCommission = (float) $pendingAppts->sum('commission_amount');
            $barbershopCut = $totalGross - $totalCommission;

            $payout = CommissionPayout::create([
                'barbershop_id' => $tenant->id,
                'professional_id' => $prof->id,
                'period_start' => $this->startDate,
                'period_end' => $this->endDate,
                'appointments_count' => $pendingAppts->count(),
                'total_gross' => $totalGross,
                'total_commission' => $totalCommission,
                'barbershop_cut' => $barbershopCut,
                'payment_method' => $this->payoutMethod,
                'notes' => $this->payoutNotes ?: null,
                'paid_at' => now(),
            ]);

            Appointment::whereIn('id', $pendingAppts->pluck('id'))->update([
                'commission_status' => 'paid',
                'commission_payout_id' => $payout->id,
                'commission_paid_at' => now(),
            ]);
        });

        $this->payoutModalOpen = false;

        Notification::make()
            ->title('Comissão liquidada com sucesso!')
            ->body('Recibo gerado e atendimentos marcados como pagos.')
            ->success()
            ->send();
    }

    public function openPrintPayoutModal(int $payoutId): void
    {
        $this->selectedPayoutToPrint = CommissionPayout::with(['professional', 'barbershop', 'appointments.service'])->findOrFail($payoutId);
        $this->printPayoutModalOpen = true;
    }
}
