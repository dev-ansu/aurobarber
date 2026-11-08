<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractItemCompletionHistory extends Model
{
    protected $fillable = [
        'contract_item_id',
        'completed_at',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function contractItem(): BelongsTo
    {
        return $this->belongsTo(ContractItem::class)->withTrashed();
    }
}
