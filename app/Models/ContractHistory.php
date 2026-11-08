<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'contract_id',
    'user_id',
    'type',
    'occurred_at',
    'next_follow_up_at',
    'description',
])]
class ContractHistory extends Model
{
    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public static function types(): array
    {
        return [
            'general' => 'Acompanhamento',
            'post_sale' => 'Pós-venda',
            'absence_follow_up' => 'Contato por falta',
            'financial_follow_up' => 'Acompanhamento financeiro',
            'pedagogical_follow_up' => 'Acompanhamento pedagógico',
        ];
    }

    public function typeDefinition(): BelongsTo
    {
        return $this->belongsTo(ContractHistoryType::class, 'type', 'slug');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->typeDefinition?->name ?? (self::types()[$this->type] ?? $this->type);
    }

    protected static function booted(): void
    {
        static::creating(function (self $history): void {
            $history->user_id ??= auth()->id();
            $history->occurred_at ??= now();
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
