<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Barbershop;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\Professional;
use App\Models\User;
use App\Services\CheckoutService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProductPosPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|\UnitEnum|null $navigationGroup = 'Serviços';

    protected static ?string $navigationLabel = 'PDV / Vender Produtos';

    protected static ?string $title = 'PDV — Venda de Produtos';

    protected static ?int $navigationSort = 21;

    protected string $view = 'filament.pages.product-pos-page';

    public string $search = '';

    public string $selectedCategory = 'all';

    /** @var array<int, int> */
    public array $cart = [];

    public string $clientName = '';

    public ?int $professionalId = null;

    public bool $splitPayment = false;

    public string $singlePaymentMethod = 'pix';

    public string $cashReceived = '';

    /** @var array<int, array{method: string, amount: string}> */
    public array $payments = [];

    public bool $showRecentSales = false;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        $tenant = filament()->getTenant();

        return $user instanceof User
            && $tenant instanceof Barbershop
            && $user->canAccessTenant($tenant)
            && $user->can('Sell:Product');
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return 'full';
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $user = auth()->user();
        if ($user instanceof User && $user->isRestrictedBarber()) {
            $this->professionalId = $user->resolveProfessionalId();
        }
    }

    public function getProductsProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (! $tenant instanceof Barbershop) {
            return collect();
        }

        return Product::query()
            ->where('barbershop_id', $tenant->id)
            ->where('is_active', true)
            ->where('stock_quantity', '>', 0)
            ->when(
                $this->selectedCategory !== 'all',
                fn ($query) => $query->where('category', $this->selectedCategory),
            )
            ->when(
                trim($this->search) !== '',
                fn ($query) => $query->where('name', 'like', '%'.trim($this->search).'%'),
            )
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, array{product: Product, quantity: int, unit_price: float, subtotal: float}>
     */
    public function getCartItemsProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (! $tenant instanceof Barbershop || $this->cart === []) {
            return collect();
        }

        $ids = array_map('intval', array_keys($this->cart));
        $products = Product::query()
            ->where('barbershop_id', $tenant->id)
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $items = collect();
        foreach ($this->cart as $productId => $qty) {
            $product = $products->get((int) $productId);
            if (! $product) {
                continue;
            }

            $quantity = max(1, min((int) $qty, (int) $product->stock_quantity));
            $unitPrice = (float) $product->price;

            $items->push([
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => round($unitPrice * $quantity, 2),
            ]);
        }

        return $items;
    }

    public function getCartTotalProperty(): float
    {
        return round((float) $this->cartItems->sum('subtotal'), 2);
    }

    public function getCartCountProperty(): int
    {
        return (int) $this->cartItems->sum('quantity');
    }

    public function getProfessionalsProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (! $tenant instanceof Barbershop) {
            return collect();
        }

        return Professional::query()
            ->where('barbershop_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array{paid: float, remaining: float, change: float, is_valid: bool, error: ?string}
     */
    public function getPaymentSummaryProperty(): array
    {
        $total = $this->cartTotal;

        if ($total <= 0) {
            return [
                'paid' => 0.0,
                'remaining' => 0.0,
                'change' => 0.0,
                'is_valid' => false,
                'error' => null,
            ];
        }

        if (! $this->splitPayment) {
            if ($this->singlePaymentMethod === 'dinheiro' && trim($this->cashReceived) !== '') {
                $received = $this->parseAmount($this->cashReceived);
                if ($received < $total) {
                    return [
                        'paid' => $received,
                        'remaining' => round($total - $received, 2),
                        'change' => 0.0,
                        'is_valid' => false,
                        'error' => 'Valor recebido em dinheiro é menor que o total.',
                    ];
                }

                return [
                    'paid' => $received,
                    'remaining' => 0.0,
                    'change' => round($received - $total, 2),
                    'is_valid' => true,
                    'error' => null,
                ];
            }

            return [
                'paid' => $total,
                'remaining' => 0.0,
                'change' => 0.0,
                'is_valid' => true,
                'error' => null,
            ];
        }

        $paid = 0.0;
        $nonCash = 0.0;
        $cash = 0.0;

        foreach ($this->payments as $row) {
            $amount = $this->parseAmount($row['amount'] ?? 0);
            if ($amount < 0) {
                return [
                    'paid' => 0.0,
                    'remaining' => $total,
                    'change' => 0.0,
                    'is_valid' => false,
                    'error' => 'Os valores de pagamento não podem ser negativos.',
                ];
            }

            $paid = round($paid + $amount, 2);
            if (($row['method'] ?? '') === 'dinheiro') {
                $cash = round($cash + $amount, 2);
            } else {
                $nonCash = round($nonCash + $amount, 2);
            }
        }

        if ($nonCash > $total) {
            return [
                'paid' => $paid,
                'remaining' => 0.0,
                'change' => 0.0,
                'is_valid' => false,
                'error' => 'PIX/cartão não pode ultrapassar o total da venda.',
            ];
        }

        if ($paid < $total) {
            return [
                'paid' => $paid,
                'remaining' => round($total - $paid, 2),
                'change' => 0.0,
                'is_valid' => false,
                'error' => null,
            ];
        }

        $change = round($paid - $total, 2);
        if ($change > 0 && $cash < $change) {
            return [
                'paid' => $paid,
                'remaining' => 0.0,
                'change' => 0.0,
                'is_valid' => false,
                'error' => 'O valor excedente só é permitido quando há pagamento em dinheiro (troco).',
            ];
        }

        return [
            'paid' => $paid,
            'remaining' => 0.0,
            'change' => $change,
            'is_valid' => true,
            'error' => null,
        ];
    }

    public function getTodaySalesProperty(): Collection
    {
        $tenant = filament()->getTenant();
        if (! $tenant instanceof Barbershop) {
            return collect();
        }

        return ProductSale::query()
            ->where('barbershop_id', $tenant->id)
            ->whereNull('appointment_id')
            ->whereDate('created_at', now()->toDateString())
            ->with(['product', 'professional'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn (ProductSale $sale): string => $sale->sale_group_id ?? 'single-'.$sale->id);
    }

    public function addToCart(int $productId): void
    {
        $tenant = filament()->getTenant();
        if (! $tenant instanceof Barbershop) {
            return;
        }

        $product = Product::query()
            ->where('barbershop_id', $tenant->id)
            ->where('is_active', true)
            ->whereKey($productId)
            ->first();

        if (! $product || (int) $product->stock_quantity <= 0) {
            Notification::make()
                ->title('Produto indisponível em estoque.')
                ->warning()
                ->send();

            return;
        }

        $currentQty = (int) ($this->cart[$product->id] ?? 0);
        if ($currentQty >= (int) $product->stock_quantity) {
            Notification::make()
                ->title("Limite de estoque atingido para {$product->name} ({$product->stock_quantity} un.).")
                ->warning()
                ->send();

            return;
        }

        $this->cart[$product->id] = $currentQty + 1;
    }

    public function decrementCart(int $productId): void
    {
        if (! isset($this->cart[$productId])) {
            return;
        }

        if ($this->cart[$productId] <= 1) {
            unset($this->cart[$productId]);

            return;
        }

        $this->cart[$productId]--;
    }

    public function setQuantity(int $productId, mixed $quantity): void
    {
        $tenant = filament()->getTenant();
        if (! $tenant instanceof Barbershop) {
            return;
        }

        $qty = (int) $quantity;
        if ($qty <= 0) {
            unset($this->cart[$productId]);

            return;
        }

        $product = Product::query()
            ->where('barbershop_id', $tenant->id)
            ->where('is_active', true)
            ->whereKey($productId)
            ->first();

        if (! $product) {
            unset($this->cart[$productId]);

            return;
        }

        if ($qty > (int) $product->stock_quantity) {
            $qty = (int) $product->stock_quantity;
            Notification::make()
                ->title("Quantidade ajustada para o máximo disponível em estoque ({$qty} un.).")
                ->warning()
                ->send();
        }

        $this->cart[$product->id] = max(1, $qty);
    }

    public function removeFromCart(int $productId): void
    {
        unset($this->cart[$productId]);
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->cashReceived = '';
        if ($this->splitPayment) {
            $this->payments = [
                ['method' => 'pix', 'amount' => ''],
                ['method' => 'dinheiro', 'amount' => ''],
            ];
        }
    }

    public function selectSinglePaymentMethod(string $method): void
    {
        if (! in_array($method, ['pix', 'dinheiro', 'debito', 'credito'], true)) {
            return;
        }

        $this->splitPayment = false;
        $this->singlePaymentMethod = $method;
        if ($method !== 'dinheiro') {
            $this->cashReceived = '';
        }
    }

    public function enableSplitPayment(): void
    {
        $this->splitPayment = true;
        $this->cashReceived = '';

        if ($this->payments === []) {
            $secondMethod = $this->singlePaymentMethod === 'dinheiro' ? 'pix' : 'dinheiro';
            $this->payments = [
                ['method' => $this->singlePaymentMethod, 'amount' => ''],
                ['method' => $secondMethod, 'amount' => ''],
            ];
        }
    }

    public function disableSplitPayment(): void
    {
        $this->splitPayment = false;
        $this->payments = [];
    }

    public function addPaymentRow(): void
    {
        $used = array_column($this->payments, 'method');
        $nextMethod = 'dinheiro';
        foreach (['pix', 'dinheiro', 'debito', 'credito'] as $candidate) {
            if (! in_array($candidate, $used, true)) {
                $nextMethod = $candidate;
                break;
            }
        }

        $remaining = $this->paymentSummary['remaining'];
        $this->payments[] = [
            'method' => $nextMethod,
            'amount' => $remaining > 0 ? number_format($remaining, 2, '.', '') : '',
        ];
    }

    public function removePaymentRow(int $index): void
    {
        if (! isset($this->payments[$index])) {
            return;
        }

        unset($this->payments[$index]);
        $this->payments = array_values($this->payments);

        if ($this->payments === []) {
            $this->disableSplitPayment();
        }
    }

    public function fillRemaining(int $index): void
    {
        if (! isset($this->payments[$index])) {
            return;
        }

        $otherTotal = 0.0;
        foreach ($this->payments as $i => $row) {
            if ($i === $index) {
                continue;
            }
            $otherTotal = round($otherTotal + $this->parseAmount($row['amount'] ?? 0), 2);
        }

        $remaining = max(0.0, round($this->cartTotal - $otherTotal, 2));
        $this->payments[$index]['amount'] = $remaining > 0 ? number_format($remaining, 2, '.', '') : '';
    }

    public function finalizeSale(): void
    {
        abort_unless(static::canAccess(), 403);

        $tenant = filament()->getTenant();
        if (! $tenant instanceof Barbershop) {
            return;
        }

        if ($this->cart === []) {
            Notification::make()
                ->title('Adicione pelo menos um produto ao carrinho.')
                ->warning()
                ->send();

            return;
        }

        $total = $this->cartTotal;
        $summary = $this->paymentSummary;

        if (! $summary['is_valid']) {
            Notification::make()
                ->title($summary['error'] ?? 'Complete o valor do pagamento antes de finalizar a venda.')
                ->danger()
                ->send();

            return;
        }

        $paymentsPayload = [];
        if ($this->splitPayment) {
            foreach ($this->payments as $row) {
                $amount = $this->parseAmount($row['amount'] ?? 0);
                if ($amount > 0) {
                    $paymentsPayload[] = [
                        'method' => (string) ($row['method'] ?? 'pix'),
                        'amount' => $amount,
                    ];
                }
            }
        } else {
            $amount = $total;
            if ($this->singlePaymentMethod === 'dinheiro' && trim($this->cashReceived) !== '') {
                $amount = $this->parseAmount($this->cashReceived);
            }
            $paymentsPayload[] = [
                'method' => $this->singlePaymentMethod,
                'amount' => $amount,
            ];
        }

        $user = auth()->user();
        $professionalId = $this->professionalId;
        if ($user instanceof User && $user->isRestrictedBarber()) {
            $professionalId = $user->resolveProfessionalId() ?? $professionalId;
        }

        try {
            $registeredTotal = app(CheckoutService::class)->registerStandaloneSale(
                barbershop: $tenant,
                productIds: array_keys($this->cart),
                quantities: $this->cart,
                primaryPaymentMethod: $this->singlePaymentMethod,
                additionalAmounts: [],
                clientName: trim($this->clientName) !== '' ? trim($this->clientName) : null,
                professionalId: $professionalId,
                payments: $paymentsPayload,
            );
        } catch (ValidationException $exception) {
            $firstMessage = collect($exception->errors())->flatten()->first()
                ?? 'Não foi possível registrar a venda. Verifique os dados informados.';

            Notification::make()
                ->title((string) $firstMessage)
                ->danger()
                ->send();

            return;
        }

        $changeMessage = $summary['change'] > 0
            ? ' Troco: R$ '.number_format($summary['change'], 2, ',', '.').'.'
            : '';

        $this->cart = [];
        $this->clientName = '';
        $this->cashReceived = '';
        $this->splitPayment = false;
        $this->payments = [];
        if (! ($user instanceof User && $user->isRestrictedBarber())) {
            $this->professionalId = null;
        }

        Notification::make()
            ->title('Venda registrada com sucesso!')
            ->body('Total de R$ '.number_format($registeredTotal, 2, ',', '.').' lançado no caixa e baixado do estoque.'.$changeMessage)
            ->success()
            ->send();
    }

    private function parseAmount(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return 0.0;
        }

        $raw = str_replace(['R$', ' '], '', $raw);
        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif (str_contains($raw, ',')) {
            $raw = str_replace(',', '.', $raw);
        }

        return round((float) $raw, 2);
    }
}
