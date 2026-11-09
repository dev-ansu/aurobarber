<?php

namespace App\Filament\Pages\Tenancy;

use App\Models\Barbershop;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Validation\Rules\Unique;

class EditBarbershopProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Configurações da Barbearia';
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $tenant): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->role === 'master_admin'
            || $user->role === 'owner'
            || (int) $tenant->owner_id === (int) $user->id
            || (method_exists($user, 'hasRole') && $user->hasRole('super_admin'));
    }

    public function getMaxWidth(): Width | string | null
    {
        return '5xl';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Unidade')
                    ->tabs([
                        Tab::make('Identidade & Marca (White-Label)')
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
                                    ->unique(
                                        Barbershop::class,
                                        'slug',
                                        ignoreRecord: true,
                                        modifyRuleUsing: fn (Unique $rule): Unique => $rule->withoutTrashed(),
                                    )
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('headline')
                                    ->label('Slogan / Frase de Destaque')
                                    ->placeholder('Ex: Tradição, Estilo e Barboterapia')
                                    ->columnSpanFull(),

                                ColorPicker::make('brand_color')
                                    ->label('Cor Principal da Marca (Sistema & Página Pública)')
                                    ->helperText('Esta cor será aplicada automaticamente nos botões, destaques do seu painel e no site de agendamento.')
                                    ->default('#d97706')
                                    ->columnSpanFull(),

                                FileUpload::make('logo_url')
                                    ->label('Logomarca da Barbearia')
                                    ->image()
                                    ->disk('public')
                                    ->directory('barbershops/logos')
                                    ->helperText('Exibida no topo do sistema e na página pública de agendamento.')
                                    ->columnSpanFull(),

                                FileUpload::make('banner_url')
                                    ->label('Banner / Foto de Capa (Opcional)')
                                    ->image()
                                    ->disk('public')
                                    ->directory('barbershops/banners')
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

                                TextInput::make('instagram')
                                    ->label('Instagram (@usuario)')
                                    ->placeholder('@barbeariamarinho')
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

                        Tab::make('Horários & Funcionamento')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                CheckboxList::make('opening_hours.days')
                                    ->label('Dias de Funcionamento da Barbearia')
                                    ->options([
                                        'mon' => 'Segunda-feira',
                                        'tue' => 'Terça-feira',
                                        'wed' => 'Quarta-feira',
                                        'thu' => 'Quinta-feira',
                                        'fri' => 'Sexta-feira',
                                        'sat' => 'Sábado',
                                        'sun' => 'Domingo',
                                    ])
                                    ->default(['mon', 'tue', 'wed', 'thu', 'fri', 'sat'])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                TimePicker::make('opening_hours.start_time')
                                    ->label('Horário Geral de Abertura')
                                    ->default('08:00')
                                    ->columnSpanFull(),

                                TimePicker::make('opening_hours.end_time')
                                    ->label('Horário Geral de Fechamento')
                                    ->default('19:30')
                                    ->columnSpanFull(),

                                Select::make('opening_hours.slot_interval')
                                    ->label('Intervalo entre Horários na Agenda')
                                    ->options([
                                        15 => 'A cada 15 minutos',
                                        30 => 'A cada 30 minutos (Padrão recomendável)',
                                        45 => 'A cada 45 minutos',
                                        60 => 'A cada 1 hora',
                                    ])
                                    ->default(30)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Fidelidade & Retorno de Clientes')
                            ->icon('heroicon-o-gift')
                            ->schema([
                                Toggle::make('loyalty_enabled')
                                    ->label('Ativar Programa de Cartão Fidelidade Automático')
                                    ->helperText('Contabiliza automaticamente 1 selo a cada atendimento concluído do cliente.')
                                    ->default(true)
                                    ->columnSpanFull(),

                                TextInput::make('loyalty_target_cuts')
                                    ->label('Meta de Cortes para Ganhar 1 Corte/Prêmio')
                                    ->numeric()
                                    ->default(10)
                                    ->minValue(2)
                                    ->maxValue(50)
                                    ->suffix('cortes')
                                    ->helperText('Ex: A cada 10 cortes concluídos, o sistema avisa que o cliente ganhou 1 prêmio.')
                                    ->columnSpanFull(),

                                TextInput::make('return_reminder_days')
                                    ->label('Prazo para Alerta de Retorno ("Cliente Sumido")')
                                    ->numeric()
                                    ->default(20)
                                    ->minValue(5)
                                    ->maxValue(90)
                                    ->suffix('dias')
                                    ->helperText('Clientes que não cortam há mais que este número de dias entram na lista de chamada rápida no WhatsApp.')
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
