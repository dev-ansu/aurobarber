<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (! Schema::hasColumn('schools', 'allowed_ips')) {
                    $table->text('allowed_ips')->nullable()->after('showcase_enabled');
                }
                if (! Schema::hasColumn('schools', 'enforce_school_ip_for_lms')) {
                    $table->boolean('enforce_school_ip_for_lms')->default(false)->after('allowed_ips');
                }
                if (! Schema::hasColumn('schools', 'enforce_school_ip_for_attendance')) {
                    $table->boolean('enforce_school_ip_for_attendance')->default(false)->after('enforce_school_ip_for_lms');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (Schema::hasColumn('schools', 'enforce_school_ip_for_attendance')) {
                    $table->dropColumn('enforce_school_ip_for_attendance');
                }
                if (Schema::hasColumn('schools', 'enforce_school_ip_for_lms')) {
                    $table->dropColumn('enforce_school_ip_for_lms');
                }
                if (Schema::hasColumn('schools', 'allowed_ips')) {
                    $table->dropColumn('allowed_ips');
                }
            });
        }
    }
};
