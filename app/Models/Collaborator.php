<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Table('collaborators')]
#[Fillable([
    'person_id',
    'admission_date',
    'dismissal_date',
    'role',
    'pix',
    'active',
    'is_educator',
    'commission_type',
    'commission_rate',
    'hourly_rate',
    'commission_notes',
])]
class Collaborator extends Model
{
    public const COMMISSION_TYPE_NONE = 'none';

    public const COMMISSION_TYPE_FIXED_PER_CONTRACT = 'fixed_per_contract';

    public const COMMISSION_TYPE_PERCENTAGE_CONTRACT = 'percentage_contract';

    public const COMMISSION_TYPE_PERCENTAGE_RECEIVED = 'percentage_received';

    public const COMMISSION_TYPE_HOURLY_RATE = 'hourly_rate';

    protected $casts = [
        'admission_date' => 'date',
        'dismissal_date' => 'date',
        'is_educator' => 'boolean',
        'commission_rate' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
    ];

    protected function pix(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                try {
                    return \Illuminate\Support\Facades\Crypt::decryptString($value);
                } catch (\Illuminate\Contracts\Encryption\DecryptException) {
                    return $value;
                } catch (\Throwable) {
                    return $value;
                }
            },
            set: function (?string $value): ?string {
                if (blank($value)) {
                    return null;
                }

                return \Illuminate\Support\Facades\Crypt::encryptString(trim($value));
            }
        );
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'person_id', 'person_id');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'collaborator_courses');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->person?->name ?? 'Colaborador #'.$this->id;
    }

    public function getFormattedCommissionRuleAttribute(): string
    {
        return match ($this->commission_type) {
            self::COMMISSION_TYPE_FIXED_PER_CONTRACT => 'R$ '.number_format((float) $this->commission_rate, 2, ',', '.').' por matrícula',
            self::COMMISSION_TYPE_PERCENTAGE_CONTRACT => number_format((float) $this->commission_rate, 2, ',', '.').'% sobre o valor das vendas',
            self::COMMISSION_TYPE_PERCENTAGE_RECEIVED => number_format((float) $this->commission_rate, 2, ',', '.').'% sobre parcelas recebidas',
            self::COMMISSION_TYPE_HOURLY_RATE => 'R$ '.number_format((float) $this->hourly_rate, 2, ',', '.').' / hora-aula',
            default => 'Sem comissão',
        };
    }

    public function calculateCommission(
        int $contractsCount = 0,
        float $totalSales = 0.0,
        float $totalPaid = 0.0,
        float $teachingHours = 0.0
    ): float {
        $rate = (float) $this->commission_rate;

        return match ($this->commission_type) {
            self::COMMISSION_TYPE_FIXED_PER_CONTRACT => round($contractsCount * $rate, 2),
            self::COMMISSION_TYPE_PERCENTAGE_CONTRACT => round($totalSales * ($rate / 100), 2),
            self::COMMISSION_TYPE_PERCENTAGE_RECEIVED => round($totalPaid * ($rate / 100), 2),
            self::COMMISSION_TYPE_HOURLY_RATE => round($teachingHours * (float) $this->hourly_rate, 2),
            default => 0.0,
        };
    }
}
