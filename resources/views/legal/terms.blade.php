<!DOCTYPE html>
<html lang="pt-BR" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso • Aurosoft Gestão Escolar & LMS</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        amber: {
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex flex-col font-sans antialiased selection:bg-amber-500 selection:text-slate-950">

    {{-- HEADER --}}
    <header class="border-b border-slate-800/80 bg-slate-950/80 backdrop-blur sticky top-0 z-40">
        <div class="max-w-5xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-amber-400 flex items-center justify-center font-black text-slate-950 text-xl shadow-lg shadow-amber-500/20 group-hover:scale-105 transition">
                    A
                </div>
                <div>
                    <span class="text-xl font-black tracking-tight text-white block">Aurosoft</span>
                    <span class="text-[10px] uppercase tracking-widest text-amber-400 font-bold block">Gestão Escolar & LMS</span>
                </div>
            </a>
            <a href="{{ route('home') }}" class="text-sm font-semibold text-slate-400 hover:text-amber-400 transition flex items-center gap-1.5">
                ← Voltar ao Início
            </a>
        </div>
    </header>

    {{-- CONTEÚDO PRINCIPAL --}}
    <main class="flex-1 py-12 px-6">
        <div class="max-w-4xl mx-auto space-y-10">

            {{-- CABEÇALHO DO DOCUMENTO --}}
            <div class="border-b border-slate-800 pb-8 space-y-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    Documento Legal Oficial
                </span>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                    Termos e Condições de Uso do Software
                </h1>
                <p class="text-slate-400 text-sm">
                    Última atualização: 06 de novembro de 2026 • Versão 2.4 Cloud SaaS
                </p>
            </div>

            {{-- TEXTO DOS TERMOS --}}
            <div class="space-y-8 text-slate-300 leading-relaxed text-sm sm:text-base">

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">1</span>
                        Objeto e Escopo do Serviço
                    </h2>
                    <p>
                        O presente termo estabelece as condições de uso da plataforma <strong>Aurosoft</strong>, um software como serviço (SaaS - <em>Software as a Service</em>) destinado à gestão acadêmica, pedagógica, administrativa e financeira de escolas profissionalizantes, cursos livres e instituições de ensino em todo o território nacional.
                    </p>
                    <p>
                        Ao contratar uma licença, criar uma conta de demonstração ou acessar o sistema, a <strong>Instituição de Ensino Contratante</strong> concorda integralmente com estes Termos de Uso e com a nossa Política de Privacidade.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">2</span>
                        Licenciamento e Acesso à Plataforma
                    </h2>
                    <p>
                        A Aurosoft concede à Contratante uma licença não exclusiva, intransferível e revogável de uso do software, disponibilizada através da nuvem em subdomínio institucional próprio (ex: <em>escola.aurosoft.com.br</em>) ou mediante instalação On-Premise autorizada.
                    </p>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-2 text-sm">
                        <strong class="text-amber-400 block font-semibold">Isolamento e Segurança Multi-Tenant:</strong>
                        <p class="text-slate-400">
                            No ambiente em nuvem, cada instituição possui um banco de dados logicamente isolado (schema PostgreSQL dedicado). Nenhuma instituição possui acesso, visibilidade ou vínculo com os dados de outros clientes da plataforma.
                        </p>
                    </div>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">3</span>
                        Propriedade dos Dados vs. Propriedade Intelectual
                    </h2>
                    <ul class="list-disc pl-6 space-y-2 text-slate-300">
                        <li>
                            <strong class="text-white">Os Dados pertencem à Escola:</strong> Todas as informações inseridas na plataforma (cadastros de alunos, matrículas, notas, contratos, faturas e registros pedagógicos) são de propriedade exclusiva e inalienável da Instituição de Ensino Contratante.
                        </li>
                        <li>
                            <strong class="text-white">A Tecnologia pertence à Aurosoft:</strong> O software, código-fonte, rotinas, algoritmos, designs, interfaces, marcas e arquitetura de sistema são de propriedade intelectual exclusiva da Aurosoft, protegidos pelas leis de software e direitos autorais (Leis nº 9.609/98 e 9.610/98).
                        </li>
                    </ul>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">4</span>
                        Responsabilidades da Instituição Contratante
                    </h2>
                    <p>A instituição contratante se compromete a:</p>
                    <ul class="list-disc pl-6 space-y-1.5 text-slate-300">
                        <li>Cadastrar apenas informações verídicas e lícitas no sistema;</li>
                        <li>Zelar pela confidencialidade das credenciais de acesso (senhas) de seus colaboradores;</li>
                        <li>Utilizar a Autenticação em Duas Etapas (2FA) recomendada para contas administrativas;</li>
                        <li>Obter o consentimento necessário junto aos responsáveis legais para cadastro de alunos menores de idade, conforme as diretrizes da LGPD (Lei nº 13.709/2018).</li>
                    </ul>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">5</span>
                        Disponibilidade, Backups e Manutenção (SLA)
                    </h2>
                    <p>
                        A Aurosoft empregará os melhores esforços técnicos para manter a plataforma online com índice de disponibilidade superior a <strong>99,5%</strong> ao mês, excetuando-se manutenções programadas (preferencialmente realizadas de madrugada) ou instabilidades generalizadas na infraestrutura de rede global.
                    </p>
                    <p>
                        <strong>Rotina de Segurança de Dados:</strong> O sistema executa rotinas automáticas de backup diário com retenção histórica de 15 dias para fins de recuperação de desastres (<em>disaster recovery</em>).
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">6</span>
                        Planos, Pagamentos e Tolerância de Inadimplência
                    </h2>
                    <p>
                        O acesso ao Aurosoft é mantido mediante o pagamento pontual do plano de assinatura acordado (mensal ou anual).
                    </p>
                    <p>
                        Em caso de inadimplência, a instituição terá um período de cortesia e tolerância de <strong>5 (cinco) dias corridos</strong> após a data de vencimento. Persistindo a pendência após esse prazo, o acesso ao painel poderá ser temporariamente bloqueado até a regularização, preservando-se integralmente todos os dados cadastrados.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">7</span>
                        Cancelamento e Portabilidade de Dados
                    </h2>
                    <p>
                        A Contratante poderá cancelar a assinatura a qualquer momento mediante aviso prévio de 30 dias. Em caso de encerramento contratual, a Aurosoft disponibilizará à Contratante a exportação integral de seus dados acadêmicos e financeiros em formato legível (.csv / .sql / relatórios .pdf).
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">8</span>
                        Foro e Legislação Aplicável
                    </h2>
                    <p>
                        Estes Termos de Uso são regidos pelas leis da República Federativa do Brasil, em especial o Marco Civil da Internet (Lei nº 12.965/2014) e a Lei Geral de Proteção de Dados (Lei nº 13.709/2018). Fica eleito o Foro da Comarca da sede da Contratada para dirimir quaisquer dúvidas decorrentes deste instrumento.
                    </p>
                </section>

            </div>

            {{-- BOX DE CONTATO --}}
            <div class="mt-12 p-6 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <h3 class="text-white font-bold text-base">Dúvidas sobre estes termos?</h3>
                    <p class="text-slate-400 text-sm">Fale diretamente com o nosso suporte técnico e administrativo.</p>
                </div>
                <a href="mailto:contato@aurosoft.com.br" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-sm transition shrink-0">
                    contato@aurosoft.com.br
                </a>
            </div>

        </div>
    </main>

    {{-- FOOTER --}}
    <footer class="border-t border-slate-800 bg-slate-950 py-6 px-6 text-center text-xs text-slate-500">
        <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
            <span>© {{ date('Y') }} Aurosoft Gestão Escolar. Todos os direitos reservados.</span>
            <div class="flex items-center gap-4">
                <a href="{{ route('privacy') }}" class="hover:text-amber-400 transition">Política de Privacidade (LGPD)</a>
                <span>•</span>
                <a href="{{ route('home') }}" class="hover:text-amber-400 transition">Página Inicial</a>
            </div>
        </div>
    </footer>

</body>
</html>
