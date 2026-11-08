<!DOCTYPE html>
<html lang="pt-BR" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade & LGPD • Aurosoft</title>
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
                    <span class="text-[10px] uppercase tracking-widest text-amber-400 font-bold block">Privacidade & Proteção de Dados</span>
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
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    🛡️ Conformidade com a Lei Geral de Proteção de Dados (LGPD)
                </span>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                    Política de Privacidade e Segurança da Informação
                </h1>
                <p class="text-slate-400 text-sm">
                    Em total conformidade com a Lei nº 13.709/2018 (LGPD) e o Marco Civil da Internet • Atualizada em Novembro de 2026
                </p>
            </div>

            {{-- TEXTO DA POLÍTICA --}}
            <div class="space-y-8 text-slate-300 leading-relaxed text-sm sm:text-base">

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">1</span>
                        Nosso Compromisso com a Privacidade
                    </h2>
                    <p>
                        A <strong>Aurosoft</strong> reconhece a importância fundamental da privacidade e da proteção de dados pessoais, especialmente no contexto educacional. Esta Política descreve de forma transparente como os dados são tratados, protegidos e respeitados em nossa plataforma de gestão escolar e LMS.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">2</span>
                        Papéis e Responsabilidades (Artigo 5º da LGPD)
                    </h2>
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-2">
                            <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-amber-500/20 text-amber-400 uppercase tracking-wide">A Escola</span>
                            <h3 class="text-white font-bold text-base">Controladora dos Dados</h3>
                            <p class="text-slate-400 text-sm">
                                A Instituição de Ensino é a única responsável pelas decisões de coleta, matrícula e gestão dos dados de seus alunos, pais, responsáveis financeiros e educadores.
                            </p>
                        </div>
                        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 space-y-2">
                            <span class="px-2.5 py-0.5 rounded text-[11px] font-bold bg-blue-500/20 text-blue-400 uppercase tracking-wide">A Aurosoft</span>
                            <h3 class="text-white font-bold text-base">Operadora Técnica</h3>
                            <p class="text-slate-400 text-sm">
                                A Aurosoft atua exclusivamente como provedora de tecnologia e infraestrutura segura, processando os dados estritamente de acordo com as instruções da instituição contratante.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">3</span>
                        Proteção Especial de Crianças e Adolescentes (Artigo 14)
                    </h2>
                    <p>
                        Em cumprimento estrito ao <strong>Artigo 14 da Lei nº 13.709/2018 (LGPD)</strong>, o tratamento de dados pessoais de crianças e adolescentes é realizado exclusivamente em seu melhor interesse.
                    </p>
                    <p>
                        Esses dados são inseridos na plataforma exclusivamente para fins de prestação de serviços educacionais (matrícula, emissão de boletins, frequência e certificação), cabendo à instituição de ensino coletar o consentimento específico dos pais ou responsáveis legais quando aplicável.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">4</span>
                        Quais Dados são Armazenados e Para Qual Finalidade
                    </h2>
                    <ul class="list-disc pl-6 space-y-2 text-slate-300">
                        <li>
                            <strong class="text-white">Alunos e Responsáveis:</strong> Nome completo, CPF, data de nascimento, endereço, contatos (e-mail e telefone), histórico pedagógico, presenças, notas e parcelas de mensalidades;
                        </li>
                        <li>
                            <strong class="text-white">Colaboradores e Professores:</strong> Nome, e-mail de acesso corporativo, função pedagógica/administrativa e logs de auditoria do sistema;
                        </li>
                        <li>
                            <strong class="text-white">Dados de Conexão:</strong> Endereço IP, data/hora de login e identificador do dispositivo para fins de segurança, auditoria e prevenção contra acessos não autorizados.
                        </li>
                    </ul>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">5</span>
                        Garantia de Não Compartilhamento e Não Comercialização
                    </h2>
                    <div class="bg-emerald-950/30 border border-emerald-500/30 rounded-2xl p-5 space-y-2">
                        <h3 class="text-emerald-400 font-bold text-base flex items-center gap-2">
                            <span>🚫</span> Compromisso Inegociável da Aurosoft
                        </h3>
                        <p class="text-slate-300 text-sm">
                            A Aurosoft <strong>JAMAIS vende, comercializa, aluga, cede ou compartilha</strong> dados pessoais de alunos, responsáveis ou colaboradores com agências de publicidade, corretores de dados, empresas de marketing ou quaisquer terceiros.
                        </p>
                    </div>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">6</span>
                        Medidas Técnicas de Segurança da Informação
                    </h2>
                    <p>Adotamos as mais rigorosas práticas de cibersegurança da indústria:</p>
                    <ul class="list-disc pl-6 space-y-1.5 text-slate-300">
                        <li><strong class="text-white">Isolamento Multi-Tenant:</strong> Bancos de dados isolados por schema no PostgreSQL para impedir cruzamento de dados;</li>
                        <li><strong class="text-white">Criptografia em Trânsito:</strong> Toda comunicação é protegida via HTTPS com certificados TLS 1.3 e HTTP/3;</li>
                        <li><strong class="text-white">Armazenamento Seguro de Senhas:</strong> Criptografia irreversível com algoritmo Bcrypt de alta densidade;</li>
                        <li><strong class="text-white">Autenticação em Duas Etapas (2FA):</strong> Verificação por código numérico seguro de 6 dígitos no login;</li>
                        <li><strong class="text-white">Backups Automáticos Diários:</strong> Cópias de segurança com retenção de 15 dias armazenadas em ambiente seguro;</li>
                        <li><strong class="text-white">Defesa contra Ataques:</strong> Firewall com negação de entrada padrão e blindagem contra força bruta (Fail2ban).</li>
                    </ul>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">7</span>
                        Direitos dos Titulares de Dados (Artigo 18 da LGPD)
                    </h2>
                    <p>
                        Os alunos e seus responsáveis legais podem exercer os direitos de confirmação de existência de tratamento, acesso aos dados, correção de informações incompletas, bloqueio ou eliminação diretamente junto à instituição de ensino onde estão matriculados (Controladora).
                    </p>
                    <p>
                        A Aurosoft fornece todas as ferramentas tecnológicas necessárias para que a instituição atenda às solicitações dos titulares com agilidade.
                    </p>
                </section>

                <section class="space-y-3">
                    <h2 class="text-xl font-bold text-white flex items-center gap-2">
                        <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">8</span>
                        Canal do Encarregado de Proteção de Dados (DPO)
                    </h2>
                    <p>
                        Para esclarecer dúvidas sobre esta Política de Privacidade ou sobre as medidas de segurança implementadas na plataforma, entre em contato com o nosso Encarregado pelo Tratamento de Dados Pessoais:
                    </p>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 text-sm space-y-1 text-slate-300">
                        <p><strong class="text-white">Encarregado (DPO):</strong> Equipe de Segurança e Privacidade Aurosoft</p>
                        <p><strong class="text-white">E-mail oficial:</strong> <a href="mailto:privacidade@aurosoft.com.br" class="text-amber-400 hover:underline">privacidade@aurosoft.com.br</a></p>
                        <p><strong class="text-white">Prazo de resposta:</strong> Até 2 (dois) dias úteis</p>
                    </div>
                </section>

            </div>

        </div>
    </main>

    {{-- FOOTER --}}
    <footer class="border-t border-slate-800 bg-slate-950 py-6 px-6 text-center text-xs text-slate-500">
        <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
            <span>© {{ date('Y') }} Aurosoft Gestão Escolar. Todos os direitos reservados.</span>
            <div class="flex items-center gap-4">
                <a href="{{ route('terms') }}" class="hover:text-amber-400 transition">Termos de Uso</a>
                <span>•</span>
                <a href="{{ route('home') }}" class="hover:text-amber-400 transition">Página Inicial</a>
            </div>
        </div>
    </footer>

</body>
</html>
