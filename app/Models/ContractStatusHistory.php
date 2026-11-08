<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractStatusHistory extends Model
{
    protected $table = 'contract_status_histories';

    protected $fillable = [
        'contract_id',
        'user_id',
        'from_status',
        'to_status',
        'reason',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
