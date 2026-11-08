<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Barbershop;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\RegisterTenant;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RegisterBarbershop extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Nova Unidade / Filial';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da Nova Unidade')
                    ->description('Cadastre uma nova filial para gerenciar sua equipe, horários e faturamento.')
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
                            }),

                        TextInput::make('slug')
                            ->label('Link Exclusivo (Slug)')
                            ->helperText('Ex: marinho-aldeota -> barber.aurosoft.com.br/marinho-aldeota')
                            ->required()
                            ->unique(Barbershop::class, 'slug')
                            ->maxLength(255),

                        TextInput::make('whatsapp')
                            ->label('WhatsApp da Unidade')
                            ->tel()
                            ->placeholder('85999999999'),

                        TextInput::make('city')
                            ->label('Cidade / UF')
                            ->placeholder('Fortaleza / CE'),

                        TextInput::make('address')
                            ->label('Endereço da Unidade')
                            ->placeholder('Av. Santos Dumont, 1200 - Aldeota')
                            ->columnSpanFull(),

                        ColorPicker::make('brand_color')
                            ->label('Cor da Marca')
                            ->default('#d97706'),

                        Textarea::make('description')
                            ->label('Sobre a Unidade')
                            ->placeholder('Descreva o ambiente e diferenciais desta filial...')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $user = auth()->user();
        $data['owner_id'] = $user->id;
        $data['is_active'] = true;
        $data['subscription_status'] = 'active';

        // Se o usuário já tiver uma matriz/unidade cadastrada, herda como parent_id
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
