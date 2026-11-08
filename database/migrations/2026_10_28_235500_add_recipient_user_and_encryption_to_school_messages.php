<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('school_messages') && ! Schema::hasColumn('school_messages', 'recipient_user_id')) {
            Schema::table('school_messages', function (Blueprint $table) {
                $table->foreignId('recipient_user_id')->nullable()->after('recipient_school_id')->constrained('users')->nullOnDelete();
                $table->index(['recipient_user_id', 'read_at']);
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'last_seen_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_seen_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('school_messages') && Schema::hasColumn('school_messages', 'recipient_user_id')) {
            Schema::table('school_messages', function (Blueprint $table) {
                $table->dropForeign(['recipient_user_id']);
                $table->dropColumn('recipient_user_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'last_seen_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('last_seen_at');
            });
        }
    }
};

