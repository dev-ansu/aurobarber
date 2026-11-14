<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Colunas de Tabela
    |--------------------------------------------------------------------------
    */
    'column.name' => 'Cargo / Função',
    'column.guard_name' => 'Guard',
    'column.roles' => 'Funções',
    'column.permissions' => 'Permissões Ativas',
    'column.updated_at' => 'Alterado em',

    /*
    |--------------------------------------------------------------------------
    | Campos de Formulário
    |--------------------------------------------------------------------------
    */
    'field.name' => 'Identificador Técnico do Cargo',
    'field.guard_name' => 'Guard',
    'field.permissions' => 'Permissões',
    'field.select_all.name' => 'Marcar todas as permissões',
    'field.select_all.message' => 'Habilita acesso irrestrito a todos os recursos selecionados abaixo.',

    /*
    |--------------------------------------------------------------------------
    | Navegação e Recursos
    |--------------------------------------------------------------------------
    */
    'nav.group' => 'Equipe',
    'nav.role.label' => 'Cargos & Permissões',
    'nav.role.icon' => 'heroicon-o-shield-check',
    'resource.label.role' => 'Cargo & Permissões',
    'resource.label.roles' => 'Cargos & Permissões',

    /*
    |--------------------------------------------------------------------------
    | Seções e Abas
    |--------------------------------------------------------------------------
    */
    'section' => 'Módulos e Recursos da Barbearia',
    'resources' => 'Tabelas e Cadastros Principais',
    'widgets' => 'Cards e Indicadores do Painel Inicial',
    'pages' => 'Telas Especiais e Rotinas de Gestão',
    'custom' => 'Permissões Especiais de Segurança',

    /*
    |--------------------------------------------------------------------------
    | Mensagens
    |--------------------------------------------------------------------------
    */
    'forbidden' => 'Você não tem permissão para acessar esta área.',

    /*
    |--------------------------------------------------------------------------
    | Rótulos de Permissões de Recursos (Para Leigos)
    |--------------------------------------------------------------------------
    */
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
        'toggleStatus' => 'Ativar / Desativar Status',
    ],
];
