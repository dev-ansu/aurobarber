<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Payable extends Model
{
    use HasFactory;

    protected $fillable = [
        'barbershop_id',
        'created_by_id',
        'description',
        'supplier',
        'category',
        'amount',
        'due_date',
        'status',
        'payment_date',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'payment_date' => 'date',
        ];
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function markPaid(CarbonInterface $paymentDate, ?string $paymentMethod = null): void
    {
        if ($paymentDate->isAfter(today())) {
            throw new DomainException('A data do pagamento não pode estar no futuro.');
        }

        DB::transaction(function () use ($paymentDate, $paymentMethod): void {
            $payable = self::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($payable->status !== 'pending') {
                throw new DomainException('Somente contas pendentes podem ser pagas.');
            }

            $payable->update([
                'status' => 'paid',
                'payment_date' => $paymentDate->toDateString(),
                'payment_method' => $paymentMethod,
            ]);
        });

        $this->refresh();
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

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date?->isBefore(today());
    }
}
