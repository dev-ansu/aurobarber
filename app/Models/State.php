<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('states')]
#[Fillable(['name', 'acronym'])]
class State extends Model
{
    public function city(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
