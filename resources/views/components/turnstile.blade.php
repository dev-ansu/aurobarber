@props([
    'action' => 'generic',
    'theme' => 'dark',
    'size' => 'normal',
    'callback' => null,
    'expiredCallback' => null,
])

@php
    $siteKey = config('services.turnstile.site_key');
@endphp

@if ($siteKey)
    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce

    <div
        class="cf-turnstile"
        data-sitekey="{{ $siteKey }}"
        data-action="{{ $action }}"
        data-theme="{{ $theme }}"
        data-size="{{ $size }}"
        @if($callback) data-callback="{{ $callback }}" @endif
        @if($expiredCallback) data-expired-callback="{{ $expiredCallback }}" @endif
        {{ $attributes->merge(['class' => 'my-2 flex justify-center']) }}
    ></div>
@elseif(app()->environment('local', 'testing'))
    <div {{ $attributes->merge(['class' => 'my-2 p-2 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs text-center']) }}>
        <span class="font-bold">Turnstile (Ambiente de Teste):</span> Chave pública não configurada no <code>.env</code>.
    </div>
@endif
