<?php

declare(strict_types=1);

namespace App\Filament\Saas\Pages;

use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\Professional;
use App\Models\User;
use BackedEnum;
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

    public function mount(): void
    {
        $this->loadMetrics();
    }

    public function loadMetrics(): void
    {
        $allBarbershops = Barbershop::with('owner')->latest('id')->get();

        $this->totalBarbershops = $allBarbershops->count();
        $this->activeBarbershops = $allBarbershops->filter(fn ($b) => $b->isLicenseActive() && !$b->isInGracePeriod())->count();
        $this->graceBarbershops = $allBarbershops->filter(fn ($b) => $b->isInGracePeriod())->count();
        $this->suspendedBarbershops = $allBarbershops->filter(fn ($b) => $b->isSuspended() || !$b->isLicenseActive())->count();
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
                'admin_url' => url('/admin/' . $b->slug),
            ])
            ->toArray();
    }

    public function renew30(int $barbershopId): void
    {
        $b = Barbershop::find($barbershopId);
        if (!$b) {
            return;
        }

        $b->renew(30);
        $this->loadMetrics();

        \Filament\Notifications\Notification::make()
            ->title("Licença renovada por +30 dias!")
            ->body("A barbearia {$b->name} teve o vencimento estendido para {$b->license_expires_at->format('d/m/Y')}.")
            ->success()
            ->send();
    }

    public function toggleStatus(int $barbershopId): void
    {
        $b = Barbershop::find($barbershopId);
        if (!$b) {
            return;
        }

        if ($b->isSuspended()) {
            $b->activate();
            \Filament\Notifications\Notification::make()
                ->title("Barbearia reativada!")
                ->body("O acesso de {$b->name} foi restabelecido.")
                ->success()
                ->send();
        } else {
            $b->suspend('Suspensão manual por inadimplência via Painel SaaS');
            \Filament\Notifications\Notification::make()
                ->title("Barbearia suspensa!")
                ->body("O acesso de {$b->name} foi bloqueado imediatamente.")
                ->warning()
                ->send();
        }

        $this->loadMetrics();
    }
}
