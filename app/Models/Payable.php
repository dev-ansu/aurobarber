<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Payable extends Model
{
    use HasFactory;

    protected $fillable = [
        'barbershop_id',
        'created_by_id',
        'description',
        'supplier',
        'financial_type_id',
        'category',
        'amount',
        'due_date',
        'status',
        'payment_date',
        'amount_paid',
        'payment_method',
        'notes',
        'receipt_path',
        'is_recurring',
        'recurrence_period',
        'recurrence_limit',
        'recurrence_group_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'due_date' => 'date',
            'payment_date' => 'date',
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
                Storage::disk('public')->delete($oldPath);
            }
        });

        static::deleted(function (self $payable): void {
            if ($payable->receipt_path) {
                Storage::disk('public')->delete($payable->receipt_path);
            }
        });
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function financialType(): BelongsTo
    {
        return $this->belongsTo(FinancialType::class, 'financial_type_id');
    }

    /**
     * Garante compatibilidade retroativa com código que consulta $payable->category.
     */
    public function getCategoryAttribute(?string $value): ?string
    {
        return $this->financialType?->name ?? $value;
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date?->isBefore(today());
    }

    /**
     * Baixa simples retrocompatível com código e testes existentes.
     */
    public function markPaid(CarbonInterface $paymentDate, ?string $paymentMethod = null, ?float $amountPaid = null): void
    {
        if ($paymentDate->isAfter(today())) {
            throw new DomainException('A data do pagamento não pode estar no futuro.');
        }

        DB::transaction(function () use ($paymentDate, $paymentMethod, $amountPaid): void {
            $payable = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($payable->status !== 'pending') {
                throw new DomainException('Somente contas pendentes podem ser pagas.');
            }

            $payable->update([
                'status' => 'paid',
                'payment_date' => $paymentDate->toDateString(),
                'payment_method' => $paymentMethod,
                'amount_paid' => $amountPaid ?? $payable->amount,
            ]);
        });

        $this->refresh();
    }

    /**
     * Realiza a baixa do pagamento (total ou parcial) no padrão Aurosoft.
     * Caso o valor pago seja menor que o valor total da conta,
     * desmembra o saldo restante criando automaticamente uma nova conta pendente.
     */
    public function pay(array $data): ?self
    {
        $originalAmount = (float) $this->amount;
        $amountPaid = (float) ($data['amount_paid'] ?? $originalAmount);
        $paymentDate = isset($data['payment_date'])
            ? Carbon::parse($data['payment_date'])
            : today();

        if ($paymentDate->isAfter(today())) {
            throw new DomainException('A data do pagamento não pode estar no futuro.');
        }

        $paymentMethod = $data['payment_method'] ?? null;
        $receiptPath = $data['receipt_path'] ?? $this->receipt_path;
        $remainingDueDate = $data['remaining_due_date'] ?? $this->due_date;

        $createdRemaining = null;

        DB::transaction(function () use (
            $originalAmount,
            $amountPaid,
            $paymentDate,
            $paymentMethod,
            $receiptPath,
            $remainingDueDate,
            &$createdRemaining
        ): void {
            $payable = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($payable->status !== 'pending') {
                throw new DomainException('Somente contas pendentes podem ser pagas.');
            }

            $remainingBalance = round($originalAmount - $amountPaid, 2);

            $notes = $payable->notes;
            if ($remainingBalance > 0 && $amountPaid > 0) {
                $paidFmt = number_format($amountPaid, 2, ',', '.');
                $totalFmt = number_format($originalAmount, 2, ',', '.');
                $notes = trim(($notes ? $notes."\n" : '')."Baixa parcial: R$ {$paidFmt} pagos de um total de R$ {$totalFmt}.");
            }

            $payable->update([
                'status' => 'paid',
                'amount' => ($remainingBalance > 0 && $amountPaid > 0) ? $amountPaid : $originalAmount,
                'payment_date' => $paymentDate->toDateString(),
                'amount_paid' => $amountPaid,
                'payment_method' => $paymentMethod,
                'receipt_path' => $receiptPath,
                'notes' => $notes,
            ]);

            if ($remainingBalance > 0 && $amountPaid > 0) {
                $cleanDesc = preg_replace('/\s*\(Saldo restante.*?\)/i', '', $payable->description);
                $createdRemaining = self::create([
                    'barbershop_id' => $payable->barbershop_id,
                    'created_by_id' => Auth::id() ?? $payable->created_by_id,
                    'description' => "{$cleanDesc} (Saldo restante)",
                    'supplier' => $payable->supplier,
                    'financial_type_id' => $payable->financial_type_id,
                    'category' => $payable->category,
                    'amount' => $remainingBalance,
                    'due_date' => $remainingDueDate,
                    'status' => 'pending',
                    'notes' => "Saldo restante gerado a partir da baixa parcial da conta #{$payable->id} ({$payable->description}).",
                    'is_recurring' => false,
                    'recurrence_group_id' => $payable->recurrence_group_id,
                ]);
            }
        });

        $this->refresh();

        return $createdRemaining;
    }

    /**
     * Gera as próximas ocorrências da conta recorrente no conceito do Aurosoft.
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
                    'barbershop_id' => $this->barbershop_id,
                    'created_by_id' => $this->created_by_id ?? Auth::id(),
                    'description' => "{$baseDescription} ({$i}/{$totalOccurrences})",
                    'supplier' => $this->supplier,
                    'financial_type_id' => $this->financial_type_id,
                    'category' => $this->category,
                    'amount' => $this->amount,
                    'due_date' => $nextDueDate->toDateString(),
                    'status' => 'pending',
                    'notes' => $this->notes,
                    'is_recurring' => true,
                    'recurrence_period' => $period,
                    'recurrence_limit' => $totalOccurrences,
                    'recurrence_group_id' => $groupId,
                ]);
            }

            return $created;
        });
    }

    public function cancel(): void
    {
        DB::transaction(function (): void {
            $payable = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($payable->status !== 'pending') {
                throw new DomainException('Somente contas pendentes podem ser canceladas.');
            }

            $payable->update(['status' => 'cancelled']);
        });

        $this->refresh();
    }
}
