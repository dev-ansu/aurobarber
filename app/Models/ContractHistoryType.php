<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'is_active', 'sort_order'])]
class ContractHistoryType extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ContractHistory::class, 'type', 'slug');
    }
}
