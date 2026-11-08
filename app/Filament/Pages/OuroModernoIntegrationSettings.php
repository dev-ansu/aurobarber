<?php

namespace App\Filament\Pages;

use App\Models\IntegrationSetting;
use App\Models\School;
use App\Services\CourseSyncService;
use App\Services\OuroModernoEadService;
use App\Services\OuroModernoService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class OuroModernoIntegrationSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|UnitEnum|null $navigationGroup = 'Integrações';

    protected static ?string $navigationLabel = 'Ouro Moderno';

    protected static ?string $title = 'Integração com a Ouro Moderno';

    protected static ?string $slug = 'ouro-moderno-integration';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.ouro-moderno-integration-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()?->can('View:OuroModernoIntegration') ?? false;
    }

    public function mount(): void
    {
        $settings = $this->settings();
        $school = $this->currentSchool();

        $this->form->fill([
            'base_url' => $settings?->base_url ?? config('services.ouro_moderno.url'),
            'token' => null,
            'ouro_moderno_ead_enabled' => (bool) ($school?->ouro_moderno_ead_enabled ?? false),
            'ouro_moderno_ead_api_key' => null,
            'ouro_moderno_ead_unidade_nome' => $school?->ouro_moderno_ead_unidade_nome ?? 'Não identificada',
        ]);
    }

    private function currentSchool(): ?School
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof School) {
            return $tenant;
        }

        $schoolId = session('filament_tenant_id') ?? auth()->user()?->school_id;

        return $schoolId ? School::find($schoolId) : School::first();
    }

    public function form(Schema $schema): Schema
    {
        $schoolName = Filament::getTenant()?->getFilamentName();

        return $schema
            ->components([
                Section::make($schoolName ? "Servidor Local Ouro Moderno — {$schoolName}" : 'Servidor Local Ouro Moderno')
                    ->description('Configuração do servidor presencial da escola (instalado na sala de aula / laboratório, acessado via Tailscale Funnel ou IP local).')
                    ->schema([
                        TextInput::make('base_url')
                            ->label('URL base do LMS Ouro Moderno desta escola')
                            ->placeholder('https://servidorouro-unidade.tailxxxx.ts.net')
                            ->url()
                            ->required()
                            ->maxLength(255)
                            ->helperText('Informe o endereço público/Tailscale do servidor Ouro Moderno desta unidade (sem /api). Será usado tanto na API quanto no auto-login do Totem de Presença.'),
                        TextInput::make('token')
                            ->label('Token da API desta escola')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->maxLength(500)
                            ->helperText('O token salvo nunca é exibido. Deixe em branco para manter o token atual desta unidade.'),
                    ])
                    ->columns(2),

                Section::make('Plataforma EAD Online (Ouro Moderno Cursos em Casa)')
                    ->description('Para unidades que oferecem cursos a distância pelo EAD da Ouro Moderno. Permite matricular alunos em casa, acompanhar notas/frequência e gerar auto-login sem necessidade de Tailscale/VPN.')
                    ->schema([
                        Toggle::make('ouro_moderno_ead_enabled')
                            ->label('Habilitar Plataforma EAD da Ouro Moderno para esta escola')
                            ->default(false)
                            ->live(),

                        TextInput::make('ouro_moderno_ead_api_key')
                            ->label('Chave da API EAD (EAD_API_KEY)')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->placeholder('Ex: 53afd7ab97449cdbb682b6b7e335c524')
                            ->maxLength(255)
                            ->helperText('Chave da API fornecida pela Ouro Moderno para integração cloud. Deixe em branco para manter a atual.')
                            ->visible(fn ($get): bool => (bool) $get('ouro_moderno_ead_enabled')),

                        TextInput::make('ouro_moderno_ead_unidade_nome')
                            ->label('Unidade Identificada no EAD')
                            ->disabled()
                            ->placeholder('Clique em "Testar Conexão EAD" para identificar')
                            ->visible(fn ($get): bool => (bool) $get('ouro_moderno_ead_enabled')),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testConnection')
                ->label('Testar Servidor Local')
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->visible(fn (): bool => Auth::user()?->can('Test:OuroModernoIntegration') ?? false)
                ->action(fn () => $this->testConnection()),

            Action::make('testEadConnection')
                ->label('Testar Conexão EAD')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => Auth::user()?->can('Test:OuroModernoIntegration') ?? false)
                ->action(fn () => $this->testEadConnection()),

            Action::make('importCourses')
                ->label('Importar cursos')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->visible(fn (): bool => Auth::user()?->can('Import:OuroModernoCourses') ?? false)
                ->requiresConfirmation()
                ->modalHeading('Importar catálogo de cursos?')
                ->modalDescription('Os cursos da Ouro Moderno serão criados ou atualizados no Aurosoft. Cursos cadastrados localmente e ausentes no LMS não serão excluídos.')
                ->modalSubmitActionLabel('Importar cursos')
                ->action(fn () => $this->importCourses()),
        ];
    }

    public function testEadConnection(): void
    {
        abort_unless(Auth::user()?->can('Test:OuroModernoIntegration'), 403);

        $school = $this->currentSchool();
        $data = $this->form->getState();
        $apiKey = filled($data['ouro_moderno_ead_api_key'] ?? null)
            ? trim((string) $data['ouro_moderno_ead_api_key'])
            : ($school?->ouro_moderno_ead_api_key);

        if (blank($apiKey)) {
            Notification::make()
                ->title('Chave EAD Não Informada')
                ->body('Informe a chave EAD_API_KEY no campo correspondente.')
                ->warning()
                ->send();

            return;
        }

        $service = app(OuroModernoEadService::class);
        $result = $service->testConnection($apiKey);

        if ($result['success'] && $school) {
            $unidadeNome = $result['unidade']['nome'] ?? 'Unidade Identificada';
            $unidadeId = (string) ($result['unidade']['id'] ?? '');

            $school->update([
                'ouro_moderno_ead_enabled' => true,
                'ouro_moderno_ead_api_key' => $apiKey,
                'ouro_moderno_ead_unidade_id' => $unidadeId,
                'ouro_moderno_ead_unidade_nome' => $unidadeNome,
            ]);

            $this->form->fill([
                'ouro_moderno_ead_enabled' => true,
                'ouro_moderno_ead_api_key' => null,
                'ouro_moderno_ead_unidade_nome' => $unidadeNome,
            ]);

            Notification::make()
                ->title('EAD Conectado com Sucesso!')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Falha na Conexão EAD')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function save(): void
    {
        abort_unless(Auth::user()?->can('Update:OuroModernoIntegration'), 403);

        $data = $this->form->getState();
        $settings = $this->settings();
        $token = filled($data['token'] ?? null)
            ? trim((string) $data['token'])
            : ($settings?->token ?? (string) config('services.ouro_moderno.token'));

        if (blank($token)) {
            Notification::make()
                ->title('Informe o token da API')
                ->body('Nenhum token está salvo. Informe o token para habilitar a integração.')
                ->danger()
                ->send();

            return;
        }

        $url = rtrim(trim((string) $data['base_url']), '/');
        validator(['base_url' => $url], [
            'base_url' => ['required', 'url', 'regex:/^https?:\/\//i', 'max:255'],
        ])->validate();

        $schoolId = Filament::getTenant()?->id;

        IntegrationSetting::query()->updateOrCreate(
            [
                'provider' => 'ouro_moderno',
                'school_id' => $schoolId,
            ],
            [
                'base_url' => $url,
                'token' => $token,
                'updated_by' => Auth::id(),
            ],
        );

        $school = $this->currentSchool();
        if ($school) {
            $eadEnabled = (bool) ($data['ouro_moderno_ead_enabled'] ?? false);
            $eadData = ['ouro_moderno_ead_enabled' => $eadEnabled];

            if (filled($data['ouro_moderno_ead_api_key'] ?? null)) {
                $eadData['ouro_moderno_ead_api_key'] = trim((string) $data['ouro_moderno_ead_api_key']);
            }

            $school->update($eadData);
        }

        $this->form->fill([
            'base_url' => $url,
            'token' => null,
            'ouro_moderno_ead_enabled' => (bool) ($school?->ouro_moderno_ead_enabled ?? false),
            'ouro_moderno_ead_api_key' => null,
            'ouro_moderno_ead_unidade_nome' => $school?->ouro_moderno_ead_unidade_nome ?? 'Não identificada',
        ]);

        Notification::make()
            ->title('Configuração salva')
            ->body('As credenciais locais e do EAD foram armazenadas com sucesso.')
            ->success()
            ->send();
    }

    public function testConnection(): void
    {
        abort_unless(Auth::user()?->can('Test:OuroModernoIntegration'), 403);

        try {
            $schoolId = Filament::getTenant()?->id;
            app(OuroModernoService::class)->forSchool($schoolId)->courses();

            Notification::make()
                ->title('Conexão validada')
                ->body('A API desta unidade respondeu à consulta de cursos. Nenhum curso foi importado.')
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Não foi possível conectar à API')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function importCourses(): void
    {
        abort_unless(Auth::user()?->can('Import:OuroModernoCourses'), 403);

        try {
            $schoolId = Filament::getTenant()?->id;
            $count = app(CourseSyncService::class)->syncCourses($schoolId);

            Notification::make()
                ->title('Importação concluída')
                ->body("{$count} curso(s) criado(s) ou atualizado(s). Cursos locais ausentes na API foram mantidos.")
                ->success()
                ->send();
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Falha na importação de cursos')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function integrationConfigured(): bool
    {
        $schoolId = Filament::getTenant()?->id;

        return app(OuroModernoService::class)->forSchool($schoolId)->isConfigured();
    }

    public function currentSchoolName(): ?string
    {
        return Filament::getTenant()?->getFilamentName();
    }

    private function settings(): ?IntegrationSetting
    {
        $schoolId = Filament::getTenant()?->id;

        if ($schoolId) {
            return IntegrationSetting::query()
                ->where('provider', 'ouro_moderno')
                ->where('school_id', $schoolId)
                ->first()
                ?? IntegrationSetting::query()
                    ->where('provider', 'ouro_moderno')
                    ->whereNull('school_id')
                    ->first();
        }

        return IntegrationSetting::query()
            ->where('provider', 'ouro_moderno')
            ->whereNull('school_id')
            ->first()
            ?? IntegrationSetting::query()
                ->where('provider', 'ouro_moderno')
                ->first();
    }
}
