<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolPartner extends Model
{
    protected $fillable = [
        'school_id',
        'name',
        'cpf',
        'role',
        'ownership_percentage',
        'legal_representative',
    ];

    protected function casts(): array
    {
        return [
            'ownership_percentage' => 'decimal:2',
            'legal_representative' => 'boolean',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
