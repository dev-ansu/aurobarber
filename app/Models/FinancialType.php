<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FinancialType extends Model
{
    use HasFactory;

    public const TYPE_REVENUE = 'revenue';

    public const TYPE_COST = 'cost';

    public const TYPE_EXPENSE = 'expense';

    public const TYPE_FINANCIAL = 'financial';

    protected $fillable = [
        'barbershop_id',
        'code',
        'name',
        'slug',
        'parent_id',
        'type',
        'is_synthetic',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_synthetic' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_REVENUE => 'Receita Operacional',
            self::TYPE_COST => 'Custo Direto (Serviços e Bancada)',
            self::TYPE_EXPENSE => 'Despesa Operacional Fixa / Ocupação',
            self::TYPE_FINANCIAL => 'Despesa Financeira / Tarifas',
        ];
    }

    public static function getTypeColors(): array
    {
        return [
            self::TYPE_REVENUE => 'success',
            self::TYPE_COST => 'warning',
            self::TYPE_EXPENSE => 'danger',
            self::TYPE_FINANCIAL => 'info',
        ];
    }

    public function setTypeAttribute(?string $value): void
    {
        $normalized = match (strtolower(trim($value ?? ''))) {
            'receita', 'revenue' => self::TYPE_REVENUE,
            'custo', 'cost' => self::TYPE_COST,
            'despesa', 'expense' => self::TYPE_EXPENSE,
            'financeira', 'financial' => self::TYPE_FINANCIAL,
            default => $value ?? self::TYPE_EXPENSE,
        };

        $this->attributes['type'] = $normalized;
    }

    protected static function booted(): void
    {
        static::saving(function (FinancialType $model): void {
            if (empty($model->slug) || $model->isDirty('name')) {
                $model->slug = Str::slug($model->name, '_');
            } else {
                $model->slug = Str::slug($model->slug, '_');
            }
        });
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FinancialType::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(FinancialType::class, 'parent_id')->orderBy('code');
    }

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class, 'financial_type_id');
    }

    public function scopeAnalytical(Builder $query): Builder
    {
        return $query->where('is_synthetic', false);
    }

    public function scopeSynthetic(Builder $query): Builder
    {
        return $query->where('is_synthetic', true);
    }

    public function scopeRevenues(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_REVENUE);
    }

    public function scopeCosts(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_COST);
    }

    public function scopeExpenses(Builder $query): Builder
    {
        return $query->whereIn('type', [self::TYPE_EXPENSE, self::TYPE_COST, self::TYPE_FINANCIAL]);
    }

    public function scopeForBarbershop(Builder $query, ?int $barbershopId): Builder
    {
        if ($barbershopId === null) {
            return $query->whereNull('barbershop_id');
        }

        return $query->where(function (Builder $q) use ($barbershopId): void {
            $q->whereNull('barbershop_id')
                ->orWhere('barbershop_id', $barbershopId);
        });
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->code ? "{$this->code} - {$this->name}" : $this->name,
        );
    }
}
