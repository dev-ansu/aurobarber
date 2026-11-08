<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->convertStandaloneCoursesToFormations();
        $this->createContractItemCourses();

        Schema::table('contract_items', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropForeign(['formation_id']);
            $table->dropColumn(['course_id', 'price']);
            $table->foreignId('formation_id')
                ->nullable(false)
                ->change();
            $table->foreign('formation_id')
                ->references('id')
                ->on('formations')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contract_items', function (Blueprint $table) {
            $table->dropForeign(['formation_id']);
            $table->foreignId('formation_id')
                ->nullable()
                ->change();
            $table->foreign('formation_id')
                ->references('id')
                ->on('formations')
                ->nullOnDelete();
            $table->foreignId('course_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->decimal('price', 10, 2)->nullable();
        });

        DB::table('contract_items')
            ->join('formations', 'formations.id', '=', 'contract_items.formation_id')
            ->join('formation_course', 'formation_course.formation_id', '=', 'formations.id')
            ->where('formations.description', 'Formação de compatibilidade criada durante a migração de cursos avulsos.')
            ->select([
                'contract_items.id as contract_item_id',
                'formation_course.course_id',
            ])
            ->get()
            ->each(function (object $item): void {
                DB::table('contract_items')
                    ->where('id', $item->contract_item_id)
                    ->update([
                        'course_id' => $item->course_id,
                        'formation_id' => null,
                    ]);
            });

        Schema::table('contract_items', function (Blueprint $table) {
            $table->dropForeign(['formation_id']);
            $table->foreignId('formation_id')
                ->nullable()
                ->change();
            $table->foreign('formation_id')
                ->references('id')
                ->on('formations')
                ->nullOnDelete();
        });

        Schema::dropIfExists('contract_item_courses');
    }

    private function convertStandaloneCoursesToFormations(): void
    {
        $invalidItems = DB::table('contract_items')
            ->whereNull('formation_id')
            ->whereNull('course_id')
            ->pluck('id');

        if ($invalidItems->isNotEmpty()) {
            throw new RuntimeException(
                'Itens de contrato sem curso ou formação precisam ser corrigidos antes da migração: '
                    .$invalidItems->implode(', '),
            );
        }

        $legacyItems = DB::table('contract_items')
            ->whereNull('formation_id')
            ->whereNotNull('course_id')
            ->get(['id', 'contract_id', 'course_id']);

        foreach ($legacyItems as $item) {
            $now = now();
            $formationId = DB::table('formations')->insertGetId([
                'name' => "Formação migrada do contrato {$item->contract_id} (curso {$item->course_id})",
                'description' => 'Formação de compatibilidade criada durante a migração de cursos avulsos.',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('formation_course')->insert([
                'formation_id' => $formationId,
                'course_id' => $item->course_id,
                'order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('contract_items')
                ->where('id', $item->id)
                ->update(['formation_id' => $formationId]);
        }
    }

    private function createContractItemCourses(): void
    {
        DB::table('contract_items')
            ->join('formation_course', 'formation_course.formation_id', '=', 'contract_items.formation_id')
            ->select([
                'contract_items.id as contract_item_id',
                'formation_course.course_id',
            ])
            ->orderBy('contract_items.id')
            ->orderBy('formation_course.order')
            ->orderBy('formation_course.course_id')
            ->get()
            ->each(function (object $course): void {
                DB::table('contract_item_courses')->insertOrIgnore([
                    'contract_item_id' => $course->contract_item_id,
                    'course_id' => $course->course_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }
};
