@props([
    'activeSaasPlans' => collect(),
    'commercialWhatsapp' => env('AUROSOFT_COMMERCIAL_WHATSAPP', '5585999999999'),
])

<script>
    window.onLeadTurnstileSuccess = function(token) {
        window.dispatchEvent(new CustomEvent('turnstile-lead-success', { detail: token }));
    };
    window.onLeadTurnstileExpired = function() {
        window.dispatchEvent(new CustomEvent('turnstile-lead-expired'));
    };

    function landingLeadForm() {
        return {
            name: '',
            barbershop_name: '',
            phone: '',
            email: '',
            city: '',
            chairs_count: '2 a 4 Barbeiros',
            message: '',
            websiteUrl: '',
            turnstileToken: '{{ config('services.turnstile.site_key') ? '' : 'bypass' }}',
            submitting: false,
            success: false,
            errorMessage: '',
            init() {
                window.addEventListener('turnstile-lead-success', (e) => {
                    this.turnstileToken = e.detail;
                });
                window.addEventListener('turnstile-lead-expired', () => {
                    this.turnstileToken = '';
                });
            },
            async submitForm() {
                this.errorMessage = '';

                @if(config('services.turnstile.site_key'))
                if (!this.turnstileToken) {
                    const input = document.querySelector('[name="cf-turnstile-response"]');
                    if (input && input.value) {
                        this.turnstileToken = input.value;
                    }
                }
                if (!this.turnstileToken) {
                    this.errorMessage = 'Por favor, complete a verificação de segurança antes de prosseguir.';
                    return;
                }
                @endif

                this.submitting = true;

                try {
                    const res = await fetch('{{ route('landing.lead.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            name: this.name,
                            barbershop_name: this.barbershop_name,
                            phone: this.phone,
                            email: this.email,
                            city: this.city,
                            chairs_count: this.chairs_count,
                            selected_plan: (this.$data && this.$data.selectedPlanName) || (window.selectedPlanName || 'Demonstração Geral'),
                            selected_plan_id: (this.$data && this.$data.selectedPlanId) || (window.selectedPlanId || null),
                            message: this.message,
                            website_url: this.websiteUrl,
                            turnstile_token: this.turnstileToken
                        })
                    });

                    const data = await res.json();
                    this.submitting = false;

                    if (data && data.success) {
                        this.success = true;
                    } else {
                        this.errorMessage = (data && data.message) ? data.message : 'Erro ao enviar. Tente novamente ou chame no WhatsApp.';
                        if (window.turnstile && typeof window.turnstile.reset === 'function') {
                            try { window.turnstile.reset(); } catch(e){}
                            this.turnstileToken = '';
                        }
                    }
                } catch(err) {
                    this.submitting = false;
                    this.errorMessage = 'Erro ao conectar ao servidor. Chame no WhatsApp.';
                }
            }
        };
    }
</script>

<div
    x-show="leadModalOpen"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div
        @click.away="leadModalOpen = false"
        class="relative w-full max-w-lg rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-2xl text-left"
        x-data="landingLeadForm()"
    >
        <button
            type="button"
            @click="leadModalOpen = false"
            class="absolute top-5 right-5 text-slate-400 hover:text-white text-xl p-1 font-bold"
        >
            ✕
        </button>

        {{-- Tela de Sucesso --}}
        <div x-show="success" class="text-center py-6 space-y-4">
            <div class="w-16 h-16 mx-auto rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-3xl font-bold">
                ✓
            </div>
            <h3 class="text-xl font-black text-white">Solicitação Recebida com Sucesso!</h3>
            <p class="text-xs sm:text-sm text-slate-300 max-w-md mx-auto">
                Recebemos seus dados com sucesso. Nossa equipe comercial já foi notificada e entrará em contato em breve para liberar seu acesso demonstrativo de 7 dias.
            </p>
            <div class="pt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                <button
                    type="button"
                    @click="leadModalOpen = false; success = false;"
                    class="w-full sm:w-auto px-6 py-3 rounded-xl bg-amber-500 bg-gradient-to-r from-amber-500 to-amber-400 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs transition shadow-lg shadow-amber-500/25 active:scale-95 cursor-pointer"
                >
                    Concluir
                </button>
                <a
                    :href="'https://wa.me/{{ $commercialWhatsapp }}?text=' + encodeURIComponent('Olá! Acabei de me cadastrar no site do AuroBarber. Meu nome é ' + name + ' da barbearia ' + barbershop_name + '. Gostaria de tirar algumas dúvidas!')"
                    target="_blank"
                    rel="noopener"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-slate-700 bg-slate-800 text-emerald-400 font-bold text-xs hover:bg-slate-700 hover:text-emerald-300 transition"
                >
                    <span>💬</span>
                    <span>Falar no WhatsApp agora (Opcional)</span>
                </a>
            </div>
        </div>

        {{-- Formulário Normal --}}
        <div x-show="!success" class="space-y-5">
            <div>
                <span class="text-xs font-bold text-amber-400 uppercase tracking-widest block">Ambiente de Demonstração</span>
                <h3 class="text-xl font-black text-white mt-1">Experimente o AuroBarber na sua barbearia</h3>
                <p class="text-xs text-slate-400 mt-1" x-text="selectedPlanName ? 'Plano Selecionado: ' + selectedPlanName : '7 dias de teste grátis com suporte dedicado'"></p>
            </div>

            <div x-show="errorMessage" class="p-3 rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs">
                <span x-text="errorMessage"></span>
            </div>

            <form @submit.prevent="submitForm()" class="space-y-3.5">
                <div>
                    <label class="text-[11px] font-bold text-slate-300 block mb-1">Seu Nome Completo *</label>
                    <input
                        type="text"
                        x-model="name"
                        required
                        placeholder="Ex: Carlos Eduardo"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-xs focus:outline-none focus:border-amber-500"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-slate-300 block mb-1">Nome da Barbearia *</label>
                        <input
                            type="text"
                            x-model="barbershop_name"
                            required
                            placeholder="Ex: Barbearia Vip"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-xs focus:outline-none focus:border-amber-500"
                        />
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-slate-300 block mb-1">WhatsApp com DDD *</label>
                        <input
                            type="text"
                            x-model="phone"
                            required
                            placeholder="(00) 00000-0000"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-xs focus:outline-none focus:border-amber-500"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold text-slate-300 block mb-1">Seu E-mail *</label>
                        <input
                            type="email"
                            x-model="email"
                            required
                            placeholder="contato@barbearia.com"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-xs focus:outline-none focus:border-amber-500"
                        />
                    </div>
                    <div>
                        <label class="text-[11px] font-bold text-slate-300 block mb-1">Cidade / UF *</label>
                        <input
                            type="text"
                            x-model="city"
                            placeholder="Ex: Fortaleza / CE"
                            class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white placeholder-slate-500 text-xs focus:outline-none focus:border-amber-500"
                        />
                    </div>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-slate-300 block mb-1">Quantos Barbeiros / Cadeiras?</label>
                    <select
                        x-model="chairs_count"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:outline-none focus:border-amber-500"
                    >
                        <option value="1 (Trabalho Sozinho)">1 (Trabalho Sozinho)</option>
                        <option value="2 a 4 Barbeiros" selected>2 a 4 Barbeiros</option>
                        <option value="5 a 8 Barbeiros">5 a 8 Barbeiros</option>
                        <option value="Mais de 8 Barbeiros">Mais de 8 Barbeiros / Rede</option>
                    </select>
                </div>

                {{-- Campo Honeypot anti-spam invisível --}}
                <div style="display:none; position:absolute; left:-9999px;">
                    <input type="text" x-model="websiteUrl" name="website_url" tabindex="-1" autocomplete="off" />
                </div>

                {{-- Cloudflare Turnstile anti-bot --}}
                @if(config('services.turnstile.site_key'))
                    <div class="my-2 flex justify-center">
                        <div class="cf-turnstile"
                             data-sitekey="{{ config('services.turnstile.site_key') }}"
                             data-action="landing_lead"
                             data-theme="dark"
                             data-callback="onLeadTurnstileSuccess"
                             data-expired-callback="onLeadTurnstileExpired"></div>
                    </div>
                @endif

                <button
                    type="submit"
                    :disabled="submitting"
                    class="w-full py-3.5 rounded-xl bg-amber-500 bg-gradient-to-r from-amber-500 via-amber-400 to-amber-500 hover:from-amber-400 hover:to-amber-300 text-slate-950 font-black text-xs transition shadow-lg shadow-amber-500/25 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50 active:scale-95"
                >
                    <span x-show="!submitting">🚀 Liberar Meu Acesso Grátis</span>
                    <span x-show="submitting">Processando...</span>
                </button>

                <p class="text-[10px] text-slate-500 text-center">
                    Sem necessidade de cartão de crédito. Seus dados estão 100% seguros.
                </p>
            </form>
        </div>
    </div>
</div>
