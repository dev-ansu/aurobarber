<div 
    x-data="{ 
        isInstalled: false, 
        showModal: false,
        hasPrompt: false,
        dismissed: false,
        copied: false,
        init() {
            if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
                this.isInstalled = true;
            }
            if (localStorage.getItem('aurobarber_pwa_dismissed') === 'true') {
                this.dismissed = true;
            }
            window.addEventListener('pwa-installable', () => {
                this.hasPrompt = true;
            });
            window.addEventListener('appinstalled', () => {
                this.isInstalled = true;
                this.showModal = false;
            });
        },
        handleInstallClick() {
            if (window.deferredPWAInstallPrompt) {
                window.deferredPWAInstallPrompt.prompt();
                window.deferredPWAInstallPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        this.isInstalled = true;
                    }
                    window.deferredPWAInstallPrompt = null;
                });
            } else {
                this.showModal = true;
            }
        },
        copyUrl() {
            navigator.clipboard.writeText(window.location.origin + '/admin').then(() => {
                this.copied = true;
                setTimeout(() => this.copied = false, 2500);
            });
        },
        dismiss() {
            this.dismissed = true;
            localStorage.setItem('aurobarber_pwa_dismissed', 'true');
        }
    }"
    x-cloak
    x-show="!isInstalled && !dismissed"
    class="fixed bottom-4 right-4 z-40"
>
    <!-- Botão Discreto de Atalho / Instalação -->
    <div class="relative group">
        <button 
            type="button"
            @click="handleInstallClick()"
            class="flex items-center gap-2.5 px-3.5 py-2 rounded-2xl bg-slate-900/95 dark:bg-gray-800/95 hover:bg-black dark:hover:bg-gray-700 text-white shadow-xl shadow-black/25 border border-amber-500/50 backdrop-blur transition transform hover:scale-105 active:scale-95 text-xs font-semibold"
            title="Instalar AuroBarber na Área de Trabalho"
        >
            <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gradient-to-tr from-amber-500 to-amber-300 text-slate-950 text-xs font-black shadow-sm">
                A
            </span>
            <div class="text-left leading-tight pr-1">
                <span class="block text-amber-400 font-bold">Instalar Aplicativo</span>
                <span class="block text-[10px] text-gray-300 dark:text-gray-400">Atalho na Área de Trabalho</span>
            </div>
        </button>

        <!-- Botão fechar (X) -->
        <button 
            type="button"
            @click.stop="dismiss()"
            class="absolute -top-1.5 -right-1.5 h-4 w-4 rounded-full bg-gray-700 hover:bg-gray-600 text-gray-300 flex items-center justify-center text-[10px] border border-gray-600 leading-none shadow"
            title="Ocultar este aviso"
        >
            ×
        </button>
    </div>

    <!-- Modal de Orientação para Instalação no Computador -->
    <div 
        x-show="showModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
        @click.self="showModal = false"
    >
        <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-2xl overflow-hidden p-6 text-gray-900 dark:text-gray-100 text-left">
            <!-- Header do Modal -->
            <div class="flex items-start justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-tr from-amber-500 via-amber-400 to-amber-300 text-slate-950 font-black text-2xl shadow-md shadow-amber-500/20">
                        A
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Criar Atalho do AuroBarber</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Instale como aplicativo nativo na sua Área de Trabalho</p>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click="showModal = false" 
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-lg p-1"
                >
                    ✕
                </button>
            </div>

            <!-- Conteúdo do Modal -->
            <div class="py-4 space-y-3.5 text-xs">
                <p class="text-gray-600 dark:text-gray-300 text-xs">
                    Instale o AuroBarber como aplicativo pelo navegador. O atalho abre o sistema em uma janela própria, sem abas e sem a barra de endereço:
                </p>

                <!-- Passo a passo: Microsoft Edge -->
                <div class="p-3.5 rounded-xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-800/60">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 rounded font-black text-[11px] bg-blue-600 text-white">Edge</span>
                        <span class="font-bold text-blue-950 dark:text-blue-200 text-xs">Recomendado no Windows</span>
                    </div>
                    <ol class="space-y-1.5 text-gray-700 dark:text-gray-300 pl-4 list-decimal">
                        <li>Clique no menu <strong>···</strong> (3 pontinhos no canto superior direito do Edge).</li>
                        <li>Passe o mouse em <strong>Aplicativos</strong> (Apps).</li>
                        <li>Clique em <strong>"Instalar este site como um aplicativo"</strong> (ou <em>Instalar AuroBarber</em>).</li>
                        <li>Clique em <strong>Instalar</strong>.</li>
                    </ol>
                    <p class="mt-2 text-[11px] text-blue-800 dark:text-blue-300 font-medium">
                        ✓ O atalho com o ícone oficial será fixado na Área de Trabalho e no Menu Iniciar automaticamente!
                    </p>
                </div>

                <!-- Passo a passo: Google Chrome -->
                <div class="p-3.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800/60">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 rounded font-black text-[11px] bg-emerald-600 text-white">Chrome</span>
                        <span class="font-bold text-emerald-950 dark:text-emerald-200 text-xs">Google Chrome</span>
                    </div>
                    <ol class="space-y-1.5 text-gray-700 dark:text-gray-300 pl-4 list-decimal">
                        <li>Clique no menu <strong>⋮</strong> (3 pontinhos no canto superior direito do Chrome).</li>
                        <li>Passe o mouse em <strong>Salvar e compartilhar</strong> (ou <em>Mais ferramentas</em>).</li>
                        <li>Clique em <strong>"Criar atalho..."</strong> ou <strong>"Instalar AuroBarber"</strong>.</li>
                        <li><strong>Importante:</strong> Marque a caixinha <strong>☑ "Abrir como janela"</strong> e clique em <strong>Criar</strong>.</li>
                    </ol>
                </div>

                <!-- Atalho de Copiar URL do Servidor -->
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700/60">
                    <span class="text-[11px] text-gray-600 dark:text-gray-400 truncate mr-2" x-text="window.location.origin + '/admin'"></span>
                    <button 
                        type="button"
                        @click="copyUrl()"
                        class="shrink-0 px-3 py-1 rounded-lg bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 font-bold text-[11px] transition"
                    >
                        <span x-text="copied ? '✓ Copiado!' : 'Copiar Link'"></span>
                    </button>
                </div>
            </div>

            <!-- Rodapé do Modal -->
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <button 
                    type="button" 
                    @click="showModal = false" 
                    class="px-5 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-bold transition shadow-sm"
                >
                    Entendi, obrigado!
                </button>
            </div>
        </div>
    </div>
</div>
