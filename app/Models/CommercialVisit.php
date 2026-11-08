<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'school_id',
    'commercial_lead_id',
    'collaborator_id',
    'contract_id',
    'visited_at',
    'status',
    'purpose',
    'notes',
])]
class CommercialVisit extends Model
{
    use BelongsToSchool;

    protected function casts(): array
    {
        return ['visited_at' => 'datetime'];
    }

    public static function statuses(): array
    {
        return [
            'attended' => 'Realizada',
            'scheduled' => 'Agendada',
            'no_show' => 'Não compareceu',
            'canceled' => 'Cancelada',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CommercialLead::class, 'commercial_lead_id')->withTrashed();
    }

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }
}
