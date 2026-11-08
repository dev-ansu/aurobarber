<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('student_announcements') && ! Schema::hasColumn('student_announcements', 'school_id')) {
            Schema::table('student_announcements', function (Blueprint $table): void {
                $table->foreignId('school_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('schools')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('satisfaction_surveys') && ! Schema::hasColumn('satisfaction_surveys', 'school_id')) {
            Schema::table('satisfaction_surveys', function (Blueprint $table): void {
                $table->foreignId('school_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('schools')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('satisfaction_survey_responses') && ! Schema::hasColumn('satisfaction_survey_responses', 'school_id')) {
            Schema::table('satisfaction_survey_responses', function (Blueprint $table): void {
                $table->foreignId('school_id')
                    ->nullable()
                    ->after('student_attendance_id')
                    ->constrained('schools')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('satisfaction_survey_responses') && Schema::hasColumn('satisfaction_survey_responses', 'school_id')) {
            Schema::table('satisfaction_survey_responses', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('school_id');
            });
        }

        if (Schema::hasTable('satisfaction_surveys') && Schema::hasColumn('satisfaction_surveys', 'school_id')) {
            Schema::table('satisfaction_surveys', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('school_id');
            });
        }

        if (Schema::hasTable('student_announcements') && Schema::hasColumn('student_announcements', 'school_id')) {
            Schema::table('student_announcements', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('school_id');
            });
        }
    }
};
