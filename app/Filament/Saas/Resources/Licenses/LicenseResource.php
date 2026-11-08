<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\Licenses;

use App\Filament\Saas\Resources\Licenses\Pages\CreateLicense;
use App\Filament\Saas\Resources\Licenses\Pages\EditLicense;
use App\Filament\Saas\Resources\Licenses\Pages\ListLicenses;
use App\Models\License;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LicenseResource extends Resource
{
    protected static ?string $model = License::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Licenças & Escolas';

    protected static ?string $modelLabel = 'Licença';

    protected static ?string $pluralModelLabel = 'Licenças & Clientes';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Configurações da Licença')
                    ->columnSpanFull()
                    ->tabs([
                        // ABA 1: IDENTIFICAÇÃO DA INSTITUIÇÃO & CONTATOS
                        Tab::make('Identificação & Contatos')
                            ->icon('heroicon-m-building-office-2')
                            ->columns(['sm' => 1, 'md' => 2])
                            ->schema([
                                TextInput::make('client_name')
                                    ->label('Razão Social / Nome da Empresa')
                                    ->required()
                                    ->placeholder('Ex: SG Consultoria e Treinamentos LTDA')
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                TextInput::make('trade_name')
                                    ->label('Nome Fantasia Principal')
                                    ->placeholder('Ex: ITEP - Álvaro Weyne')
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                TextInput::make('cnpj')
                                    ->label('CNPJ da Matriz')
                                    ->placeholder('00.000.000/0001-00')
                                    ->mask('99.999.999/9999-99')
                                    ->helperText('CNPJ titular da assinatura.')
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                TextInput::make('max_schools')
                                    ->label('Qtd. Máxima de Escolas / Unidades')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->helperText('Ex: 2 se a rede possuir Matriz + 1 Filial.')
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                TextInput::make('contact_name')
                                    ->label('Responsável / Diretor')
                                    ->placeholder('Ex: Prof. Carlos Eduardo')
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                TextInput::make('contact_phone')
                                    ->label('WhatsApp / Telefone de Suporte')
                                    ->tel()
                                    ->placeholder('(85) 99999-9999')
                                    ->mask('(99) 99999-9999')
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                TextInput::make('contact_email')
                                    ->label('E-mail do Responsável')
                                    ->email()
                                    ->placeholder('diretoria@escola.com.br')
                                    ->columnSpan(['sm' => 1, 'md' => 2]),
                            ]),

                        // ABA 2: TERMOS DA LICENÇA, VENCIMENTO & VALORES
                        Tab::make('Termos da Licença & Faturamento')
                            ->icon('heroicon-m-banknotes')
                            ->columns(['sm' => 1, 'md' => 2])
                            ->schema([
                                TextInput::make('license_key')
                                    ->label('Chave da Licença')
                                    ->default(fn () => License::generateKey())
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Chave única utilizada pelo cliente para validação remota.')
                                    ->columnSpan(['sm' => 1, 'md' => 2]),

                                Select::make('type')
                                    ->label('Modalidade')
                                    ->options([
                                        'cloud_saas' => '☁️ Nuvem Multi-tenant (SaaS)',
                                        'on_premise' => '🖥️ Local On-Premise (Servidor Físico)',
                                    ])
                                    ->default('cloud_saas')
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                Select::make('status')
                                    ->label('Status da Assinatura')
                                    ->options([
                                        'active' => '✅ Ativo',
                                        'trial' => '⏳ Em Teste',
                                        'suspended' => '⛔ Suspenso',
                                        'expired' => '⚠️ Expirado',
                                        'cancelled' => '❌ Cancelado',
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->native(false)
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                DatePicker::make('starts_at')
                                    ->label('Início do Contrato')
                                    ->default(now())
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                DatePicker::make('expires_at')
                                    ->label('Vencimento da Licença')
                                    ->default(now()->addDays(30))
                                    ->helperText('Data de vencimento da assinatura.')
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                TextInput::make('monthly_price')
                                    ->label('Valor da Mensalidade (R$)')
                                    ->numeric()
                                    ->prefix('R$')
                                    ->default(250.00)
                                    ->columnSpan(['sm' => 1, 'md' => 2]),
                            ]),

                        // ABA 3: ENDEREÇO DA SEDE / INSTITUIÇÃO
                        Tab::make('Endereço da Sede')
                            ->icon('heroicon-m-map-pin')
                            ->columns(['sm' => 1, 'md' => 3])
                            ->schema([
                                TextInput::make('postal_code')
                                    ->label('CEP')
                                    ->placeholder('60000-000')
                                    ->mask('99999-999')
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                TextInput::make('street')
                                    ->label('Logradouro / Rua')
                                    ->placeholder('Ex: Av. Bezerra de Menezes')
                                    ->columnSpan(['sm' => 1, 'md' => 2]),

                                TextInput::make('number')
                                    ->label('Número')
                                    ->placeholder('Ex: 123')
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                TextInput::make('complement')
                                    ->label('Complemento')
                                    ->placeholder('Ex: Bloco B, Sala 10')
                                    ->columnSpan(['sm' => 1, 'md' => 2]),

                                TextInput::make('neighborhood')
                                    ->label('Bairro')
                                    ->placeholder('Ex: Farias Brito')
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                TextInput::make('city')
                                    ->label('Cidade')
                                    ->placeholder('Ex: Fortaleza')
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                TextInput::make('state')
                                    ->label('UF (Estado)')
                                    ->placeholder('CE')
                                    ->maxLength(2)
                                    ->columnSpan(['sm' => 1, 'md' => 1]),
                            ]),

                        // ABA 4: INFRAESTRUTURA & CONFIGURAÇÕES TÉCNICAS
                        Tab::make('Infraestrutura & SaaS')
                            ->icon('heroicon-m-cog-6-tooth')
                            ->columns(['sm' => 1, 'md' => 2])
                            ->schema([
                                TextInput::make('slug')
                                    ->label('Subdomínio do Tenant (Nuvem / SaaS)')
                                    ->placeholder('ex: sao-jose, itep')
                                    ->unique(ignoreRecord: true)
                                    ->helperText(function (?License $record): string {
                                        if ($record && $record->slug) {
                                            return "URL gerada: " . ($record->getTenantUrl() ?? "{$record->slug}.aurosoft.com.br");
                                        }
                                        return 'Identificador do subdomínio: {slug}.aurosoft.com.br ou {slug}.localhost';
                                    })
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                TextInput::make('custom_domain')
                                    ->label('Domínio Próprio Personalizado (Opcional)')
                                    ->placeholder('ex: sistema.colegiosaojose.com.br')
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Caso a instituição utilize seu próprio domínio corporativo via CNAME.')
                                    ->columnSpan(['sm' => 1, 'md' => 1]),

                                TextInput::make('schema_name')
                                    ->label('Schema PostgreSQL (Nuvem / SaaS)')
                                    ->placeholder('ex: tenant_sao_jose, tenant_itep')
                                    ->helperText('Nome do schema isolado para este cliente no PostgreSQL. Criado automaticamente no provisionamento.')
                                    ->columnSpan(['sm' => 1, 'md' => 2]),

                                Toggle::make('is_demo')
                                    ->label('Ambiente de Demonstração (Demo)')
                                    ->helperText('Marque se for uma conta fictícia pública para degustação de novos clientes.')
                                    ->columnSpanFull(),

                                Textarea::make('notes')
                                    ->label('Observações Internas & CNPJs das Filiais')
                                    ->rows(3)
                                    ->placeholder("Ex:\nFilial 1: Capelos Cursos e Treinamentos LTDA (34.048.696/0001-48 - Barra do Ceará)")
                                    ->helperText('Dica: Qualquer CNPJ secundário ou filial anotado aqui também será reconhecido pela API!')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('client_name')
                    ->label('Escola / Cliente')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (License $record): string => $record->trade_name ? "Fantasia: {$record->trade_name}" : ''),

                TextColumn::make('license_key')
                    ->label('Chave da Licença')
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('Chave da licença copiada!')
                    ->searchable(),

                TextColumn::make('type')
                    ->label('Modalidade')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (): string => 'Nuvem (SaaS)')
                    ->description(function (License $record): string {
                        if ($record->isSchemaProvisioned()) {
                            return "🟢 Schema: {$record->schema_name}";
                        }

                        return '🟡 Schema pendente';
                    }),

                TextColumn::make('slug')
                    ->label('Subdomínio / URL')
                    ->badge(fn (License $record): bool => (bool) $record->slug)
                    ->color('slate')
                    ->placeholder('Nenhum')
                    ->description(fn (License $record): ?string => $record->getTenantUrl())
                    ->copyable()
                    ->copyableState(fn (License $record): ?string => $record->getTenantUrl())
                    ->copyMessage('URL do tenant copiada!'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'primary',
                        'suspended', 'cancelled' => 'danger',
                        'expired' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Ativo',
                        'trial' => 'Em Teste',
                        'suspended' => 'Suspenso',
                        'expired' => 'Expirado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    }),

                TextColumn::make('expires_at')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(function (License $record): string {
                        $days = $record->daysRemaining();
                        if ($days < 0) {
                            return abs($days).' dias atrasado';
                        }
                        if ($days === 0) {
                            return 'Vence hoje!';
                        }

                        return "Restam {$days} dias";
                    }),

                TextColumn::make('telemetry.git_commit')
                    ->label('Versão / Servidor')
                    ->badge()
                    ->color(fn (License $record): string => ($record->last_checked_at && $record->last_checked_at->diffInMinutes(now()) <= 15) ? 'success' : 'gray')
                    ->placeholder('Pendente')
                    ->description(function (License $record): ?string {
                        if (! $record->last_checked_at) {
                            return 'Sem heartbeat';
                        }
                        return $record->last_checked_at->diffForHumans();
                    }),

                TextColumn::make('monthly_price')
                    ->label('Mensalidade')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Ativo',
                        'trial' => 'Em Teste',
                        'suspended' => 'Suspenso',
                        'expired' => 'Expirado',
                    ]),
                SelectFilter::make('type')
                    ->label('Modalidade')
                    ->options([
                        'cloud_saas' => 'Nuvem (SaaS)',
                        'on_premise' => 'On-Premise',
                    ]),
            ])
            ->actions([
                Action::make('provision_cloud')
                    ->label('Provisionar Schema')
                    ->icon('heroicon-m-cloud-arrow-up')
                    ->color('success')
                    ->visible(fn (License $record): bool => $record->type === 'cloud_saas' && ! $record->isSchemaProvisioned())
                    ->modalHeading(fn (License $record): string => "Provisionar Nuvem: {$record->client_name}")
                    ->modalDescription('Cria o schema isolado no PostgreSQL, roda todas as migrações e inicializa a escola e o administrador principal.')
                    ->modalSubmitActionLabel('Iniciar Provisionamento')
                    ->form([
                        TextInput::make('admin_name')
                            ->label('Nome do Diretor / Administrador')
                            ->default(fn (License $record): string => $record->contact_name ?: 'Diretor Geral')
                            ->required(),
                        TextInput::make('admin_email')
                            ->label('E-mail de Login do Administrador')
                            ->default(fn (License $record): string => $record->contact_email ?: ('admin@' . ($record->slug ?: 'escola') . '.com.br'))
                            ->email()
                            ->required(),
                        TextInput::make('admin_password')
                            ->label('Senha Provisória de Acesso')
                            ->default('12345678')
                            ->required(),
                        Toggle::make('send_welcome_email')
                            ->label('Enviar e-mail automático de boas-vindas com dados de acesso')
                            ->default(true),
                    ])
                    ->action(function (License $record, array $data): void {
                        try {
                            $tenantService = app(\App\Services\TenantService::class);
                            $result = $tenantService->provisionCloudTenant($record, $data);

                            // Envia e-mail de boas-vindas se solicitado
                            $emailNotification = '';
                            if (! empty($data['send_welcome_email']) && filter_var($result['admin_email'], FILTER_VALIDATE_EMAIL)) {
                                try {
                                    \Illuminate\Support\Facades\Mail::to($result['admin_email'])
                                        ->send(new \App\Mail\TenantWelcomeMail(
                                            license: $record,
                                            adminName: $data['admin_name'],
                                            adminEmail: $result['admin_email'],
                                            initialPassword: $result['admin_password'],
                                        ));
                                    $emailNotification = "\n✉ E-mail de boas-vindas enviado para {$result['admin_email']}!";
                                } catch (\Throwable $mailError) {
                                    \Illuminate\Support\Facades\Log::warning("Falha ao enviar e-mail de boas-vindas do tenant: " . $mailError->getMessage());
                                    $emailNotification = "\n(Aviso: não foi possível enviar o e-mail automático)";
                                }
                            }

                            Notification::make()
                                ->title('Schema PostgreSQL Provisionado!')
                                ->body("Tenant pronto em {$result['duration_ms']} ms!\nSchema: {$result['schema']}\nAdmin: {$result['admin_email']}{$emailNotification}")
                                ->success()
                                ->persistent()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Falha no provisionamento do tenant')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),

                Action::make('open_school')
                    ->label('Acessar Escola')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('info')
                    ->visible(fn (License $record): bool => $record->type === 'cloud_saas' && $record->isSchemaProvisioned() && ! empty($record->getTenantUrl()))
                    ->url(fn (License $record): string => $record->getTenantUrl())
                    ->openUrlInNewTab(),

                Action::make('ping_live')
                    ->label('Consultar ao Vivo')
                    ->icon('heroicon-m-bolt')
                    ->color('primary')
                    ->visible(fn (License $record): bool => $record->type === 'on_premise')
                    ->action(function (License $record): void {
                        $result = $record->fetchLiveTelemetry();
                        if ($result['success']) {
                            Notification::make()
                                ->title('Servidor consultado ao vivo via Tailscale!')
                                ->body($result['message'])
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Falha ao conectar no servidor da escola')
                                ->body($result['message'])
                                ->warning()
                                ->send();
                        }
                    }),

                ActionGroup::make([
                    EditAction::make(),

                    Action::make('telemetry')
                        ->label('Telemetria Servidor On-Premise')
                        ->icon('heroicon-m-server-stack')
                        ->color('info')
                        ->visible(fn (License $record): bool => $record->type === 'on_premise')
                        ->modalHeading(fn (License $record): string => "Telemetria & Diagnóstico: {$record->client_name}")
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Fechar')
                        ->modalContent(fn (License $record) => view('filament.saas.modals.server-telemetry', ['license' => $record])),

                    Action::make('re_migrate')
                        ->label('Atualizar Banco do Schema')
                        ->icon('heroicon-m-circle-stack')
                        ->color('primary')
                        ->visible(fn (License $record): bool => $record->type === 'cloud_saas' && $record->isSchemaProvisioned())
                        ->requiresConfirmation()
                        ->modalHeading('Re-executar Migrações do Schema')
                        ->modalDescription(fn (License $record) => "Deseja rodar todas as últimas migrações do banco no schema '{$record->schema_name}'?")
                        ->action(function (License $record): void {
                            try {
                                $tenantService = app(\App\Services\TenantService::class);
                                $tenantService->migrateSchema($record->schema_name);
                                Notification::make()
                                    ->title('Schema Atualizado!')
                                    ->body("As migrações foram aplicadas com sucesso no schema {$record->schema_name}.")
                                    ->success()
                                    ->send();
                            } catch (\Throwable $e) {
                                Notification::make()
                                    ->title('Erro ao atualizar schema')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('renew_30')
                        ->label('Renovar +30 Dias')
                        ->icon('heroicon-m-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (License $record): void {
                            $record->renew(30);
                            Notification::make()
                                ->title('Licença renovada com sucesso!')
                                ->body("Novo vencimento: {$record->expires_at->format('d/m/Y')}")
                                ->success()
                                ->send();
                        }),

                    Action::make('toggle_status')
                        ->label(fn (License $record): string => $record->status === 'suspended' ? 'Reativar Licença' : 'Suspender Licença')
                        ->icon(fn (License $record): string => $record->status === 'suspended' ? 'heroicon-m-check-circle' : 'heroicon-m-no-symbol')
                        ->color(fn (License $record): string => $record->status === 'suspended' ? 'success' : 'danger')
                        ->requiresConfirmation()
                        ->action(function (License $record): void {
                            if ($record->status === 'suspended') {
                                $record->activate();
                                Notification::make()->title('Licença reativada!')->success()->send();
                            } else {
                                $record->suspend('Suspensão manual via Painel SaaS');
                                Notification::make()->title('Licença suspensa. O acesso foi bloqueado.')->warning()->send();
                            }
                        }),

                    Action::make('reset_hardware')
                        ->label('Desvincular Máquina (Liberar Hardware ID)')
                        ->icon('heroicon-m-cpu-chip')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Desvincular Computador Autorizado')
                        ->modalDescription('Isso permitirá que a escola autorize um novo computador/servidor no próximo acesso.')
                        ->visible(fn (License $record): bool => $record->type === 'on_premise' && ! empty($record->hardware_id))
                        ->action(function (License $record): void {
                            $record->update(['hardware_id' => null]);
                            Notification::make()
                                ->title('Máquina desvinculada com sucesso!')
                                ->body('O próximo computador da escola que consultar a licença será registrado automaticamente como a máquina autorizada.')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicenses::route('/'),
            'create' => CreateLicense::route('/create'),
            'edit' => EditLicense::route('/{record}/edit'),
        ];
    }
}
