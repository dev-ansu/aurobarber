<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->string('postal_code', 15)->nullable()->after('cnpj');
            $table->string('street')->nullable()->after('postal_code');
            $table->string('number', 30)->nullable()->after('street');
            $table->string('complement')->nullable()->after('number');
            $table->string('neighborhood')->nullable()->after('complement');
            $table->string('city')->nullable()->after('neighborhood');
            $table->char('state', 2)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            $table->dropColumn([
                'postal_code',
                'street',
                'number',
                'complement',
                'neighborhood',
                'city',
                'state',
            ]);
        });
    }
};
