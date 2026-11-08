<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->boolean('google_drive_backup_enabled')->default(false)->after('ouro_moderno_ead_unidade_nome');
            $table->text('google_drive_client_id')->nullable()->after('google_drive_backup_enabled');
            $table->text('google_drive_client_secret')->nullable()->after('google_drive_client_id');
            $table->text('google_drive_refresh_token')->nullable()->after('google_drive_client_secret');
            $table->text('google_drive_folder_id')->nullable()->after('google_drive_refresh_token');
            $table->timestamp('google_drive_last_backup_at')->nullable()->after('google_drive_folder_id');
            $table->text('google_drive_last_backup_status')->nullable()->after('google_drive_last_backup_at');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'google_drive_backup_enabled',
                'google_drive_client_id',
                'google_drive_client_secret',
                'google_drive_refresh_token',
                'google_drive_folder_id',
                'google_drive_last_backup_at',
                'google_drive_last_backup_status',
            ]);
        });
    }
};
