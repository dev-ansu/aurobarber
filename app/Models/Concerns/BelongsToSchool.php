<?php

namespace App\Models\Concerns;

use App\Models\School;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToSchool
{
    public static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school_tenant', function (Builder $builder): void {
            $tenant = Filament::getTenant();

            if ($tenant instanceof School) {
                $builder->where($builder->getModel()->qualifyColumn('school_id'), $tenant->getKey());
            }
        });

        static::creating(function (Model $model): void {
            if (empty($model->getAttribute('school_id'))) {
                $tenant = Filament::getTenant();
                if ($tenant instanceof School) {
                    $model->setAttribute('school_id', $tenant->getKey());
                }
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
