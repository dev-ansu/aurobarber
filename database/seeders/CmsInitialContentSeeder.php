<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CmsInitialContentSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql'
            && DB::selectOne('SELECT current_schema() AS schema')->schema !== 'public') {
            return;
        }

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
                            'features' => [
                                [
                                    'badge' => '⚡ Agendamento Online',
                                    'title' => 'Link White-Label no Instagram e App PWA',
                                    'description' => 'O cliente escolhe o profissional, serviço e horário sem baixar apps pesados da loja.',
                                    'benefits' => [
                                        'Sem necessidade de senha ou cadastro longo',
                                        'Confirmação instantânea sem conflito de horários',
                                        'Sua logomarca e suas cores personalizadas',
                                    ],
                                ],
                                [
                                    'badge' => '⚖️ Lei Salão Parceiro',
                                    'title' => 'Comissões Automáticas & Recibo Jurídico',
                                    'description' => 'Fechamento semanal da folha em 1 clique com desconto de vales e recibo detalhado.',
                                    'benefits' => [
                                        'Porcentagem individual por profissional',
                                        'Abatimento automático de taxas e adiantamentos',
                                        'Proteção jurídica para o dono da barbearia',
                                    ],
                                ],
                                [
                                    'badge' => '🍺 Bar & Estoque',
                                    'title' => 'Comanda Integrada de Cervejas e Cosméticos',
                                    'description' => 'Lance bebidas e pomadas no corte do cliente com baixa automática de estoque.',
                                    'benefits' => [
                                        'Aumento imediato do ticket médio por cliente',
                                        'Alerta automático de estoque mínimo',
                                        'Fechamento unificado no PIX ou Cartão',
                                    ],
                                ],
                                [
                                    'badge' => '🎁 Máquina de Retorno',
                                    'title' => 'Cartão Fidelidade Digital & Clientes Sumidos',
                                    'description' => 'Selos automáticos e alerta para chamar no WhatsApp quem não corta há mais de 20 dias.',
                                    'benefits' => [
                                        'Zero papel ou carimbos que se perdem',
                                        'Alerta automático de aniversariantes do mês',
                                        'Retenção comprovada com resgates protegidos',
                                    ],
                                ],
                                [
                                    'badge' => '💈 Sigilo e Privacidade',
                                    'title' => 'Visão Restrita do Barbeiro no Celular',
                                    'description' => 'Cada colaborador acompanha apenas seus próprios agendamentos e comissões.',
                                    'benefits' => [
                                        'O barbeiro não vê o caixa geral da barbearia',
                                        'Bloqueio automático de relatórios estratégicos',
                                        'Apenas o gestor dá baixa e liquida pagamentos',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'modules_grid',
                        'data' => [
                            'section_badge' => 'Ecossistema Completo',
                            'title' => 'Tudo o que sua barbearia precisa em uma única assinatura',
                            'subtitle' => 'Sem cobrança extra por módulo e sem taxas escondidas sobre os seus cortes.',
                            'modules' => [
                                [
                                    'icon' => '📱',
                                    'title' => 'Página Pública & App PWA',
                                    'tag' => 'Agendamento',
                                    'description' => 'Link próprio White-Label com suas cores e logotipo, instalável na tela inicial do celular dos seus clientes.',
                                ],
                                [
                                    'icon' => '💰',
                                    'title' => 'Comissões & Recibos',
                                    'tag' => 'Financeiro',
                                    'description' => 'Cálculo automático de comissões por barbeiro e emissão de recibo jurídico assinado para segurança trabalhista.',
                                ],
                                [
                                    'icon' => '🍺',
                                    'title' => 'Bar, Produtos & Estoque',
                                    'tag' => 'Balcão',
                                    'description' => 'Controle de bebidas, pomadas e insumos com alerta de estoque baixo e lançamento direto na comanda do corte.',
                                ],
                                [
                                    'icon' => '🎁',
                                    'title' => 'Cartão Fidelidade & CRM',
                                    'tag' => 'Fidelização',
                                    'description' => 'Selos automáticos a cada visita, alerta de clientes sumidos há +20 dias e lista de aniversariantes do mês.',
                                ],
                                [
                                    'icon' => '💈',
                                    'title' => 'Acesso Restrito do Barbeiro',
                                    'tag' => 'Equipe',
                                    'description' => 'Cada colaborador acessa pelo próprio celular apenas sua agenda e extrato de comissões, sem ver o caixa da casa.',
                                ],
                                [
                                    'icon' => '🏦',
                                    'title' => 'Fechamento de Caixa Diário',
                                    'tag' => 'Fluxo de Caixa',
                                    'description' => 'Controle de abertura de gaveta, sangrias, suprimentos e conferência por PIX, Dinheiro, Crédito e Débito.',
                                ],
                                [
                                    'icon' => '⭐',
                                    'title' => 'Pesquisa 5 Estrelas & Ranking',
                                    'tag' => 'Qualidade',
                                    'description' => 'Link pós-atendimento para o cliente avaliar de 1 a 5 estrelas e relatórios de desempenho da equipe.',
                                ],
                                [
                                    'icon' => '🛡️',
                                    'title' => 'Segurança 2FA + 30 Dias',
                                    'tag' => 'Segurança',
                                    'description' => 'Autenticação em 2 fatores (App ou E-mail) com opção de lembrar seu dispositivo confiável por 30 dias.',
                                ],
                            ],
                        ],
                    ],
                    [
                        'type' => 'comparison_table',
                        'data' => [
                            'section_badge' => 'A Evolução da Sua Barbearia',
                            'title' => 'Por que as barbearias mais lucrativas estão migrando para o AuroBarber?',
                            'subtitle' => 'Compare a rotina de quem ainda depende de caderno ou aplicativos engessados versus a liberdade do AuroBarber Cloud.',
                            'items' => [
                                [
                                    'feature' => 'Agendamento pelo Cliente',
                                    'traditional_value' => 'Mensagens infinitas no WhatsApp, cliente desiste por demora no retorno',
                                    'aurobarber_value' => 'Link White-Label no Instagram, agendamento em 30 segundos 24h por dia',
                                ],
                                [
                                    'feature' => 'Taxas sobre Faturamento',
                                    'traditional_value' => 'Apps tradicionais cobram 10% a 20% sobre cada serviço ou cliente agendado',
                                    'aurobarber_value' => '0% de taxa por corte ou Pix. 100% do dinheiro da bancada fica no seu bolso',
                                ],
                                [
                                    'feature' => 'Fechamento de Comissões',
                                    'traditional_value' => 'Caderno rasurado, desconfiança de barbeiro e horas de cálculos no sábado',
                                    'aurobarber_value' => 'Cálculo automático em 1 clique com recibo jurídico impresso/assinado',
                                ],
                                [
                                    'feature' => 'Privacidade da Equipe',
                                    'traditional_value' => 'Barbeiros vendo o caixa geral da barbearia ou faturamento dos colegas',
                                    'aurobarber_value' => 'Visão restrita no celular: cada profissional só vê sua agenda e seu extrato',
                                ],
                                [
                                    'feature' => 'Consumo de Bar & Produtos',
                                    'traditional_value' => 'Cervejas sumindo da geladeira e pomadas vendidas sem registro na comanda',
                                    'aurobarber_value' => 'Comanda integrada ao corte com baixa imediata no estoque e markup de lucro',
                                ],
                                [
                                    'feature' => 'Retenção de Clientes',
                                    'traditional_value' => 'Cartão de visita com carimbo de papel que o cliente sempre perde na carteira',
                                    'aurobarber_value' => 'Cartão Fidelidade 100% digital com alerta inteligente de clientes ausentes há +20 dias',
                                ],
                            ],
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
                            'faqs' => [
                                [
                                    'question' => 'O cliente precisa baixar algum aplicativo na Play Store ou Apple Store?',
                                    'answer' => 'Não! Esse é um dos maiores diferenciais do AuroBarber. O seu cliente acessa um link direto e leve (ex: barber.aurosoft.com.br/suabarbearia), sem senha e sem atrito. Se quiser, ele pode instalar o atalho PWA direto no celular em 1 toque.',
                                ],
                                [
                                    'question' => 'O AuroBarber cobra porcentagem sobre meus cortes ou sobre agendamentos?',
                                    'answer' => 'Zero por cento! Você paga apenas a mensalidade fixa do plano escolhido. Todo o dinheiro dos cortes, Pix e comandas vai 100% direto para o caixa da sua barbearia.',
                                ],
                                [
                                    'question' => 'Os barbeiros conseguem ver o faturamento total da barbearia?',
                                    'answer' => 'Não. Ao ativar a Visão Restrita para o login do barbeiro, ele só consegue visualizar os agendamentos da própria cadeira e o valor das próprias comissões. Os relatórios gerenciais e o lucro total da barbearia são visíveis exclusivamente para você (dono ou gerente).',
                                ],
                                [
                                    'question' => 'Como funciona o cálculo de comissões e a segurança jurídica?',
                                    'answer' => 'Você define as porcentagens de comissão de serviços e produtos para cada profissional. O sistema calcula automaticamente o valor bruto, desconta vales ou adiantamentos e emite um recibo detalhado de prestação de serviços com base na Lei do Salão Parceiro para o profissional assinar.',
                                ],
                                [
                                    'question' => 'Consigo controlar a venda de bebidas, cervejas e pomadas?',
                                    'answer' => 'Sim! O AuroBarber conta com módulo de Comanda e Balcão completo. Enquanto o cliente corta o cabelo, você adiciona cervejas ou cosméticos na comanda dele, dando baixa em tempo real no seu estoque.',
                                ],
                                [
                                    'question' => 'Como funciona o teste grátis de 7 dias?',
                                    'answer' => 'Basta clicar em qualquer botão de contratação ou demonstração e preencher seus dados. Nossa equipe criará seu ambiente na hora para você testar com seus barbeiros e clientes sem nenhum compromisso.',
                                ],
                            ],
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
