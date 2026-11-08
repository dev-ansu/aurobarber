<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contract_item_courses', function (Blueprint $table) {
            $table->string('status')->default('not_started')->index();
            $table->decimal('grade', 10, 2)->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->string('progress_source')->default('manual');
            $table->timestamp('lms_synced_at')->nullable();
            $table->softDeletes();
        });

        DB::statement(
            'CREATE UNIQUE INDEX contract_item_courses_active_unique '
            .'ON contract_item_courses (contract_item_id, course_id) WHERE deleted_at IS NULL',
        );

        Schema::table('contract_items', function (Blueprint $table) {
            $table->timestamp('formation_completed_at')->nullable()->index();
            $table->softDeletes();
        });

        Schema::create('contract_course_progress_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_item_course_id')
                ->constrained('contract_item_courses')
                ->cascadeOnDelete();
            $table->string('status');
            $table->decimal('grade', 10, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('source');
            $table->timestamps();

            $table->index(['status', 'completed_at']);
        });

        Schema::create('contract_item_completion_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_item_id')
                ->constrained('contract_items')
                ->cascadeOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->string('source')->default('course_progress');
            $table->timestamps();

            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_item_completion_histories');
        Schema::dropIfExists('contract_course_progress_histories');
        Schema::table('contract_item_courses', function (Blueprint $table) {
            $table->dropUnique('contract_item_courses_active_unique');
        });

        Schema::table('contract_items', function (Blueprint $table) {
            $table->dropColumn(['formation_completed_at', 'deleted_at']);
        });

        Schema::table('contract_item_courses', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'status',
                'grade',
                'completed_at',
                'progress_source',
                'lms_synced_at',
            ]);
        });
    }
};
