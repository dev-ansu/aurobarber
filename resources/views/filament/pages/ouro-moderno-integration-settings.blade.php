<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Situação da integração{{ $this->currentSchoolName() ? ' — ' . $this->currentSchoolName() : '' }}
            </x-slot>
            @if ($this->integrationConfigured())
                <x-filament::badge color="success">Credenciais configuradas nesta unidade</x-filament::badge>
            @else
                <x-filament::badge color="warning">Configuração pendente nesta unidade</x-filament::badge>
            @endif
            <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                Cada unidade escolar possui sua própria integração Ouro Moderno independente (ex.: servidor local exposto via Tailscale Funnel). A URL e o token configurados aqui serão usados exclusivamente nos contratos, sincronizações e no auto-login do Totem de Presença desta unidade.
            </p>
        </x-filament::section>

        <x-filament::section>
            <form wire:submit="save" class="space-y-6">
                {{ $this->form }}

                @can('Update:OuroModernoIntegration')
                    <div class="flex justify-end">
                        <x-filament::button type="submit" icon="heroicon-o-check">Salvar configuração</x-filament::button>
                    </div>
                @endcan
            </form>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Importação disponível</x-slot>
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Salve alterações de URL ou token antes de testar a conexão ou importar. A importação atual consulta o catálogo de cursos do LMS e cria ou atualiza os cursos vinculados pelo código Ouro Moderno. Ela não cadastra alunos nem altera dados no LMS. Cursos locais que não apareçam na resposta são mantidos.
            </p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
