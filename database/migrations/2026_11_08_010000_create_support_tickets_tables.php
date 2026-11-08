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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('protocol', 30)->unique();
            
            // Tipo do chamado:
            // - student_support: Aluno -> Escola
            // - school_support: Escola -> Matriz SaaS Aurosoft
            // - system_incident: Erro do Sistema capturado automaticamente pela Telemetria
            $table->string('type', 30)->default('student_support')->index();
            
            $table->string('category', 50)->default('geral')->index();
            $table->string('priority', 20)->default('medium')->index(); // low, medium, high, critical
            $table->string('status', 30)->default('open')->index(); // open, in_progress, waiting_user, resolved, closed
            
            // Relacionamentos e Multi-Tenancy (IDs de tenant sem FK direta de banco, pois tickets centraliza em public)
            $table->unsignedBigInteger('school_id')->nullable()->index();
            $table->unsignedBigInteger('contract_id')->nullable()->index();
            $table->unsignedBigInteger('student_id')->nullable()->index();
            $table->unsignedBigInteger('opened_by_user_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_to_user_id')->nullable()->index();
            
            // Conteúdo principal
            $table->string('subject', 255);
            $table->text('description')->nullable();
            
            // Telemetria técnica para incidentes (system_incident)
            $table->string('exception_class', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->string('error_file_line', 255)->nullable();
            $table->string('error_hash', 64)->nullable()->index(); // Hash MD5 para agrupar ocorrências repetidas do mesmo erro
            $table->text('request_url')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->json('request_payload')->nullable();
            $table->longText('stack_trace')->nullable();
            $table->unsignedInteger('occurrences_count')->default(1);
            $table->timestamp('last_occurred_at')->nullable();
            
            // Conclusão / Resolução
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('resolved_by_user_id')->nullable()->index();
            
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            
            // Autor da mensagem (Usuário da equipe ou Aluno)
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('person_id')->nullable()->index();
            $table->string('author_name', 150)->nullable();
            $table->string('author_type', 30)->default('user'); // student, staff, system, master
            
            $table->text('message');
            $table->boolean('is_internal_note')->default(false); // Anotação técnica privada da equipe
            $table->json('attachments')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
