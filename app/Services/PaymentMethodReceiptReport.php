<?php

namespace App\Services;

use App\Models\PaymentMethod;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PaymentMethodReceiptReport
{
    public function query(Carbon $start, Carbon $end, ?int $schoolId = null): Builder
    {
        $schoolId ??= Filament::getTenant()?->id
            ?? (session('filament_tenant_id') ? (int) session('filament_tenant_id') : null);

        $cashReceipts = DB::table('cash_transactions')
            ->join('cash_registers', 'cash_registers.id', '=', 'cash_transactions.cash_register_id')
            ->select('cash_transactions.payment_method_id')
            ->selectRaw('COUNT(*) as receipt_count, SUM(cash_transactions.amount) as amount_total')
            ->where('cash_transactions.type', 'input')
            ->whereNotNull('cash_transactions.installment_id')
            ->whereNotNull('cash_transactions.payment_method_id')
            ->when($schoolId, fn ($q) => $q->where('cash_registers.school_id', $schoolId))
            ->whereBetween('cash_transactions.created_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->groupBy('cash_transactions.payment_method_id');

        // When a receipt was registered without an open cash register, installments retain its amount and method.
        // Prefer cash transactions whenever available so the same payment is never counted twice.
        $installmentFallbacks = DB::table('installments')
            ->leftJoin('contracts', 'contracts.id', '=', 'installments.contract_id')
            ->select('installments.payment_method_id')
            ->selectRaw('COUNT(*) as receipt_count, SUM(COALESCE(installments.amount_paid, installments.amount)) as amount_total')
            ->where('installments.status', 'paid')
            ->whereNotNull('installments.payment_method_id')
            ->whereNotNull('installments.paid_at')
            ->when($schoolId, function ($q) use ($schoolId) {
                $q->where(function ($sub) use ($schoolId) {
                    $sub->where('installments.school_id', $schoolId)
                        ->orWhere(function ($legacy) use ($schoolId) {
                            $legacy->whereNull('installments.school_id')
                                ->where('contracts.school_id', $schoolId);
                        });
                });
            })
            ->whereBetween('installments.paid_at', [$start->toDateString(), $end->toDateString()])
            ->whereRaw('COALESCE(installments.amount_paid, installments.amount) > 0')
            ->whereNotExists(fn ($query) => $query
                ->selectRaw('1')
                ->from('cash_transactions')
                ->whereColumn('cash_transactions.installment_id', 'installments.id')
                ->where('cash_transactions.type', 'input'))
            ->groupBy('installments.payment_method_id');

        return PaymentMethod::query()
            ->leftJoinSub($cashReceipts, 'cash_receipts', fn ($join) => $join->on('cash_receipts.payment_method_id', '=', 'payment_methods.id'))
            ->leftJoinSub($installmentFallbacks, 'installment_receipts', fn ($join) => $join->on('installment_receipts.payment_method_id', '=', 'payment_methods.id'))
            ->select('payment_methods.*')
            ->selectRaw('COALESCE(cash_receipts.receipt_count, 0) + COALESCE(installment_receipts.receipt_count, 0) as receipt_count')
            ->selectRaw('COALESCE(cash_receipts.amount_total, 0) + COALESCE(installment_receipts.amount_total, 0) as amount_total')
            ->where(function ($query): void {
                $query->whereNotNull('cash_receipts.payment_method_id')
                    ->orWhereNotNull('installment_receipts.payment_method_id');
            });
    }
}
