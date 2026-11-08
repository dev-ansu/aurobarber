<?php

namespace App\Filament\Pages;

use App\Models\School;
use App\Models\User;
use App\Services\PixService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use UnitEnum;

class PixSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|UnitEnum|null $navigationGroup = 'Configurações Financeiras';

    protected static ?string $navigationLabel = 'Configurações Financeiras';

    protected static ?string $title = 'Parâmetros Financeiros, Juros, Descontos e PIX';

    protected static ?string $slug = 'configuracao-pix';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.pages.pix-settings';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('View:FinancialSettings')
            || $user->can('View:PixSettings');
    }

    public function mount(): void
    {
        $school = \Filament\Facades\Filament::getTenant() ?? School::getActiveSchool() ?? School::query()->first();
        $isHq = (bool) ($school?->is_headquarters ?? false);
        $headquarters = School::getHeadquarters();

        $this->form->fill([
            // Configurações Globais / Herança da Matriz
            'inherit_financial_policy' => (bool) ($school?->inherit_financial_policy ?? ! $isHq),
            'inherit_pix_settings' => (bool) ($school?->inherit_pix_settings ?? false),

            // Encargos por atraso
            'late_fine_percentage' => (float) ($school?->late_fine_percentage ?? ($headquarters?->late_fine_percentage ?? 2.00)),
            'daily_interest_percentage' => (float) ($school?->daily_interest_percentage ?? ($headquarters?->daily_interest_percentage ?? 0.0333)),
            'grace_days' => (int) ($school?->grace_days ?? ($headquarters?->grace_days ?? 0)),

            // Desconto de pontualidade
            'punctuality_discount_enabled' => (bool) ($school?->punctuality_discount_enabled ?? false),
            'punctuality_discount_type' => $school?->punctuality_discount_type ?? 'fixed',
            'punctuality_discount_value' => (float) ($school?->punctuality_discount_value ?? 0.00),
            'punctuality_discount_days_tolerance' => (int) ($school?->punctuality_discount_days_tolerance ?? 0),

            // PIX
            'pix_enabled' => (bool) ($school?->pix_enabled ?? true),
            'pix_key_type' => $school?->pix_key_type ?? 'cnpj',
            'pix_key' => $school?->pix_key ?? '',
            'pix_merchant_name' => $school?->pix_merchant_name ?? ($school?->trade_name ?: ($school?->legal_name ?: '')),
            'pix_merchant_city' => $school?->pix_merchant_city ?? ($school?->city ?: ''),
            'pix_instructions' => $school?->pix_instructions ?? 'Pagamento via PIX: aponte a câmera do seu aplicativo bancário para o QR Code.',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $currentSchool = \Filament\Facades\Filament::getTenant() ?? School::getActiveSchool() ?? School::query()->first();
        $isHq = (bool) ($currentSchool?->is_headquarters ?? false);
        $hq = School::getHeadquarters();
        $hqName = $hq?->trade_name ?: ($hq?->legal_name ?: 'Matriz');
        $hqFine = number_format((float) ($hq?->late_fine_percentage ?? 2.00), 2, ',', '.');
        $hqInterest = number_format((float) ($hq?->daily_interest_percentage ?? 0.0333), 4, ',', '.');
        $hqGrace = (int) ($hq?->grace_days ?? 0);
        $hqPixKey = $hq?->pix_key ?? 'Não cadastrada';
        $hqPixBeneficiary = $hq?->pix_merchant_name ?: ($hq?->trade_name ?: 'Matriz');

        return $schema
            ->components([
                Section::make('Política de Encargos por Atraso (Juros e Multa)')
                    ->description('Defina a política automática para cobrança e recebimento de mensalidades vencidas.')
                    ->icon('heroicon-m-receipt-percent')
                    ->schema([
                        Toggle::make('inherit_financial_policy')
                            ->label("🌐 Herdar política financeira da Matriz ({$hqName})")
                            ->helperText("Quando ativo, esta escola adotará automaticamente as taxas globais da rede (Multa: {$hqFine}%, Juros: {$hqInterest}%/dia, Carência: {$hqGrace} dias). Desative para definir regras personalizadas para esta unidade.")
                            ->default(true)
                            ->live()
                            ->visible(! $isHq)
                            ->columnSpanFull(),

                        Placeholder::make('inherited_financial_info')
                            ->label('')
                            ->content(new HtmlString("
                                <div class='flex items-center gap-3 p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/25 text-amber-900 dark:text-amber-200 text-sm'>
                                    <span class='text-xl select-none'>🌐</span>
                                    <div>
                                        <strong>Configuração Global da Rede Ativa:</strong> Esta unidade está sincronizada com a Matriz (<strong>{$hqName}</strong>).<br>
                                        Taxas aplicadas automaticamente: Multa de <strong>{$hqFine}%</strong>, Juros de <strong>{$hqInterest}%/dia</strong> e <strong>{$hqGrace} dia(s)</strong> de carência.
                                    </div>
                                </div>
                            "))
                            ->visible(fn ($get) => ! $isHq && (bool) $get('inherit_financial_policy'))
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('late_fine_percentage')
                                    ->label('Multa Moratória (%)')
                                    ->helperText('Aplicada uma única vez quando o pagamento ocorrer após o vencimento (Padrão legal CDC: máx 2%).')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(2.00),

                                TextInput::make('daily_interest_percentage')
                                    ->label('Juros de Mora Diários (%)')
                                    ->helperText('Cobrado proporcionalmente por dia corrido de atraso (0,0333% ao dia = ~1% ao mês).')
                                    ->numeric()
                                    ->step(0.0001)
                                    ->suffix('% / dia')
                                    ->default(0.0333),

                                TextInput::make('grace_days')
                                    ->label('Dias de Tolerância / Carência')
                                    ->helperText('Número de dias após o vencimento sem cobrança de juros/multa (0 = cobra logo no dia seguinte).')
                                    ->numeric()
                                    ->integer()
                                    ->suffix('dia(s)')
                                    ->default(0),
                            ])
                            ->visible(fn ($get) => $isHq || ! (bool) $get('inherit_financial_policy')),
                    ]),

                Section::make('Política de Desconto de Pontualidade')
                    ->description('Conceda um benefício financeiro aos alunos que realizarem o pagamento até o vencimento acordado.')
                    ->icon('heroicon-m-sparkles')
                    ->schema([
                        Toggle::make('punctuality_discount_enabled')
                            ->label('Habilitar Desconto de Pontualidade na Unidade')
                            ->helperText('Quando ativo, o sistema sugerirá o abatimento de pontualidade nas baixas realizadas dentro do prazo.')
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),

                        Grid::make(3)
                            ->schema([
                                Select::make('punctuality_discount_type')
                                    ->label('Tipo do Desconto')
                                    ->options([
                                        'fixed' => 'Valor Fixo em Reais (R$)',
                                        'percentage' => 'Percentual da Parcela (%)',
                                    ])
                                    ->default('fixed')
                                    ->visible(fn ($get) => (bool) $get('punctuality_discount_enabled')),

                                TextInput::make('punctuality_discount_value')
                                    ->label('Valor / Percentual do Desconto')
                                    ->numeric()
                                    ->prefix(fn ($get) => $get('punctuality_discount_type') === 'percentage' ? null : 'R$')
                                    ->suffix(fn ($get) => $get('punctuality_discount_type') === 'percentage' ? '%' : null)
                                    ->default(0.00)
                                    ->visible(fn ($get) => (bool) $get('punctuality_discount_enabled')),

                                TextInput::make('punctuality_discount_days_tolerance')
                                    ->label('Tolerância de Dias')
                                    ->helperText('Dias além do vencimento em que o desconto ainda é concedido (0 = estritamente até o vencimento).')
                                    ->numeric()
                                    ->integer()
                                    ->suffix('dia(s)')
                                    ->default(0)
                                    ->visible(fn ($get) => (bool) $get('punctuality_discount_enabled')),
                            ]),
                    ])
                    ->visible(fn ($get) => $isHq || ! (bool) $get('inherit_financial_policy')),

                Section::make('Chave e Dados do Recebedor PIX')
                    ->description('Estes dados serão utilizados para gerar o QR Code dinâmico e o código Pix Copia e Cola em cada lâmina dos carnês e nas cobranças de WhatsApp.')
                    ->icon('heroicon-m-qr-code')
                    ->schema([
                        Toggle::make('inherit_pix_settings')
                            ->label("🌐 Utilizar Chave PIX centralizada da Matriz ({$hqName})")
                            ->helperText("Ative se todos os recebimentos via Pix desta escola caírem diretamente na conta bancária principal da Matriz. Desative se esta unidade possuir CNPJ filial ou conta bancária própria.")
                            ->default(false)
                            ->live()
                            ->visible(! $isHq)
                            ->columnSpanFull(),

                        Placeholder::make('inherited_pix_info')
                            ->label('')
                            ->content(new HtmlString("
                                <div class='flex items-center gap-3 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-emerald-900 dark:text-emerald-200 text-sm'>
                                    <span class='text-xl select-none'>🏦</span>
                                    <div>
                                        <strong>Conta Centralizada da Rede:</strong> O QR Code e o código Pix dos carnês e cobranças desta unidade são gerados apontando para a conta da Matriz (<strong>{$hqName}</strong>).<br>
                                        Chave PIX: <code>{$hqPixKey}</code> &middot; Beneficiário: <strong>{$hqPixBeneficiary}</strong>.
                                    </div>
                                </div>
                            "))
                            ->visible(fn ($get) => ! $isHq && (bool) $get('inherit_pix_settings'))
                            ->columnSpanFull(),

                        Toggle::make('pix_enabled')
                            ->label('Habilitar PIX nos Carnês e Cobranças')
                            ->helperText('Quando desativado, nenhum QR Code ou código Pix será gerado para impressão ou mensagens.')
                            ->default(true)
                            ->live()
                            ->visible(fn ($get) => $isHq || ! (bool) $get('inherit_pix_settings'))
                            ->columnSpanFull(),

                        Select::make('pix_key_type')
                            ->label('Tipo de chave')
                            ->options([
                                'cnpj' => 'CNPJ',
                                'cpf' => 'CPF',
                                'email' => 'E-mail',
                                'phone' => 'Celular (+55 com DDD)',
                                'random' => 'Chave Aleatória (EVP)',
                            ])
                            ->required()
                            ->visible(fn ($get) => ($isHq || ! (bool) $get('inherit_pix_settings')) && (bool) ($get('pix_enabled') ?? true))
                            ->live(),

                        TextInput::make('pix_key')
                            ->label('Chave PIX')
                            ->placeholder('Ex: 12.345.678/0001-90, financeiro@suaescola.com, etc.')
                            ->maxLength(255)
                            ->helperText('Informe a chave PIX onde a sua escola receberá os pagamentos dos alunos.')
                            ->visible(fn ($get) => ($isHq || ! (bool) $get('inherit_pix_settings')) && (bool) ($get('pix_enabled') ?? true))
                            ->live(debounce: 500),

                        TextInput::make('pix_merchant_name')
                            ->label('Nome do beneficiário / recebedor')
                            ->placeholder('Ex: AUROSOFT CURSOS')
                            ->maxLength(25)
                            ->helperText('Máximo de 25 caracteres (exigência normativa do Banco Central do Brasil). Caracteres especiais e acentos serão automaticamente convertidos.')
                            ->visible(fn ($get) => ($isHq || ! (bool) $get('inherit_pix_settings')) && (bool) ($get('pix_enabled') ?? true))
                            ->live(debounce: 500),

                        TextInput::make('pix_merchant_city')
                            ->label('Cidade do beneficiário')
                            ->placeholder('Ex: SAO PAULO')
                            ->maxLength(15)
                            ->helperText('Máximo de 15 caracteres (exigência normativa do Banco Central do Brasil).')
                            ->visible(fn ($get) => ($isHq || ! (bool) $get('inherit_pix_settings')) && (bool) ($get('pix_enabled') ?? true))
                            ->live(debounce: 500),

                        TextInput::make('pix_instructions')
                            ->label('Instruções adicionais impressas no carnê')
                            ->placeholder('Ex: Pagável via PIX. Envie o comprovante para o WhatsApp da secretaria.')
                            ->maxLength(255)
                            ->visible(fn ($get) => ($isHq || ! (bool) $get('inherit_pix_settings')) && (bool) ($get('pix_enabled') ?? true))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $user = Auth::user();
        if (! $user instanceof User || (! $user->hasRole('super_admin') && ! $user->can('Update:FinancialSettings'))) {
            Notification::make()
                ->title('Acesso Negado')
                ->body('Você não tem permissão para alterar as configurações financeiras.')
                ->danger()
                ->send();

            return;
        }

        $data = $this->form->getState();

        $school = \Filament\Facades\Filament::getTenant() ?? School::query()->first();

        if (! $school) {
            $school = School::query()->create([
                'legal_name' => $data['pix_merchant_name'] ?: 'Aurosoft Escola',
                'trade_name' => $data['pix_merchant_name'] ?: 'Aurosoft Escola',
                'cnpj' => '00.000.000/0001-00',
            ]);
        }

        $isPixEnabled = (bool) ($data['pix_enabled'] ?? true);

        $updateData = [
            'inherit_financial_policy' => (bool) ($data['inherit_financial_policy'] ?? false),
            'inherit_pix_settings' => (bool) ($data['inherit_pix_settings'] ?? false),

            // Juros e Multa
            'late_fine_percentage' => (float) ($data['late_fine_percentage'] ?? 2.00),
            'daily_interest_percentage' => (float) ($data['daily_interest_percentage'] ?? 0.0333),
            'grace_days' => (int) ($data['grace_days'] ?? 0),

            // Desconto Pontualidade
            'punctuality_discount_enabled' => (bool) ($data['punctuality_discount_enabled'] ?? false),
            'punctuality_discount_type' => $data['punctuality_discount_type'] ?? 'fixed',
            'punctuality_discount_value' => (float) ($data['punctuality_discount_value'] ?? 0.00),
            'punctuality_discount_days_tolerance' => (int) ($data['punctuality_discount_days_tolerance'] ?? 0),

            // PIX
            'pix_key_type' => $data['pix_key_type'] ?? null,
            'pix_key' => ! empty($data['pix_key']) ? trim($data['pix_key']) : null,
            'pix_merchant_name' => ! empty($data['pix_merchant_name']) ? trim($data['pix_merchant_name']) : null,
            'pix_merchant_city' => ! empty($data['pix_merchant_city']) ? trim($data['pix_merchant_city']) : null,
            'pix_instructions' => ! empty($data['pix_instructions']) ? trim($data['pix_instructions']) : null,
            'pix_enabled' => $isPixEnabled,
        ];

        $school->update($updateData);

        Notification::make()
            ->title('Configurações do PIX salvas')
            ->body('As políticas de juros, multas, desconto de pontualidade e PIX foram atualizadas com sucesso.')
            ->success()
            ->send();
    }

    /**
     * Gera a prévia do QR Code e Pix Copia e Cola para exibição visual imediata ao usuário.
     *
     * @return array<string, mixed>|null
     */
    public function getPixPreviewProperty(): ?array
    {
        $school = \Filament\Facades\Filament::getTenant() ?? School::getActiveSchool() ?? School::query()->first();
        $isHq = (bool) ($school?->is_headquarters ?? false);
        $inheritPix = ! $isHq && (bool) ($this->data['inherit_pix_settings'] ?? false);

        if ($inheritPix) {
            $pixConfig = School::getHeadquarters()?->resolvePixSettings() ?? [];
        } else {
            $pixConfig = [
                'pix_enabled' => (bool) ($this->data['pix_enabled'] ?? true),
                'pix_key' => $this->data['pix_key'] ?? '',
                'pix_merchant_name' => $this->data['pix_merchant_name'] ?? 'ESCOLA',
                'pix_merchant_city' => $this->data['pix_merchant_city'] ?? 'BRASIL',
                'pix_instructions' => $this->data['pix_instructions'] ?? null,
            ];
        }

        if (! ($pixConfig['pix_enabled'] ?? true)) {
            return null;
        }

        $pixKey = trim((string) ($pixConfig['pix_key'] ?? ''));

        if ($pixKey === '') {
            return null;
        }

        $merchantName = trim((string) ($pixConfig['pix_merchant_name'] ?: 'ESCOLA'));
        $merchantCity = trim((string) ($pixConfig['pix_merchant_city'] ?: 'BRASIL'));

        $service = app(PixService::class);

        try {
            $payload = $service->generatePayload(
                pixKey: $pixKey,
                merchantName: $merchantName,
                merchantCity: $merchantCity,
                amount: 150.00,
                txid: 'CTR001P01',
                description: 'Exemplo Parcela 01',
            );

            $qrUri = $service->generateQrCodeDataUri($payload);

            return [
                'pix_key' => $pixKey,
                'merchant_name' => mb_strtoupper($merchantName),
                'merchant_city' => mb_strtoupper($merchantCity),
                'amount' => 150.00,
                'txid' => 'CTR001P01',
                'instructions' => $pixConfig['instructions'] ?? null,
                'copy_paste' => $payload,
                'qr_code_uri' => $qrUri,
                'is_inherited' => $inheritPix,
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
