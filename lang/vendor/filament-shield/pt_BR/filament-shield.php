<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Table Columns
    |--------------------------------------------------------------------------
    */

    'column.name' => 'Nome',
    'column.guard_name' => 'Guard',
    'column.roles' => 'Funções',
    'column.permissions' => 'Permissões',
    'column.updated_at' => 'Alterado em',

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */

    'field.name' => 'Nome',
    'field.guard_name' => 'Guard',
    'field.permissions' => 'Permissões',
    'field.select_all.name' => 'Selecionar todos',
    'field.select_all.message' => 'Habilitar todas as permissões para essa função',

    /*
    |--------------------------------------------------------------------------
    | Navigation & Resource
    |--------------------------------------------------------------------------
    */

    'nav.group' => 'Equipe',
    'nav.role.label' => 'Cargos & Permissões',
    'nav.role.icon' => 'heroicon-o-shield-check',
    'resource.label.role' => 'Cargo & Permissões',
    'resource.label.roles' => 'Cargos & Permissões',

    /*
    |--------------------------------------------------------------------------
    | Section & Tabs
    |--------------------------------------------------------------------------
    */
    'section' => 'Entidades',
    'resources' => 'Recursos',
    'widgets' => 'Widgets',
    'pages' => 'Páginas',
    'custom' => 'Permissões customizadas',

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    'forbidden' => 'Você não tem permissão para acessar',

    /*
    |--------------------------------------------------------------------------
    | Resource Permissions' Labels
    |--------------------------------------------------------------------------
    */

    'resource_permission_prefixes_labels' => [
        'view' => 'Visualizar ficha detalhada',
        'view_any' => 'Listar e ver registros na tabela',
        'create' => 'Cadastrar novo registro',
        'update' => 'Editar e alterar dados',
        'delete' => 'Excluir registro',
        'delete_any' => 'Excluir registros em lote',
        'force_delete' => 'Exclusão permanente (destrutiva)',
        'force_delete_any' => 'Exclusão permanente em lote',
        'restore' => 'Restaurar item excluído',
        'restore_any' => 'Restaurar itens em lote',
        'replicate' => 'Duplicar registro',
        'reorder' => 'Reordenar ordem/posição',
        'toggleStatus' => 'Ativar / Desativar status',
        'reactivate' => 'Reativar contrato trancado/concluído',
        'adjustFinancial' => 'Ajustar financeiro do contrato',
    ],
];
