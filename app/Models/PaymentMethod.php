<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (Model $model) {
            // Se o slug estiver vazio ou o nome foi alterado, gera/regera o slug com '_'
            if (empty($model->slug) || $model->isDirty('name')) {
                $model->slug = Str::slug($model->name, '_');
            } else {
                // Assegura que mesmo um slug inserido manualmente siga o formato correto
                $model->slug = Str::slug($model->slug, '_');
            }
        });
    }

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class, 'payment_method_id');
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'payment_method_id');
    }
}
