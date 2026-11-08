<?php

namespace App\Filament\Pages\Auth;

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
use Illuminate\Validation\Rules\Password;

class ForcePasswordChange extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-key';

    protected static ?string $title = 'Redefinição Obrigatória de Senha';

    protected static ?string $slug = 'force-password-change';

    protected static bool $isScopedToTenant = false;

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.auth.force-password-change';

    public ?string $current_password = '';
    public ?string $password = '';
    public ?string $password_confirmation = '';

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Medium;
    }

    public static function canAccess(): bool
    {
        return auth()->check() && (bool) auth()->user()->must_change_password;
    }

    public function mount(): void
    {
        if (!auth()->check() || !auth()->user()->must_change_password) {
            $this->redirect(filament()->getUrl());
            return;
        }

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Segurança')
                    ->tabs([
                        Tab::make('Criar Nova Senha')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                TextInput::make('current_password')
                                    ->label('Senha Atual / Temporária')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->currentPassword()
                                    ->helperText('Informe a senha temporária que você utilizou para entrar.')
                                    ->columnSpanFull(),

                                TextInput::make('password')
                                    ->label('Nova Senha Definitiva')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->confirmed()
                                    ->rule(
                                        Password::min(8)
                                            ->letters()
                                            ->mixedCase()
                                            ->numbers()
                                            ->symbols()
                                    )
                                    ->helperText('Mínimo 8 caracteres, contendo maiúsculas, minúsculas, números e símbolos.')
                                    ->columnSpanFull(),

                                TextInput::make('password_confirmation')
                                    ->label('Confirmar Nova Senha')
                                    ->password()
                                    ->revealable()
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                    ]),
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

        $this->redirect(filament()->getUrl());
    }
}
