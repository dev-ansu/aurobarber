<?php

declare(strict_types=1);

namespace App\Filament\Saas\Pages;

use App\Models\SaasBackupSetting;
use App\Models\User;
use App\Services\SaasGoogleDriveBackupService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Crypt;
use UnitEnum;

class SaasBackupCloudSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cloud-arrow-up';

    protected static ?string $navigationLabel = 'Backup Externo (Google Drive)';

    protected static ?string $title = 'Backup Externo — Google Drive';

    protected static string|UnitEnum|null $navigationGroup = 'SaaS Master';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'backup-cloud-settings';

    protected string $view = 'filament.saas.pages.backup-cloud-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->role === 'master_admin';
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $settings = SaasBackupSetting::current();
        $this->form->fill([
            'google_drive_enabled' => (bool) ($settings->google_drive_enabled ?? false),
            'google_drive_client_id' => $settings->google_drive_client_id,
            'google_drive_client_secret' => null,
            'google_drive_refresh_token' => null,
            'google_drive_folder_id' => $settings->google_drive_folder_id,
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToBackups')
                ->label('Voltar para Backups')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn (): string => SaasBackups::getUrl()),

            Action::make('testConnection')
                ->label('Testar Conexão')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->action(function (SaasGoogleDriveBackupService $service): void {
                    abort_unless(static::canAccess(), 403);
                    try {
                        $service->testConnection();
                        Notification::make()
                            ->title('Conexão com Google Drive validada com sucesso!')
                            ->body('A pasta de destino foi localizada e está pronta para receber backups criptografados.')
                            ->success()
                            ->send();
                    } catch (\Throwable $exception) {
                        Notification::make()
                            ->title('Falha ao conectar com o Google Drive')
                            ->body($exception->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            Action::make('createBackupFolder')
                ->label('Criar Pasta no Drive')
                ->icon('heroicon-o-folder-plus')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Criar pasta dedicada no Google Drive?')
                ->modalDescription('Será criada a pasta “Backups AuroBarber SaaS” no Meu Drive da conta autorizada, e o ID será preenchido automaticamente.')
                ->action(function (SaasGoogleDriveBackupService $service): void {
                    abort_unless(static::canAccess(), 403);
                    try {
                        $folderId = $service->createBackupFolder();
                        $this->form->fill(array_merge($this->data ?? [], ['google_drive_folder_id' => $folderId]));

                        // Atualiza direto no banco
                        $settings = SaasBackupSetting::current();
                        $settings->google_drive_folder_id = $folderId;
                        $settings->save();

                        Notification::make()
                            ->title('Pasta criada com sucesso no Google Drive!')
                            ->body("ID da pasta configurado: {$folderId}. Agora teste a conexão para validar.")
                            ->success()
                            ->send();
                    } catch (\Throwable $exception) {
                        Notification::make()
                            ->title('Não foi possível criar a pasta no Drive')
                            ->body($exception->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $settings = SaasBackupSetting::current();

        return $schema->statePath('data')->components([
            Section::make('Envio dos Backups para Nuvem')
                ->description('Quando ativado, todo backup manual ou agendado será automaticamente criptografado em fluxo (Libsodium XChaCha20-Poly1305) com o APP_KEY do servidor e enviado para o Google Drive.')
                ->schema([
                    Toggle::make('google_drive_enabled')
                        ->label('Habilitar envio automático para o Google Drive')
                        ->default(false),
                    Placeholder::make('encryption_notice')
                        ->label('Criptografia em Trânsito e Repouso')
                        ->content('Os backups são transmitidos com criptografia SecretStream. O arquivo (.zip.enc) é ilegível sem a chave APP_KEY do seu servidor AuroBarber.'),
                ])->columns(2),

            Section::make('Credenciais Google OAuth 2.0')
                ->description('Configuração do projeto Google Cloud (Google Drive API v3). Os segredos salvos são gravados com criptografia no banco de dados.')
                ->schema([
                    TextInput::make('google_drive_client_id')
                        ->label('Client ID do Google')
                        ->placeholder('ex: 123456789-abcdef.apps.googleusercontent.com')
                        ->maxLength(500)
                        ->columnSpanFull(),

                    TextInput::make('google_drive_client_secret')
                        ->label('Client Secret do Google')
                        ->password()
                        ->revealable()
                        ->placeholder(filled($settings->google_drive_client_secret) ? '•••••••••••••••• (Já configurado — deixe em branco para manter)' : 'Informe o Client Secret')
                        ->helperText('Deixe vazio para manter o segredo atual já gravado.')
                        ->columnSpan(1),

                    TextInput::make('google_drive_refresh_token')
                        ->label('Refresh Token do Google')
                        ->password()
                        ->revealable()
                        ->placeholder(filled($settings->google_drive_refresh_token) ? '•••••••••••••••• (Já configurado — deixe em branco para manter)' : 'Informe o Refresh Token')
                        ->helperText('Token com escopo https://www.googleapis.com/auth/drive.file.')
                        ->columnSpan(1),

                    TextInput::make('google_drive_folder_id')
                        ->label('ID da Pasta de Destino no Google Drive')
                        ->placeholder('ex: 1a2B3c4D5e6F7g8H9i0J')
                        ->helperText('Você pode colar o ID da pasta do Drive aqui ou usar o botão "Criar Pasta no Drive" no topo da página.')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        $data = $this->form->getState();
        $settings = SaasBackupSetting::current();
        $settings->google_drive_enabled = (bool) ($data['google_drive_enabled'] ?? false);
        $settings->google_drive_client_id = trim((string) ($data['google_drive_client_id'] ?? '')) ?: null;
        $settings->google_drive_folder_id = trim((string) ($data['google_drive_folder_id'] ?? '')) ?: null;

        if (filled($data['google_drive_client_secret'] ?? null)) {
            $settings->google_drive_client_secret = Crypt::encryptString(trim((string) $data['google_drive_client_secret']));
        }
        if (filled($data['google_drive_refresh_token'] ?? null)) {
            $settings->google_drive_refresh_token = Crypt::encryptString(trim((string) $data['google_drive_refresh_token']));
        }

        $settings->save();

        Notification::make()
            ->title('Configurações de backup externo salvas!')
            ->success()
            ->send();
    }
}
