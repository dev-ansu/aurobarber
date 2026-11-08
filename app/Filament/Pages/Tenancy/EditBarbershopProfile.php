<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Barbershop;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class EditBarbershopProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Configurações da Unidade';
    }

    public function getMaxWidth(): Width | string | null
    {
        return '4xl';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Unidade')
                    ->tabs([
                        Tab::make('Identidade & Marca')
                            ->icon('heroicon-o-building-storefront')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nome da Barbearia / Unidade')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('slug')
                                    ->label('Link Exclusivo (Slug)')
                                    ->prefix('barber.aurosoft.com.br/')
                                    ->required()
                                    ->unique(Barbershop::class, 'slug', ignoreRecord: true)
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('headline')
                                    ->label('Slogan / Frase de Destaque')
                                    ->placeholder('Ex: Tradição, Estilo e Barboterapia')
                                    ->columnSpanFull(),

                                ColorPicker::make('brand_color')
                                    ->label('Cor da Marca')
                                    ->default('#d97706')
                                    ->columnSpanFull(),

                                Textarea::make('description')
                                    ->label('Sobre a Unidade')
                                    ->placeholder('Descreva o espaço e diferenciais desta unidade...')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Contato & Localização')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                TextInput::make('whatsapp')
                                    ->label('WhatsApp de Atendimento')
                                    ->tel()
                                    ->placeholder('85999999999')
                                    ->columnSpanFull(),

                                TextInput::make('city')
                                    ->label('Cidade / UF')
                                    ->placeholder('Fortaleza / CE')
                                    ->columnSpanFull(),

                                TextInput::make('address')
                                    ->label('Endereço Completo')
                                    ->placeholder('Rua / Avenida, Número, Bairro')
                                    ->columnSpanFull(),

                                TextInput::make('pix_key')
                                    ->label('Chave PIX da Unidade')
                                    ->placeholder('CNPJ, Telefone ou E-mail')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Salvar Alterações')
                ->color('primary'),

            Action::make('back')
                ->label('Voltar ao Painel')
                ->color('gray')
                ->outlined()
                ->url(fn () => filament()->getUrl() ?? url('/admin')),
        ];
    }
}
