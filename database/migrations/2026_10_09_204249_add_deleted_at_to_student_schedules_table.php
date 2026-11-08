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
        Schema::table('student_schedules', function (Blueprint $table): void {
            $table->softDeletes();
            $table->dropUnique('student_schedules_item_slot_unique');
        });

        DB::statement(
            'CREATE UNIQUE INDEX student_schedules_item_slot_active_unique '
                .'ON student_schedules (contract_item_id, weekday, start_hour, end_hour) '
                .'WHERE deleted_at IS NULL',
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasArchivedSlotDuplicates = DB::table('student_schedules')
            ->select(['contract_item_id', 'weekday', 'start_hour', 'end_hour'])
            ->groupBy(['contract_item_id', 'weekday', 'start_hour', 'end_hour'])
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasArchivedSlotDuplicates) {
            throw new RuntimeException(
                'Schedule history contains archived duplicate slots; preserve the archive migration or resolve duplicates before rollback.',
            );
        }

        DB::statement('DROP INDEX student_schedules_item_slot_active_unique');

        Schema::table('student_schedules', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->unique([
                'contract_item_id',
                'weekday',
                'start_hour',
                'end_hour',
            ], 'student_schedules_item_slot_unique');
        });
    }
};
