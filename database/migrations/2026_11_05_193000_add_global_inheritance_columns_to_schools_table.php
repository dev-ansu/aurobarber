<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->boolean('inherit_financial_policy')->default(true)->after('is_headquarters');
            $table->boolean('inherit_pix_settings')->default(false)->after('inherit_financial_policy');
            $table->boolean('inherit_ouro_moderno_ead')->default(false)->after('inherit_pix_settings');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'inherit_financial_policy',
                'inherit_pix_settings',
                'inherit_ouro_moderno_ead',
            ]);
        });
    }
};

