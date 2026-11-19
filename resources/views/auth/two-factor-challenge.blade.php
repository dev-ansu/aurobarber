<x-layouts.auth-card title="Verificação de Segurança • AuroBarber" maxWidth="440px">
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
        </svg>
    </x-slot:icon>

    <x-slot:heading>
        Código de Segurança
    </x-slot:heading>

    <x-slot:subtitle>
        Para sua segurança, enviamos um código de 6 dígitos para o e-mail <strong>{{ $maskedEmail }}</strong>.
    </x-slot:subtitle>

    @if (session('warning'))
        <div class="alert alert-warning">
            ⏳ {{ session('warning') }}
        </div>
    @endif

    <form action="{{ route('auth.2fa.verify') }}" method="POST">
        @csrf
        <div class="input-group">
            <label for="code" class="input-label" style="text-align: center;">Digite o código recebido</label>
            <input 
                type="text" 
                id="code" 
                name="code" 
                class="code-input" 
                maxlength="6" 
                pattern="[0-9]*" 
                inputmode="numeric" 
                autocomplete="one-time-code" 
                placeholder="••••••" 
                autofocus 
                required
            >
        </div>

        <div class="remember-wrapper" style="display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 24px; cursor: pointer; user-select: none;">
            <input type="checkbox" id="remember_device" name="remember_device" value="1" checked style="accent-color: #f59e0b; width: 18px; height: 18px; cursor: pointer;">
            <label for="remember_device" style="font-size: 13px; color: #cbd5e1; cursor: pointer;">Lembrar deste dispositivo por 30 dias</label>
        </div>

        <button type="submit" class="btn-submit">
            Confirmar e Entrar
        </button>
    </form>

    <x-slot:footer>
        <form action="{{ route('auth.2fa.resend') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="footer-link">
                Reenviar código
            </button>
        </form>

        <form action="{{ route('auth.2fa.cancel') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" class="footer-link" style="color: #ef4444;">
                Voltar ao Login
            </button>
        </form>
    </x-slot:footer>
</x-layouts.auth-card>
