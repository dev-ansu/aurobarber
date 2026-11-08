<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtAgreement extends Model
{
    protected $fillable = [
        'contract_id',
        'agreement_number',
        'user_id',
        'total_original_amount',
        'discount_amount',
        'total_agreed_amount',
        'down_payment_amount',
        'down_payment_paid',
        'installments_count',
        'renegotiated_installment_ids',
        'new_installment_ids',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_original_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_agreed_amount' => 'decimal:2',
            'down_payment_amount' => 'decimal:2',
            'down_payment_paid' => 'boolean',
            'installments_count' => 'integer',
            'renegotiated_installment_ids' => 'array',
            'new_installment_ids' => 'array',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Retorna a lista de parcelas originais renegociadas.
     *
     * @return Collection<int, Installment>
     */
    public function getOriginalInstallmentsAttribute()
    {
        $ids = $this->renegotiated_installment_ids ?? [];
        if (empty($ids)) {
            return collect();
        }

        return Installment::whereIn('id', $ids)->orderBy('installment_number')->get();
    }

    /**
     * Retorna a lista de novas parcelas geradas pelo acordo.
     *
     * @return Collection<int, Installment>
     */
    public function getNewInstallmentsAttribute()
    {
        $ids = $this->new_installment_ids ?? [];
        if (empty($ids)) {
            return collect();
        }

        return Installment::whereIn('id', $ids)->orderBy('due_date')->get();
    }
}
