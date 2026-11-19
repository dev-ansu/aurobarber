<x-layouts.legal title="Política de Privacidade & LGPD" badge="Privacidade & Proteção de Dados">
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
                    A plataforma <strong>AuroBarber</strong> (operada pela Aurosoft Tecnologia) tem como premissa inegociável a proteção integral da privacidade e dos dados pessoais de barbearias, profissionais e seus respectivos clientes finais. Esta Política descreve de forma clara e transparente as medidas de proteção aplicadas em nosso software de agendamento e gestão.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">2</span>
                    Papéis e Responsabilidades (Artigo 5º da LGPD)
                </h2>
                <p>Nos termos da legislação brasileira:</p>
                <ul class="list-disc pl-6 space-y-2 text-slate-300">
                    <li><strong class="text-white">Controladora:</strong> A Barbearia Contratante é a única e exclusiva controladora dos dados cadastrais de seus clientes e barbeiros. É ela quem define quais serviços são prestados e realiza a gestão do atendimento.</li>
                    <li><strong class="text-white">Operadora:</strong> O AuroBarber / Aurosoft atua exclusivamente como operador da infraestrutura tecnológica, processando as informações com o objetivo estrito de viabilizar a agenda, os cálculos financeiros e o envio de lembretes.</li>
                    <li><strong class="text-white">Titulares:</strong> Os clientes da barbearia e os membros da equipe que utilizam a plataforma.</li>
                </ul>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">3</span>
                    Finalidades do Tratamento de Dados
                </h2>
                <p>Os dados processados no AuroBarber destinam-se exclusivamente às seguintes finalidades legítimas:</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-2">
                        <span class="text-amber-400 font-bold text-sm block">✂️ Agendamento & Gestão de Horários</span>
                        <p class="text-xs text-slate-400">Reserva de horários, seleção de barbeiros e múltiplos serviços pelo cliente final na página pública.</p>
                    </div>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-2">
                        <span class="text-amber-400 font-bold text-sm block">💬 Confirmação e Lembretes WhatsApp</span>
                        <p class="text-xs text-slate-400">Envio de mensagens pré-formatadas para redução de faltas e confirmação de presença do cliente.</p>
                    </div>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-2">
                        <span class="text-amber-400 font-bold text-sm block">💰 Divisão e Rateio de Comissões</span>
                        <p class="text-xs text-slate-400">Cálculo de comissões por profissional em conformidade com a Lei do Salão Parceiro.</p>
                    </div>
                    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 space-y-2">
                        <span class="text-amber-400 font-bold text-sm block">⭐ Avaliação e Fidelização</span>
                        <p class="text-xs text-slate-400">Links pós-atendimento para feedback de qualidade e métricas de satisfação da equipe.</p>
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">4</span>
                    Medidas Técnicas de Segurança e Criptografia
                </h2>
                <p>O AuroBarber adota padrões de segurança enterprise-grade para salvaguarda dos dados:</p>
                <ul class="list-disc pl-6 space-y-2 text-slate-300">
                    <li><strong class="text-white">Criptografia de Dados Sensíveis:</strong> Os números de telefone e e-mails dos clientes finais são armazenados com criptografia em repouso e hashes determinísticos, impedindo acesso indevido por terceiros.</li>
                    <li><strong class="text-white">Isolamento Multi-Tenant por Schemas:</strong> Cada barbearia opera em schema PostgreSQL próprio, garantindo isolamento total do banco de dados.</li>
                    <li><strong class="text-white">Proteção Anti-Bot Cloudflare Turnstile:</strong> As rotas públicas de agendamento e login contam com desafios invisíveis do Cloudflare Turnstile para impedir ataques automatizados e agendamentos fantasmas.</li>
                    <li><strong class="text-white">Autenticação com 2FA:</strong> O painel disponibiliza autenticação em dois fatores para os administradores da barbearia.</li>
                </ul>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">5</span>
                    Não Comercialização de Dados
                </h2>
                <p>
                    <strong class="text-amber-400">O AuroBarber e a Aurosoft não vendem, não alugam e não monetizam a base de clientes das barbearias com qualquer terceiro.</strong>
                </p>
                <p>
                    Os dados dos clientes nunca são utilizados para propaganda externa, anúncios direcionados de terceiros ou qualquer finalidade alheia ao próprio relacionamento da barbearia com o seu cliente.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">6</span>
                    Direitos dos Titulares de Dados
                </h2>
                <p>
                    Em consonância com o Artigo 18 da LGPD, os clientes da barbearia e os barbeiros têm o direito de solicitar a qualquer tempo: confirmação da existência de tratamento, acesso aos dados, correção de informações inexatas, anonimização ou exclusão de registros.
                </p>
                <p class="text-xs text-slate-400">
                    A barbearia possui ferramentas nativas no painel para efetuar a exclusão ou retificação de cadastros mediante solicitação de seus clientes.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">7</span>
                    Encarregado pelo Tratamento de Dados (DPO)
                </h2>
                <p>
                    Para quaisquer esclarecimentos ou solicitações relacionadas a esta Política de Privacidade ou ao exercício de direitos da LGPD, disponibilizamos o contato direto com nosso Encarregado:
                </p>
                <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 text-sm space-y-1 text-slate-300">
                    <p><strong class="text-white">Encarregado (DPO):</strong> Equipe de Segurança e Privacidade Aurosoft</p>
                    <p><strong class="text-white">E-mail:</strong> <a href="mailto:privacidade@aurosoft.com.br" class="text-amber-400 hover:underline">privacidade@aurosoft.com.br</a></p>
                    <p><strong class="text-white">Prazo de Resposta:</strong> Até 2 (dois) dias úteis</p>
                </div>
            </section>

        </div>

        {{-- BOX DE CONTATO --}}
        <div class="mt-12 p-6 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="text-white font-bold text-base">Dúvidas sobre Privacidade ou LGPD?</h3>
                <p class="text-slate-400 text-sm">Nosso time de privacidade está à disposição.</p>
            </div>
            <a href="mailto:privacidade@aurosoft.com.br" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-sm transition shrink-0">
                privacidade@aurosoft.com.br
            </a>
        </div>

    </div>
</x-layouts.legal>
