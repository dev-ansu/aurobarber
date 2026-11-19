<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('saas_backup_settings')) {
            Schema::create('saas_backup_settings', function (Blueprint $table): void {
                $table->id();
                $table->boolean('google_drive_enabled')->default(false);
                $table->text('google_drive_client_id')->nullable();
                $table->text('google_drive_client_secret')->nullable();
                $table->text('google_drive_refresh_token')->nullable();
                $table->string('google_drive_folder_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_backup_settings');
    }
};
