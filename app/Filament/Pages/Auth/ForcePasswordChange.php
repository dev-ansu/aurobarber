<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rules\Password;

class ForcePasswordChange extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $title = 'Redefinição Obrigatória de Senha';

    protected static ?string $slug = 'force-password-change';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.auth.force-password-change';

    public ?string $current_password = '';

    public ?string $password = '';

    public ?string $password_confirmation = '';

    public function getMaxContentWidth(): Width|string|null
    {
        return 'full';
    }

    public function getMaxWidth(): Width|string|null
    {
        return 'full';
    }

    public static function canAccess(): bool
    {
        return auth()->check() && (bool) auth()->user()->must_change_password;
    }

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user || ! (bool) $user->must_change_password) {
            if ($user && $user->role === 'master_admin') {
                $this->redirect(url('/saas'));

                return;
            }

            $this->redirect(filament()->getUrl());

            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Redefinição Obrigatória de Senha')
                    ->tabs([
                        Tab::make('Credenciais de Acesso')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                TextInput::make('current_password')
                                    ->label('Senha Atual / Temporária')
                                    ->password()
                                    ->revealable()
                                    ->prefixIcon('heroicon-m-key')
                                    ->required()
                                    ->currentPassword()
                                    ->helperText('Informe a senha temporária que você utilizou para acessar o sistema.')
                                    ->columnSpanFull(),

                                TextInput::make('password')
                                    ->label('Nova Senha Definitiva')
                                    ->password()
                                    ->revealable()
                                    ->prefixIcon('heroicon-m-shield-check')
                                    ->required()
                                    ->confirmed()
                                    ->rule(
                                        Password::min(8)
                                            ->letters()
                                            ->mixedCase()
                                            ->numbers()
                                            ->symbols()
                                    )
                                    ->helperText('Mínimo 8 caracteres, contendo maiúsculas, minúsculas, números e caracteres especiais.')
                                    ->columnSpanFull(),

                                TextInput::make('password_confirmation')
                                    ->label('Confirmar Nova Senha')
                                    ->password()
                                    ->revealable()
                                    ->prefixIcon('heroicon-m-check-badge')
                                    ->required()
                                    ->helperText('Digite exatamente a mesma nova senha para validação.')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Diretrizes & Segurança da Conta')
                            ->icon('heroicon-o-shield-exclamation')
                            ->schema([
                                Placeholder::make('security_guidelines')
                                    ->label('Boas Práticas de Proteção e Privacidade')
                                    ->content(new HtmlString('
                                        <div class="flex flex-col gap-3 py-2 text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                                            <div class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                                                <span class="text-base font-bold text-success-600 dark:text-success-400">✓</span>
                                                <div><strong class="text-gray-950 dark:text-white">Complexidade Forte:</strong> Crie uma senha exclusiva combinando letras maiúsculas, minúsculas, números e símbolos especiais (ex: @, #, $, !).</div>
                                            </div>
                                            <div class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                                                <span class="text-base font-bold text-success-600 dark:text-success-400">✓</span>
                                                <div><strong class="text-gray-950 dark:text-white">Credencial Pessoal e Exclusiva:</strong> Não compartilhe suas credenciais com terceiros. Cada membro de sua equipe possui acesso próprio parametrizado.</div>
                                            </div>
                                            <div class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                                                <span class="text-base font-bold text-success-600 dark:text-success-400">✓</span>
                                                <div><strong class="text-gray-950 dark:text-white">Criptografia no Padrão Bancário:</strong> O AuroBarber protege seus dados com hash unidirecional seguro e auditoria ativa de segurança.</div>
                                            </div>
                                        </div>
                                    '))
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();
        $user->forceFill([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();

        Notification::make()
            ->title('Senha redefinida com sucesso!')
            ->body('Sua nova senha está ativa. Seu acesso ao sistema está liberado!')
            ->success()
            ->send();

        if ($user->role === 'master_admin') {
            $this->redirect(url('/saas'));

            return;
        }

        $this->redirect(filament()->getUrl());
    }
}
