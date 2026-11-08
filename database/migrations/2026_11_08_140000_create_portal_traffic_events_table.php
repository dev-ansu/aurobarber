<?php

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
        Schema::create('portal_traffic_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('scope', 40)->default('aurosoft_landing')->index(); // 'aurosoft_landing' ou 'school_showcase'
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('formation_id')->nullable()->index();
            $table->string('event_type', 50)->index(); // 'page_view', 'cta_demo', 'cta_whatsapp', 'lead_submit'
            $table->string('event_label', 150)->nullable();
            $table->string('visitor_hash', 64)->index();
            $table->string('device_type', 20)->nullable(); // 'mobile', 'desktop', 'tablet'
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('page_url', 500)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();

            // Índices compostos para performance de relatórios e dashboards
            $table->index(['scope', 'created_at']);
            $table->index(['school_id', 'created_at']);
            $table->index(['scope', 'event_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_traffic_events');
    }
};
