<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesPublicSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SaasBillingSetting extends Model
{
    use UsesPublicSchema;

    protected $table = 'saas_billing_settings';

    protected $fillable = [
        'id',
        'pix_enabled',
        'pix_key',
        'merchant_name',
        'merchant_city',
        'support_whatsapp',
        'payment_instructions',
    ];

    protected $casts = [
        'pix_enabled' => 'boolean',
    ];

    public function getTable(): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? 'public.saas_billing_settings'
            : 'saas_billing_settings';
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            [
                'pix_enabled' => false,
                'merchant_name' => 'AUROSOFT',
                'merchant_city' => 'FORTALEZA',
                'payment_instructions' => 'Após pagar, envie o comprovante nesta página. A equipe Aurosoft validará o pagamento e renovará a licença da barbearia.',
            ],
        );
    }
}
