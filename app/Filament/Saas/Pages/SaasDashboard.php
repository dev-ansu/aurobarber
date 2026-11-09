<?php

declare(strict_types=1);

namespace App\Filament\Saas\Pages;

use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Professional;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;

class SaasDashboard extends Dashboard
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Visão Geral SaaS';

    protected static ?string $title = 'Painel de Controle SaaS Master';

    protected static ?int $navigationSort = 1;

    protected static string $routePath = '/';

    protected string $view = 'filament.saas.pages.dashboard';

    public int $totalBarbershops = 0;

    public int $activeBarbershops = 0;

    public int $graceBarbershops = 0;

    public int $suspendedBarbershops = 0;

    public int $totalBarbers = 0;

    public int $totalAppointments = 0;

    public float $totalGrossRevenue = 0.0;

    /** @var array<int, array<string, mixed>> */
    public array $recentBarbershops = [];

    public ?int $editingBarbershopId = null;

    public ?string $editingBarbershopName = null;

    public ?string $newDueDate = null;

    public function mount(): void
    {
        $this->loadMetrics();
    }

    public function openDueDateModal(int $barbershopId): void
    {
        abort_unless(auth()->user()?->role === 'master_admin', 403);

        $b = Barbershop::find($barbershopId);
        if (! $b) {
            return;
        }

        $this->editingBarbershopId = $barbershopId;
        $this->editingBarbershopName = $b->name;
        $this->newDueDate = $b->license_expires_at?->format('Y-m-d') ?? now()->addDays(30)->format('Y-m-d');
    }

    public function closeDueDateModal(): void
    {
        $this->editingBarbershopId = null;
        $this->editingBarbershopName = null;
        $this->newDueDate = null;
    }

    public function saveDueDate(): void
    {
        abort_unless(auth()->user()?->role === 'master_admin', 403);

        if (! $this->editingBarbershopId || ! $this->newDueDate) {
            return;
        }

        $b = Barbershop::find($this->editingBarbershopId);
        if ($b) {
            $b->license_expires_at = Carbon::parse($this->newDueDate)->endOfDay();
            $b->license_status = 'active';
            $b->is_active = true;
            $b->suspended_at = null;
            $b->suspension_reason = null;
            $b->save();

            Notification::make()
                ->title('Vencimento atualizado com sucesso!')
                ->body("A barbearia {$b->name} teve o vencimento ajustado para {$b->license_expires_at->format('d/m/Y')}. O acesso está regularizado!")
                ->success()
                ->send();
        }

        $this->closeDueDateModal();
        $this->loadMetrics();
    }

    public function loadMetrics(): void
    {
        abort_unless(auth()->user()?->role === 'master_admin', 403);

        $allBarbershops = Barbershop::with('owner')->latest('id')->get();

        $this->totalBarbershops = $allBarbershops->count();
        $this->activeBarbershops = $allBarbershops->filter(fn ($b) => $b->isLicenseActive() && ! $b->isInGracePeriod())->count();
        $this->graceBarbershops = $allBarbershops->filter(fn ($b) => $b->isInGracePeriod())->count();
        $this->suspendedBarbershops = $allBarbershops->filter(fn ($b) => $b->isSuspended() || ! $b->isLicenseActive())->count();
        $this->totalBarbers = Professional::count();
        $this->totalAppointments = Appointment::count();
        $this->totalGrossRevenue = (float) Appointment::where('status', 'completed')->sum('service_price');

        $this->recentBarbershops = $allBarbershops
            ->take(15)
            ->map(fn (Barbershop $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'city' => $b->city ?? 'Não informada',
                'owner_name' => $b->owner?->name ?? 'Sem proprietário',
                'owner_email' => $b->owner?->email ?? '-',
                'created_at' => $b->created_at?->format('d/m/Y H:i') ?? '-',
                'is_active' => (bool) $b->is_active,
                'is_suspended' => $b->isSuspended(),
                'is_in_grace' => $b->isInGracePeriod(),
                'grace_days_remaining' => $b->graceDaysRemaining(),
                'days_remaining' => $b->daysRemaining(),
                'expires_at_formatted' => $b->license_expires_at?->format('d/m/Y') ?? 'Não definido',
                'monthly_price' => number_format((float) ($b->monthly_price ?? 120.00), 2, ',', '.'),
                'admin_url' => null,
            ])
            ->toArray();
    }

    public function renew30(int $barbershopId): void
    {
        abort_unless(auth()->user()?->role === 'master_admin', 403);

        $b = Barbershop::find($barbershopId);
        if (! $b) {
            return;
        }

        $b->renew(30);
        $this->loadMetrics();

        Notification::make()
            ->title('Licença renovada por +30 dias!')
            ->body("A barbearia {$b->name} teve o vencimento estendido para {$b->license_expires_at->format('d/m/Y')}.")
            ->success()
            ->send();
    }

    public function toggleStatus(int $barbershopId): void
    {
        abort_unless(auth()->user()?->role === 'master_admin', 403);

        $b = Barbershop::find($barbershopId);
        if (! $b) {
            return;
        }

        if ($b->isSuspended()) {
            $b->activate();
            Notification::make()
                ->title('Barbearia reativada!')
                ->body("O acesso de {$b->name} foi restabelecido.")
                ->success()
                ->send();
        } else {
            $b->suspend('Suspensão manual por inadimplência via Painel SaaS');
            Notification::make()
                ->title('Barbearia suspensa!')
                ->body("O acesso de {$b->name} foi bloqueado imediatamente.")
                ->warning()
                ->send();
        }

        $this->loadMetrics();
    }
}
