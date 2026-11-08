<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commercial_leads', function (Blueprint $table): void {
            $table->foreignId('city_id')->nullable()->after('person_id')->constrained()->nullOnDelete();
            $table->string('neighborhood')->nullable()->after('city_id');
            $table->index(['city_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('commercial_leads', function (Blueprint $table): void {
            $table->dropIndex(['city_id', 'created_at']);
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn('neighborhood');
        });
    }
};
