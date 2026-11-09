<?php

namespace App\Filament\Auth;

use App\Models\Barbershop;
use Filament\Auth\Pages\EditProfile;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class EditOwnProfile extends EditProfile
{
    public function boot(): void
    {
        if (Filament::hasTenancy() && ! Filament::getTenant() && auth()->check()) {
            $tenantParam = request()->query('tenant');
            $tenant = null;

            if ($tenantParam) {
                $tenant = Barbershop::where('slug', (string) $tenantParam)
                    ->orWhere('id', is_numeric($tenantParam) ? (int) $tenantParam : 0)
                    ->first();
            }

            if (! $tenant) {
                $user = auth()->user();
                $tenant = $user?->barbershops()->first()
                    ?? ($user?->barbershop_id ? Barbershop::find($user->barbershop_id) : null)
                    ?? Barbershop::first();
            }

            if ($tenant) {
                Filament::setTenant($tenant, isQuiet: true);
            }
        }
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function getLabel(): string
    {
        return 'Meu Perfil & Segurança';
    }

    public function getMaxWidth(): Width | string | null
    {
        return '5xl';
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->helperText('A senha deve ter no mínimo 8 caracteres, contendo letras maiúsculas, minúsculas, números e símbolos.')
            ->columnSpanFull();
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()
            ->columnSpanFull();
    }

    protected function getCurrentPasswordFormComponent(): Component
    {
        return parent::getCurrentPasswordFormComponent()
            ->columnSpanFull();
    }

    public function form(Schema $schema): Schema
    {
        $mfaComponent = $this->getMultiFactorAuthenticationContentComponent();

        return $schema
            ->components([
                Tabs::make('Perfil & Segurança')
                    ->tabs([
                        Tab::make('Dados Pessoais')
                            ->icon('heroicon-o-user')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome Completo')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                $this->getEmailFormComponent()->columnSpanFull(),
                            ]),

                        Tab::make('Segurança & Senha')
                            ->icon('heroicon-o-key')
                            ->schema([
                                $this->getPasswordFormComponent(),
                                $this->getPasswordConfirmationFormComponent(),
                                $this->getCurrentPasswordFormComponent(),
                            ]),

                        Tab::make('Autenticação em Duas Etapas (2FA)')
                            ->icon('heroicon-o-shield-check')
                            ->schema(array_filter([
                                $mfaComponent,
                            ])),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }
}
