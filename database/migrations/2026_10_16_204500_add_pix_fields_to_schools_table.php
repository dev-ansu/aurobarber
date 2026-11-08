<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('pix_key', 255)->nullable()->after('state');
            $table->string('pix_key_type', 30)->nullable()->after('pix_key');
            $table->string('pix_merchant_name', 100)->nullable()->after('pix_key_type');
            $table->string('pix_merchant_city', 50)->nullable()->after('pix_merchant_name');
            $table->text('pix_instructions')->nullable()->after('pix_merchant_city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'pix_key',
                'pix_key_type',
                'pix_merchant_name',
                'pix_merchant_city',
                'pix_instructions',
            ]);
        });
    }
};
