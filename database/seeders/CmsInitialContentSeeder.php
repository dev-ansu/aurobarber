<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class CmsInitialContentSeeder extends Seeder
{
    public function run(): void
    {
        Page::updateOrCreate(
            ['slug' => '/'],
            [
                'title' => 'AuroBarber • Sistema de Gestão, Agendamento Online & Fidelização',
                'slug' => '/',
                'meta_description' => 'Transforme sua barbearia com agendamento online em 30 segundos, comanda de bar e produtos, cálculo automático de comissões, visão restrita do barbeiro e cartão fidelidade.',
                'meta_keywords' => 'sistema para barbearia, agendamento online barbearia, controle de comissao barbeiro, software gestao barbearia, comanda barbearia, cartao fidelidade barbearia, aurobarber',
                'og_image' => null,
                'is_published' => true,
                'content' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'section_badge' => 'O Sistema Operacional Completo para Barbearias de Alto Padrão',
                            'title' => 'Transforme sua barbearia em uma máquina de agenda cheia, comanda lucrativa e gestão no piloto automático.',
                            'subtitle' => 'Link exclusivo White-Label para seu cliente agendar em 30 segundos sem baixar nada, comanda com estoque de bar e cosméticos, cartão fidelidade automático e visão restrita individual no celular de cada barbeiro.',
                            'primary_cta_text' => 'Quero Implantar na Minha Barbearia',
                            'secondary_cta_text' => 'Ver Demonstração Interativa',
                            'secondary_cta_url' => '#recursos',
                            'highlight_metrics' => [
                                ['value' => '30 seg', 'label' => 'Agendamento Online s/ Senha'],
                                ['value' => '0% Taxa', 'label' => '100% do Pix e Caixa é Seu'],
                                ['value' => 'App PWA', 'label' => 'Instalável no Android & iPhone'],
                                ['value' => 'Recibo PJ', 'label' => 'Comissão com Validade Jurídica'],
                            ],
                        ],
                    ],
                    [
                        'type' => 'features_showcase',
                        'data' => [
                            'section_badge' => 'Tecnologia Desenhada para a Bancada',
                            'title' => 'Conheça por dentro o sistema que organiza sua equipe e fideliza seus clientes',
                            'subtitle' => 'Navegue pelas abas e veja como o AuroBarber resolve desde o agendamento no Instagram até o fechamento das comissões no sábado à noite.',
                        ],
                    ],
                    [
                        'type' => 'modules_grid',
                        'data' => [
                            'section_badge' => 'Ecossistema Completo',
                            'title' => 'Tudo o que sua barbearia precisa em uma única assinatura',
                            'subtitle' => 'Sem cobrança extra por módulo e sem taxas escondidas sobre os seus cortes.',
                        ],
                    ],
                    [
                        'type' => 'comparison_table',
                        'data' => [
                            'section_badge' => 'A Evolução da Sua Barbearia',
                            'title' => 'Por que as barbearias mais lucrativas estão migrando para o AuroBarber?',
                            'subtitle' => 'Compare a rotina de quem ainda depende de caderno ou aplicativos engessados versus a liberdade do AuroBarber Cloud.',
                        ],
                    ],
                    [
                        'type' => 'roi_calculator',
                        'data' => [
                            'section_badge' => 'Simulador de Impacto Financeiro',
                            'title' => 'Quanto sua barbearia pode faturar a mais por ano?',
                            'subtitle' => 'Ajuste abaixo a média de atendimentos diários da sua barbearia e veja o ganho estimado com redução de faltas, retorno de clientes e comanda de produtos:',
                            'default_cuts_day' => 25,
                            'default_ticket' => 50,
                        ],
                    ],
                    [
                        'type' => 'pricing_plans',
                        'data' => [
                            'section_badge' => 'Investimento Acessível & Sem Pegadinhas',
                            'title' => 'Escolha o plano ideal para o momento da sua barbearia',
                            'subtitle' => 'Todos os planos incluem página pública de agendamento, atalho PWA, cartão fidelidade e suporte direto.',
                            'use_database_plans' => true,
                        ],
                    ],
                    [
                        'type' => 'partners_network',
                        'data' => [
                            'section_badge' => 'Rede de Barbearias Parceiras',
                            'title' => 'Barbearias de elite que confiam no AuroBarber',
                            'subtitle' => 'Conheça estabelecimentos que transformaram sua gestão e o atendimento aos seus clientes.',
                            'show_search' => true,
                        ],
                    ],
                    [
                        'type' => 'faq_accordion',
                        'data' => [
                            'section_badge' => 'Tire suas dúvidas',
                            'title' => 'Perguntas frequentes sobre o AuroBarber',
                            'subtitle' => 'Tudo o que você precisa saber antes de transformar a gestão da sua barbearia.',
                        ],
                    ],
                    [
                        'type' => 'cta_banner',
                        'data' => [
                            'title' => 'Pronto para profissionalizar a gestão da sua barbearia?',
                            'subtitle' => 'Junte-se às barbearias que abandonaram as planilhas e o caderno para ter agenda cheia e controle financeiro total.',
                            'button_text' => 'Começar Teste Grátis Agora',
                            'secondary_button_text' => 'Tirar Dúvidas no WhatsApp',
                            'secondary_button_url' => null,
                        ],
                    ],
                ],
            ]
        );
    }
}
