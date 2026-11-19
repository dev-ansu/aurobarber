<x-layouts.legal title="Termos de Uso" badge="Termos & Condições de Uso">
    <div class="max-w-4xl mx-auto space-y-10">

        {{-- CABEÇALHO DO DOCUMENTO --}}
        <div class="border-b border-slate-800 pb-8 space-y-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                Documento Legal Oficial
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">
                Termos e Condições de Uso do Software AuroBarber
            </h1>
            <p class="text-slate-400 text-sm">
                Última atualização: Novembro de 2026 • Versão 2.5 Cloud Multi-Tenant
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
                    O presente instrumento estabelece as condições de uso da plataforma <strong>AuroBarber</strong>, um software como serviço (SaaS - <em>Software as a Service</em>) desenvolvido pela <strong>Aurosoft Tecnologia</strong>, destinado à gestão operacional, agendamento online de clientes, controle de comandas, comissões de profissionais, frente de caixa (PDV) e finanças de barbearias, salões de beleza e estúdios em todo o território nacional.
                </p>
                <p>
                    Ao contratar uma licença, solicitar uma demonstração ou acessar os painéis de gestão, a <strong>Barbearia Contratante</strong> declara ciência e concordância integral com estes Termos de Uso e com a nossa Política de Privacidade.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">2</span>
                    Disponibilidade do Serviço (SLA)
                </h2>
                <p>
                    A Aurosoft compromete-se a envidar todos os esforços técnicos razoáveis para manter o AuroBarber disponível 24 horas por dia, 7 dias por semana, com um índice de disponibilidade (Uptime) de <strong class="text-white">99,5% (noventa e nove vírgula cinco por cento)</strong> ao mês, excluindo-se:
                </p>
                <ul class="list-disc pl-6 space-y-2 text-slate-300">
                    <li>Manutenções preventivas previamente programadas e comunicadas com antecedência mínima de 24 horas, executadas preferencialmente nas madrugadas para não interferir no fluxo de atendimento.</li>
                    <li>Falhas de conectividade de internet originadas pelo provedor de acesso local da barbearia ou do cliente final.</li>
                    <li>Eventos de força maior, ataques de negação de serviço (DDoS) ou instabilidade crítica nos provedores globais de computação em nuvem.</li>
                </ul>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">3</span>
                    Propriedade dos Dados e Isolamento Estrito
                </h2>
                <p>
                    <strong class="text-white">Os dados pertencem exclusivamente à Barbearia Contratante.</strong> Toda a base de clientes cadastrados, histórico de agendamentos, serviços realizados, vendas de produtos e valores de comissão pertencem unicamente ao estabelecimento.
                </p>
                <p>
                    A plataforma opera sob isolamento estrito via schemas dedicados em PostgreSQL. Isso significa que dados de clientes e faturamento de uma barbearia são física e logicamente segregados das demais, sem qualquer risco de vazamento cruzado. A barbearia poderá exportar seus relatórios a qualquer momento por meio dos recursos nativos do painel.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">4</span>
                    Responsabilidades da Barbearia e Lei do Salão Parceiro
                </h2>
                <p>São obrigações da barbearia contratante:</p>
                <ul class="list-disc pl-6 space-y-2 text-slate-300">
                    <li>Zelar pelo sigilo das credenciais de acesso de seus proprietários, gerentes e barbeiros, mantendo senhas seguras e ativando a Autenticação em Duas Etapas (2FA).</li>
                    <li>Atuar em estrita conformidade com a <strong>Lei do Salão Parceiro (Lei nº 13.352/2016)</strong>, utilizando as ferramentas de comissionamento e rateio para registrar as divisões contratuais legítimas de serviços e produtos.</li>
                    <li>Cumprir a Lei Geral de Proteção de Dados (LGPD - Lei nº 13.709/2018), coletando telefones de clientes estritamente para confirmação de agendamentos e atendimento.</li>
                    <li>Não utilizar mecanismos automatizados (bots, scrapers) para sobrecarregar a infraestrutura da plataforma.</li>
                </ul>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">5</span>
                    Planos, Cobrança e Licenciamento
                </h2>
                <p>
                    O acesso ao AuroBarber é concedido mediante assinatura mensal ou anual recorrente, com cobrança via PIX (QR Code dinâmico) ou cartão, de acordo com o plano contratado (quantidade de cadeiras/barbeiros e unidades).
                </p>
                <p>
                    O atraso no pagamento da mensalidade superior a 5 (cinco) dias poderá acarretar a suspensão temporária do acesso administrativo, sendo disponibilizada a tela de quitação imediata via PIX. Os dados da barbearia permanecerão íntegros e seguros por até 60 (sessenta) dias para fins de regularização.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">6</span>
                    Suporte Técnico e Atualizações
                </h2>
                <p>
                    O suporte operacional é fornecido através do WhatsApp oficial e abertura de chamados no painel administrativo, de segunda a sábado, em horário comercial.
                </p>
                <p>
                    Todas as atualizações contínuas de segurança, melhorias de usabilidade e novidades do sistema Cloud são aplicadas de forma automática e transparente, sem custos adicionais à mensalidade.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">7</span>
                    Cancelamento sem Fidelidade
                </h2>
                <p>
                    O AuroBarber não possui contrato com cláusula de fidelidade obrigatória. A barbearia pode cancelar sua assinatura a qualquer momento, mantendo o acesso até o término do ciclo mensal já pago.
                </p>
            </section>

            <section class="space-y-3">
                <h2 class="text-xl font-bold text-white flex items-center gap-2">
                    <span class="w-7 h-7 rounded-lg bg-amber-500/20 text-amber-400 inline-flex items-center justify-center text-sm font-black">8</span>
                    Foro de Eleição
                </h2>
                <p>
                    Estes Termos são regidos pelas leis da República Federativa do Brasil. Para dirimir quaisquer litígios, fica eleito o Foro da Comarca da sede da Aurosoft Tecnologia, com renúncia a qualquer outro.
                </p>
            </section>

        </div>

        {{-- BOX DE CONTATO --}}
        <div class="mt-12 p-6 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <h3 class="text-white font-bold text-base">Dúvidas sobre nossos Termos de Uso?</h3>
                <p class="text-slate-400 text-sm">Fale diretamente com nossa equipe jurídica e comercial.</p>
            </div>
            <a href="mailto:contato@aurosoft.com.br" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-sm transition shrink-0">
                contato@aurosoft.com.br
            </a>
        </div>

    </div>
</x-layouts.legal>
