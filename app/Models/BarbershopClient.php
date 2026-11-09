<?php

namespace App\Models;

use App\Services\SensitiveClientData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BarbershopClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'barbershop_id',
        'name',
        'phone',
        'birth_date',
        'notes',
        'loyalty_stamps',
        'loyalty_rewards_redeemed',
        'total_visits',
        'total_spent',
        'last_visit_at',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'total_spent' => 'decimal:2',
        'last_visit_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (BarbershopClient $client): void {
            $plainPhone = $client->phone;
            $client->phone_hash = SensitiveClientData::phoneHash($plainPhone);

            if (! SensitiveClientData::isEncrypted($client->getRawOriginal('phone'))) {
                $client->phone = SensitiveClientData::encrypt($plainPhone);
            }
        });
    }

    public function getPhoneAttribute(?string $value): ?string
    {
        return SensitiveClientData::decrypt($value);
    }

    public function setPhoneAttribute(?string $value): void
    {
        $this->attributes['phone'] = SensitiveClientData::encrypt($value);
    }

    public function barbershop(): BelongsTo
    {
        return $this->belongsTo(Barbershop::class);
    }

    public function daysSinceLastVisit(): ?int
    {
        if (! $this->last_visit_at) {
            return null;
        }

        return (int) $this->last_visit_at->startOfDay()->diffInDays(now()->startOfDay());
    }
}
