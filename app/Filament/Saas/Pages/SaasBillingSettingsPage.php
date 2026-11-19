<?php

declare(strict_types=1);

namespace App\Filament\Saas\Pages;

use App\Models\SaasBillingSetting;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use UnitEnum;

class SaasBillingSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'Pix da licença';

    protected static ?string $title = 'Cobrança das licenças SaaS';

    protected static string|UnitEnum|null $navigationGroup = 'Cobrança de Licenças';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.saas.pages.billing-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->role === 'master_admin';
    }

    public function mount(): void
    {
        $settings = SaasBillingSetting::query()->find(1);

        $this->form->fill($settings?->only([
            'pix_enabled',
            'pix_key',
            'merchant_name',
            'merchant_city',
            'support_whatsapp',
            'payment_instructions',
        ]) ?? [
            'pix_enabled' => false,
            'merchant_name' => 'AUROSOFT',
            'merchant_city' => 'FORTALEZA',
            'payment_instructions' => 'Após pagar, envie o comprovante nesta página. A equipe Aurosoft validará o pagamento e renovará a licença da barbearia.',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Configuração de cobrança')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Pix e recebedor')
                            ->icon('heroicon-m-qr-code')
                            ->schema([
                                Section::make('Dados para receber as mensalidades SaaS')
                                    ->description('O QR Code Pix é gerado automaticamente para o valor mensal de cada barbearia. O cliente também poderá copiar a chave Pix e enviar o comprovante.')
                                    ->schema([
                                        Toggle::make('pix_enabled')
                                            ->label('Aceitar pagamento de licença por Pix')
                                            ->default(false)
                                            ->columnSpanFull(),
                                        TextInput::make('pix_key')
                                            ->label('Chave Pix')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        TextInput::make('merchant_name')
                                            ->label('Nome do recebedor')
                                            ->required()
                                            ->maxLength(25)
                                            ->columnSpanFull(),
                                        TextInput::make('merchant_city')
                                            ->label('Cidade do recebedor')
                                            ->required()
                                            ->maxLength(15)
                                            ->columnSpanFull(),
                                        TextInput::make('support_whatsapp')
                                            ->label('WhatsApp para suporte de cobrança')
                                            ->tel()
                                            ->maxLength(30)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Instruções')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Section::make('Orientações ao cliente')
                                    ->schema([
                                        Textarea::make('payment_instructions')
                                            ->label('Instruções exibidas na página Planos de licenciamento')
                                            ->rows(5)
                                            ->maxLength(2000)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $data = $this->form->getState();
        $settings = SaasBillingSetting::query()->find(1) ?? new SaasBillingSetting(['id' => 1]);
        $settings->fill($data)->save();

        Notification::make()
            ->title('Configuração de Pix salva com sucesso!')
            ->success()
            ->send();
    }
}
