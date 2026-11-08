<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_goals', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('month');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('target_enrollments')->default(0);
            $table->decimal('target_sales_amount', 12, 2)->default(0.00);
            $table->decimal('target_revenue_amount', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_goals');
    }
};
