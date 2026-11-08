<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'school_id',
    'name',
    'active',
])]
class CommercialLossReason extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public static function defaultReasons(): array
    {
        return [
            'Preço / Condição financeira',
            'Horário incompatível com a rotina',
            'Distância / Localização da escola',
            'Fechou com outra escola / concorrente',
            'Não respondeu aos contatos (Incontactável)',
            'Desistiu dos estudos / Adiou planos',
            'Outro motivo',
        ];
    }

    public static function ensureDefaultsForSchool(int $schoolId): void
    {
        foreach (self::defaultReasons() as $reason) {
            self::firstOrCreate([
                'school_id' => $schoolId,
                'name' => $reason,
            ], [
                'active' => true,
            ]);
        }
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CommercialLead::class, 'commercial_loss_reason_id');
    }
}

