<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Seções e Abas do Gerenciador de Cargos e Permissões
    |--------------------------------------------------------------------------
    */
    'section' => 'Módulos e Recursos da Barbearia',
    'resources' => 'Tabelas e Cadastros Principais',
    'widgets' => 'Cards e Indicadores do Painel Inicial',
    'pages' => 'Telas Especiais e Rotinas de Gestão',
    'custom' => 'Permissões Especiais de Segurança',

    /*
    |--------------------------------------------------------------------------
    | Campos de Formulário e Mensagens
    |--------------------------------------------------------------------------
    */
    'field.name' => 'Identificador Técnico do Cargo',
    'field.guard_name' => 'Guard de Autenticação',
    'field.permissions' => 'Permissões do Cargo',
    'field.select_all.name' => 'Marcar todas as permissões',
    'field.select_all.message' => 'Habilita acesso irrestrito a todos os recursos, páginas e relatórios selecionados abaixo.',
    'forbidden' => 'Você não tem permissão para acessar esta área.',

    /*
    |--------------------------------------------------------------------------
    | Navegação do Painel
    |--------------------------------------------------------------------------
    */
    'nav.group' => 'Equipe',
    'nav.role.label' => 'Cargos & Permissões',
    'nav.role.icon' => 'heroicon-o-shield-check',
    'resource.label.role' => 'Cargo & Permissões',
    'resource.label.roles' => 'Cargos & Permissões',

    /*
    |--------------------------------------------------------------------------
    | Ações de Recursos (O que o usuário pode fazer nas tabelas)
    |--------------------------------------------------------------------------
    */
    'view_any' => 'Listar e Ver Tabela (Acesso ao Menu)',
    'view' => 'Visualizar Ficha Completa (Ver Detalhes)',
    'create' => 'Cadastrar / Adicionar Novo',
    'update' => 'Editar / Alterar Dados Existentes',
    'delete' => 'Excluir Registro (Enviar para Lixeira)',
    'delete_any' => 'Excluir Vários em Lote',
    'force_delete' => 'Exclusão Permanente (Sem Recuperação)',
    'force_delete_any' => 'Exclusão Permanente em Lote',
    'restore' => 'Restaurar Item da Lixeira',
    'restore_any' => 'Restaurar Itens em Lote',
    'replicate' => 'Duplicar / Clonar Registro',
    'reorder' => 'Reordenar Posição / Prioridade',

    // Compatibilidade com chaves aninhadas do pacote
    'resource_permission_prefixes_labels' => [
        'view' => 'Visualizar Ficha Completa (Ver Detalhes)',
        'view_any' => 'Listar e Ver Tabela (Acesso ao Menu)',
        'create' => 'Cadastrar / Adicionar Novo',
        'update' => 'Editar / Alterar Dados Existentes',
        'delete' => 'Excluir Registro (Enviar para Lixeira)',
        'delete_any' => 'Excluir Vários em Lote',
        'force_delete' => 'Exclusão Permanente (Sem Recuperação)',
        'force_delete_any' => 'Exclusão Permanente em Lote',
        'restore' => 'Restaurar Item da Lixeira',
        'restore_any' => 'Restaurar Itens em Lote',
        'replicate' => 'Duplicar / Clonar Registro',
        'reorder' => 'Reordenar Posição / Prioridade',
    ],

    /*
    |--------------------------------------------------------------------------
    | Telas Especiais (Pages da Barbearia) - Descrições para Leigos
    |--------------------------------------------------------------------------
    */
    'view_daily_schedule' => 'Agenda Diária da Equipe — Grade visual de horários marcados, cadeiras dos barbeiros e encaixes do dia',
    'view_cash_register_page' => 'Frente de Caixa Diário — Abertura do caixa da recepção, lançamentos de recebimentos e fechamento do dia',
    'view_commissions_page' => 'Fechamento de Comissões — Extrato e acerto de comissões por barbeiro (cortes e venda de produtos)',
    'view_cash_flow_forecast' => 'Previsão de Fluxo de Caixa — Projeção financeira de faturamento e contas a pagar nos próximos dias',
    'view_reports_page' => 'Relatórios Gerenciais — Estatísticas de faturamento, ticket médio e serviços mais lucrativos',
    'view_force_password_change' => 'Redefinição Obrigatória de Senha — Tela de alteração no primeiro login do colaborador',

    /*
    |--------------------------------------------------------------------------
    | Indicadores do Painel Inicial (Widgets) - Descrições para Leigos
    |--------------------------------------------------------------------------
    */
    'view_barbershop_stats_overview_widget' => 'Resumo Geral do Dia/Mês — Cards no topo do painel com faturamento, clientes e atendimentos',
    'view_payables_overview_widget' => 'Alerta de Contas a Pagar — Avisos de contas atrasadas e boletos a vencer em 7 dias',
    'view_revenue_trend_chart_widget' => 'Gráfico de Faturamento — Evolução diária das receitas da barbearia nos últimos 7 dias',
    'view_today_appointments_table_widget' => 'Agendamentos de Hoje — Tabela rápida na página inicial com os próximos clientes agendados',

    /*
    |--------------------------------------------------------------------------
    | Permissões Customizadas de Segurança - Descrições para Leigos
    |--------------------------------------------------------------------------
    */
    'view_sensitive_client_data' => 'Visualizar Telefones e Dados Sensíveis de Clientes — Permite ver o WhatsApp/telefone completo dos clientes (recomenda-se desmarcar para barbeiros para proteger a carteira da barbearia)',
];
