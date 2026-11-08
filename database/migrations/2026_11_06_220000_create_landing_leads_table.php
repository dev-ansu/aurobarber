<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('school_name', 160);
            $table->string('email', 160);
            $table->string('phone', 40);
            $table->string('city', 100)->nullable();
            $table->string('students_count', 60)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 30)->default('novo')->index(); // novo, em_contato, agendado, convertido, arquivado
            $table->text('notes')->nullable();
            $table->string('ip', 45)->nullable();
            $table->foreignId('converted_license_id')->nullable()->constrained('licenses')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_leads');
    }
};
