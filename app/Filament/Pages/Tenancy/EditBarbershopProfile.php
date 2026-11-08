<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Barbershop;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditBarbershopProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Configurações da Unidade';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identidade & Atendimento da Unidade')
                    ->description('Personalize o nome, link público, cores e contatos desta filial.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome da Barbearia / Unidade')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label('Link Exclusivo (Slug)')
                            ->helperText('Ex: marinho -> barber.aurosoft.com.br/marinho')
                            ->required()
                            ->unique(Barbershop::class, 'slug', ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('headline')
                            ->label('Slogan / Frase de Destaque')
                            ->placeholder('Ex: Tradição, Estilo e Barboterapia')
                            ->columnSpanFull(),

                        TextInput::make('whatsapp')
                            ->label('WhatsApp de Atendimento')
                            ->tel()
                            ->placeholder('85999999999'),

                        TextInput::make('city')
                            ->label('Cidade / UF')
                            ->placeholder('Fortaleza / CE'),

                        TextInput::make('address')
                            ->label('Endereço Completo')
                            ->placeholder('Rua / Avenida, Número, Bairro')
                            ->columnSpanFull(),

                        ColorPicker::make('brand_color')
                            ->label('Cor da Marca')
                            ->default('#d97706'),

                        TextInput::make('pix_key')
                            ->label('Chave PIX da Unidade')
                            ->placeholder('CNPJ, Telefone ou E-mail'),

                        Textarea::make('description')
                            ->label('Sobre a Unidade')
                            ->placeholder('Descreva o espaço e os diferenciais para seus clientes...')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }
}
