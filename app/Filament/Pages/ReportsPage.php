<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Services\SensitiveClientDataAccess;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;

class ReportsPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Relatórios & Ranking';

    protected static ?string $title = 'Relatórios Estratégicos & Desempenho';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.reports-page';

    public static function canAccess(): bool
    {
        return ! auth()->user()?->isRestrictedBarber();
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return 'full';
    }

    public string $activeTab = 'ranking'; // ranking, services, absent

    public string $periodPreset = 'month'; // month, 30days, year

    public string $startDate = '';

    public string $endDate = '';

    public int $absentDays = 45; // dias sem retornar

    public function mount(): void
    {
        $this->applyPreset('month');
    }

    public function applyPreset(string $preset): void
    {
        $this->periodPreset = $preset;

        match ($preset) {
            'month' => [
                $this->startDate = now()->startOfMonth()->format('Y-m-d'),
                $this->endDate = now()->endOfMonth()->format('Y-m-d'),
            ],
            '30days' => [
                $this->startDate = now()->subDays(30)->format('Y-m-d'),
                $this->endDate = now()->format('Y-m-d'),
            ],
            'year' => [
                $this->startDate = now()->startOfYear()->format('Y-m-d'),
                $this->endDate = now()->endOfYear()->format('Y-m-d'),
            ],
            default => null,
        };
    }

    // 1. Ranking da Equipe
    public function getBarbersRankingProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return collect();
        }

        $appts = Appointment::where('barbershop_id', $tenant->id)
            ->where('status', 'completed')
            ->whereDate('scheduled_at', '>=', $this->startDate)
            ->whereDate('scheduled_at', '<=', $this->endDate)
            ->get();

        return $tenant->professionals()->where('is_active', true)->get()->map(function ($pro) use ($appts) {
            $proAppts = $appts->where('professional_id', $pro->id);
            $count = $proAppts->count();
            $totalGross = (float) $proAppts->sum('service_price');
            $totalCommission = (float) $proAppts->sum('commission_amount');
            $avgTicket = $count > 0 ? $totalGross / $count : 0.00;

            return [
                'professional' => $pro,
                'count' => $count,
                'total_gross' => $totalGross,
                'total_commission' => $totalCommission,
                'avg_ticket' => $avgTicket,
            ];
        })->sortByDesc('total_gross')->values();
    }

    // 2. Serviços Mais Lucrativos
    public function getTopServicesProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return collect();
        }

        return Appointment::where('barbershop_id', $tenant->id)
            ->where('status', 'completed')
            ->whereDate('scheduled_at', '>=', $this->startDate)
            ->whereDate('scheduled_at', '<=', $this->endDate)
            ->with('service')
            ->get()
            ->groupBy('service_id')
            ->map(function ($items, $serviceId) {
                $service = $items->first()->service;
                $count = $items->count();
                $totalRevenue = (float) $items->sum('service_price');

                return [
                    'name' => $service?->name ?? 'Serviço Personalizado',
                    'count' => $count,
                    'total_revenue' => $totalRevenue,
                    'avg_price' => $count > 0 ? $totalRevenue / $count : 0.00,
                ];
            })
            ->sortByDesc('total_revenue')
            ->values();
    }

    // 3. Clientes Ausentes / Sumidos (Recuperação de Clientes)
    public function getAbsentClientsProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return collect();
        }

        $cutoffDate = now()->subDays($this->absentDays)->format('Y-m-d');

        // Busca o último atendimento de cada cliente
        $canViewContacts = app(SensitiveClientDataAccess::class)->canView();

        return Appointment::where('barbershop_id', $tenant->id)
            ->where('status', 'completed')
            ->select('client_name', 'client_phone_hash')
            ->selectRaw('MAX(id) as id, MAX(client_phone) as client_phone, MAX(scheduled_at) as last_appointment, COUNT(*) as total_cuts')
            ->groupBy('client_name', 'client_phone_hash')
            ->havingRaw('MAX(scheduled_at) <= ?', [$cutoffDate])
            ->orderBy('last_appointment', 'asc')
            ->limit(50)
            ->get()
            ->map(function ($record) use ($canViewContacts) {
                $lastDate = Carbon::parse($record->last_appointment);
                $daysAway = (int) $lastDate->diffInDays(now());

                $phone = $canViewContacts ? $record->client_phone : null;
                if ($phone) {
                    app(SensitiveClientDataAccess::class)->record($record, ['client_phone']);
                }

                $whatsappUrl = null;
                if ($phone) {
                    $cleanPhone = preg_replace('/\D/', '', $phone);
                    $phone55 = str_starts_with($cleanPhone, '55') ? $cleanPhone : '55'.$cleanPhone;
                    $encodedMsg = urlencode("Fala {$record->client_name}, tudo bem? Notamos que já faz {$daysAway} dias desde o seu último corte! Que tal agendar um horário para dar um trato no visual esta semana?");
                    $whatsappUrl = "https://api.whatsapp.com/send?phone={$phone55}&text={$encodedMsg}";
                }

                return [
                    'client_name' => $record->client_name,
                    'client_phone' => $phone
                        ? $phone
                        : 'Contato protegido',
                    'last_appointment' => $lastDate,
                    'days_away' => $daysAway,
                    'total_cuts' => $record->total_cuts,
                    'whatsapp_url' => $whatsappUrl,
                ];
            });
    }
}
