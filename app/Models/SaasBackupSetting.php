<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasBackupSetting extends Model
{
    protected $table = 'saas_backup_settings';

    protected $fillable = [
        'id',
        'google_drive_enabled',
        'google_drive_client_id',
        'google_drive_client_secret',
        'google_drive_refresh_token',
        'google_drive_folder_id',
    ];

    protected $casts = [
        'google_drive_enabled' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
