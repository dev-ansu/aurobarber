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
        Schema::table('schools', function (Blueprint $table) {
            $table->boolean('is_headquarters')->default(false)->after('trade_name');
        });

        $firstSchool = DB::table('schools')->orderBy('id')->first();
        if ($firstSchool) {
            DB::table('schools')->where('id', $firstSchool->id)->update(['is_headquarters' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('is_headquarters');
        });
    }
};
