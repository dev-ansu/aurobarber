<?php

namespace App\Filament\Pages;

use App\Models\School;
use App\Services\GoogleDriveBackupService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\HtmlString;
use UnitEnum;

class GoogleDriveIntegrationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|UnitEnum|null $navigationGroup = 'Integrações';

    protected static ?string $navigationLabel = 'Google Drive (Nuvem)';

    protected static ?string $title = 'Backup em Nuvem — Google Drive';

    protected static ?string $slug = 'google-drive-integration';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cloud-arrow-up';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.google-drive-integration-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        // No ambiente SaaS Cloud, backups em nuvem são gerenciados centralmente pela infraestrutura da VPS
        if ((bool) env('SAAS_ENABLED', false)) {
            return false;
        }

        return Auth::user()?->hasRole('super_admin') || Auth::user()?->can('View:GoogleDriveIntegration') || Auth::user()?->can('View:BackupManagement');
    }

    public function mount(): void
    {
        $school = $this->getEffectiveConfigSchool();

        $this->form->fill([
            'google_drive_backup_enabled' => (bool) ($school?->google_drive_backup_enabled ?? true),
            'google_drive_client_id' => $school?->google_drive_client_id ?? env('GOOGLE_DRIVE_CLIENT_ID'),
            'google_drive_client_secret' => null, // Oculto por segurança
            'google_drive_refresh_token' => null, // Oculto por segurança
            'google_drive_folder_id' => $school?->google_drive_folder_id ?? env('GOOGLE_DRIVE_FOLDER_ID'),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Testar Conexão com Google Drive')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->action(function (GoogleDriveBackupService $driveService): void {
                    try {
                        $token = $driveService->getAccessToken();
                        if ($token) {
                            Notification::make()
                                ->title('Conexão Homologada com Sucesso!')
                                ->body('O Aurosoft conseguiu autenticar e validar as credenciais do Google Drive.')
                                ->success()
                                ->send();
                        }
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Falha ao conectar com o Google Drive')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $school = $this->getEffectiveConfigSchool();
        $hasSecret = filled($school?->google_drive_client_secret) || filled(env('GOOGLE_DRIVE_CLIENT_SECRET'));
        $hasToken = filled($school?->google_drive_refresh_token) || filled(env('GOOGLE_DRIVE_REFRESH_TOKEN'));

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Status do Backup em Nuvem')
                    ->description('Proteja o banco de dados e arquivos de toda a rede sincronizando backups automáticos em nuvem no Google Drive. Como todas as unidades compartilham o mesmo banco de dados, esta configuração é aplicada globalmente para a Matriz e todas as filiais.')
                    ->schema([
                        Toggle::make('google_drive_backup_enabled')
                            ->label('Habilitar envio automático de backups para o Google Drive')
                            ->helperText('Quando ativado, os backups automáticos (12:00 e 23:00) serão enviados para a pasta configurada.')
                            ->default(true),
                        Placeholder::make('last_backup_info')
                            ->label('Último envio para a nuvem')
                            ->content(function (): string {
                                $school = School::query()
                                    ->whereNotNull('google_drive_last_backup_at')
                                    ->orderByDesc('google_drive_last_backup_at')
                                    ->first() ?? School::query()->orderBy('id')->first();

                                return $school?->google_drive_last_backup_at
                                    ? $school->google_drive_last_backup_at->format('d/m/Y H:i:s') . ' (' . ($school->google_drive_last_backup_status ?? 'OK') . ')'
                                    : 'Nenhum envio recente registrado ainda.';
                            }),
                    ])
                    ->columns(2),

                Section::make('Credenciais da API Google Cloud (OAuth 2.0)')
                    ->description('Informe as credenciais obtidas no console da Google para permitir o upload dos arquivos.')
                    ->schema([
                        TextInput::make('google_drive_client_id')
                            ->label('ID do Cliente (Client ID)')
                            ->placeholder('Ex: 1024235957639-xxxxxxxx.apps.googleusercontent.com')
                            ->columnSpanFull()
                            ->required(),
                        TextInput::make('google_drive_client_secret')
                            ->label('Chave Secreta do Cliente (Client Secret)')
                            ->password()
                            ->revealable()
                            ->placeholder($hasSecret ? '•••••••••••••••••••• (Já configurado, deixe em branco para manter)' : 'GOCSPX-xxxxxxxx')
                            ->helperText('Deixe em branco para manter a chave atual salva.')
                            ->columnSpan(1),
                        TextInput::make('google_drive_refresh_token')
                            ->label('Refresh Token de Autorização')
                            ->password()
                            ->revealable()
                            ->placeholder($hasToken ? '•••••••••••••••••••• (Já autorizado, deixe em branco para manter)' : '1//0434-xxxxxxxx')
                            ->helperText('Token de acesso permanente gerado via OAuth Playground.')
                            ->columnSpan(1),
                        TextInput::make('google_drive_folder_id')
                            ->label('ID da Pasta no Google Drive')
                            ->placeholder('Ex: 16fTIFiGYU3YnN3EWsv6FtuvWJQ2SfvTw')
                            ->helperText('O código de letras e números que aparece no final do link da pasta no Google Drive.')
                            ->columnSpanFull()
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Ajuda & Instruções Rápidas')
                    ->collapsed()
                    ->schema([
                        Placeholder::make('Instruções')
                            ->label('')
                            ->content(new HtmlString('
                                <div class="text-xs space-y-2 text-gray-600 dark:text-gray-300">
                                    <p><strong>1. Como criar a credencial:</strong> No Google Cloud Console, crie um projeto, ative a <em>Google Drive API</em> e gere um ID do cliente OAuth para aplicativo Web.</p>
                                    <p><strong>2. Redirecionamento:</strong> Em <em>URIs de redirecionamento autorizados</em>, cadastre <code>https://developers.google.com/oauthplayground</code>.</p>
                                    <p><strong>3. Gerar o Refresh Token:</strong> Acesse o OAuth Playground, configure com seu Client ID e Client Secret, autorize o escopo <code>https://www.googleapis.com/auth/drive.file</code> e troque o código pelo token permanente.</p>
                                </div>
                            ')),
                    ]),
            ]);
    }

    public function save(): void
    {
        $school = $this->getEffectiveConfigSchool() ?? $this->currentSchool();
        if (! $school) {
            Notification::make()->title('Nenhuma escola encontrada no sistema.')->danger()->send();

            return;
        }

        $formData = $this->form->getState();

        $updateData = [
            'google_drive_backup_enabled' => (bool) ($formData['google_drive_backup_enabled'] ?? false),
            'google_drive_client_id' => trim((string) ($formData['google_drive_client_id'] ?? '')),
            'google_drive_folder_id' => trim((string) ($formData['google_drive_folder_id'] ?? '')),
        ];

        if (filled($formData['google_drive_client_secret'] ?? null)) {
            $updateData['google_drive_client_secret'] = Crypt::encryptString(trim((string) $formData['google_drive_client_secret']));
        } elseif ($school && filled($school->google_drive_client_secret)) {
            $updateData['google_drive_client_secret'] = $school->google_drive_client_secret;
        }

        if (filled($formData['google_drive_refresh_token'] ?? null)) {
            $updateData['google_drive_refresh_token'] = Crypt::encryptString(trim((string) $formData['google_drive_refresh_token']));
        } elseif ($school && filled($school->google_drive_refresh_token)) {
            $updateData['google_drive_refresh_token'] = $school->google_drive_refresh_token;
        }

        // Replica a configuração para todas as escolas da rede (Matriz e filiais)
        School::query()->get()->each(function (School $unit) use ($updateData) {
            $unit->update($updateData);
        });

        // Atualiza o service imediatamente
        app(GoogleDriveBackupService::class)->resolveCredentials();

        Notification::make()
            ->title('Configurações do Google Drive salvas com sucesso!')
            ->body('A configuração foi replicada e está ativa para todas as unidades da rede.')
            ->success()
            ->send();
    }

    private function currentSchool(): ?School
    {
        try {
            $tenant = Filament::getTenant();
            if ($tenant instanceof School) {
                return $tenant;
            }
        } catch (\Throwable) {
            //
        }

        return School::getHeadquarters() ?? School::query()->orderBy('id')->first();
    }

    private function getEffectiveConfigSchool(): ?School
    {
        $current = $this->currentSchool();
        if ($current && filled($current->google_drive_client_id)) {
            return $current;
        }

        return School::query()
            ->where('is_headquarters', true)
            ->whereNotNull('google_drive_client_id')
            ->where('google_drive_client_id', '!=', '')
            ->first()
            ?? School::query()
                ->whereNotNull('google_drive_client_id')
                ->where('google_drive_client_id', '!=', '')
                ->orderBy('id')
                ->first()
            ?? $current;
    }
}

