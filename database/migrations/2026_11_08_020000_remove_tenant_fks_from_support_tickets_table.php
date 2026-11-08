<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $fks = [
            'support_tickets' => [
                'support_tickets_contract_id_foreign',
                'support_tickets_student_id_foreign',
                'support_tickets_opened_by_user_id_foreign',
                'support_tickets_assigned_to_user_id_foreign',
                'support_tickets_resolved_by_user_id_foreign',
            ],
            'support_ticket_messages' => [
                'support_ticket_messages_user_id_foreign',
                'support_ticket_messages_person_id_foreign',
            ],
        ];

        foreach ($fks as $table => $constraints) {
            foreach ($constraints as $fk) {
                try {
                    DB::statement("ALTER TABLE public.{$table} DROP CONSTRAINT IF EXISTS {$fk}");
                } catch (\Throwable) {
                    // Ignora caso a constraint já tenha sido removida ou não exista
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Operação unidirecional intencional: tabelas de tickets vivem no schema public
        // e não devem ter foreign keys rígidas para tabelas dinâmicas de tenants.
    }
};
