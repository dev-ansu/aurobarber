<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Campos adicionais na tabela 'formations' (100% opcionais e com valores padrão seguros)
        if (Schema::hasTable('formations')) {
            Schema::table('formations', function (Blueprint $table) {
                if (! Schema::hasColumn('formations', 'image_path')) {
                    $table->string('image_path')->nullable()->after('description');
                }
                if (! Schema::hasColumn('formations', 'workload_hours')) {
                    $table->unsignedInteger('workload_hours')->nullable()->after('image_path');
                }
                if (! Schema::hasColumn('formations', 'duration_months')) {
                    $table->unsignedSmallInteger('duration_months')->nullable()->after('workload_hours');
                }
                if (! Schema::hasColumn('formations', 'has_certificate')) {
                    $table->boolean('has_certificate')->default(true)->after('duration_months');
                }
                if (! Schema::hasColumn('formations', 'highlight_badge')) {
                    $table->string('highlight_badge')->nullable()->after('has_certificate');
                }
                if (! Schema::hasColumn('formations', 'short_description')) {
                    $table->text('short_description')->nullable()->after('highlight_badge');
                }
                if (! Schema::hasColumn('formations', 'show_in_showcase')) {
                    $table->boolean('show_in_showcase')->default(true)->after('short_description');
                }
                if (! Schema::hasColumn('formations', 'order')) {
                    $table->integer('order')->default(0)->after('show_in_showcase');
                }
            });
        }

        // 2. Campos adicionais na tabela 'schools' (100% opcionais e com valores padrão seguros)
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (! Schema::hasColumn('schools', 'whatsapp')) {
                    $table->string('whatsapp')->nullable()->after('phone');
                }
                if (! Schema::hasColumn('schools', 'instagram')) {
                    $table->string('instagram')->nullable()->after('website');
                }
                if (! Schema::hasColumn('schools', 'facebook')) {
                    $table->string('facebook')->nullable()->after('instagram');
                }
                if (! Schema::hasColumn('schools', 'hero_headline')) {
                    $table->string('hero_headline')->nullable()->after('logo_path');
                }
                if (! Schema::hasColumn('schools', 'hero_subheadline')) {
                    $table->text('hero_subheadline')->nullable()->after('hero_headline');
                }
                if (! Schema::hasColumn('schools', 'custom_students_count_text')) {
                    $table->string('custom_students_count_text')->nullable()->after('hero_subheadline');
                }
                if (! Schema::hasColumn('schools', 'show_network_units')) {
                    $table->boolean('show_network_units')->default(true)->after('custom_students_count_text');
                }
                if (! Schema::hasColumn('schools', 'showcase_enabled')) {
                    $table->boolean('showcase_enabled')->default(true)->after('show_network_units');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('formations')) {
            Schema::table('formations', function (Blueprint $table) {
                $columns = [
                    'image_path',
                    'workload_hours',
                    'duration_months',
                    'has_certificate',
                    'highlight_badge',
                    'short_description',
                    'show_in_showcase',
                    'order',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('formations', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                $columns = [
                    'whatsapp',
                    'instagram',
                    'facebook',
                    'hero_headline',
                    'hero_subheadline',
                    'custom_students_count_text',
                    'show_network_units',
                    'showcase_enabled',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('schools', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
