<?php

namespace App\Filament\Resources\Schools\Schemas;

use App\Actions\FillAddressFromZipCode;
use App\Models\School;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class SchoolForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação da escola')
                    ->schema([
                        TextInput::make('legal_name')
                            ->label('Razão social')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('trade_name')
                            ->label('Nome fantasia')
                            ->maxLength(255),
                        Toggle::make('is_headquarters')
                            ->label('Definir como Unidade Matriz')
                            ->helperText('Ao ativar esta opção, esta escola se tornará a Matriz oficial e todas as demais filiais deixarão de ser Matriz automaticamente. Ao fazer login, o sistema carregará os dados e painel desta unidade por padrão.')
                            ->default(false)
                            ->columnSpanFull(),
                        TextInput::make('cnpj')
                            ->label('CNPJ')
                            ->mask('99.999.999/9999-99')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(18),
                        TextInput::make('state_registration')
                            ->label('Inscrição estadual')
                            ->maxLength(255),
                        TextInput::make('municipal_registration')
                            ->label('Inscrição municipal')
                            ->maxLength(255),
                        FileUpload::make('logo_path')
                            ->label('Logotipo Oficial da Escola')
                            ->image()
                            ->disk('public')
                            ->directory('schools/logos')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->helperText('Padrão para documentos impressos: formato retangular horizontal (recomendado 400x120px a 600x200px) ou quadrado (300x300px), PNG (com fundo transparente) ou JPG, máx. 2MB. A imagem será impressa no cabeçalho de Contratos, Certificados, Carnês, Fichas de Matrícula e Declarações.')
                            ->columnSpanFull(),
                        ColorPicker::make('brand_color')
                            ->label('Cor Principal da Marca (Identidade Visual)')
                            ->helperText('Define a cor de destaque da escola na Vitrine Pública (botões "Garantir Bolsa", WhatsApp, badges, selos e links). Padrão: Âmbar Aurosoft (#F59E0B). Dicas para redes conhecidas: Vermelho (#E11D48 ou #DC2626), Azul Royal (#2563EB), Verde (#059669).')
                            ->default('#F59E0B')
                            ->hex()
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Contato & Redes Sociais')
                    ->schema([
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Telefone Fixo / Recepção')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('whatsapp')
                            ->label('WhatsApp da Secretaria / Comercial')
                            ->helperText('Usado para os botões de contato direto na vitrine e carnês.')
                            ->tel()
                            ->maxLength(30),
                        TextInput::make('website')
                            ->label('Site Externo (Opcional)')
                            ->url()
                            ->maxLength(255),
                        TextInput::make('instagram')
                            ->label('Instagram da Escola')
                            ->placeholder('Ex: @suaescola ou link completo')
                            ->maxLength(255),
                        TextInput::make('facebook')
                            ->label('Facebook da Escola')
                            ->placeholder('Ex: facebook.com/suaescola')
                            ->maxLength(255),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Endereço')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('postal_code')
                                    ->label('CEP')
                                    ->mask('99999-999')
                                    ->maxLength(9)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (?string $state, Set $set) {
                                        if (blank($state)) {
                                            return;
                                        }

                                        $address = app(FillAddressFromZipCode::class)->execute($state);
                                        if (! $address) {
                                            Notification::make()
                                                ->title('CEP não encontrado.')
                                                ->danger()
                                                ->send();

                                            return;
                                        }

                                        $set('street', $address['street'] ?? null);
                                        $set('neighborhood', $address['neighborhood'] ?? null);
                                        $set('city', $address['city'] ?? null);
                                        $set('state', $address['state'] ?? null);
                                        if (! empty($address['complement'])) {
                                            $set('complement', $address['complement']);
                                        }
                                    }),
                                TextInput::make('street')
                                    ->label('Logradouro')
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('number')
                                    ->label('Número')
                                    ->maxLength(30),
                                TextInput::make('complement')
                                    ->label('Complemento')
                                    ->maxLength(255),
                                TextInput::make('neighborhood')
                                    ->label('Bairro')
                                    ->maxLength(255),
                                TextInput::make('city')
                                    ->label('Município')
                                    ->maxLength(255),
                                Select::make('state')
                                    ->label('UF')
                                    ->options([
                                        'AC' => 'Acre',
                                        'AL' => 'Alagoas',
                                        'AP' => 'Amapá',
                                        'AM' => 'Amazonas',
                                        'BA' => 'Bahia',
                                        'CE' => 'Ceará',
                                        'DF' => 'Distrito Federal',
                                        'ES' => 'Espírito Santo',
                                        'GO' => 'Goiás',
                                        'MA' => 'Maranhão',
                                        'MT' => 'Mato Grosso',
                                        'MS' => 'Mato Grosso do Sul',
                                        'MG' => 'Minas Gerais',
                                        'PA' => 'Pará',
                                        'PB' => 'Paraíba',
                                        'PR' => 'Paraná',
                                        'PE' => 'Pernambuco',
                                        'PI' => 'Piauí',
                                        'RJ' => 'Rio de Janeiro',
                                        'RN' => 'Rio Grande do Norte',
                                        'RS' => 'Rio Grande do Sul',
                                        'RO' => 'Rondônia',
                                        'RR' => 'Roraima',
                                        'SC' => 'Santa Catarina',
                                        'SP' => 'São Paulo',
                                        'SE' => 'Sergipe',
                                        'TO' => 'Tocantins',
                                    ])
                                    ->searchable(),
                            ]),
                    ])
                    ->columnSpanFull(),
                Section::make('Vitrine Pública & Mini-site da Escola')
                    ->description('Personalize o portal público da escola exibido para visitantes e futuros alunos na internet.')
                    ->schema([
                        Toggle::make('showcase_enabled')
                            ->label('Ativar Vitrine Pública / Portal de Cursos da Escola')
                            ->helperText('Quando ativado, visitantes que acessarem o endereço da escola verão o mini-site com as formações, módulos e formulário de inscrição em vez da tela de login padrão.')
                            ->default(true)
                            ->columnSpanFull(),

                        TextInput::make('hero_headline')
                            ->label('Título de Destaque (Headline Principal)')
                            ->placeholder('Ex: Formações Profissionais com Alta Empregabilidade')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('hero_subheadline')
                            ->label('Subtítulo de Apresentação')
                            ->placeholder('Ex: Transforme seu futuro com cursos práticos em laboratórios modernos, professores experientes e certificado reconhecido.')
                            ->maxLength(500)
                            ->columnSpanFull(),

                        TextInput::make('custom_students_count_text')
                            ->label('Texto de Prova Social (Alunos Formados)')
                            ->placeholder('Ex: +1.500 alunos formados (Deixe em branco para calcular automaticamente do banco)')
                            ->helperText('Se deixado em branco, o sistema calcula automaticamente a quantidade de alunos matriculados nesta escola.')
                            ->maxLength(100),

                        Toggle::make('show_network_units')
                            ->label('Exibir todas as Unidades da Rede no rodapé da Vitrine')
                            ->helperText('Mostra os endereços e contatos de todas as escolas filiais/matriz da rede para facilitar a localização pelo aluno.')
                            ->default(true),

                        Toggle::make('show_satisfaction_rating')
                            ->label('⭐ Exibir Selo de Satisfação dos Alunos na Vitrine')
                            ->helperText('Exibe o índice de satisfação calculado a partir das avaliações das aulas (LMS) e pesquisas de satisfação dos alunos. Desative caso não queira exibir publicamente.')
                            ->default(false)
                            ->live(),

                        TextInput::make('custom_satisfaction_score')
                            ->label('Nota de Satisfação Personalizada (Opcional)')
                            ->placeholder('Ex: 4.9 ou 98% de satisfação (Deixe vazio para cálculo automático das notas reais)')
                            ->helperText('Se vazio, o sistema calcula a média real das avaliações dos alunos.')
                            ->maxLength(30)
                            ->visible(fn ($get) => (bool) $get('show_satisfaction_rating')),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Configurações do PIX (Carnês e Recebimentos)')
                    ->description('Defina a chave PIX e os dados do recebedor impressos nos carnês de pagamento.')
                    ->schema([
                        Toggle::make('inherit_pix_settings')
                            ->label('🌐 Utilizar Chave PIX centralizada da Matriz (Conta Única da Rede)')
                            ->helperText('Ao ativar, as cobranças desta unidade usarão a chave Pix e beneficiário configurados na escola Matriz. Desative se esta escola tiver conta bancária ou CNPJ filial com chave Pix própria.')
                            ->default(false)
                            ->live()
                            ->visible(fn ($get) => ! (bool) $get('is_headquarters'))
                            ->columnSpanFull(),

                        Placeholder::make('inherited_pix_info_school')
                            ->label('')
                            ->content(function () {
                                $hq = School::getHeadquarters();
                                $hqName = $hq?->trade_name ?: ($hq?->legal_name ?: 'Matriz');
                                $hqPixKey = $hq?->pix_key ?? 'Não cadastrada';
                                $hqPixBeneficiary = $hq?->pix_merchant_name ?: ($hq?->trade_name ?: 'Matriz');

                                return new HtmlString("
                                    <div class='flex items-center gap-3 p-3.5 rounded-xl bg-emerald-500/10 border border-emerald-500/25 text-emerald-900 dark:text-emerald-200 text-sm'>
                                        <span class='text-xl select-none'>🏦</span>
                                        <div>
                                            <strong>Conta Centralizada da Rede:</strong> O QR Code e o código Pix dos carnês e cobranças desta unidade apontam para a conta da Matriz (<strong>{$hqName}</strong>).<br>
                                            Chave PIX: <code>{$hqPixKey}</code> &middot; Beneficiário: <strong>{$hqPixBeneficiary}</strong>.
                                        </div>
                                    </div>
                                ");
                            })
                            ->visible(fn ($get) => ! (bool) $get('is_headquarters') && (bool) $get('inherit_pix_settings'))
                            ->columnSpanFull(),

                        Toggle::make('pix_enabled')
                            ->label('Habilitar PIX nos carnês e documentos')
                            ->helperText('Se desativado, o QR Code e os dados do Pix não serão impressos nos carnês.')
                            ->default(true)
                            ->visible(fn ($get) => (bool) $get('is_headquarters') || ! (bool) $get('inherit_pix_settings'))
                            ->columnSpanFull(),
                        Select::make('pix_key_type')
                            ->label('Tipo de chave')
                            ->options([
                                'cnpj' => 'CNPJ',
                                'cpf' => 'CPF',
                                'email' => 'E-mail',
                                'phone' => 'Celular (+55)',
                                'random' => 'Chave Aleatória (EVP)',
                            ])
                            ->placeholder('Selecione o tipo')
                            ->visible(fn ($get) => (bool) $get('is_headquarters') || ! (bool) $get('inherit_pix_settings')),
                        TextInput::make('pix_key')
                            ->label('Chave PIX')
                            ->maxLength(255)
                            ->helperText('Chave cadastrada no banco da escola (ex: CNPJ, e-mail, celular ou chave aleatória).')
                            ->visible(fn ($get) => (bool) $get('is_headquarters') || ! (bool) $get('inherit_pix_settings')),
                        TextInput::make('pix_merchant_name')
                            ->label('Nome do beneficiário no PIX')
                            ->maxLength(25)
                            ->helperText('Máximo de 25 caracteres (exigência do BACEN). Deixe em branco para usar o Nome Fantasia.')
                            ->visible(fn ($get) => (bool) $get('is_headquarters') || ! (bool) $get('inherit_pix_settings')),
                        TextInput::make('pix_merchant_city')
                            ->label('Cidade do beneficiário')
                            ->maxLength(15)
                            ->helperText('Máximo de 15 caracteres (exigência do BACEN). Deixe em branco para usar a Cidade do endereço.')
                            ->visible(fn ($get) => (bool) $get('is_headquarters') || ! (bool) $get('inherit_pix_settings')),
                        TextInput::make('pix_instructions')
                            ->label('Instruções adicionais impressas no carnê')
                            ->maxLength(255)
                            ->placeholder('Ex: Enviar comprovante para o WhatsApp da secretaria')
                            ->visible(fn ($get) => (bool) $get('is_headquarters') || ! (bool) $get('inherit_pix_settings'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make('Segurança da Rede, Totem e Laboratório Interativo')
                    ->description('Restrinja o registro de presença no Totem e o acesso às aulas práticas apenas aos dispositivos conectados na rede de internet autorizada da escola.')
                    ->schema([
                        Textarea::make('allowed_ips')
                            ->label('IPs Públicos / Redes Autorizadas da Escola')
                            ->placeholder("Ex:\n187.54.120.30\n189.28.15.0")
                            ->helperText('Informe o IP de saída da internet da escola (separados por vírgula ou linha). Seu IP atual detectado: ' . request()->ip())
                            ->rows(3)
                            ->columnSpanFull(),

                        Toggle::make('enforce_school_ip_for_lms')
                            ->label('🔒 Bloquear Acesso ao Laboratório Interativo Fora da Escola')
                            ->helperText('Impede que o aluno inicie as aulas práticas em casa pelo portal do aluno. A liberação só ocorre nos computadores conectados à rede autorizada da unidade.')
                            ->default(false),

                        Toggle::make('enforce_school_ip_for_attendance')
                            ->label('📍 Bloquear Registro de Presença no Totem Fora da Escola')
                            ->helperText('Impede que alunos burlem a chamada abrindo o link do Totem (/presenca) de computadores ou celulares fora da unidade escolar.')
                            ->default(false),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
