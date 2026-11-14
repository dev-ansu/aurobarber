<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use App\Models\Role;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use BezhanSalleh\PluginEssentials\Concerns\Resource as Essentials;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Override;

class RoleResource extends Resource
{
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
                        Section::make('Guia de Permissões para a Barbearia')
                            ->description('Recomendações práticas para manter a gestão da barbearia segura e organizada')
                            ->icon('heroicon-o-information-circle')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Placeholder::make('guia_cargos_leigos')
                                    ->label('')
                                    ->content(new HtmlString('
                                        <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                                            <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                                <p class="font-semibold text-gray-900 dark:text-gray-100">👑 Proprietário (Dono da Barbearia):</p>
                                                <p>Acesso total ao financeiro, fluxo de caixa, despesas, faturamento geral, divisão de comissões e configurações da conta. Não deve ser atribuído a prestadores de serviços.</p>
                                            </div>
                                            <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                                <p class="font-semibold text-gray-900 dark:text-gray-100">👔 Gerente da Barbearia:</p>
                                                <p>Ideal para o encarregado do dia a dia. Controla a grade de horários, abre/fecha caixa, gerencia estoque e organiza atendimentos, sem acesso a dados confidenciais do dono.</p>
                                            </div>
                                            <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                                <p class="font-semibold text-gray-900 dark:text-gray-100">🛎️ Recepcionista / Atendente:</p>
                                                <p>Ideal para a recepção do balcão. Cria agendamentos, atende clientes, faz encaixes na grade e lança pagamentos no caixa diário.</p>
                                            </div>
                                            <div class="p-2.5 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                                <p class="font-semibold text-gray-900 dark:text-gray-100">💈 Barbeiro Parceiro:</p>
                                                <p>Profissional que atende na cadeira. Recomenda-se liberar apenas a <strong>Agenda Diária</strong> e o <strong>Fechamento de Comissões</strong>. Nunca dê acesso a <em>Contas a Pagar</em> ou <em>Exclusão de Clientes</em>.</p>
                                            </div>
                                        </div>
                                    ')),
                            ])
                            ->columnSpanFull(),

                        Section::make('Identificação do Cargo')
                            ->description('Defina o nome e uma explicação compreensível para qualquer membro da barbearia')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Identificador do Cargo')
                                    ->helperText('Exemplo: barbeiro_auxiliar, gerente_geral. Em funções padrão do sistema o nome é fixo para manter a integração.')
                                    ->unique(
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule): Unique => Utils::isTenancyEnabled() ? $rule->where(Utils::getTenantModelForeignKey(), Filament::getTenant()?->id) : $rule
                                    )
                                    ->required()
                                    ->disabled(fn (?Model $record): bool => $record && in_array($record->name, ['super_admin', 'barbershop_owner', 'barbershop_manager', 'barber', 'receptionist'], true))
                                    ->maxLength(255),

                                TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->hidden()
                                    ->nullable()
                                    ->maxLength(255),

                                Textarea::make('description')
                                    ->label('Descrição da Função (Para Leigos)')
                                    ->placeholder('Explique em palavras simples para que serve esse cargo e quais responsabilidades quem tiver esse cargo terá na barbearia.')
                                    ->helperText('Esta descrição aparecerá na listagem de cargos para facilitar o entendimento de toda a equipe.')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                Select::make(config('permission.column_names.team_foreign_key'))
                                    ->label(__('filament-shield::filament-shield.field.team'))
                                    ->placeholder(__('filament-shield::filament-shield.field.team.placeholder'))
                                    ->default(Filament::getTenant()?->id)
                                    ->options(fn (): array => in_array(Utils::getTenantModel(), [null, '', '0'], true) ? [] : Utils::getTenantModel()::pluck('name', 'id')->toArray())
                                    ->visible(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled())
                                    ->dehydrated(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),

                                static::getSelectAllFormComponent(),
                            ])
                            ->columns([
                                'sm' => 1,
                                'lg' => 2,
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
                    ->label('Cargo / Função')
                    ->weight(FontWeight::Bold)
                    ->formatStateUsing(function (string $state, ?Model $record): string {
                        if ($record && method_exists($record, 'getFriendlyName')) {
                            return $record->getFriendlyName();
                        }
                        return match ($state) {
                            'super_admin' => 'Super Administrador (SaaS Master)',
                            'barbershop_owner' => 'Proprietário (Dono da Barbearia)',
                            'barbershop_manager' => 'Gerente da Barbearia',
                            'receptionist' => 'Recepcionista / Atendente',
                            'barber' => 'Barbeiro / Profissional Parceiro',
                            'panel_user' => 'Usuário Básico do Painel',
                            default => Str::headline($state),
                        };
                    })
                    ->description(fn (Model $record): string => "Identificador: {$record->name}")
                    ->searchable(),

                TextColumn::make('access_level')
                    ->label('Nível de Acesso')
                    ->badge()
                    ->color(function (Model $record): string {
                        return match ($record->name) {
                            'super_admin' => 'gray',
                            'barbershop_owner' => 'danger',
                            'barbershop_manager' => 'warning',
                            'receptionist' => 'info',
                            'barber' => 'success',
                            default => 'primary',
                        };
                    })
                    ->state(function (Model $record): string {
                        return match ($record->name) {
                            'super_admin' => 'Infraestrutura / TI',
                            'barbershop_owner' => 'Acesso Total',
                            'barbershop_manager' => 'Gestão Operacional',
                            'receptionist' => 'Balcão & Caixa',
                            'barber' => 'Atendimento & Comissões',
                            'panel_user' => 'Consulta Básica',
                            default => 'Personalizado',
                        };
                    }),

                TextColumn::make('description')
                    ->label('Descrição da Função (Para Leigos)')
                    ->state(function (Model $record): string {
                        if (filled($record->description)) {
                            return $record->description;
                        }
                        return match ($record->name) {
                            'super_admin' => 'Administração técnica global. Acesso irrestrito a toda a plataforma Aurobarber, múltiplos estabelecimentos e suporte do sistema.',
                            'barbershop_owner' => 'Acesso total e irrestrito. Gerencia financeiro da barbearia (faturamento e despesas), relatórios de lucro, fechamento de comissões da equipe, catálogo de serviços, controle de estoque e configurações do estabelecimento.',
                            'barbershop_manager' => 'Gestão operacional do dia a dia. Controla a grade da agenda, abre e fecha o caixa diário, dá baixa em comandas, cadastra clientes e repõe produtos. Não possui permissão para exclusões irreversíveis nem configurações estruturais.',
                            'receptionist' => 'Operação de balcão e atendimento ao cliente. Realiza agendamentos de clientes, encaixes na agenda dos profissionais, cadastro de fichas de clientes e recebimento de pagamentos no caixa da recepção.',
                            'barber' => 'Acesso restrito ao profissional. Visualiza exclusivamente sua própria agenda de cortes e horários marcados, lança produtos vendidos durante o atendimento e consulta o extrato das suas comissões de serviços e vendas.',
                            'panel_user' => 'Acesso básico de leitura para novos colaboradores ou aprendizes em fase de treinamento.',
                            default => 'Cargo personalizado com permissões customizadas definidas pela barbearia.',
                        };
                    })
                    ->wrap()
                    ->searchable(),

                TextColumn::make('permissions_count')
                    ->badge()
                    ->label('Permissões Ativas')
                    ->counts('permissions')
                    ->color('primary'),

                TextColumn::make('updated_at')
                    ->label('Alterado em')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar Permissões'),
                DeleteAction::make()
                    ->label('Excluir Cargo')
                    ->hidden(fn (Model $record): bool => in_array($record->name, ['super_admin', 'barbershop_owner', 'barber', 'receptionist', 'barbershop_manager'], true)),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    /**
     * Retorna descrições em linguagem leiga para cada entidade da barbearia no formulário de permissões.
     */
    public static function getResourceDescription(string $resourceFqcn, string $modelFqcn): string
    {
        $short = class_basename($resourceFqcn);

        return match ($short) {
            'AppointmentResource' => 'Controle de agendamentos, comanda de atendimento dos clientes, horários marcados e serviços em andamento.',
            'BarbershopClientResource' => 'Cadastro de clientes, histórico de visitas, preferências de corte, datas de aniversário e programa de fidelidade.',
            'PayableResource' => 'Contas a pagar e despesas da barbearia (aluguel, água, energia, reposição de produtos, boletos e fornecedores).',
            'ProductResource' => 'Catálogo de pomadas, ceras, óleos, lâminas e bebidas, além do controle de entradas e saídas de estoque.',
            'ProfessionalResource' => 'Cadastro dos barbeiros parceiros, percentuais de comissão e horários de trabalho.',
            'RoleResource' => 'Cargos e perfis de permissão (quem pode ver ou alterar cada recurso da barbearia).',
            'ServiceResource' => 'Tabela de serviços oferecidos pela barbearia (Cortes, Barboterapia, Luzes, etc.) com valores e duração estimada.',
            'UserResource' => 'Contas de acesso com e-mail e senha usadas pela equipe para entrar no painel da barbearia.',
            default => 'Gerenciamento dos registros deste módulo da barbearia.',
        };
    }

    /**
     * Sobrescreve o schema das entidades de recursos para incluir descrições para leigos em cada card.
     */
    public static function getResourceEntitiesSchema(): ?array
    {
        return collect(\BezhanSalleh\FilamentShield\Facades\FilamentShield::getResources())
            ->map(function (array $entity): Section {
                $sectionLabel = strval(
                    static::shield()->hasLocalizedPermissionLabels()
                        ? \BezhanSalleh\FilamentShield\Facades\FilamentShield::getLocalizedResourceLabel($entity['resourceFqcn'])
                        : $entity['model']
                );

                $desc = static::getResourceDescription($entity['resourceFqcn'], $entity['modelFqcn']);

                return Section::make($sectionLabel)
                    ->description($desc)
                    ->compact()
                    ->schema([
                        static::getCheckBoxListComponentForResource($entity),
                    ])
                    ->columnSpan(static::shield()->getSectionColumnSpan())
                    ->collapsible();
            })
            ->toArray();
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
        return Role::class;
    }

    #[Override]
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->whereNotIn('name', ['super_admin', 'master_admin']);
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
}
