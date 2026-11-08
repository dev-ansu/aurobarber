<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collaborators', function (Blueprint $table): void {
            $table->string('commission_type', 30)->default('none')->after('pix');
            $table->decimal('commission_rate', 10, 2)->default(0.00)->after('commission_type');
            $table->decimal('hourly_rate', 10, 2)->default(0.00)->after('commission_rate');
            $table->text('commission_notes')->nullable()->after('hourly_rate');
        });
    }

    public function down(): void
    {
        Schema::table('collaborators', function (Blueprint $table): void {
            $table->dropColumn([
                'commission_type',
                'commission_rate',
                'hourly_rate',
                'commission_notes',
            ]);
        });
    }
};
