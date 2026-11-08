<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('responsible_id')->nullable()->change();
            $table->decimal('total_value', 10, 2)->default(0.00)->change();
        });
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('responsible_id')->nullable(false)->change();
            $table->decimal('total_value', 10, 2)->nullable(false)->change();
        });
    }
};

