<x-filament-panels::page>
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
        {{-- Banner de Alerta de Segurança e Boas-Vindas --}}
        <div class="flex flex-col gap-4 rounded-xl border border-warning-500/30 bg-warning-50 p-5 shadow-sm dark:bg-warning-500/10 sm:flex-row sm:items-center">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-warning-500/40 bg-warning-100 text-warning-600 dark:bg-warning-500/20 dark:text-warning-400">
                <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-6 w-6" />
            </div>
            <div class="flex-1 space-y-1">
                <h3 class="text-base font-bold text-gray-950 dark:text-white">Primeiro Acesso ao AuroBarber Detectado</h3>
                <p class="text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                    Você está autenticado utilizando uma <strong>senha temporária provisória</strong>. Para garantir a segurança total da sua barbearia e a confidencialidade dos dados dos seus clientes e finanças, defina agora sua senha definitiva de acesso.
                </p>
            </div>
        </div>

        {{-- Formulário Filament Completo em Abas e 100% de Largura --}}
        <form wire:submit="save" class="flex w-full flex-col gap-6">
            <div class="w-full">
                {{ $this->form }}
            </div>

            <div class="flex items-center justify-end gap-4 border-t border-gray-200 pt-4 dark:border-white/10">
                <x-filament::button
                    type="submit"
                    color="primary"
                    size="lg"
                    icon="heroicon-o-check"
                >
                    Salvar Nova Senha e Acessar Painel
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
