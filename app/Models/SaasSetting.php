<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\UsesPublicSchema;
use Illuminate\Database\Eloquent\Model;

class SaasSetting extends Model
{
    use UsesPublicSchema;

    protected $fillable = [
        'key',
        'value',
    ];
}
