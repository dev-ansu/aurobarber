<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Models\Barbershop;
use App\Models\CashMovement;
use App\Models\Product;
use App\Models\ProductSale;
use App\Models\Professional;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /**
     * Registra a conclusão de um atendimento com serviços e produtos consumidos.
     *
     * @param  array<int, int|string>  $productIds
     * @param  array<string, int|float|string|null>  $additionalAmounts
     */
    public function completeAppointment(
        Appointment $appointment,
        float $servicePrice,
        string $primaryPaymentMethod,
        array $additionalAmounts = [],
        array $productIds = []
    ): float {
        return DB::transaction(function () use (
            $appointment,
            $servicePrice,
            $primaryPaymentMethod,
            $additionalAmounts,
            $productIds
        ): float {
            $lockedAppointment = Appointment::query()
                ->where('barbershop_id', $appointment->barbershop_id)
                ->whereKey($appointment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($lockedAppointment->status, ['completed', 'cancelled', 'no_show'], true) || $lockedAppointment->is_blocked_slot) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Este agendamento já foi concluído ou não pode ser finalizado.',
                ]);
            }

            $normalizedProductIds = collect($productIds)
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values();

            $products = collect();
            if ($normalizedProductIds->isNotEmpty()) {
                $products = Product::query()
                    ->where('barbershop_id', $lockedAppointment->barbershop_id)
                    ->where('is_active', true)
                    ->whereIn('id', $normalizedProductIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($products->count() !== $normalizedProductIds->count()) {
                    throw ValidationException::withMessages([
                        'consumed_product_ids' => 'Um ou mais produtos selecionados não pertencem a esta barbearia ou estão inativos.',
                    ]);
                }
            }

            $productsTotal = 0.0;
            $productsCommissionTotal = 0.0;
            $saleGroupId = (string) Str::uuid();

            foreach ($normalizedProductIds as $productId) {
                /** @var Product $product */
                $product = $products->get($productId);

                if ((int) $product->stock_quantity < 1) {
                    throw ValidationException::withMessages([
                        'consumed_product_ids' => "Estoque insuficiente para o produto {$product->name}.",
                    ]);
                }

                $itemPrice = (float) $product->price;
                $productsTotal += $itemPrice;

                $prodCommRate = (float) $product->commission_rate;
                if ($prodCommRate <= 0 && $lockedAppointment->professional) {
                    $prodCommRate = (float) ($lockedAppointment->professional->commission_rate ?? 0);
                }
                $prodCommAmount = round(($itemPrice * $prodCommRate) / 100, 2);
                $productsCommissionTotal += $prodCommAmount;

                $lockedAppointment->products()->attach($product->id, [
                    'quantity' => 1,
                    'unit_price' => $product->price,
                    'total_price' => $product->price,
                ]);

                $product->decrement('stock_quantity', 1);

                ProductSale::create([
                    'barbershop_id' => $lockedAppointment->barbershop_id,
                    'product_id' => $product->id,
                    'professional_id' => $lockedAppointment->professional_id,
                    'appointment_id' => $lockedAppointment->id,
                    'quantity' => 1,
                    'unit_price' => $product->price,
                    'total_price' => $itemPrice,
                    'cost_price' => $product->cost_price,
                    'commission_rate' => $prodCommRate,
                    'commission_amount' => $prodCommAmount,
                    'commission_status' => 'pending',
                    'payment_method' => $primaryPaymentMethod,
                    'client_name' => $lockedAppointment->client_name,
                    'sale_group_id' => $saleGroupId,
                    'notes' => "Produto vendido no atendimento #{$lockedAppointment->id}",
                ]);
            }

            $commRate = (float) ($lockedAppointment->professional?->commission_rate ?: $lockedAppointment->commission_rate ?: 50.0);
            $serviceCommAmount = round(($servicePrice * $commRate) / 100, 2);
            $totalCommAmount = $serviceCommAmount + $productsCommissionTotal;
            $grandTotal = round($servicePrice + $productsTotal, 2);

            $paymentDetails = PaymentBreakdown::fromAdditionalAmounts(
                $grandTotal,
                $primaryPaymentMethod,
                $additionalAmounts,
            );
            $summaryMethod = PaymentBreakdown::summaryMethod($paymentDetails);

            $lockedAppointment->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => $summaryMethod,
                'payment_details' => $paymentDetails,
                'service_price' => $servicePrice,
                'products_total' => (float) $lockedAppointment->products_total + $productsTotal,
                'products_commission_amount' => (float) $lockedAppointment->products_commission_amount + $productsCommissionTotal,
                'commission_rate' => $commRate,
                'commission_amount' => $totalCommAmount,
                'commission_status' => 'pending',
                'paid_at' => now(),
            ]);

            CashMovement::create([
                'barbershop_id' => $lockedAppointment->barbershop_id,
                'user_id' => auth()->id(),
                'type' => 'inflow',
                'description' => "Corte/Serviço: {$lockedAppointment->client_name}".($productsTotal > 0 ? ' (+ produtos: R$ '.number_format($productsTotal, 2, ',', '.').')' : ''),
                'amount' => $grandTotal,
                'date' => now()->toDateString(),
            ]);

            return $grandTotal;
        });
    }

    /**
     * Registra uma venda avulsa de produtos de balcão.
     *
     * @param  array<int, int|string>  $productIds
     * @param  array<int|string, int|string|null>  $quantities
     * @param  array<string, int|float|string|null>  $additionalAmounts
     * @param  array<int, array{method?: string, payment_method?: string, amount?: int|float|string|null}>|null  $payments
     */
    public function registerStandaloneSale(
        Barbershop $barbershop,
        array $productIds,
        array $quantities,
        string $primaryPaymentMethod = 'pix',
        array $additionalAmounts = [],
        ?string $clientName = null,
        ?int $professionalId = null,
        ?array $payments = null
    ): float {
        return DB::transaction(function () use (
            $barbershop,
            $productIds,
            $quantities,
            $primaryPaymentMethod,
            $additionalAmounts,
            $clientName,
            $professionalId,
            $payments
        ): float {
            $normalizedProductIds = collect($productIds)
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values();

            if ($normalizedProductIds->isEmpty()) {
                throw ValidationException::withMessages([
                    'product_ids' => 'Selecione pelo menos um produto para vender.',
                ]);
            }

            $products = Product::query()
                ->where('barbershop_id', $barbershop->id)
                ->where('is_active', true)
                ->whereIn('id', $normalizedProductIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $normalizedProductIds->count()) {
                throw ValidationException::withMessages([
                    'product_ids' => 'Um ou mais produtos não pertencem a esta barbearia ou estão inativos.',
                ]);
            }

            $professional = null;
            if ($professionalId !== null && $professionalId > 0) {
                $professional = Professional::query()
                    ->where('barbershop_id', $barbershop->id)
                    ->whereKey($professionalId)
                    ->first();

                if (! $professional) {
                    throw ValidationException::withMessages([
                        'professional_id' => 'O profissional selecionado não pertence a esta barbearia.',
                    ]);
                }
            }

            $total = 0.0;
            foreach ($normalizedProductIds as $productId) {
                /** @var Product $product */
                $product = $products->get($productId);
                $qty = max(1, (int) ($quantities[$productId] ?? 1));

                if ($qty > (int) $product->stock_quantity) {
                    throw ValidationException::withMessages([
                        "quantities.{$productId}" => "Estoque insuficiente para {$product->name} (disponível: {$product->stock_quantity}).",
                    ]);
                }

                $total += (float) $product->price * $qty;
            }

            $total = round($total, 2);
            $paymentDetails = $payments !== null
                ? PaymentBreakdown::fromPayments($total, $payments)
                : PaymentBreakdown::fromAdditionalAmounts(
                    $total,
                    $primaryPaymentMethod,
                    $additionalAmounts,
                );
            $saleGroupId = (string) Str::uuid();
            $paymentMethod = PaymentBreakdown::summaryMethod($paymentDetails);

            $trimmedClientName = $clientName !== null ? trim($clientName) : null;
            if ($trimmedClientName === '') {
                $trimmedClientName = null;
            }

            foreach ($normalizedProductIds as $index => $productId) {
                /** @var Product $product */
                $product = $products->get($productId);
                $qty = max(1, (int) ($quantities[$productId] ?? 1));
                $lineTotal = round((float) $product->price * $qty, 2);

                $commRate = 0.0;
                $commAmount = 0.0;
                if ($professional !== null) {
                    $commRate = (float) $product->commission_rate;
                    if ($commRate <= 0) {
                        $commRate = (float) ($professional->commission_rate ?? 0);
                    }
                    $commAmount = round(($lineTotal * $commRate) / 100, 2);
                }

                $product->decrement('stock_quantity', $qty);

                ProductSale::create([
                    'barbershop_id' => $barbershop->id,
                    'product_id' => $product->id,
                    'professional_id' => $professional?->id,
                    'quantity' => $qty,
                    'unit_price' => $product->price,
                    'total_price' => $lineTotal,
                    'cost_price' => $product->cost_price,
                    'commission_rate' => $commRate,
                    'commission_amount' => $commAmount,
                    'commission_status' => $professional !== null ? 'pending' : 'none',
                    'payment_method' => $paymentMethod,
                    'payment_details' => $index === 0 ? $paymentDetails : null,
                    'sale_group_id' => $saleGroupId,
                    'client_name' => $trimmedClientName,
                    'notes' => $professional !== null
                        ? "Venda PDV realizada por {$professional->display_name}"
                        : 'Venda avulsa de balcão',
                ]);
            }

            CashMovement::create([
                'barbershop_id' => $barbershop->id,
                'user_id' => auth()->id(),
                'type' => 'inflow',
                'description' => 'Venda de produtos no balcão'
                    .($trimmedClientName ? " ({$trimmedClientName})" : '')
                    .($professional !== null ? " - Barbeiro: {$professional->display_name}" : ''),
                'amount' => $total,
                'date' => now()->toDateString(),
            ]);

            return $total;
        });
    }
}
