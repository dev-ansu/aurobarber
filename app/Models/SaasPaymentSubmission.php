<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class SaasPaymentSubmission extends Model
{
    protected $table = 'saas_payment_submissions';

    protected $fillable = [
        'barbershop_id',
        'saas_plan_id',
        'submitted_by_name',
        'submitted_by_email',
        'amount',
        'proof_path',
        'status',
        'review_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'reviewed_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? 'public.saas_payment_submissions'
            : 'saas_payment_submissions';
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class)->withTrashed();
    }

    public function saasPlan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class, 'saas_plan_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(SaasPaymentReceipt::class, 'payment_submission_id');
    }
}
