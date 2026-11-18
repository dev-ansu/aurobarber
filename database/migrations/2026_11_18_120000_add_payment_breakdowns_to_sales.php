<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->json('payment_details')->nullable();
        });

        Schema::table('product_sales', function (Blueprint $table): void {
            $table->uuid('sale_group_id')->nullable()->index();
            $table->json('payment_details')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('product_sales', function (Blueprint $table): void {
            $table->dropIndex(['sale_group_id']);
            $table->dropColumn(['sale_group_id', 'payment_details']);
        });

        Schema::table('appointments', function (Blueprint $table): void {
            $table->dropColumn('payment_details');
        });
    }
};
