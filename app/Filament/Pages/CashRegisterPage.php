<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\CashMovement;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;

class CashRegisterPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'Financeiro';

    protected static ?string $navigationLabel = 'Fechamento de Caixa';

    protected static ?string $title = 'Fechamento de Caixa Diário';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.cash-register-page';

    public static function canAccess(): bool
    {
        return ! auth()->user()?->isRestrictedBarber();
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return 'full';
    }

    public string $selectedDate = '';

    // Modal de Movimentação (Sangria / Suprimento / Abertura)
    public bool $movementModalOpen = false;

    public string $movementType = 'outflow'; // opening, inflow, outflow

    public float $movementAmount = 0.00;

    public string $movementDescription = '';

    // Modal de Impressão do Caixa
    public bool $printModalOpen = false;

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
        if (! $tenant) {
            return collect();
        }

        return Appointment::where('barbershop_id', $tenant->id)
            ->whereDate('scheduled_at', $this->selectedDate)
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->with(['professional', 'service', 'services'])
            ->orderBy('scheduled_at', 'desc')
            ->get();
    }

    public function getDailyMovementsProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return collect();
        }

        return CashMovement::where('barbershop_id', $tenant->id)
            ->whereDate('date', $this->selectedDate)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getCashStatsProperty(): array
    {
        $txs = $this->dailyTransactions;
        $movs = $this->dailyMovements;

        $pix = (float) $txs->where('payment_method', 'pix')->sum('total_amount');
        $dinheiroCortes = (float) $txs->where('payment_method', 'dinheiro')->sum('total_amount');
        $debito = (float) $txs->where('payment_method', 'debito')->sum('total_amount');
        $credito = (float) $txs->where('payment_method', 'credito')->sum('total_amount');
        $outros = (float) $txs->whereNotIn('payment_method', ['pix', 'dinheiro', 'debito', 'credito'])->sum('total_amount');

        $faturamentoTotal = $pix + $dinheiroCortes + $debito + $credito + $outros;

        $fundoAbertura = (float) $movs->where('type', 'opening')->sum('amount');
        $suprimentos = (float) $movs->where('type', 'inflow')->sum('amount');
        $sangrias = (float) $movs->where('type', 'outflow')->sum('amount');

        // Dinheiro Físico Esperado na Gaveta
        $saldoDinheiroGaveta = ($fundoAbertura + $suprimentos + $dinheiroCortes) - $sangrias;

        return [
            'pix' => $pix,
            'dinheiro' => $dinheiroCortes,
            'debito' => $debito,
            'credito' => $credito,
            'outros' => $outros,
            'total' => $faturamentoTotal,
            'count' => $txs->count(),
            'fundo_abertura' => $fundoAbertura,
            'suprimentos' => $suprimentos,
            'sangrias' => $sangrias,
            'saldo_dinheiro_gaveta' => $saldoDinheiroGaveta,
        ];
    }

    public function openMovementModal(string $type = 'outflow'): void
    {
        $this->movementType = $type;
        $this->movementAmount = 0.00;
        $this->movementDescription = match ($type) {
            'opening' => 'Fundo de troco inicial do caixa',
            'inflow' => 'Aporte / Troco adicional',
            'outflow' => 'Retirada / Despesa avulsa',
            default => '',
        };
        $this->movementModalOpen = true;
    }

    public function saveMovement(): void
    {
        $this->validate([
            'movementType' => 'required|in:opening,inflow,outflow',
            'movementAmount' => 'required|numeric|min:0.01',
            'movementDescription' => 'required|string|min:3|max:255',
        ]);

        $tenant = filament()->getTenant();
        if (! $tenant) {
            return;
        }

        CashMovement::create([
            'barbershop_id' => $tenant->id,
            'user_id' => auth()->id(),
            'type' => $this->movementType,
            'amount' => $this->movementAmount,
            'description' => $this->movementDescription,
            'date' => $this->selectedDate,
        ]);

        $this->movementModalOpen = false;

        Notification::make()
            ->title('Movimentação de caixa registrada!')
            ->success()
            ->send();
    }

    public function deleteMovement(int $id): void
    {
        $tenant = filament()->getTenant();
        CashMovement::where('barbershop_id', $tenant->id)->where('id', $id)->delete();

        Notification::make()
            ->title('Movimentação removida!')
            ->success()
            ->send();
    }
}
