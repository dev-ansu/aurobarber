<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;

class CashRegisterPage extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string | \UnitEnum | null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Fechamento de Caixa';

    protected static ?string $title = 'Fechamento de Caixa Diário';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.cash-register-page';

    public function getMaxContentWidth(): Width | string | null
    {
        return 'full';
    }

    public string $selectedDate = '';

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function setToday(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
    }

    public function setYesterday(): void
    {
        $this->selectedDate = now()->subDay()->format('Y-m-d');
    }

    public function getDailyTransactionsProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (!$tenant) {
            return collect();
        }

        return Appointment::where('barbershop_id', $tenant->id)
            ->whereDate('scheduled_at', $this->selectedDate)
            ->where('status', 'completed')
            ->with(['professional', 'service', 'services'])
            ->orderBy('scheduled_at', 'desc')
            ->get();
    }

    public function getCashStatsProperty(): array
    {
        $txs = $this->dailyTransactions;

        $pix = (float) $txs->where('payment_method', 'pix')->sum('service_price');
        $dinheiro = (float) $txs->where('payment_method', 'dinheiro')->sum('service_price');
        $debito = (float) $txs->where('payment_method', 'debito')->sum('service_price');
        $credito = (float) $txs->where('payment_method', 'credito')->sum('service_price');
        $outros = (float) $txs->whereNotIn('payment_method', ['pix', 'dinheiro', 'debito', 'credito'])->sum('service_price');

        $total = $pix + $dinheiro + $debito + $credito + $outros;

        return [
            'pix' => $pix,
            'dinheiro' => $dinheiro,
            'debito' => $debito,
            'credito' => $credito,
            'outros' => $outros,
            'total' => $total,
            'count' => $txs->count(),
        ];
    }
}
