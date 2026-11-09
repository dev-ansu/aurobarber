<x-filament-panels::page>
    <div class="mx-auto w-full max-w-2xl space-y-6">
        <x-filament::section>
            <x-slot name="heading">WhatsApp oficial do suporte</x-slot>
            <x-slot name="description">
                Este contato será mostrado aos clientes quando a licença da barbearia estiver suspensa. Sem número configurado, o botão de WhatsApp ficará oculto.
            </x-slot>

            <form wire:submit="save" class="space-y-5">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="tel"
                        wire:model="supportPhone"
                        placeholder="Ex.: 5511999999999"
                    />
                </x-filament::input.wrapper>
                @error('supportPhone')
                    <p class="text-sm text-danger-600">{{ $message }}</p>
                @enderror
                <p class="text-sm text-gray-500">
                    Digite o DDI e o DDD quando aplicável. Nenhum número fictício será usado como alternativa.
                </p>
                <x-filament::button type="submit">Salvar contato</x-filament::button>
            </form>
        </x-filament::section>
    </div>
</x-filament-panels::page>
