<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Barbershop;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;

class RegisterBarbershop extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Nova Unidade / Filial';
    }

    public function mount(): void
    {
        parent::mount();

        $user = auth()->user();
        if (! $user) {
            return;
        }

        $primaryShop = $user->barbershops()->whereNull('parent_id')->first()
            ?? $user->barbershops()->first();

        if ($primaryShop && ! $primaryShop->canAddBranch()) {
            $allowed = $primaryShop->getAllowedUnits();

            Notification::make()
                ->title('Limite de Unidades Atingido')
                ->body("Sua assinatura permite no máximo {$allowed} ".($allowed === 1 ? 'unidade' : 'unidades').'. Para cadastrar novas filiais, solicite a alteração do seu plano.')
                ->danger()
                ->persistent()
                ->send();

            $this->redirect(url("/admin/{$primaryShop->slug}/planos-de-licenciamento"));
        }
    }

    public function getMaxWidth(): Width|string|null
    {
        return '3xl';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('NovaFilial')
                    ->tabs([
                        Tab::make('Dados da Filial')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome da Barbearia / Unidade')
                                    ->placeholder('Ex: Barbearia Marinho - Aldeota')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (filled($state)) {
                                            $set('slug', Str::slug($state));
                                        }
                                    })
                                    ->columnSpanFull(),

                                TextInput::make('slug')
                                    ->label('Link Exclusivo (Slug)')
                                    ->prefix('barber.aurosoft.com.br/')
                                    ->placeholder('marinho-aldeota')
                                    ->required()
                                    ->unique(
                                        Barbershop::class,
                                        'slug',
                                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->withoutTrashed(),
                                    )
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                ColorPicker::make('brand_color')
                                    ->label('Cor da Marca')
                                    ->default('#d97706')
                                    ->columnSpanFull(),

                                Textarea::make('description')
                                    ->label('Sobre a Unidade (Opcional)')
                                    ->placeholder('Descreva os diferenciais desta filial...')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Contato & Endereço')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                TextInput::make('whatsapp')
                                    ->label('WhatsApp da Unidade')
                                    ->tel()
                                    ->placeholder('85999999999')
                                    ->columnSpanFull(),

                                TextInput::make('city')
                                    ->label('Cidade / UF')
                                    ->placeholder('Fortaleza / CE')
                                    ->columnSpanFull(),

                                TextInput::make('address')
                                    ->label('Endereço Completo')
                                    ->placeholder('Av. Santos Dumont, 1200 - Aldeota')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getRegisterFormAction()
                ->label('Cadastrar Unidade')
                ->color('primary'),

            Action::make('cancel')
                ->label('Cancelar / Voltar')
                ->color('gray')
                ->outlined()
                ->url(fn () => filament()->getUrl() ?? url('/admin')),
        ];
    }

    protected function handleRegistration(array $data): Model
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        return DB::transaction(function () use ($user, $data): Model {
            $primaryBarbershop = $user->barbershops()->whereNull('parent_id')->first()
                ?? $user->barbershops()->first();

            if ($primaryBarbershop) {
                // Bloqueio atômico para evitar condições de corrida (race conditions)
                $lockedPrimary = Barbershop::query()->lockForUpdate()->findOrFail($primaryBarbershop->id);

                if (! $lockedPrimary->canAddBranch()) {
                    $allowed = $lockedPrimary->getAllowedUnits();

                    throw ValidationException::withMessages([
                        'name' => "O limite de {$allowed} ".($allowed === 1 ? 'unidade' : 'unidades').' da sua assinatura foi atingido. Solicite a alteração do plano para cadastrar novas filiais.',
                    ]);
                }

                $data['parent_id'] = $lockedPrimary->id;
                $data['saas_plan_id'] = $lockedPrimary->saas_plan_id;
                $data['license_status'] = $lockedPrimary->license_status ?? 'active';
                $data['license_expires_at'] = $lockedPrimary->license_expires_at;
            }

            $data['owner_id'] = $user->id;
            $data['is_active'] = true;
            $data['subscription_status'] = 'active';

            $barbershop = Barbershop::create($data);

            $user->barbershops()->syncWithoutDetaching([
                $barbershop->id => [
                    'role' => 'owner',
                    'is_primary' => false,
                ],
            ]);

            return $barbershop;
        });
    }
}
