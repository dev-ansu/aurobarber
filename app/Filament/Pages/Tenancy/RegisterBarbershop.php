<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Barbershop;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RegisterBarbershop extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Nova Unidade / Filial';
    }

    public function getMaxWidth(): Width | string | null
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
                                    ->unique(Barbershop::class, 'slug')
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
        $data['owner_id'] = $user->id;
        $data['is_active'] = true;
        $data['subscription_status'] = 'active';

        // Se o usuário já tiver uma matriz/unidade cadastrada, herda como filial (parent_id)
        $primaryBarbershop = $user->barbershops()->first();
        if ($primaryBarbershop) {
            $data['parent_id'] = $primaryBarbershop->id;
        }

        $barbershop = Barbershop::create($data);

        $user->barbershops()->attach($barbershop->id, [
            'role' => 'owner',
            'is_primary' => false,
        ]);

        return $barbershop;
    }
}
