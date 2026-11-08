<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles;

use App\Filament\Exports\RoleExporter;
use App\Filament\Resources\CashRegisters\CashRegisterResource;
use App\Filament\Resources\CashTransactions\CashTransactionResource;
use App\Filament\Resources\Classrooms\ClassroomResource;
use App\Filament\Resources\Collaborators\CollaboratorResource;
use App\Filament\Resources\CommercialLeads\CommercialLeadResource;
use App\Filament\Resources\CommercialVisits\CommercialVisitResource;
use App\Filament\Resources\Contracts\ContractsResource;
use App\Filament\Resources\ContractTypes\ContractTypeResource;
use App\Filament\Resources\Courses\CourseResource;
use App\Filament\Resources\FinancialTypes\FinancialTypeResource;
use App\Filament\Resources\Formations\FormationResource;
use App\Filament\Resources\Installments\InstallmentsResource;
use App\Filament\Resources\Payables\PayableResource;
use App\Filament\Resources\PaymentMethods\PaymentMethodResource;
use App\Filament\Resources\People\PersonResource;
use App\Filament\Resources\Responsibles\ResponsibleResource;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use App\Filament\Resources\Schools\SchoolResource;
use App\Filament\Resources\StudentAnnouncements\StudentAnnouncementResource;
use App\Filament\Resources\StudentAttendances\StudentAttendanceResource;
use App\Filament\Resources\StudentSchedules\StudentScheduleResource;
use App\Filament\Resources\TypingExercises\TypingExerciseResource;
use App\Filament\Resources\Users\UserResource;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use BezhanSalleh\PluginEssentials\Concerns\Resource as Essentials;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Override;

class RoleResource extends Resource
{
    protected static bool $isScopedToTenant = false;

    use Essentials\BelongsToParent;
    use Essentials\BelongsToTenant;
    use Essentials\HasGlobalSearch;
    use Essentials\HasLabels;
    use Essentials\HasNavigation;
    use HasShieldFormComponents;

    protected static ?string $recordTitleAttribute = 'name';

    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->unique(
                                        ignoreRecord: true, /** @phpstan-ignore-next-line */
                                        modifyRuleUsing: fn (Unique $rule): Unique => Utils::isTenancyEnabled() ? $rule->where(Utils::getTenantModelForeignKey(), Filament::getTenant()?->id) : $rule
                                    )
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255),

                                Select::make(config('permission.column_names.team_foreign_key'))
                                    ->label(__('filament-shield::filament-shield.field.team'))
                                    ->placeholder(__('filament-shield::filament-shield.field.team.placeholder'))
                                    /** @phpstan-ignore-next-line */
                                    ->default(Filament::getTenant()?->id)
                                    ->options(fn (): array => in_array(Utils::getTenantModel(), [null, '', '0'], true) ? [] : Utils::getTenantModel()::pluck('name', 'id')->toArray())
                                    ->visible(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled())
                                    ->dehydrated(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),
                                static::getSelectAllFormComponent(),

                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ])
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                static::getShieldFormComponents(),
            ]);
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight(FontWeight::Medium)
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->searchable(),
                TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                TextColumn::make('team.name')
                    ->default('Global')
                    ->badge()
                    ->color(fn (mixed $state): string => str($state)->contains('Global') ? 'gray' : 'primary')
                    ->label(__('filament-shield::filament-shield.column.team'))
                    ->searchable()
                    ->visible(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),
                TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->color('primary'),
                TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('guard_name')->label('Guard')->options(fn (): array => static::getModel()::query()
                    ->distinct()->orderBy('guard_name')->pluck('guard_name', 'guard_name')->all()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->headerActions([
                ExportAction::make()->label('Exportar perfis')->exporter(RoleExporter::class)
                    ->formats([ExportFormat::Csv, ExportFormat::Xlsx])
                    ->authorize(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Role') ?? false)
                    ->visible(fn (): bool => Auth::user()?->getAllPermissions()->contains('name', 'Export:Role') ?? false),
            ]);
    }

    #[Override]
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function getModel(): string
    {
        return Utils::getRoleModel();
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return Utils::getResourceSlug();
    }

    public static function getCluster(): ?string
    {
        return Utils::getResourceCluster();
    }

    public static function getEssentialsPlugin(): ?FilamentShieldPlugin
    {
        return FilamentShieldPlugin::get();
    }

    public static function getResourceDescription(string $resourceFqcn): string
    {
        return match ($resourceFqcn) {
            ContractsResource::class => 'Contratos de prestação de serviços educacionais, termos de adesão, cancelamentos e impressão da matrícula.',
            PersonResource::class => 'Cadastro geral de pessoas físicas: alunos matriculados, responsáveis financeiros e interessados comerciais.',
            CollaboratorResource::class => 'Funcionários da escola: educadores/professores, recepcionistas, consultores de vendas e coordenação.',
            TypingExerciseResource::class => 'Exercícios práticos de digitação, textos de aquecimento obrigatório e metas de WPM do portal do aluno.',
            ClassroomResource::class => 'Salas de aula físicas e laboratórios de informática, capacidade de estações e computadores.',
            CourseResource::class => 'Catálogo de cursos livres, módulos Aurosoft LMS e integração com os 300+ cursos da Ouro Moderno.',
            FormationResource::class => 'Pacotes de formação profissional que agrupam múltiplos cursos para venda em conjunto.',
            StudentAttendanceResource::class => 'Controle diário de presenças, faltas, justificativas e listas de chamada da escola.',
            StudentScheduleResource::class => 'Alocação de horários de aula dos alunos: dia da semana, faixa de horário e laboratório.',
            InstallmentsResource::class => 'Gestão de mensalidades, carnês de cobrança, baixas financeiras manuais e emissão de recibos.',
            CashRegisterResource::class => 'Abertura, fechamento e controle físico dos caixas diários da recepção.',
            CashTransactionResource::class => 'Lançamento de entradas, sangrias, suprimentos e despesas no caixa da escola.',
            PayableResource::class => 'Contas a pagar da escola: fornecedores, aluguel, energia, internet e folha de colaboradores.',
            CommercialLeadResource::class => 'CRM Comercial: interessados captados em campanhas, telefone, WhatsApp e balcão.',
            CommercialVisitResource::class => 'Visitas comerciais agendadas e presenciais de pais e alunos para apresentação da escola.',
            UserResource::class => 'Contas de login, senhas e vínculos de colaboradores com acesso ao painel do Aurosoft.',
            RoleResource::class => 'Cargos e funções da equipe: define quais telas, relatórios e ações cada colaborador pode acessar na escola.',
            SchoolResource::class => 'Dados cadastrais da unidade escolar: razão social, CNPJ, telefone, logotipo e endereço.',
            ContractTypeResource::class => 'Modelos de Documentos: contratos de matrícula, termos de trancamento, termos de rescisão e declarações escolares.',
            FinancialTypeResource::class => 'Classificação de receitas e despesas (mensalidade, taxa de matrícula, material didático).',
            PaymentMethodResource::class => 'Meios de liquidação: Pix, Cartão de Crédito/Débito, Boleto Bancário e Dinheiro.',
            ResponsibleResource::class => 'Responsáveis legais e financeiros vinculados a alunos menores de idade.',
            StudentAnnouncementResource::class => 'Avisos e comunicados exibidos no totem de presença e no portal do aluno.',
            SchoolGoalResource::class => 'Metas da escola: objetivos mensais de novas matrículas, faturamento e presença para a equipe.',
            AuditLogResource::class => 'Auditoria e segurança: histórico detalhado de quem criou, alterou ou excluiu qualquer registro no sistema.',
            FinancialAccountResource::class => 'Contas bancárias e cofres: contas correntes, poupanças e reservas financeiras da escola.',
            ContractHistoryTypeResource::class => 'Ocorrências contratuais: tipos de advertência pedagógica, registros de conduta e observações.',
            SatisfactionSurveyResource::class => 'Pesquisas de satisfação: avaliações de NPS e notas de atendimento preenchidas por alunos e responsáveis.',
            default => 'Permissões operacionais de acesso e manipulação deste módulo no sistema.',
        };
    }

    public static function getResourceEntitiesSchema(): ?array
    {
        return collect(FilamentShield::getResources())
            ->map(function (array $entity): Section {
                $sectionLabel = strval(
                    static::shield()->hasLocalizedPermissionLabels()
                    ? FilamentShield::getLocalizedResourceLabel($entity['resourceFqcn'])
                    : $entity['model']
                );

                $description = static::getResourceDescription($entity['resourceFqcn']);

                return Section::make($sectionLabel)
                    ->description($description)
                    ->compact()
                    ->schema([
                        static::getCheckBoxListComponentForResource($entity),
                    ])
                    ->columnSpan(static::shield()->getSectionColumnSpan())
                    ->collapsible();
            })
            ->toArray();
    }

    public static function getPageOptions(): array
    {
        $customLabels = [
            'view_classroom_live_monitor' => 'Monitor de Laboratório (Ao Vivo) — Acompanhar telas dos alunos em tempo real e responder a chamados de ajuda',
            'view_lms_studio' => 'Estúdio LMS — Editor interativo para criar e organizar cursos, módulos, lições e passos multimídia',
            'view_school_appointments_calendar' => 'Agenda & Calendário da Escola — Gestão visual de compromissos, reposições e atendimentos',
            'view_user_guide' => 'Guia do Usuário & Tutoriais — Manual interativo de operação da escola',
            'view_commercial_leads_kanban' => 'Funil de Vendas (Kanban) — Gestão visual do pipeline comercial e conversão de matrículas',
            'view_sales_by_seller_report' => 'Relatório de Vendas por Vendedor — Acompanhamento de faturamento e comissões da equipe comercial',
            'view_student_absences_report' => 'Relatório de Faltas — Alertas de infrequência para retenção preventiva de alunos',
            'view_student_contacts_report' => 'Relatório de Contatos dos Alunos — Exportação de telefones e WhatsApp para cobrança e secretaria',
            'view_commercial_report' => 'Relatório Comercial — Métricas globais de conversão de interessados em matrículas',
            'view_contract_cancellation_report' => 'Relatório de Cancelamentos — Indicadores de evasão e motivos de rescisão contratual',
            'view_pedagogical_follow_up_report' => 'Acompanhamento Pedagógico — Desempenho geral, notas de lições e ritmo dos alunos',
            'view_dre_report' => 'DRE Gerencial — Demonstrativo de Resultado do Exercício financeiro da escola',
            'view_overdue_installment_report' => 'Relatório de Inadimplência — Relação detalhada de títulos e mensalidades vencidas',
            'view_completion_reports' => 'Relatório de Conclusão de Alunos — Alunos aptos para formatura e emissão de certificados',
            'view_export_history' => 'Histórico de Exportações do Sistema — Relação e download das planilhas e arquivos exportados',
            'view_payment_method_report' => 'Relatório por Forma de Pagamento — Totalizadores por Pix, cartão, dinheiro e boleto',
            'view_ouro_moderno_course_status_report' => 'Situação dos Cursos Ouro Moderno — Auditoria de sincronização no LMS externo',
            'view_ouro_moderno_student_logs_report' => 'Logs de Atividade Ouro Moderno — Detalhamento de acessos e notas no LMS externo',
            'view_ouro_moderno_transcript' => 'Boletim Escolar Ouro Moderno — Histórico de notas para conferência e certificados',
            'view_ouro_moderno_lms_students' => 'Alunos no LMS sem cadastro — Consultar novos alunos do LMS Ouro Moderno e formalizar matrículas na escola',
            'view_ouro_moderno_ead_students' => 'Alunos EAD sem cadastro — Consultar alunos remotos do LMS Ouro Moderno e formalizar matrículas',
            'view_suspended_contracts_report' => 'Trancamentos de Matrícula — Acompanhar contratos suspensos, previsões de retorno e alertas via WhatsApp',
            'view_students_by_course_and_formation_report' => 'Alunos por Curso e Formação — Ranking de formações mais vendidas, cursos mais procurados e alunos matriculados',
            'view_ouro_moderno_integration_settings' => 'Configurações Ouro Moderno — Parâmetros de API, URL e chave de integração',
            'view_google_drive_integration_settings' => 'Backup Google Drive — Parâmetros de sincronização automática na nuvem (OAuth 2.0)',
            'view_pix_settings' => 'Configurações de Pix — Chaves Pix da escola para cobrança no carnê',
            'view_schedule_board' => 'Quadro de Horários — Matriz de ocupação semanal das salas e máquinas',
            'view_backup_management' => 'Gestão de Backups — Criação e restauração de cópias de segurança do banco',
            'view_student_attendance_kiosk' => 'Totem de Presença — Terminal de autoatendimento para registro de ponto',
            'view_agenda' => 'Agenda da Escola — Visualização dos compromissos e tarefas pedagógicas',
            'view_commission_report' => 'Relatório de Comissões — Fechamento e demonstrativo de comissões da equipe',
            'view_ouro_moderno_gamification_report' => 'Relatório de Gamificação — Ranking e pontuação de engajamento dos alunos',
            'view_task_board_kanban' => 'Quadro de Tarefas da Equipe (Kanban) — Gestão de demandas internas da escola',
            'view_network_overview' => 'Visão Geral da Rede 360º — Painel consolidado comercial, financeiro e pedagógico de todas as escolas',
            'view_satisfaction_survey_report' => 'Relatório de Pesquisas de Satisfação — Resultados consolidados de NPS e avaliações',
            'view_flex_and_free_students_report' => 'Relatório de Alunos Flex e Bolsistas — Acompanhamento de contratos flexíveis e gratuitos',
        ];

        return collect(collect(FilamentShield::getPages())->flatMap(fn ($page) => $page['permissions'])->toArray())
            ->mapWithKeys(function ($label, $key) use ($customLabels) {
                $normalizedKey = Str::snake(str_replace(':', '', $key));
                $custom = $customLabels[$key] ?? $customLabels[$normalizedKey] ?? null;

                return [$key => $custom ?? $label];
            })
            ->toArray();
    }

    public static function getWidgetOptions(): array
    {
        $customLabels = [
            'view_dashboard_stats_overview' => 'Cards Resumo do Dashboard — Indicadores principais de alunos, caixa e matrículas',
            'view_attendance_rate_chart' => 'Gráfico de Taxa de Presença — Percentual de presença e faltas dos alunos',
            'view_birthdays_table_widget' => 'Aniversariantes do Mês — Lista rápida de aniversariantes para ações de engajamento',
            'view_overdue_receivables_chart' => 'Gráfico de Inadimplência — Mensalidades em atraso segmentadas por faixas de dias',
            'view_receipts_by_payment_method_chart' => 'Gráfico de Recebimentos — Distribuição de faturamento por forma de pagamento',
            'view_contracts_by_status_chart' => 'Gráfico de Contratos por Situação — Ativos, suspensos, cancelados e finalizados',
            'view_dropout_risk_radar_widget' => 'Radar de Evasão & Alerta Preventivo — Monitoramento integrado de alunos em risco (Presencial & EAD)',
            'view_school_goals_progress_widget' => 'Termômetro de Metas da Escola — Acompanhamento visual das metas da equipe',
        ];

        return collect(collect(FilamentShield::getWidgets())->flatMap(fn ($widget) => $widget['permissions'])->toArray())
            ->mapWithKeys(function ($label, $key) use ($customLabels) {
                $normalizedKey = Str::snake(str_replace(':', '', $key));
                $custom = $customLabels[$key] ?? $customLabels[$normalizedKey] ?? null;

                return [$key => $custom ?? $label];
            })
            ->toArray();
    }
}
