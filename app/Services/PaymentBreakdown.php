<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class PaymentBreakdown
{
    private const METHODS = ['pix', 'dinheiro', 'debito', 'credito'];

    /**
     * @param  array<string, int|float|string|null>  $additionalAmounts
     * @return array<int, array{method: string, amount: float}>
     */
    public static function fromAdditionalAmounts(float $total, string $primaryMethod, array $additionalAmounts): array
    {
        $additionalPayments = [];

        foreach ($additionalAmounts as $method => $amount) {
            if (! in_array($method, self::METHODS, true)) {
                throw ValidationException::withMessages([
                    'additional_payments' => 'Selecione uma forma de pagamento válida.',
                ]);
            }

            $amount = round((float) ($amount ?? 0), 2);

            if ($amount < 0) {
                throw ValidationException::withMessages([
                    'additional_payments' => 'Os valores adicionais não podem ser negativos.',
                ]);
            }

            if ($amount > 0) {
                $additionalPayments[] = [
                    'payment_method' => $method,
                    'amount' => $amount,
                ];
            }
        }

        return self::fromPrimary($total, $primaryMethod, $additionalPayments);
    }

    /**
     * @param  array<int, array{payment_method?: string, amount?: int|float|string|null}>  $additionalPayments
     * @return array<int, array{method: string, amount: float}>
     */
    public static function fromPrimary(float $total, string $primaryMethod, array $additionalPayments): array
    {
        if (! in_array($primaryMethod, self::METHODS, true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Selecione uma forma de pagamento válida.',
            ]);
        }

        $payments = [];
        $additionalTotal = 0.0;

        foreach ($additionalPayments as $payment) {
            $method = (string) ($payment['payment_method'] ?? '');
            $amount = round((float) ($payment['amount'] ?? 0), 2);

            if (! in_array($method, self::METHODS, true) || $amount <= 0) {
                throw ValidationException::withMessages([
                    'additional_payments' => 'Informe uma forma e um valor maior que zero para cada pagamento adicional.',
                ]);
            }

            $payments[] = ['method' => $method, 'amount' => $amount];
            $additionalTotal += $amount;
        }

        $remaining = round($total - $additionalTotal, 2);

        if ($remaining < 0 || $total <= 0) {
            throw ValidationException::withMessages([
                'additional_payments' => 'A soma dos pagamentos não pode ultrapassar o total da venda.',
            ]);
        }

        if ($remaining > 0) {
            array_unshift($payments, ['method' => $primaryMethod, 'amount' => $remaining]);
        }

        return $payments;
    }

    /**
     * Valida e normaliza uma lista explícita de pagamentos (PDV com múltiplas formas).
     * Permite troco quando houver pagamento em dinheiro acima do total, gravando o valor líquido recebido.
     *
     * @param  array<int, array{method?: string, payment_method?: string, amount?: int|float|string|null}>  $payments
     * @return array<int, array{method: string, amount: float}>
     */
    public static function fromPayments(float $total, array $payments): array
    {
        $total = round($total, 2);

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'payments' => 'O total da venda deve ser maior que zero.',
            ]);
        }

        $grouped = [];
        foreach ($payments as $row) {
            $method = (string) ($row['method'] ?? $row['payment_method'] ?? '');
            $amount = round((float) ($row['amount'] ?? 0), 2);

            if (! in_array($method, self::METHODS, true)) {
                throw ValidationException::withMessages([
                    'payments' => 'Selecione uma forma de pagamento válida.',
                ]);
            }

            if ($amount < 0) {
                throw ValidationException::withMessages([
                    'payments' => 'Os valores de pagamento não podem ser negativos.',
                ]);
            }

            if ($amount === 0.0) {
                continue;
            }

            $grouped[$method] = round(($grouped[$method] ?? 0.0) + $amount, 2);
        }

        if ($grouped === []) {
            throw ValidationException::withMessages([
                'payments' => 'Informe pelo menos uma forma de pagamento com valor maior que zero.',
            ]);
        }

        $nonCashTotal = 0.0;
        foreach ($grouped as $method => $amount) {
            if ($method !== 'dinheiro') {
                $nonCashTotal = round($nonCashTotal + $amount, 2);
            }
        }

        if ($nonCashTotal > $total) {
            throw ValidationException::withMessages([
                'payments' => 'A soma dos pagamentos em PIX/cartão não pode ultrapassar o total da venda.',
            ]);
        }

        $paidTotal = round(array_sum($grouped), 2);

        if ($paidTotal < $total) {
            throw ValidationException::withMessages([
                'payments' => 'O valor informado no pagamento é menor que o total da venda.',
            ]);
        }

        $change = round($paidTotal - $total, 2);
        if ($change > 0) {
            $cashAmount = $grouped['dinheiro'] ?? 0.0;
            if ($cashAmount < $change) {
                throw ValidationException::withMessages([
                    'payments' => 'A soma dos pagamentos não pode ultrapassar o total da venda.',
                ]);
            }

            $netCash = round($cashAmount - $change, 2);
            if ($netCash <= 0) {
                unset($grouped['dinheiro']);
            } else {
                $grouped['dinheiro'] = $netCash;
            }
        }

        $normalized = [];
        foreach ($grouped as $method => $amount) {
            $normalized[] = [
                'method' => $method,
                'amount' => round($amount, 2),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{method?: string, amount?: int|float|string|null}>|null  $payments
     * @return array<string, float>
     */
    public static function totals(?array $payments, ?string $fallbackMethod, float $fallbackAmount): array
    {
        if (blank($payments)) {
            $method = match ($fallbackMethod) {
                'cartao_debito' => 'debito',
                'cartao_credito' => 'credito',
                default => in_array($fallbackMethod, self::METHODS, true) ? $fallbackMethod : 'outros',
            };

            return [$method => round($fallbackAmount, 2)];
        }

        $totals = [];

        foreach ($payments as $payment) {
            $method = (string) ($payment['method'] ?? '');
            if (! in_array($method, self::METHODS, true)) {
                $method = 'outros';
            }

            $totals[$method] = ($totals[$method] ?? 0.0) + (float) ($payment['amount'] ?? 0);
        }

        return $totals;
    }

    /** @param array<int, array{method: string, amount: float}> $payments */
    public static function summaryMethod(array $payments): string
    {
        $methods = array_values(array_unique(array_column($payments, 'method')));

        return count($methods) === 1 ? $methods[0] : 'misto';
    }
}
