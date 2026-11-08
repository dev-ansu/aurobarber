<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('integration_settings')) {
            Schema::table('integration_settings', function (Blueprint $table) {
                // Remove restrição única antiga por provedor (permitindo múltiplos registros por escola)
                try {
                    $table->dropUnique(['provider']);
                } catch (\Throwable) {
                    // Ignora caso não exista
                }

                try {
                    $table->unique(['provider', 'school_id'], 'integration_settings_provider_school_unique');
                } catch (\Throwable) {
                    // Ignora se já existir
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('integration_settings')) {
            Schema::table('integration_settings', function (Blueprint $table) {
                try {
                    $table->dropUnique('integration_settings_provider_school_unique');
                } catch (\Throwable) {
                }

                try {
                    $table->unique('provider');
                } catch (\Throwable) {
                }
            });
        }
    }
};

