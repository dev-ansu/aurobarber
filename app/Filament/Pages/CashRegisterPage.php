<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Appointment;
use App\Models\CashMovement;
use App\Models\ProductSale;
use App\Services\PaymentBreakdown;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
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

    public function getDailyProductSalesProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (! $tenant) {
            return collect();
        }

        return ProductSale::query()
            ->where('barbershop_id', $tenant->id)
            ->whereNull('appointment_id')
            ->whereDate('created_at', $this->selectedDate)
            ->with(['product', 'professional'])
            ->orderBy('created_at')
            ->get();
    }

    public function getCashStatsProperty(): array
    {
        $txs = $this->dailyTransactions;
        $movs = $this->dailyMovements;

        $methodTotals = ['pix' => 0.0, 'dinheiro' => 0.0, 'debito' => 0.0, 'credito' => 0.0, 'outros' => 0.0];

        foreach ($txs as $transaction) {
            foreach (PaymentBreakdown::totals(
                $transaction->payment_details,
                $transaction->payment_method,
                (float) $transaction->total_amount,
            ) as $method => $amount) {
                $methodTotals[$method] = ($methodTotals[$method] ?? 0.0) + $amount;
            }
        }

        $sales = $this->dailyProductSales;
        $salesWithDetails = $sales->whereNotNull('sale_group_id')->groupBy('sale_group_id');
        foreach ($salesWithDetails as $saleLines) {
            $firstLine = $saleLines->first();
            foreach (PaymentBreakdown::totals(
                $firstLine->payment_details,
                $firstLine->payment_method,
                (float) $saleLines->sum('total_price'),
            ) as $method => $amount) {
                $methodTotals[$method] = ($methodTotals[$method] ?? 0.0) + $amount;
            }
        }

        foreach ($sales->whereNull('sale_group_id') as $sale) {
            foreach (PaymentBreakdown::totals(
                $sale->payment_details,
                $sale->payment_method,
                (float) $sale->total_price,
            ) as $method => $amount) {
                $methodTotals[$method] = ($methodTotals[$method] ?? 0.0) + $amount;
            }
        }

        $pix = $methodTotals['pix'];
        $dinheiroCortes = $methodTotals['dinheiro'];
        $debito = $methodTotals['debito'];
        $credito = $methodTotals['credito'];
        $outros = $methodTotals['outros'];

        $faturamentoTotal = $pix + $dinheiroCortes + $debito + $credito + $outros;

        $fundoAbertura = (float) $movs->where('type', 'opening')->sum('amount');
        $suprimentos = (float) $movs->where('type', 'inflow')
            ->reject(fn (CashMovement $movement): bool => str_starts_with($movement->description, 'Corte/Serviço:')
                || str_starts_with($movement->description, 'Venda de produto:')
                || str_starts_with($movement->description, 'Venda de produtos no balcão'))
            ->sum('amount');
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
            'count' => $txs->count() + $sales->whereNull('sale_group_id')->count() + $salesWithDetails->count(),
            'fundo_abertura' => $fundoAbertura,
            'suprimentos' => $suprimentos,
            'sangrias' => $sangrias,
            'saldo_dinheiro_gaveta' => $saldoDinheiroGaveta,
        ];
    }

    public function registerProductSaleAction(): Action
    {
        return ProductResource::registerSaleAction();
    }

    public function registerMovementAction(): Action
    {
        return Action::make('registerMovement')
            ->modalHeading('Nova Movimentação de Caixa')
            ->modalIcon('heroicon-o-currency-dollar')
            ->modalSubmitActionLabel('Salvar Movimentação')
            ->modalWidth(Width::Medium)
            ->schema([
                Select::make('type')
                    ->label('Tipo de Movimentação')
                    ->options([
                        'opening' => 'Fundo de Troco (Abertura de Caixa)',
                        'inflow' => 'Suprimento (Entrada de Dinheiro)',
                        'outflow' => 'Sangria (Retirada de Dinheiro da Gaveta)',
                    ])
                    ->required()
                    ->default('outflow'),

                TextInput::make('amount')
                    ->label('Valor (R$)')
                    ->prefix('R$')
                    ->numeric()
                    ->minValue(0.01)
                    ->required(),

                TextInput::make('description')
                    ->label('Motivo / Descrição')
                    ->placeholder('Ex: Compra de café, troco inicial...')
                    ->required()
                    ->maxLength(255),
            ])
            ->mountUsing(function (Schema $schema, Action $action): void {
                $arguments = $action->getArguments();
                $type = $arguments['type'] ?? 'outflow';
                $defaultDesc = match ($type) {
                    'opening' => 'Fundo de troco inicial do caixa',
                    'inflow' => 'Aporte / Troco adicional',
                    'outflow' => 'Retirada / Despesa avulsa',
                    default => '',
                };

                $schema->fill([
                    'type' => $type,
                    'amount' => null,
                    'description' => $defaultDesc,
                ]);
            })
            ->action(function (array $data): void {
                $tenant = filament()->getTenant();
                if (! $tenant) {
                    return;
                }

                CashMovement::create([
                    'barbershop_id' => $tenant->id,
                    'user_id' => auth()->id(),
                    'type' => $data['type'],
                    'amount' => (float) $data['amount'],
                    'description' => trim($data['description']),
                    'date' => $this->selectedDate,
                ]);

                Notification::make()
                    ->title('Movimentação de caixa registrada!')
                    ->success()
                    ->send();
            });
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
