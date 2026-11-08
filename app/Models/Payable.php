<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Fillable([
    'description',
    'supplier',
    'financial_type_id',
    'amount',
    'due_date',
    'payment_date',
    'amount_paid',
    'status',
    'school_id',
    'payment_method_id',
    'cash_register_id',
    'notes',
    'receipt_path',
    'is_recurring',
    'recurrence_period',
    'recurrence_limit',
    'recurrence_group_id',
    'created_by_id',
])]
class Payable extends Model
{
    use BelongsToSchool, HasFactory;

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'is_recurring' => 'boolean',
            'recurrence_limit' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payable $payable): void {
            $payable->created_by_id ??= Auth::id();
        });

        static::updated(function (self $payable): void {
            if ($payable->wasChanged('receipt_path') && $oldPath = $payable->getOriginal('receipt_path')) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($oldPath);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
        });

        static::deleted(function (self $payable): void {
            if ($payable->receipt_path) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($payable->receipt_path);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($payable->receipt_path);
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function financialType(): BelongsTo
    {
        return $this->belongsTo(FinancialType::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date?->isPast();
    }

    /**
     * Realiza a baixa do pagamento da conta e, opcionalmente,
     * registra a saída no caixa aberto selecionado.
     * Caso o valor pago seja menor que o valor total da conta, desmembra
     * o saldo restante criando automaticamente uma nova conta a pagar pendente.
     */
    public function pay(array $data): ?self
    {
        $originalAmount = (float) $this->amount;
        $amountPaid = (float) ($data['amount_paid'] ?? $originalAmount);
        $paymentDate = $data['payment_date'] ?? today()->toDateString();
        $paymentMethodId = $data['payment_method_id'] ?? null;
        $cashRegisterId = $data['cash_register_id'] ?? null;
        $receiptPath = $data['receipt_path'] ?? $this->receipt_path;
        $remainingDueDate = $data['remaining_due_date'] ?? $this->due_date;

        $createdRemaining = null;

        DB::transaction(function () use (
            $originalAmount,
            $amountPaid,
            $paymentDate,
            $paymentMethodId,
            $cashRegisterId,
            $receiptPath,
            $remainingDueDate,
            &$createdRemaining
        ): void {
            $remainingBalance = round($originalAmount - $amountPaid, 2);

            $notes = $this->notes;
            if ($remainingBalance > 0 && $amountPaid > 0) {
                $paidFmt = number_format($amountPaid, 2, ',', '.');
                $totalFmt = number_format($originalAmount, 2, ',', '.');
                $notes = trim(($notes ? $notes."\n" : '')."Baixa parcial: R$ {$paidFmt} pagos de um total de R$ {$totalFmt}.");
            }

            $this->update([
                'status' => 'paid',
                'amount' => ($remainingBalance > 0 && $amountPaid > 0) ? $amountPaid : $originalAmount,
                'payment_date' => $paymentDate,
                'amount_paid' => $amountPaid,
                'payment_method_id' => $paymentMethodId,
                'cash_register_id' => $cashRegisterId,
                'receipt_path' => $receiptPath,
                'notes' => $notes,
            ]);

            if ($remainingBalance > 0 && $amountPaid > 0) {
                $cleanDesc = preg_replace('/\s*\(Saldo restante.*?\)/i', '', $this->description);
                $createdRemaining = self::create([
                    'description' => "{$cleanDesc} (Saldo restante)",
                    'supplier' => $this->supplier,
                    'financial_type_id' => $this->financial_type_id,
                    'amount' => $remainingBalance,
                    'due_date' => $remainingDueDate,
                    'status' => 'pending',
                    'notes' => "Saldo restante gerado a partir da baixa parcial da conta #{$this->id} ({$this->description}).",
                    'is_recurring' => false,
                    'recurrence_group_id' => $this->recurrence_group_id,
                    'created_by_id' => Auth::id() ?? $this->created_by_id,
                    'school_id' => $this->school_id,
                ]);
            }

            if ($cashRegisterId && $amountPaid > 0) {
                CashTransaction::create([
                    'cash_register_id' => $cashRegisterId,
                    'user_id' => Auth::id() ?? $this->created_by_id,
                    'type' => 'output',
                    'amount' => $amountPaid,
                    'payment_method_id' => $paymentMethodId,
                    'description' => "Pagamento de conta: {$this->description}".($this->supplier ? " (Fornecedor: {$this->supplier})" : ''),
                    'financial_type_id' => $this->financial_type_id,
                ]);

                $cashRegister = CashRegister::find($cashRegisterId);
                $cashRegister?->decrement('current_balance', $amountPaid);
            }
        });

        return $createdRemaining;
    }

    /**
     * Gera as próximas ocorrências da conta recorrente
     *
     * @return array<int, Payable>
     */
    public function generateRecurringOccurrences(int $totalOccurrences, ?string $period = null): array
    {
        if ($totalOccurrences <= 1) {
            return [];
        }

        $period = $period ?: ($this->recurrence_period ?: 'monthly');
        $groupId = $this->recurrence_group_id ?: Str::uuid()->toString();

        $baseDescription = preg_replace('/\s*\(\d+\/\d+\)$/', '', $this->description);

        return DB::transaction(function () use ($totalOccurrences, $period, $groupId, $baseDescription): array {
            if (! $this->recurrence_group_id) {
                $this->update([
                    'recurrence_group_id' => $groupId,
                    'description' => "{$baseDescription} (1/{$totalOccurrences})",
                    'is_recurring' => true,
                    'recurrence_period' => $period,
                    'recurrence_limit' => $totalOccurrences,
                ]);
            }

            $created = [];
            $baseDueDate = $this->due_date ? Carbon::parse($this->due_date) : today();

            for ($i = 2; $i <= $totalOccurrences; $i++) {
                $offset = $i - 1;
                $nextDueDate = match ($period) {
                    'weekly' => $baseDueDate->copy()->addWeeks($offset),
                    'bimonthly' => $baseDueDate->copy()->addMonthsNoOverflow($offset * 2),
                    'yearly' => $baseDueDate->copy()->addYears($offset),
                    default => $baseDueDate->copy()->addMonthsNoOverflow($offset),
                };

                $created[] = self::create([
                    'description' => "{$baseDescription} ({$i}/{$totalOccurrences})",
                    'supplier' => $this->supplier,
                    'financial_type_id' => $this->financial_type_id,
                    'amount' => $this->amount,
                    'due_date' => $nextDueDate->toDateString(),
                    'status' => 'pending',
                    'notes' => $this->notes,
                    'is_recurring' => true,
                    'recurrence_period' => $period,
                    'recurrence_limit' => $totalOccurrences,
                    'recurrence_group_id' => $groupId,
                    'created_by_id' => $this->created_by_id ?? Auth::id(),
                ]);
            }

            return $created;
        });
    }
}
