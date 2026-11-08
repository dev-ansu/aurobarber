<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingLead extends Model
{
    use HasFactory;

    protected $table = 'landing_leads';

    protected $fillable = [
        'name',
        'school_name',
        'email',
        'phone',
        'city',
        'students_count',
        'message',
        'status',
        'notes',
        'ip',
        'converted_license_id',
    ];

    public function convertedLicense(): BelongsTo
    {
        return $this->belongsTo(License::class, 'converted_license_id');
    }

    public function getCleanPhone(): string
    {
        $clean = preg_replace('/[^0-9]/', '', (string) $this->phone);
        if (strlen($clean) <= 11 && ! str_starts_with($clean, '55')) {
            $clean = '55' . $clean;
        }

        return $clean;
    }

    public function getWhatsAppUrl(): string
    {
        $phone = $this->getCleanPhone();
        $text = urlencode("Olá {$this->name}! Sou o Anderson da Aurosoft. Recebi sua solicitação de demonstração sobre a {$this->school_name}. Como posso ajudar?");

        return "https://wa.me/{$phone}?text={$text}";
    }
}
