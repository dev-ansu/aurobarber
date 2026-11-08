<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_goals')) {
            return;
        }

        if (! Schema::hasColumn('school_goals', 'school_id')) {
            Schema::table('school_goals', function (Blueprint $table): void {
                $table->foreignId('school_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('schools')
                    ->nullOnDelete();
            });

            $defaultSchoolId = DB::table('schools')->orderBy('id')->value('id');
            if ($defaultSchoolId) {
                DB::table('school_goals')
                    ->whereNull('school_id')
                    ->update(['school_id' => $defaultSchoolId]);
            }

            try {
                Schema::table('school_goals', function (Blueprint $table): void {
                    $table->dropUnique(['month', 'year']);
                });
            } catch (Throwable) {
                // Índice já ajustado
            }

            Schema::table('school_goals', function (Blueprint $table): void {
                $table->unique(['school_id', 'month', 'year']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('school_goals') && Schema::hasColumn('school_goals', 'school_id')) {
            try {
                Schema::table('school_goals', function (Blueprint $table): void {
                    $table->dropUnique(['school_id', 'month', 'year']);
                });
            } catch (Throwable) {
            }

            Schema::table('school_goals', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('school_id');
            });
        }
    }
};
