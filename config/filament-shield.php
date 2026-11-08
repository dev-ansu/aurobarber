<?php

declare(strict_types=1);

use App\Filament\Pages\SchoolMessenger;
use App\Filament\Pages\UserGuide;
use App\Filament\Resources\Classrooms\ClassroomResource;
use App\Filament\Resources\Formations\FormationResource;
use App\Filament\Resources\Installments\InstallmentsResource;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Pages\Dashboard;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;

return [

    /*
    |--------------------------------------------------------------------------
    | Shield Resource
    |--------------------------------------------------------------------------
    |
    | Here you may configure the built-in role management resource. You can
    | customize the URL, choose whether to show model paths, group it under
    | a cluster, and decide which permission tabs to display.
    |
    */

    'shield_resource' => [
        'slug' => 'shield/roles',
        'show_model_path' => true,
        'cluster' => null,
        'tabs' => [
            'pages' => true,
            'widgets' => true,
            'resources' => true,
            'custom_permissions' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy
    |--------------------------------------------------------------------------
    |
    | When your application supports teams, Shield will automatically detect
    | and configure the tenant model during setup. This enables tenant-scoped
    | roles and permissions throughout your application.
    |
    */

    'tenant_model' => null,

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | This value contains the class name of your user model. This model will
    | be used for role assignments and must implement the HasRoles trait
    | provided by the Spatie\Permission package.
    |
    */

    'auth_provider_model' => 'App\\Models\\User',

    /*
    |--------------------------------------------------------------------------
    | Super Admin
    |--------------------------------------------------------------------------
    |
    | Here you may define a super admin that has unrestricted access to your
    | application. You can choose to implement this via Laravel's gate system
    | or as a traditional role with all permissions explicitly assigned.
    |
    */

    'super_admin' => [
        'enabled' => true,
        'name' => 'super_admin',
        'define_via_gate' => false,
        'intercept_gate' => 'before',
    ],

    /*
    |--------------------------------------------------------------------------
    | Panel User
    |--------------------------------------------------------------------------
    |
    | When enabled, Shield will create a basic panel user role that can be
    | assigned to users who should have access to your Filament panels but
    | don't need any specific permissions beyond basic authentication.
    |
    */

    'panel_user' => [
        'enabled' => true,
        'name' => 'panel_user',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Builder
    |--------------------------------------------------------------------------
    |
    | You can customize how permission keys are generated to match your
    | preferred naming convention and organizational standards. Shield uses
    | these settings when creating permission names from your resources.
    |
    | Supported formats: snake, kebab, pascal, camel, upper_snake, lower_snake
    |
    | Note: The separator must not conflict with the case format's own
    | delimiter. For example, `_` cannot be used with snake/lower_snake/
    | upper_snake, and `-` cannot be used with kebab.
    |
    | When `format_custom_permission_keys` is true (default), custom
    | permissions defined below will have their keys formatted according to
    | the case setting. If your custom permissions come from external sources
    | (e.g. Terraform, Keycloak) and must remain unchanged, set this to false.
    | When using the separator in custom permission definitions, each segment
    | will be formatted independently (e.g. 'view:system_log' with pascal
    | case becomes 'View:SystemLog').
    |
    */

    'permissions' => [
        'separator' => ':',
        'case' => 'pascal',
        'generate' => true,
        'format_custom_permission_keys' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Policies
    |--------------------------------------------------------------------------
    |
    | Shield can automatically generate Laravel policies for your resources.
    | Generated policies mirror each model's location: models under
    | app/Models map into the path below (keeping their nesting), models in
    | any other "Models" directory get a sibling "Policies" directory, and
    | vendor models fall back to the path below. When merge is enabled, the
    | methods below will be combined with any resource-specific methods you
    | define in the resources section.
    |
    */

    'policies' => [
        'path' => app_path('Policies'),
        'merge' => true,
        'generate' => true,
        'methods' => [
            'viewAny', 'view', 'create', 'update', 'delete', 'deleteAny', 'restore',
            'forceDelete', 'forceDeleteAny', 'restoreAny', 'replicate', 'reorder',
        ],
        'single_parameter_methods' => [
            'viewAny',
            'create',
            'deleteAny',
            'forceDeleteAny',
            'restoreAny',
            'reorder',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | Shield supports multiple languages out of the box. When enabled, you
    | can provide translated labels for permissions to create a more
    | localized experience for your international users.
    |
    */

    'localization' => [
        'enabled' => true,
        'key' => 'filament-shield::filament-shield.resource_permission_prefixes_labels',
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | Here you can fine-tune permissions for specific Filament resources.
    | Use the 'manage' array to override the default policy methods for
    | individual resources, giving you granular control over permissions.
    |
    */

    'resources' => [
        'subject' => 'model',
        'manage' => [
            RoleResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
            ],
            ClassroomResource::class => [
                'toggleStatus',
            ],
            FormationResource::class => [
                'toggleStatus',
            ],
            InstallmentsResource::class => [
                'viewAny',
                'view',
                'create',
                'update',
                'delete',
            ],
        ],
        'exclude' => [
            //
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | Most Filament pages only require view permissions. Pages listed in the
    | exclude array will be skipped during permission generation and won't
    | appear in your role management interface.
    |
    */

    'pages' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            Dashboard::class,
            UserGuide::class,
            SchoolMessenger::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Widgets
    |--------------------------------------------------------------------------
    |
    | Like pages, widgets typically only need view permissions. Add widgets
    | to the exclude array if you don't want them to appear in your role
    | management interface.
    |
    */

    'widgets' => [
        'subject' => 'class',
        'prefix' => 'view',
        'exclude' => [
            AccountWidget::class,
            FilamentInfoWidget::class,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Permissions
    |--------------------------------------------------------------------------
    |
    | Sometimes you need permissions that don't map to resources, pages, or
    | widgets. Define any custom permissions here and they'll be available
    | when editing roles in your application.
    |
    | Keys are formatted per the Permission Builder settings above; set
    | permissions.format_custom_permission_keys to false to use them as-is.
    |
    */

    'custom_permissions' => [
        'View:Stat:RecebimentosMes' => 'Card de Recebimentos do mês',
        'View:Stat:ContasReceberHoje' => 'Card de Contas a Receber Hoje',
        'View:Stat:ContratosAtivos' => 'Card de Contratos Ativos',
        'View:Stat:AlunosAtivos' => 'Card de alunos ativos',
        'View:Stat:Inadimplencia' => 'Card de saldo vencido',
        'View:DashboardStatsOverview' => 'Exibir todos os indicadores do dashboard',
        'View:AttendanceRateChart' => 'Gráfico de presenças e faltas',
        'View:PaymentMethodReport' => 'Visualizar recebimentos por forma de pagamento',
        'Export:PaymentMethodReport' => 'Exportar relatório de recebimentos por forma',
        'View:ReceiptsByPaymentMethodChart' => 'Gráfico de recebimentos por forma',
        'View:ContractsByStatusChart' => 'Gráfico de contratos por situação',
        'View:OverdueReceivablesChart' => 'Gráfico de parcelas vencidas por faixa',
        'View:OverdueInstallmentReport' => 'Visualizar relatório de inadimplência',
        'Export:OverdueInstallmentReport' => 'Exportar relatório de inadimplência',
        'View:CompletionReports' => 'Visualizar relatório de conclusão de alunos',
        'Export:CompletionReports' => 'Exportar dados do relatório de conclusão',
        'ViewAny:Export' => 'Visualizar histórico de exportações',
        'Download:Export' => 'Baixar arquivos exportados',
        'Export:Contract' => 'Exportar contratos',
        'Export:AuditLog' => 'Exportar registros de auditoria',
        'Export:StudentAttendance' => 'Exportar frequência de alunos',
        'Export:CashRegister' => 'Exportar caixas',
        'Export:CashTransaction' => 'Exportar movimentações financeiras',
        'Export:Classroom' => 'Exportar salas de aula',
        'Export:Collaborator' => 'Exportar colaboradores',
        'Export:ContractType' => 'Exportar tipos de contrato',
        'Export:Course' => 'Exportar cursos',
        'Export:FinancialType' => 'Exportar tipos financeiros',
        'Export:Formation' => 'Exportar formações',
        'Export:Installment' => 'Exportar parcelas',
        'Export:PaymentMethod' => 'Exportar formas de pagamento',
        'Export:Person' => 'Exportar pessoas',
        'Export:Responsible' => 'Exportar responsáveis',
        'Export:Role' => 'Exportar perfis de acesso',
        'Export:School' => 'Exportar cadastro da escola',
        'Export:StudentSchedule' => 'Exportar horários dos alunos',
        'Export:User' => 'Exportar usuários',
        'ViewAny:StudentAnnouncement' => 'Listar avisos do kiosk dos alunos',
        'View:StudentAnnouncement' => 'Visualizar avisos do kiosk dos alunos',
        'Create:StudentAnnouncement' => 'Cadastrar avisos do kiosk dos alunos',
        'Update:StudentAnnouncement' => 'Editar avisos do kiosk dos alunos',
        'Delete:StudentAnnouncement' => 'Excluir avisos do kiosk dos alunos',
        'ViewAny:CommercialLead' => 'Listar interessados comerciais',
        'View:CommercialLead' => 'Visualizar interessados comerciais',
        'Create:CommercialLead' => 'Cadastrar interessados comerciais',
        'Update:CommercialLead' => 'Editar interessados comerciais',
        'Delete:CommercialLead' => 'Excluir interessados comerciais',
        'DeleteAny:CommercialLead' => 'Excluir interessados em lote',
        'ViewAny:CommercialVisit' => 'Listar visitas comerciais',
        'View:CommercialVisit' => 'Visualizar visitas comerciais',
        'Create:CommercialVisit' => 'Registrar visitas comerciais',
        'Update:CommercialVisit' => 'Editar visitas comerciais',
        'Delete:CommercialVisit' => 'Excluir visitas comerciais',
        'DeleteAny:CommercialVisit' => 'Excluir visitas em lote',
        'ViewAny:ContractHistory' => 'Consultar histórico de contratos',
        'View:ContractHistory' => 'Visualizar registros do histórico contratual',
        'Create:ContractHistory' => 'Registrar acompanhamentos nos contratos',
        'ViewAny:ContractHistoryType' => 'Listar tipos de acompanhamento',
        'View:ContractHistoryType' => 'Visualizar tipos de acompanhamento',
        'Create:ContractHistoryType' => 'Cadastrar tipos de acompanhamento',
        'Update:ContractHistoryType' => 'Editar tipos de acompanhamento',
        'Edit:OwnProfile' => 'Editar o próprio perfil',
        'View:BackupManagement' => 'Acessar backup e restauração',
        'Create:BackupManagement' => 'Criar backups do sistema',
        'Download:BackupManagement' => 'Baixar backups do sistema',
        'Restore:BackupManagement' => 'Restaurar backups do sistema',
        'View:OuroModernoIntegration' => 'Acessar configuração Ouro Moderno',
        'Update:OuroModernoIntegration' => 'Alterar URL e token Ouro Moderno',
        'Test:OuroModernoIntegration' => 'Testar conexão com Ouro Moderno',
        'Import:OuroModernoCourses' => 'Importar cursos da Ouro Moderno',
        'Sync:OuroModernoStudentStatus' => 'Sincronizar progresso com o LMS (Ouro Moderno / Aurosoft)',
        'Sync:OuroModernoContract' => 'Enviar contratos e cursos para a Ouro Moderno',
        'View:ContractCancellationReport' => 'Visualizar relatório de cancelamentos',
        'Export:ContractCancellationReport' => 'Exportar relatório de cancelamentos',
        'View:PedagogicalFollowUpReport' => 'Visualizar relatório de acompanhamento pedagógico',
        'Export:PedagogicalFollowUpReport' => 'Exportar relatório de acompanhamento pedagógico',
        'View:CommercialOverview' => 'Visualizar indicadores comerciais no dashboard',
        'View:CommercialReport' => 'Visualizar relatório de desempenho comercial',
        'Export:CommercialReport' => 'Exportar relatório de desempenho comercial',
        'View:StudentContactsReport' => 'Visualizar relatório de contatos dos alunos',
        'Export:StudentContactsReport' => 'Exportar relatório de contatos dos alunos',
        'ViewAny:OuroModernoEad' => 'Ouro Moderno EAD — Visualizar painel de alunos e cursos EAD',
        'Sync:OuroModernoEad' => 'Ouro Moderno EAD — Sincronizar alunos, notas e progresso do EAD',
        'Enroll:OuroModernoEad' => 'Ouro Moderno EAD — Matricular ou migrar aluno para o EAD',
        'AccessLogin:OuroModernoEad' => 'Ouro Moderno EAD — Gerar link de auto-login no portal do aluno',
        'View:OuroModernoCourseStatusReport' => 'Visualizar relatório de situação dos cursos LMS',
        'Export:OuroModernoCourseStatusReport' => 'Exportar relatório de situação dos cursos LMS',
        'View:OuroModernoGamificationReport' => 'Visualizar relatório de gamificação LMS',
        'Export:OuroModernoGamificationReport' => 'Exportar relatório de gamificação LMS',
        'View:OuroModernoStudentLogsReport' => 'Visualizar atividade dos alunos no LMS',
        'Export:OuroModernoStudentLogsReport' => 'Exportar atividade dos alunos no LMS',
        'View:OuroModernoTranscript' => 'Visualizar boletim do aluno no LMS',
        'View:OuroModernoLmsStudents' => 'Visualizar alunos cadastrados no LMS',
        'Sync:OuroModernoLmsStudents' => 'Sincronizar alunos do LMS',
        'View:BirthdaysTableWidget' => 'Visualizar tabela de aniversariantes',
        'View:CommercialLeadsKanban' => 'Acessar funil de vendas (Kanban)',
        'ViewAny:Payable' => 'Listar contas a pagar',
        'View:Payable' => 'Visualizar contas a pagar',
        'Create:Payable' => 'Cadastrar contas a pagar',
        'Update:Payable' => 'Editar contas a pagar',
        'Delete:Payable' => 'Excluir contas a pagar',
        'DeleteAny:Payable' => 'Excluir contas a pagar em lote',
        'View:SalesBySellerReport' => 'Visualizar relatório de vendas por vendedor',
        'Export:SalesBySellerReport' => 'Exportar relatório de vendas por vendedor',
        'View:StudentAbsencesReport' => 'Visualizar relatório de alunos faltantes',
        'Export:StudentAbsencesReport' => 'Exportar relatório de alunos faltantes',
        'View:DreReport' => 'Visualizar DRE financeiro',
        'Export:DreReport' => 'Exportar DRE financeiro',
        'View:PixSettings' => 'Acessar configurações de PIX',
        'View:ScheduleBoard' => 'Visualizar quadro de horários',
        'ViewAny:TypingExercise' => 'Treino de Digitação — Listar e pesquisar exercícios de digitação',
        'View:TypingExercise' => 'Treino de Digitação — Visualizar detalhes do exercício',
        'Create:TypingExercise' => 'Treino de Digitação — Cadastrar novos textos de digitação',
        'Update:TypingExercise' => 'Treino de Digitação — Editar textos e metas de WPM',
        'Delete:TypingExercise' => 'Treino de Digitação — Excluir exercícios de digitação',
        'View:ClassroomLiveMonitor' => 'Monitor de Laboratório (Ao Vivo) — Acompanhar telas dos alunos em tempo real',
        'page_ClassroomLiveMonitor' => 'Monitor de Laboratório (Ao Vivo) — Permissão de acesso à página',
        'View:LmsStudio' => 'Estúdio LMS — Acessar estúdio de criação de aulas interativas',
        'page_LmsStudio' => 'Estúdio LMS — Permissão de acesso à página do estúdio',
        'View:SchoolAppointmentsCalendar' => 'Agenda & Calendário da Escola — Visualizar compromissos e reposições',
        'page_SchoolAppointmentsCalendar' => 'Agenda & Calendário da Escola — Permissão de acesso à página',
        'View:StudentPortal' => 'Portal do Aluno — Acesso pedagógico direto do educador/professor',
        'View:LmsPlayer' => 'Player de Aulas LMS — Abrir e reproduzir passos das aulas',
        'View:LmsLesson' => 'Aulas LMS — Visualizar lições no catálogo',
        'View:LmsStudentProgress' => 'Progresso LMS — Acompanhar desempenho dos alunos nas aulas',
        'Reset:LmsStudentProgress' => 'Progresso LMS — Reiniciar aula travada de aluno',
        'Complete:ContractCourseManually' => 'Concluir cursos de alunos manualmente — Permissão para aprovar e concluir cursos de alunos de forma manual',
        'View:SatisfactionSurveyReport' => 'Pesquisas de Satisfação — Visualizar resultados e relatórios',
        'Export:SatisfactionSurveyReport' => 'Pesquisas de Satisfação — Exportar relatório em CSV',
        'page_SatisfactionSurveyReport' => 'Pesquisas de Satisfação — Permissão da página de relatórios',
        'View:CommissionReport' => 'Comissões — Visualizar relatório de comissões',
        'Export:CommissionReport' => 'Comissões — Exportar relatório de comissões',
        'page_CommissionReport' => 'Comissões — Permissão da página de comissões',
        'View:Agenda' => 'Agenda & Horários — Visualizar agenda geral',
        'page_Agenda' => 'Agenda & Horários — Permissão da página de agenda',
        'View:ExportHistory' => 'Exportações — Visualizar histórico de exportações',
        'page_ExportHistory' => 'Exportações — Permissão da página de exportações',
        'View:OuroModernoIntegrationSettings' => 'Integrações — Configurações Ouro Moderno',
        'page_OuroModernoIntegrationSettings' => 'Integrações — Permissão da página Ouro Moderno',
        'View:SystemUpdate' => 'Atualizações — Acessar tela de atualizações do sistema',
        'Apply:SystemUpdate' => 'Atualizações — Aplicar atualizações do sistema',
        'page_SystemUpdate' => 'Atualizações — Permissão da página de atualizações',
        'View:TaskBoardKanban' => 'Tarefas — Visualizar quadro Kanban',
        'page_TaskBoardKanban' => 'Tarefas — Permissão da página Kanban',
        'View:LmsQuestionBank' => 'Banco de Questões LMS — Acessar e gerenciar questões interativas',
        'page_LmsQuestionBank' => 'Banco de Questões LMS — Permissão de acesso à página do banco de questões',
        'View:NetworkOverview' => 'Visão Geral da Rede 360º — Painel consolidado comercial, financeiro e pedagógico de todas as escolas',
        'page_NetworkOverview' => 'Visão Geral da Rede 360º — Permissão da página de visão geral da rede',
        'Transfer:SchoolData' => 'Transferência entre Unidades — Transferir alunos, contratos, pessoas e interessados para outra escola da rede',
        'ChargeWhatsApp:Installment' => 'Cobrança via WhatsApp — Enviar cobrança com código Pix via WhatsApp',
        'Cancel:Installment' => 'Cancelar Parcela — Cancelar cobrança individual em aberto',
        'CancelAny:Installment' => 'Cancelar Parcelas em Lote — Cancelar cobranças selecionadas em aberto',
        'Refund:Installment' => 'Estornar Baixa — Reverter pagamento realizado indevidamente com auditoria',
        'ApplyPunctualityDiscount:Installment' => 'Desconto de Pontualidade — Conceder ou remover desconto de pontualidade na baixa',
        'WaiveFine:Installment' => 'Abonar / Alterar Juros e Multa — Isentar ou editar valor de acréscimo por atraso',
        'View:FinancialSettings' => 'Configurações Financeiras — Acessar parâmetros de juros, multa, pontualidade e Pix',
        'Update:FinancialSettings' => 'Configurações Financeiras — Salvar parâmetros de juros, multa, pontualidade e Pix',
        'View:CashFlowForecast' => 'Fluxo de Caixa Projetado — Visualizar projeção financeira diária e mensal',
        'Export:CashFlowForecast' => 'Fluxo de Caixa Projetado — Exportar projeção financeira em planilha/CSV',
        'page_CashFlowForecast' => 'Fluxo de Caixa Projetado — Permissão de acesso à página de fluxo projetado',
        'ViewAny:FinancialAccount' => 'Contas e Bancos — Listar contas financeiras e cofres da escola',
        'View:FinancialAccount' => 'Contas e Bancos — Visualizar detalhes da conta financeira ou cofre',
        'Create:FinancialAccount' => 'Contas e Bancos — Cadastrar nova conta bancária ou cofre',
        'Update:FinancialAccount' => 'Contas e Bancos — Editar conta bancária ou cofre',
        'Delete:FinancialAccount' => 'Contas e Bancos — Excluir conta bancária ou cofre',
        'Transfer:FinancialAccount' => 'Contas e Bancos — Realizar transferência entre contas bancárias e cofres',
        'Statement:FinancialAccount' => 'Contas e Bancos — Visualizar extrato detalhado de movimentações',
        'Adjust:FinancialAccount' => 'Contas e Bancos — Ajustar saldo manualmente com justificativa',
        'View:CollectionKanban' => 'Fila e Régua de Cobrança — Visualizar quadro Kanban de cobranças da secretaria',
        'page_CollectionKanban' => 'Fila e Régua de Cobrança — Permissão de acesso à página de cobrança',
        'Reactivate:Contract' => 'Reativar Contrato — Reativar contrato concluído, cancelado ou restaurar excluído',
        'ChangeStatus:StudentAttendance' => 'Alterar Frequência — Alterar presença para falta ou falta para presença com justificativa obrigatória',
        'ChangeCommercialLead:Contract' => 'Alterar Interessado no Contrato — Alterar o interessado de origem mesmo após consolidado/ativo',
        'ChangeCollaborator:Contract' => 'Alterar Vendedor/Captador no Contrato — Alterar o captador mesmo após consolidado/ativo',
        'ViewSensitive:Cpf' => 'LGPD — Visualizar e Revelar CPFs e Documentos Oficiais (RG/CNH)',
        'ViewSensitive:Phone' => 'LGPD — Visualizar e Revelar Telefones e WhatsApp',
        'ViewSensitive:Email' => 'LGPD — Visualizar e Revelar Endereços de E-mail',
        'ViewSensitive:Financial' => 'LGPD — Visualizar e Revelar Chaves Pix e Dados Bancários',
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity Discovery
    |--------------------------------------------------------------------------
    |
    | By default, Shield only looks for entities in your default Filament
    | panel. Enable these options if you're using multiple panels and want
    | Shield to discover entities across all of them.
    |
    */

    'discovery' => [
        'discover_all_resources' => false,
        'discover_all_widgets' => false,
        'discover_all_pages' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Role Policy
    |--------------------------------------------------------------------------
    |
    | Shield can automatically register a policy for role management itself.
    | This lets you control who can manage roles using Laravel's built-in
    | authorization system. Requires a RolePolicy class in your app.
    |
    */

    'register_role_policy' => true,

];
