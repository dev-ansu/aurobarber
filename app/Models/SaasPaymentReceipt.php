<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class SaasPaymentReceipt extends Model
{
    protected $table = 'saas_payment_receipts';

    protected $fillable = [
        'payment_submission_id',
        'barbershop_id',
        'amount',
        'received_on',
        'competence',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'received_on' => 'date',
        'competence' => 'date',
    ];

    public function getTable(): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? 'public.saas_payment_receipts'
            : 'saas_payment_receipts';
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class)->withTrashed();
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SaasPaymentSubmission::class, 'payment_submission_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
