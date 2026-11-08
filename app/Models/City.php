<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('cities')]
#[Fillable(['name', 'state_id', 'ibge_code'])]
class City extends Model
{
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function people(): HasMany
    {
        return $this->hasMany(Person::class);
    }
}
