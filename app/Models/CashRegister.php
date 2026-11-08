<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashRegister extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'status',
        'opening_balance',
        'current_balance',
        'closing_balance',
        'counted_cash',
        'difference',
        'closing_notes',
        'user_id',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'opening_balance' => 'float',
        'current_balance' => 'float',
        'closing_balance' => 'float',
        'counted_cash' => 'float',
        'difference' => 'float',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    // O operador que abriu/está cuidando deste caixa
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Todas as entradas e saídas que aconteceram neste caixa
    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'cash_register_id');
    }

    public function getCashPaymentMethodId(): ?int
    {
        return PaymentMethod::query()
            ->where('slug', 'dinheiro')
            ->orWhere('name', 'like', '%dinheiro%')
            ->value('id');
    }

    public function expectedCashBalance(): float
    {
        $cashMethodId = $this->getCashPaymentMethodId();

        $cashInputs = (float) $this->transactions()
            ->where('type', 'input')
            ->when($cashMethodId, fn ($q) => $q->where('payment_method_id', $cashMethodId))
            ->sum('amount');

        $cashOutputs = (float) $this->transactions()
            ->where('type', 'output')
            ->when($cashMethodId, fn ($q) => $q->where(fn ($sub) => $sub->where('payment_method_id', $cashMethodId)->orWhereNull('payment_method_id')))
            ->sum('amount');

        return (float) ($this->opening_balance + $cashInputs - $cashOutputs);
    }
}
