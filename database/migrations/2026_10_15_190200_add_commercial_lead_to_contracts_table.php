<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->foreignId('commercial_lead_id')
                ->nullable()
                ->unique()
                ->after('student_id')
                ->constrained('commercial_leads')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('commercial_lead_id');
        });
    }
};
