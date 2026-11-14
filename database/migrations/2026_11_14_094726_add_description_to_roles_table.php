<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (! Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('guard_name');
            }
        });

        // Preenche descrições amigáveis para leigos nas funções padrão
        $descriptions = [
            'super_admin' => 'Administração técnica global. Acesso irrestrito a toda a infraestrutura, múltiplos estabelecimentos e suporte do sistema.',
            'barbershop_owner' => 'Acesso total e irrestrito. Gerencia financeiro da barbearia (faturamento e despesas), relatórios de lucro, fechamento de comissões da equipe, catálogo de serviços, controle de estoque e configurações do estabelecimento.',
            'barbershop_manager' => 'Gestão operacional do dia a dia. Controla a grade da agenda, abre e fecha o caixa diário, dá baixa em comandas, cadastra clientes e repõe produtos. Não possui permissão para exclusões irreversíveis nem configurações estruturais.',
            'receptionist' => 'Operação de balcão e atendimento ao cliente. Realiza agendamentos de clientes, encaixes na agenda dos profissionais, cadastro de fichas de clientes e recebimento de pagamentos no caixa da recepção.',
            'barber' => 'Acesso restrito ao profissional. Visualiza exclusivamente sua própria agenda de cortes e horários marcados, lança produtos vendidos durante o atendimento e consulta o extrato das suas comissões de serviços e vendas.',
            'panel_user' => 'Acesso básico de leitura para novos colaboradores ou aprendizes em fase de treinamento.',
        ];

        foreach ($descriptions as $roleName => $desc) {
            DB::table('roles')
                ->where('name', $roleName)
                ->whereNull('description')
                ->update(['description' => $desc]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
