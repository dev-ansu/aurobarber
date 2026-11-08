<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CashTransaction extends Model
{
    protected $fillable = [
        'cash_register_id',
        'user_id',
        'installment_id',
        'financial_type_id',
        'type',
        'amount',
        'payment_method_id',
        'destination_account_id',
        'description',
    ];

    // Caixa de onde a transação faz parte
    public function register(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class, 'cash_register_id');
    }

    public function cashRegister(): BelongsTo
    {
        return $this->register();
    }

    // Quem fez o lançamento da transação
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Se veio de uma parcela, conseguimos rastrear o aluno/contrato
    public function installment(): BelongsTo
    {
        return $this->belongsTo(Installment::class, 'installment_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function destinationAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'destination_account_id');
    }

    public function financialType(): BelongsTo
    {
        return $this->belongsTo(FinancialType::class, 'financial_type_id');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }
}
