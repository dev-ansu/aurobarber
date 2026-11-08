<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            if (! Schema::hasColumn('contracts', 'is_flex')) {
                $table->boolean('is_flex')->default(false)->index()->after('status');
            }
        });

        Schema::table('contract_item_courses', function (Blueprint $table): void {
            if (! Schema::hasColumn('contract_item_courses', 'is_unlocked')) {
                $table->boolean('is_unlocked')->default(false)->index()->after('status');
            }
            if (! Schema::hasColumn('contract_item_courses', 'order')) {
                $table->integer('order')->nullable()->index()->after('course_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table): void {
            if (Schema::hasColumn('contracts', 'is_flex')) {
                $table->dropColumn('is_flex');
            }
        });

        Schema::table('contract_item_courses', function (Blueprint $table): void {
            if (Schema::hasColumn('contract_item_courses', 'is_unlocked')) {
                $table->dropColumn('is_unlocked');
            }
            if (Schema::hasColumn('contract_item_courses', 'order')) {
                $table->dropColumn('order');
            }
        });
    }
};
