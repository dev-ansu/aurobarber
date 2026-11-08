<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('installments', 'school_id')) {
            Schema::table('installments', function (Blueprint $table): void {
                $table->unsignedBigInteger('school_id')->nullable()->after('id')->index();
                $table->foreign('school_id')->references('id')->on('schools')->nullOnDelete();
            });
        }

        // Backfill school_id from the parent contract's school_id
        if (Schema::hasTable('installments') && Schema::hasTable('contracts')) {
            $contracts = DB::table('contracts')->whereNotNull('school_id')->pluck('school_id', 'id');
            foreach ($contracts as $contractId => $schoolId) {
                DB::table('installments')
                    ->where('contract_id', $contractId)
                    ->whereNull('school_id')
                    ->update(['school_id' => $schoolId]);
            }

            $defaultSchoolId = DB::table('schools')->orderBy('id')->value('id');
            if ($defaultSchoolId) {
                DB::table('installments')
                    ->whereNull('school_id')
                    ->update(['school_id' => $defaultSchoolId]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('installments', 'school_id')) {
            Schema::table('installments', function (Blueprint $table): void {
                $table->dropForeign(['school_id']);
                $table->dropColumn('school_id');
            });
        }
    }
};
