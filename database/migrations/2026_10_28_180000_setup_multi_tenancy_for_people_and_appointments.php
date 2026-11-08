<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela pivot school_person (vínculo de Pessoas por Escola)
        if (! Schema::hasTable('school_person')) {
            Schema::create('school_person', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['school_id', 'person_id']);
            });
        }

        // 2. school_id em school_appointments
        if (Schema::hasTable('school_appointments') && ! Schema::hasColumn('school_appointments', 'school_id')) {
            Schema::table('school_appointments', function (Blueprint $table) {
                $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            });
        }

        // 3. Backfill inteligente para a primeira escola existente
        $firstSchool = DB::table('schools')->orderBy('id', 'asc')->first();
        $defaultSchoolId = $firstSchool?->id ?? 1;

        if (Schema::hasTable('school_appointments')) {
            DB::table('school_appointments')
                ->whereNull('school_id')
                ->update(['school_id' => $defaultSchoolId]);
        }

        if (Schema::hasTable('people') && Schema::hasTable('school_person')) {
            $peopleIds = DB::table('people')->pluck('id');
            foreach ($peopleIds as $personId) {
                DB::table('school_person')->updateOrInsert(
                    ['school_id' => $defaultSchoolId, 'person_id' => $personId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('school_appointments') && Schema::hasColumn('school_appointments', 'school_id')) {
            Schema::table('school_appointments', function (Blueprint $table) {
                $table->dropForeign(['school_id']);
                $table->dropColumn('school_id');
            });
        }

        Schema::dropIfExists('school_person');
    }
};
