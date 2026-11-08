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
        if (! Schema::hasColumn('audit_logs', 'school_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->foreignId('school_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('schools')
                    ->nullOnDelete();

                $table->index(['school_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('audit_logs', 'school_id')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('school_id');
            });
        }
    }
};
