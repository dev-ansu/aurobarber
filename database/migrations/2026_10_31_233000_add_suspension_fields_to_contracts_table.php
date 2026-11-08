<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            if (! Schema::hasColumn('contracts', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('contracts', 'suspension_expected_return_date')) {
                $table->date('suspension_expected_return_date')->nullable()->after('suspended_at');
            }
            if (! Schema::hasColumn('contracts', 'suspension_returned_at')) {
                $table->timestamp('suspension_returned_at')->nullable()->after('suspension_expected_return_date');
            }
            if (! Schema::hasColumn('contracts', 'suspension_reason')) {
                $table->string('suspension_reason', 255)->nullable()->after('suspension_returned_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn([
                'suspended_at',
                'suspension_expected_return_date',
                'suspension_returned_at',
                'suspension_reason',
            ]);
        });
    }
};

