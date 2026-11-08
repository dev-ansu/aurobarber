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
        // 1. Tabela pivô de Usuários e Escolas
        if (! Schema::hasTable('school_user')) {
            Schema::create('school_user', function (Blueprint $table) {
                $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->primary(['school_id', 'user_id']);
                $table->timestamps();
            });
        }

        // 2. Adiciona school_id nas tabelas operacionais da escola
        $tablesWithSchool = [
            'contracts',
            'payables',
            'cash_registers',
            'classrooms',
            'student_schedules',
            'student_attendances',
            'commercial_leads',
            'commercial_visits',
            'task_boards',
            'integration_settings',
        ];

        foreach ($tablesWithSchool as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'school_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
                });
            }
        }

        // 3. Backfill seguro: garante a Escola 1 e vincula todos os registros existentes a ela
        $firstSchool = DB::table('schools')->orderBy('id', 'asc')->first();
        if (! $firstSchool) {
            DB::table('schools')->insert([
                'id' => 1,
                'legal_name' => 'Escola Matriz',
                'trade_name' => 'Matriz',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $schoolId = 1;
        } else {
            $schoolId = $firstSchool->id;
        }

        foreach ($tablesWithSchool as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'school_id')) {
                DB::table($tableName)->whereNull('school_id')->update(['school_id' => $schoolId]);
            }
        }

        if (Schema::hasTable('users') && Schema::hasTable('school_user')) {
            $userIds = DB::table('users')->pluck('id');
            foreach ($userIds as $userId) {
                DB::table('school_user')->updateOrInsert(
                    ['school_id' => $schoolId, 'user_id' => $userId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tablesWithSchool = [
            'contracts',
            'payables',
            'cash_registers',
            'classrooms',
            'student_schedules',
            'student_attendances',
            'commercial_leads',
            'commercial_visits',
            'task_boards',
            'integration_settings',
        ];

        foreach ($tablesWithSchool as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'school_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropForeign(['school_id']);
                    $table->dropColumn('school_id');
                });
            }
        }

        Schema::dropIfExists('school_user');
    }
};
