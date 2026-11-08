<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (Schema::hasTable('people')) {
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE people DROP CONSTRAINT IF EXISTS people_cpf_unique;');
                DB::statement('ALTER TABLE people ALTER COLUMN cpf TYPE text;');
            }

            Schema::table('people', function (Blueprint $table) use ($driver) {
                if ($driver !== 'pgsql') {
                    $table->text('cpf')->nullable()->change();
                }
                if (! Schema::hasColumn('people', 'cpf_hash')) {
                    $table->string('cpf_hash', 64)->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('documents')) {
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE documents ALTER COLUMN value TYPE text;');
            }

            Schema::table('documents', function (Blueprint $table) use ($driver) {
                if ($driver !== 'pgsql') {
                    $table->text('value')->change();
                }
                if (! Schema::hasColumn('documents', 'value_hash')) {
                    $table->string('value_hash', 64)->nullable()->index();
                }
            });
        }

        if (Schema::hasTable('collaborators')) {
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE collaborators ALTER COLUMN pix TYPE text;');
            }

            Schema::table('collaborators', function (Blueprint $table) use ($driver) {
                if ($driver !== 'pgsql') {
                    $table->text('pix')->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('people') && Schema::hasColumn('people', 'cpf_hash')) {
            Schema::table('people', function (Blueprint $table) {
                $table->dropIndex(['cpf_hash']);
                $table->dropColumn('cpf_hash');
            });
        }

        if (Schema::hasTable('documents') && Schema::hasColumn('documents', 'value_hash')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropIndex(['value_hash']);
                $table->dropColumn('value_hash');
            });
        }
    }
};
