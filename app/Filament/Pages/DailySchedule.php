<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Service;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;

class DailySchedule extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $navigationGroup = 'Atendimento';

    protected static ?string $navigationLabel = 'Agenda Diária';

    protected static ?string $title = 'Agenda Diária da Equipe';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.daily-schedule';

    public function getMaxContentWidth(): Width | string | null
    {
        return 'full';
    }

    public string $selectedDate = '';

    // Estado do Modal de Encaixe Rápido
    public bool $quickModalOpen = false;
    public ?int $quickProfessionalId = null;
    public ?int $quickServiceId = null;
    public string $quickTime = '10:00';
    public string $quickClientName = '';
    public string $quickClientPhone = '';
    public string $activeModalTab = 'atendimento'; // atendimento | cliente

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function setDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function previousDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->subDay()->format('Y-m-d');
    }

    public function nextDay(): void
    {
        $this->selectedDate = Carbon::parse($this->selectedDate)->addDay()->format('Y-m-d');
    }

    public function setToday(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function getProfessionalsProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (!$tenant) {
            return collect();
        }

        return $tenant->professionals()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }

    public function getServicesProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (!$tenant) {
            return collect();
        }

        return $tenant->services()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
    }

    public function getDailyAppointmentsProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (!$tenant) {
            return collect();
        }

        return Appointment::where('barbershop_id', $tenant->id)
            ->whereDate('scheduled_at', $this->selectedDate)
            ->with(['professional', 'service', 'services'])
            ->orderBy('scheduled_at')
            ->get();
    }

    public function getAppointmentsByBarberProperty(): array
    {
        $grouped = [];
        $appointments = $this->dailyAppointments;

        foreach ($this->professionals as $prof) {
            $grouped[$prof->id] = $appointments->where('professional_id', $prof->id)->values();
        }

        return $grouped;
    }

    public function getDayStatsProperty(): array
    {
        $appts = $this->dailyAppointments;

        return [
            'total_count' => $appts->count(),
            'completed_count' => $appts->where('status', 'completed')->count(),
            'in_progress_count' => $appts->where('status', 'in_progress')->count(),
            'total_revenue' => $appts->where('status', '!=', 'cancelled')->sum('service_price'),
            'realized_revenue' => $appts->where('status', 'completed')->sum('service_price'),
        ];
    }

    public function updateStatus(int $appointmentId, string $status): void
    {
        $appt = Appointment::findOrFail($appointmentId);
        $appt->status = $status;

        if ($status === 'completed' && $appt->payment_status === 'pending') {
            $appt->payment_status = 'paid';
        }

        $appt->save();

        Notification::make()
            ->title('Status atualizado para: ' . $appt->status_label)
            ->success()
            ->send();
    }

    public function openQuickModal(?int $professionalId = null, ?string $time = null): void
    {
        $this->quickProfessionalId = $professionalId ?: $this->professionals->first()?->id;
        $this->quickServiceId = $this->services->first()?->id;
        $this->quickTime = $time ?: '14:00';
        $this->quickClientName = '';
        $this->quickClientPhone = '';
        $this->activeModalTab = 'atendimento';
        $this->quickModalOpen = true;
    }

    public function saveQuickAppointment(): void
    {
        $this->validate([
            'quickProfessionalId' => 'required|exists:professionals,id',
            'quickServiceId' => 'required|exists:services,id',
            'quickTime' => 'required|date_format:H:i',
            'quickClientName' => 'required|string|min:2|max:255',
            'quickClientPhone' => 'required|string|min:8|max:20',
        ]);

        $tenant = filament()->getTenant();
        $service = Service::findOrFail($this->quickServiceId);
        $professional = Professional::findOrFail($this->quickProfessionalId);

        $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', "{$this->selectedDate} {$this->quickTime}");
        $endsAt = $scheduledAt->copy()->addMinutes($service->duration_minutes);

        $commissionRate = $professional->commission_rate ?: 50.00;
        $commissionAmount = round(($service->price * $commissionRate) / 100, 2);

        $appt = Appointment::create([
            'barbershop_id' => $tenant->id,
            'professional_id' => $professional->id,
            'service_id' => $service->id,
            'client_name' => trim($this->quickClientName),
            'client_phone' => trim($this->quickClientPhone),
            'scheduled_at' => $scheduledAt,
            'ends_at' => $endsAt,
            'status' => 'confirmed',
            'service_price' => $service->price,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'payment_status' => 'pending',
            'notes' => 'Encaixe rápido realizado via painel',
        ]);

        $appt->services()->sync([
            $service->id => [
                'price' => $service->price,
                'duration_minutes' => $service->duration_minutes,
            ],
        ]);

        $this->quickModalOpen = false;

        Notification::make()
            ->title('Encaixe agendado com sucesso para ' . $professional->display_name)
            ->success()
            ->send();
    }
}
