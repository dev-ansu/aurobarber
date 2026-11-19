<?php

declare(strict_types=1);

namespace App\Filament\Saas\Resources\CmsPages;

use App\Filament\Saas\Resources\CmsPages\Pages\CreatePage;
use App\Filament\Saas\Resources\CmsPages\Pages\EditPage;
use App\Filament\Saas\Resources\CmsPages\Pages\ListPage;
use App\Models\Page;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static bool $isScopedToTenant = false;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Páginas do Site (CMS)';

    protected static ?string $modelLabel = 'Página';

    protected static ?string $pluralModelLabel = 'Páginas do Site';

    protected static string|UnitEnum|null $navigationGroup = 'Site e Conteúdo';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user() instanceof User
            && auth()->user()->role === 'master_admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Configuração da Página')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Metadados & SEO')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->schema([
                            Section::make('Informações Principais')
                                ->description('Título da página, endereço amigável (slug) e status de publicação.')
                                ->schema([
                                    TextInput::make('title')
                                        ->label('Título da Página')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpan(2),
                                    TextInput::make('slug')
                                        ->label('Slug / URL')
                                        ->helperText('Informe "/" para a Página Inicial (Home), ou slugs como "sobre", "termos", "funcionalidades".')
                                        ->required()
                                        ->maxLength(120)
                                        ->unique(ignoreRecord: true)
                                        ->columnSpan(1),
                                    Toggle::make('is_published')
                                        ->label('Publicado')
                                        ->helperText('Se desmarcado, a página não será acessível publicamente.')
                                        ->default(true)
                                        ->inline(false)
                                        ->columnSpan(1),
                                ])
                                ->columns(4),

                            Section::make('Otimização para Buscas (SEO & OpenGraph)')
                                ->description('Configurações para Google, WhatsApp e redes sociais.')
                                ->schema([
                                    Textarea::make('meta_description')
                                        ->label('Meta Description')
                                        ->rows(2)
                                        ->maxLength(300)
                                        ->columnSpanFull(),
                                    TextInput::make('meta_keywords')
                                        ->label('Meta Keywords')
                                        ->placeholder('sistema barbearia, agendamento online, comissão barbeiro, aurobarber')
                                        ->columnSpan(2),
                                    FileUpload::make('og_image')
                                        ->label('Imagem OpenGraph (Preview Social)')
                                        ->image()
                                        ->disk('public')
                                        ->directory('cms/og-images')
                                        ->visibility('public')
                                        ->imageEditor()
                                        ->openable()
                                        ->downloadable()
                                        ->helperText('Recomendado: 1200x630px. Exibida ao compartilhar o link no WhatsApp, Instagram, Facebook e Google.')
                                        ->columnSpan(2),
                                ])
                                ->columns(4),
                        ]),

                    Tab::make('Construtor Visual de Blocos')
                        ->icon('heroicon-o-squares-plus')
                        ->schema([
                            Builder::make('content')
                                ->label('Seções e Blocos da Página')
                                ->columnSpanFull()
                                ->collapsed()
                                ->cloneable()
                                ->blocks([
                                    static::getHeroBlock(),
                                    static::getFeaturesShowcaseBlock(),
                                    static::getModulesGridBlock(),
                                    static::getComparisonTableBlock(),
                                    static::getRoiCalculatorBlock(),
                                    static::getPricingPlansBlock(),
                                    static::getPartnersNetworkBlock(),
                                    static::getFaqAccordionBlock(),
                                    static::getCtaBannerBlock(),
                                    static::getRichTextBlock(),
                                ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->label('URL (Slug)')
                    ->badge()
                    ->color('warning')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state === '/' ? '/ (Home)' : '/'.ltrim((string) $state, '/')),
                IconColumn::make('is_published')
                    ->label('Publicado')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Última Atualização')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPage::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }

    protected static function getHeroBlock(): Block
    {
        return Block::make('hero')
            ->label('Hero Principal (Destaque do Topo)')
            ->icon('heroicon-o-sparkles')
            ->schema([
                TextInput::make('section_badge')
                    ->label('Selo Superior (Badge)')
                    ->default('TECNOLOGIA PARA BARBEARIAS DE ALTA PERFORMANCE')
                    ->columnSpan(1),
                TextInput::make('title')
                    ->label('Título Principal (Suporta quebras de linha)')
                    ->default('Sua barbearia lotada, sua equipe motivada e seu lucro no bolso.')
                    ->required()
                    ->columnSpan(2),
                Textarea::make('subtitle')
                    ->label('Subtítulo')
                    ->rows(2)
                    ->default('Agendamento online sem atrito em 30 segundos, comanda de bar integrada, recibo jurídico de comissões e cartão fidelidade que faz seu cliente voltar.')
                    ->columnSpanFull(),
                TextInput::make('primary_cta_text')
                    ->label('Texto do Botão Principal')
                    ->default('Experimentar Grátis por 7 Dias')
                    ->columnSpan(1),
                TextInput::make('secondary_cta_text')
                    ->label('Texto do Botão Secundário')
                    ->default('Falar com Especialista no WhatsApp')
                    ->columnSpan(1),
                TextInput::make('secondary_cta_url')
                    ->label('URL do Botão Secundário (Opcional)')
                    ->placeholder('https://wa.me/...')
                    ->columnSpan(1),
                FileUpload::make('preview_image')
                    ->label('Mockup / Screenshot da Plataforma')
                    ->image()
                    ->disk('public')
                    ->directory('cms/hero')
                    ->visibility('public')
                    ->imageEditor()
                    ->openable()
                    ->downloadable()
                    ->columnSpanFull(),
                Repeater::make('highlight_metrics')
                    ->label('Métricas em Destaque no Hero')
                    ->schema([
                        TextInput::make('value')->label('Número / Valor')->required()->columnSpan(1),
                        TextInput::make('label')->label('Rótulo / Descrição')->required()->columnSpan(2),
                    ])
                    ->columns(3)
                    ->default([
                        ['value' => '30s', 'label' => 'Tempo médio para agendamento online'],
                        ['value' => '0%', 'label' => 'Taxa por agendamento ou por barbeiro'],
                        ['value' => '100%', 'label' => 'Controle de bar, estoque e comissões'],
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    protected static function getFeaturesShowcaseBlock(): Block
    {
        return Block::make('features_showcase')
            ->label('Vitrine de Funcionalidades em Destaque')
            ->icon('heroicon-o-presentation-chart-bar')
            ->schema([
                TextInput::make('section_badge')->label('Selo')->default('Diferenciais AuroBarber')->columnSpan(1),
                TextInput::make('title')->label('Título da Seção')->default('Tudo o que sua barbearia precisa em um único lugar')->columnSpan(2),
                Textarea::make('subtitle')->label('Subtítulo')->rows(2)->columnSpanFull(),
                Repeater::make('features')
                    ->label('Funcionalidades em Destaque')
                    ->schema([
                        TextInput::make('badge')->label('Etiqueta')->columnSpan(1),
                        TextInput::make('title')->label('Título')->required()->columnSpan(2),
                        Textarea::make('description')->label('Descrição')->rows(2)->required()->columnSpanFull(),
                        TagsInput::make('benefits')->label('Pontos Fortes (Checkmarks)')->columnSpanFull(),
                        FileUpload::make('image')
                            ->label('Imagem / Print')
                            ->image()
                            ->disk('public')
                            ->directory('cms/features')
                            ->visibility('public')
                            ->imageEditor()
                            ->openable()
                            ->downloadable()
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    protected static function getModulesGridBlock(): Block
    {
        return Block::make('modules_grid')
            ->label('Grade de Módulos & Recursos')
            ->icon('heroicon-o-view-columns')
            ->schema([
                TextInput::make('section_badge')->label('Selo')->default('Funcionalidades Completas')->columnSpan(1),
                TextInput::make('title')->label('Título')->default('Ferramentas sob medida para o dono e sua equipe')->columnSpan(2),
                Textarea::make('subtitle')->label('Subtítulo')->rows(2)->columnSpanFull(),
                Repeater::make('modules')
                    ->label('Módulos do Sistema')
                    ->schema([
                        TextInput::make('icon')->label('Ícone (Heroicon ou Emoji)')->default('scissors')->columnSpan(1),
                        TextInput::make('title')->label('Nome do Módulo')->required()->columnSpan(1),
                        TextInput::make('tag')->label('Tag / Categoria')->columnSpan(1),
                        Textarea::make('description')->label('Descrição Curta')->rows(2)->required()->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    protected static function getComparisonTableBlock(): Block
    {
        return Block::make('comparison_table')
            ->label('Tabela Comparativa (AuroBarber vs Tradicional)')
            ->icon('heroicon-o-arrows-right-left')
            ->schema([
                TextInput::make('section_badge')->label('Selo')->default('Comparativo Direto')->columnSpan(1),
                TextInput::make('title')->label('Título')->default('Por que trocar o caderno ou sistemas genéricos pelo AuroBarber?')->columnSpan(2),
                Textarea::make('subtitle')->label('Subtítulo')->rows(2)->columnSpanFull(),
                Repeater::make('items')
                    ->label('Itens Comparativos')
                    ->schema([
                        TextInput::make('feature')->label('Recurso / Critério')->required()->columnSpan(1),
                        TextInput::make('traditional_value')->label('Caderno ou Outros')->required()->columnSpan(1),
                        TextInput::make('aurobarber_value')->label('Como o AuroBarber Faz')->required()->columnSpan(1),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    protected static function getRoiCalculatorBlock(): Block
    {
        return Block::make('roi_calculator')
            ->label('Calculadora de Economia / ROI')
            ->icon('heroicon-o-calculator')
            ->schema([
                TextInput::make('section_badge')->label('Selo')->default('Simulador de Lucro')->columnSpan(1),
                TextInput::make('title')->label('Título')->default('Veja quanto sua barbearia deixa de perder todo mês')->columnSpan(2),
                Textarea::make('subtitle')->label('Subtítulo')->rows(2)->columnSpanFull(),
                TextInput::make('default_barbers')->label('Número de Barbeiros Padrão')->numeric()->default(3)->columnSpan(1),
                TextInput::make('default_cuts_day')->label('Atendimentos/Dia por Barbeiro')->numeric()->default(10)->columnSpan(1),
                TextInput::make('default_ticket')->label('Ticket Médio (R$)')->numeric()->default(45)->columnSpan(1),
            ])
            ->columns(3);
    }

    protected static function getPricingPlansBlock(): Block
    {
        return Block::make('pricing_plans')
            ->label('Tabela de Planos e Preços')
            ->icon('heroicon-o-banknotes')
            ->schema([
                TextInput::make('section_badge')->label('Selo')->default('Investimento Acessível & Sem Pegadinhas')->columnSpan(1),
                TextInput::make('title')->label('Título')->default('Escolha o plano ideal para o momento da sua barbearia')->columnSpan(2),
                Textarea::make('subtitle')->label('Subtítulo')->default('Todos os planos incluem página pública de agendamento, atalho PWA, cartão fidelidade e suporte direto.')->rows(2)->columnSpanFull(),
                Toggle::make('use_database_plans')
                    ->label('Carregar planos automaticamente do banco (Tabela Saas Plans)')
                    ->helperText('Busca em tempo real os planos ativos, valores, ciclos e recursos cadastrados no painel SaaS.')
                    ->default(true)
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    protected static function getPartnersNetworkBlock(): Block
    {
        return Block::make('partners_network')
            ->label('Rede de Barbearias Ativas (Vitrine)')
            ->icon('heroicon-o-building-storefront')
            ->schema([
                TextInput::make('section_badge')->label('Selo')->default('Quem Já Usa')->columnSpan(1),
                TextInput::make('title')->label('Título')->default('Barbearias que confiam no AuroBarber')->columnSpan(2),
                Textarea::make('subtitle')->label('Subtítulo')->rows(2)->columnSpanFull(),
                Toggle::make('show_search')->label('Exibir busca por barbearia ou cidade')->default(true),
            ])
            ->columns(3);
    }

    protected static function getFaqAccordionBlock(): Block
    {
        return Block::make('faq_accordion')
            ->label('Perguntas Frequentes (FAQ)')
            ->icon('heroicon-o-question-mark-circle')
            ->schema([
                TextInput::make('section_badge')->label('Selo')->default('Tire suas dúvidas')->columnSpan(1),
                TextInput::make('title')->label('Título')->default('Perguntas frequentes sobre o AuroBarber')->columnSpan(2),
                Textarea::make('subtitle')->label('Subtítulo')->rows(2)->columnSpanFull(),
                Repeater::make('faqs')
                    ->label('Perguntas e Respostas')
                    ->schema([
                        TextInput::make('question')->label('Pergunta')->required()->columnSpanFull(),
                        Textarea::make('answer')->label('Resposta')->rows(3)->required()->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(3);
    }

    protected static function getCtaBannerBlock(): Block
    {
        return Block::make('cta_banner')
            ->label('Banner Final de Conversão (CTA)')
            ->icon('heroicon-o-megaphone')
            ->schema([
                TextInput::make('title')->label('Título')->default('Pronto para profissionalizar a gestão da sua barbearia?')->columnSpan(2),
                Textarea::make('subtitle')->label('Subtítulo')->rows(2)->columnSpanFull(),
                TextInput::make('button_text')->label('Texto do Botão Principal')->default('Começar Teste Grátis Agora')->columnSpan(1),
                TextInput::make('secondary_button_text')->label('Texto do Botão Secundário (WhatsApp)')->default('Tirar Dúvidas no WhatsApp')->columnSpan(1),
                TextInput::make('secondary_button_url')->label('Link do WhatsApp (Opcional)')->nullable()->columnSpan(1),
            ])
            ->columns(2);
    }

    protected static function getRichTextBlock(): Block
    {
        return Block::make('rich_text')
            ->label('Conteúdo Livre (Rich Text)')
            ->icon('heroicon-o-document-text')
            ->schema([
                TextInput::make('title')->label('Título da Seção (Opcional)')->columnSpan(2),
                Select::make('container_width')->label('Largura')->options([
                    'narrow' => 'Estreita (Leitura / Termos)',
                    'normal' => 'Normal',
                    'full' => 'Largura Total',
                ])->default('normal')->columnSpan(1),
                RichEditor::make('content')->label('Conteúdo')->required()->columnSpanFull(),
            ])
            ->columns(3);
    }
}
