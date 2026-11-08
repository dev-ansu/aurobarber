<?php

namespace App\Models;

use App\Enums\RelationshipEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Table('responsibles')]
#[Fillable(['person_id', 'financial_responsible', 'relationship'])]
class Responsible extends Model
{
    protected static function booted(): void
    {
        static::updating(function (Responsible $responsible) {
            if ($responsible->isDirty('person_id')) {
                throw new LogicException('Não é permitido alterar a pessoa de um responsável.');
            }
        });
    }

    protected function casts()
    {
        return [
            'financial_responsible' => 'boolean',
            'relationship' => RelationshipEnum::class,
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->person->name;
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class, 'responsible_id');
    }
}
