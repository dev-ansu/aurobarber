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
        $this->totalBarbershops = Barbershop::count();
        $this->activeBarbershops = Barbershop::where('is_active', true)->count();
        $this->totalBarbers = Professional::count();
        $this->totalAppointments = Appointment::count();
        $this->totalGrossRevenue = (float) Appointment::where('status', 'completed')->sum('service_price');

        $this->recentBarbershops = Barbershop::with('owner')
            ->latest('id')
            ->take(10)
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'city' => $b->city ?? 'Não informada',
                'owner_name' => $b->owner?->name ?? 'Sem proprietário',
                'owner_email' => $b->owner?->email ?? '-',
                'created_at' => $b->created_at?->format('d/m/Y H:i') ?? '-',
                'is_active' => (bool) $b->is_active,
                'admin_url' => url('/admin/' . $b->slug),
            ])
            ->toArray();
    }
}
