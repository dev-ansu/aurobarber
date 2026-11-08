<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barbershop extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'headline',
        'description',
        'logo_url',
        'banner_url',
        'phone',
        'whatsapp',
        'address',
        'city',
        'instagram',
        'brand_color',
        'opening_hours',
        'pix_key',
        'subscription_status',
        'subscription_plan',
        'mercadopago_preapproval_id',
        'is_active',
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'is_active' => 'boolean',
    ];

    public function professionals(): HasMany
    {
        return $this->hasMany(Professional::class)->orderBy('display_order');
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('display_order');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getFormattedWhatsappAttribute(): ?string
    {
        if (!$this->whatsapp) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->whatsapp);
        return str_starts_with($digits, '55') ? $digits : '55' . $digits;
    }
}
