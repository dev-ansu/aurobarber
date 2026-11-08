<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TypingExercise;
use Illuminate\Database\Seeder;

class TypingExerciseSeeder extends Seeder
{
    public function run(): void
    {
        TypingExercise::query()->delete();

        $exercises = [
            // =========================================================================
            // NÍVEL 1: TECLAS GUIAS E DEDOS INDIVIDUAIS (HOME ROW - ASDF JKLÇ)
            // =========================================================================
            [
                'title' => 'Exercício 01: Linha Guia - Indicadores (F e J)',
                'level' => 1,
                'category' => 'home_row',
                'target_text' => 'ffff jjjj ff jj fj jf ffff jjjj ffjj jjff f j f j ff jj fjf jfj ffff jjjj',
                'instructions' => 'Coloque o dedo indicador esquerdo sobre a tecla F (sinta o relevo) e o indicador direito sobre a tecla J.',
                'min_wpm' => 12,
                'min_accuracy' => 85.00,
                'sort_order' => 1,
            ],
            [
                'title' => 'Exercício 02: Linha Guia - Dedos Médios (D e K)',
                'level' => 1,
                'category' => 'home_row',
                'target_text' => 'dddd kkkk dd kk dk kd dddd kkkk ddkk kkdd dk kd fjdk jfkd kdfj fddk jkkd',
                'instructions' => 'Utilize o dedo médio esquerdo na tecla D e o dedo médio direito na tecla K.',
                'min_wpm' => 14,
                'min_accuracy' => 85.00,
                'sort_order' => 2,
            ],
            [
                'title' => 'Exercício 03: Linha Guia - Anelares e Mínimos (S, L, A e Ç)',
                'level' => 1,
                'category' => 'home_row',
                'target_text' => 'ssss llll aaaa çççç asdf jklç fa sj dl aç çl ks ja asdf jklç ala laço sala fala',
                'instructions' => 'Mantenha os dedos nas teclas guias: A (mínimo esq.), S (anelar esq.), L (anelar dir.), Ç (mínimo dir.).',
                'min_wpm' => 15,
                'min_accuracy' => 85.00,
                'sort_order' => 3,
            ],
            [
                'title' => 'Exercício 04: Linha Guia Completa e Palavras Simples',
                'level' => 1,
                'category' => 'home_row',
                'target_text' => 'fada fala faca sala cala laço caso caça fada caça laço fala sala cala caso laço',
                'instructions' => 'Pratique alternar os dedos sem olhar para o teclado. Pressione o espaço com o polegar.',
                'min_wpm' => 16,
                'min_accuracy' => 88.00,
                'sort_order' => 4,
            ],

            // =========================================================================
            // NÍVEL 2: TODAS AS LETRAS DO TECLADO & PANGRAMAS (A-Z COMPLETO)
            // =========================================================================
            [
                'title' => 'Exercício 05: Alfabeto A-Z - Sequência Alfabética Completa',
                'level' => 2,
                'category' => 'all_letters',
                'target_text' => 'a b c d e f g h i j k l m n o p q r s t u v w x y z z y x w v u t s r q p o n m l k j i h g f e d c b a',
                'instructions' => 'Treine o reconhecimento ágil de cada tecla do alfabeto, tanto na ordem direta quanto na inversa.',
                'min_wpm' => 18,
                'min_accuracy' => 88.00,
                'sort_order' => 5,
            ],
            [
                'title' => 'Exercício 06: Alfabeto A-Z - Pangrama do Jabuti Xereta',
                'level' => 2,
                'category' => 'all_letters',
                'target_text' => 'Um pequeno jabuti xereta viu dez cegonhas felizes no parque de Washington.',
                'instructions' => 'Este pangrama exercita todas as letras do teclado de uma só vez, incluindo K, W, Y e Ç.',
                'min_wpm' => 20,
                'min_accuracy' => 90.00,
                'sort_order' => 6,
            ],
            [
                'title' => 'Exercício 07: Alfabeto A-Z - Pangrama da Gazeta e Quermesse',
                'level' => 2,
                'category' => 'all_letters',
                'target_text' => 'Gazeta publica hoje no jornal breve nota de faxina na quermesse de whisky.',
                'instructions' => 'Mantenha os pulsos retos e use a mão esquerda para o Shift na letra G maiúscula.',
                'min_wpm' => 20,
                'min_accuracy' => 90.00,
                'sort_order' => 7,
            ],
            [
                'title' => 'Exercício 08: Alfabeto A-Z - Pangrama das Zebras Caolhas',
                'level' => 2,
                'category' => 'all_letters',
                'target_text' => 'Zebras caolhas de Java querem passar por fax para Washington com xadrez.',
                'instructions' => 'Alterne com precisão entre as linhas superior (QWERTY), guia (ASDF) e inferior (ZXCVB).',
                'min_wpm' => 22,
                'min_accuracy' => 90.00,
                'sort_order' => 8,
            ],

            // =========================================================================
            // NÍVEL 3: PALAVRAS COMPLETAS E VOCABULÁRIO ESSENCIAL
            // =========================================================================
            [
                'title' => 'Exercício 09: Palavras Completas - Informática e Hardware',
                'level' => 3,
                'category' => 'words',
                'target_text' => 'computador teclado monitor mouse gabinete memoria processador disco placa rede cabo conector sistema',
                'instructions' => 'Digite palavras inteiras com fluidez. Pressione espaço de forma rítmica com o polegar.',
                'min_wpm' => 22,
                'min_accuracy' => 90.00,
                'sort_order' => 9,
            ],
            [
                'title' => 'Exercício 10: Palavras Completas - Rotina de Escritório e Gestão',
                'level' => 3,
                'category' => 'words',
                'target_text' => 'documento arquivo pasta planilha relatorio contrato empresa cliente usuario recibo protocolo atendimento',
                'instructions' => 'Vocabulário essencial para rotinas administrativas e atendimento em qualquer empresa.',
                'min_wpm' => 22,
                'min_accuracy' => 90.00,
                'sort_order' => 10,
            ],
            [
                'title' => 'Exercício 11: Palavras Completas - Ações e Operações Digitais',
                'level' => 3,
                'category' => 'words',
                'target_text' => 'abrir salvar fechar copiar colar recortar imprimir pesquisar formatar calcular configurar atualizar conectar',
                'instructions' => 'Foque no ritmo constante: termine uma palavra e já prepare os dedos para a próxima.',
                'min_wpm' => 24,
                'min_accuracy' => 90.00,
                'sort_order' => 11,
            ],

            // =========================================================================
            // NÍVEL 4: ACENTUAÇÃO BRASILEIRA (ABNT2: AGUDO, CIRCUNFLEXO, TIL, CRASE, CEDILHA)
            // =========================================================================
            [
                'title' => 'Exercício 12: Acentuação ABNT2 - Acento Agudo e Til (´ e ~)',
                'level' => 4,
                'category' => 'accents',
                'target_text' => 'café saída fácil rápido água pão não lição coração botão sabão irmão chão órgão bênção',
                'instructions' => 'No teclado ABNT2, aperte primeiro a tecla do acento (agudo ou til) e logo em seguida a vogal.',
                'min_wpm' => 22,
                'min_accuracy' => 90.00,
                'sort_order' => 12,
            ],
            [
                'title' => 'Exercício 13: Acentuação ABNT2 - Circunflexo, Crase e Cedilha (^, `, ç)',
                'level' => 4,
                'category' => 'accents',
                'target_text' => 'você avô mês lâmpada tênis à tarde às vezes à vista à medida que cabeça taça maçã açúcar esforço',
                'instructions' => 'Para crase: Shift + agudo, depois a letra A (à). Para circunflexo: Shift + til, depois a vogal.',
                'min_wpm' => 22,
                'min_accuracy' => 90.00,
                'sort_order' => 13,
            ],
            [
                'title' => 'Exercício 14: Acentuação ABNT2 - Nomes Próprios e Cidades do Brasil',
                'level' => 4,
                'category' => 'accents',
                'target_text' => 'São Paulo Brasília Vitória Goiânia Cuiabá Maranhão Belém Maceió João Pessoa Ribeirão Preto',
                'instructions' => 'Combine a tecla Shift para a letra inicial maiúscula com as teclas de acentuação correspondentes.',
                'min_wpm' => 24,
                'min_accuracy' => 92.00,
                'sort_order' => 14,
            ],

            // =========================================================================
            // NÍVEL 5: PONTUAÇÃO, ASPAS, PARÊNTESES E SÍMBOLOS ESPECIAIS
            // =========================================================================
            [
                'title' => 'Exercício 15: Pontuação Completa - Vírgula, Ponto, Dois Pontos e Ponto e Vírgula',
                'level' => 5,
                'category' => 'symbols',
                'target_text' => 'bom dia, tudo bem? primeiro passo: ligue o computador; segundo passo: abra o sistema, digite sua senha e comece.',
                'instructions' => 'A vírgula fica ao lado da letra M (dedo médio). O ponto final fica logo ao lado da vírgula (dedo anelar).',
                'min_wpm' => 24,
                'min_accuracy' => 92.00,
                'sort_order' => 15,
            ],
            [
                'title' => 'Exercício 16: Aspas, Parênteses e Hífen (" \', ( ), -)',
                'level' => 5,
                'category' => 'symbols',
                'target_text' => 'O instrutor disse: "Abra a pasta \'Documentos\' (veja no menu lateral) e renomeie o arquivo-final.docx".',
                'instructions' => 'Aspas duplas: Shift + tecla abaixo do Esc. Parênteses: Shift + 8 (abre) e Shift + 9 (fecha).',
                'min_wpm' => 24,
                'min_accuracy' => 92.00,
                'sort_order' => 16,
            ],
            [
                'title' => 'Exercício 17: Símbolos Comerciais e Especiais (@ # $ % & * = + /)',
                'level' => 5,
                'category' => 'symbols',
                'target_text' => 'financeiro@empresa.com.br - Fatura #5892 no valor de R$ 340,50 com taxa de 5% (desconto = 10% + 2,5%).',
                'instructions' => 'Utilize a mão oposta para segurar Shift enquanto o dedo correspondente alcança os números do topo.',
                'min_wpm' => 22,
                'min_accuracy' => 90.00,
                'sort_order' => 17,
            ],

            // =========================================================================
            // NÍVEL 6: TEXTOS CURTOS (30 A 60 PALAVRAS - ERGONOMIA E BOAS PRÁTICAS)
            // =========================================================================
            [
                'title' => 'Exercício 18: Texto Curto - Postura e Ergonomia ao Digitar',
                'level' => 6,
                'category' => 'short_text',
                'target_text' => 'Ao digitar no computador, mantenha a postura ereta com as costas apoiadas no encosto da cadeira. Os pés devem tocar firmemente o chão e os pulsos devem permanecer relaxados, sem dobrar para cima ou para baixo. Essa prática previne lesões musculares e aumenta significativamente sua velocidade e conforto no trabalho.',
                'instructions' => 'Texto curto de 52 palavras. Mantenha os ombros relaxados e respire calmamente enquanto digita.',
                'min_wpm' => 25,
                'min_accuracy' => 92.00,
                'sort_order' => 18,
            ],
            [
                'title' => 'Exercício 19: Texto Curto - O Teclado e a Memória Muscular',
                'level' => 6,
                'category' => 'short_text',
                'target_text' => 'A digitação eficiente não depende da força aplicada nas teclas, mas do ritmo constante e da memória muscular dos dedos. Com a repetição diária de exercícios, seu cérebro memoriza a localização exata de cada letra, permitindo que você digite com fluidez sem olhar para o teclado.',
                'instructions' => 'Texto curto de 46 palavras. Foque em não abaixar a cabeça para olhar o teclado.',
                'min_wpm' => 25,
                'min_accuracy' => 92.00,
                'sort_order' => 19,
            ],

            // =========================================================================
            // NÍVEL 7: TEXTOS MÉDIOS (80 A 140 PALAVRAS - INFORMÁTICA E ESCRITÓRIO)
            // =========================================================================
            [
                'title' => 'Exercício 20: Texto Médio - Organização de Arquivos e Pastas no Windows',
                'level' => 7,
                'category' => 'medium_text',
                'target_text' => 'No ambiente corporativo, a boa organização de arquivos e pastas no computador é indispensável para o sucesso profissional. Crie pastas com categorias claras, separando documentos por ano, setor ou nome de cliente. Ao salvar planilhas e relatórios, utilize nomes descritivos e verifique com atenção a pasta de destino para evitar extravios. Use atalhos clássicos do teclado: Ctrl + C para copiar, Ctrl + V para colar e Ctrl + S para salvar suas alterações com frequência. Realizar backups periódicos em dispositivos externos ou serviços de nuvem garante a proteção permanente das informações da empresa.',
                'instructions' => 'Texto médio de 95 palavras. Preste atenção nos nomes dos atalhos e na pontuação dos períodos.',
                'min_wpm' => 26,
                'min_accuracy' => 93.00,
                'sort_order' => 20,
            ],
            [
                'title' => 'Exercício 21: Texto Médio - Comunicação Profissional por E-mail',
                'level' => 7,
                'category' => 'medium_text',
                'target_text' => 'A redação de mensagens corporativas exige clareza, cortesia e excelente pontuação. Ao redigir um e-mail para um cliente ou supervisor, inicie sempre com uma saudação respeitosa, apresente o assunto de maneira objetiva e revise a concordância antes de enviar. O título da mensagem precisa resumir com exatidão o tema tratado. Nunca redija o corpo da mensagem inteiramente em letras maiúsculas, pois isso transmite a sensação de grito. Uma assinatura formal com seu nome, função e ramal transmite credibilidade imediata e agiliza a comunicação cotidiana da equipe.',
                'instructions' => 'Texto médio de 92 palavras. Ritmo constante e atenção aos acentos agudos e circunflexos.',
                'min_wpm' => 26,
                'min_accuracy' => 93.00,
                'sort_order' => 21,
            ],
            [
                'title' => 'Exercício 22: Texto Médio - Fórmulas e Planilhas no Microsoft Excel',
                'level' => 7,
                'category' => 'medium_text',
                'target_text' => 'O Microsoft Excel é a ferramenta mais solicitada em processos seletivos para áreas administrativas e financeiras. A precisão na digitação de fórmulas é essencial, pois qualquer ponto e vírgula ou parêntese incorreto impede o cálculo correto dos dados. A fórmula =SE(D5>=7; "Aprovado"; "Reprovado") ilustra como a lógica condicional organiza relatórios escolares e empresariais. Praticar a inserção rápida de números no teclado numérico e sinais matemáticos como mais, menos, asterisco para multiplicação e barra para divisão aceleram expressivamente sua produtividade no escritório.',
                'instructions' => 'Texto médio de 88 palavras. Atenção especial à fórmula com aspas e ponto e vírgula.',
                'min_wpm' => 26,
                'min_accuracy' => 93.00,
                'sort_order' => 22,
            ],

            // =========================================================================
            // NÍVEL 8: TEXTOS LONGOS (150 A 250+ PALAVRAS - REDAÇÃO EMPRESARIAL)
            // =========================================================================
            [
                'title' => 'Exercício 23: Texto Longo - Redação Empresarial e Atendimento de Excelência',
                'level' => 8,
                'category' => 'long_text',
                'target_text' => 'O atendimento ao cliente e a qualidade da comunicação escrita são diferenciais decisivos no mercado de trabalho atual. Uma proposta comercial elaborada com precisão gramatical, pontuação impecável e argumentos claros constrói uma relação de imediata confiança com o consumidor. Da mesma forma, relatórios operacionais objetivos fornecem dados seguros para que gestores tomem decisões estratégicas em momentos críticos. Para aprimorar sua comunicação escrita, cultive o hábito de reler atenciosamente cada parágrafo antes de compartilhá-lo. Observe o emprego correto da crase, dos acentos circunflexos e das vírgulas, que organizam as ideias e conduzem o leitor com naturalidade. Fuja de gírias e abreviações informais em memorandos e atas de reunião. O profissional que desenvolve velocidade e precisão na digitação libera sua mente para focar no que realmente importa: a análise crítica, a resolução de problemas complexos e a entrega contínua de resultados com excelência no ambiente corporativo.',
                'instructions' => 'Texto longo de 160 palavras. Treino de resistência física e concentração mental contínua.',
                'min_wpm' => 28,
                'min_accuracy' => 94.00,
                'sort_order' => 23,
            ],
            [
                'title' => 'Exercício 24: Texto Longo - A Evolução da Informática e o Futuro do Trabalho',
                'level' => 8,
                'category' => 'long_text',
                'target_text' => 'A tecnologia da informação revolucionou de modo irreversível a rotina de empresas em todo o mundo. O que há poucas décadas exigia pilhas de papel, carimbos manuais e máquinas de escrever barulhentas agora se resolve em poucos segundos através de softwares integrados, servidores remotos e redes de alta velocidade. Essa transformação contínua exige profissionais versáteis, capacitados a operar sistemas operacionais modernos, navegar com segurança em navegadores web e manipular grandes volumes de informações em planilhas eletrônicas. Contudo, dominar as ferramentas técnicas é apenas metade do caminho: a agilidade física no teclado, combinada com a concentração mental e a postura ergonômica correta, define o rendimento diário do colaborador. Aquele que dedica tempo ao treino metódico de digitação transforma tarefas rotineiras em procedimentos automáticos e fluidos, destacando-se como um profissional indispensável em qualquer equipe de alta performance.',
                'instructions' => 'Texto longo de 165 palavras. Desafio de maestria profissional Aurosoft LMS.',
                'min_wpm' => 28,
                'min_accuracy' => 94.00,
                'sort_order' => 24,
            ],
        ];

        foreach ($exercises as $data) {
            TypingExercise::updateOrCreate(
                ['title' => $data['title']],
                $data
            );
        }
    }
}
