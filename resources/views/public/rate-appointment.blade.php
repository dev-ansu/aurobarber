@php
    $barbershop = $appointment->barbershop;
    $brandColor = $barbershop?->brand_color ?: '#d97706';
@endphp

<x-layouts.auth-card :title="'Avaliar Atendimento • ' . ($barbershop?->name ?? 'AuroBarber')" maxWidth="460px">
    <x-slot:icon>
        @if($barbershop?->logo_url)
            <img src="{{ asset('storage/' . $barbershop->logo_url) }}" alt="{{ $barbershop->name }}" style="height:48px; width:48px; border-radius:12px; object-fit:cover;">
        @else
            ✂️
        @endif
    </x-slot:icon>

    <x-slot:heading>
        {{ $barbershop?->name ?? 'Barbearia' }}
    </x-slot:heading>

    <x-slot:subtitle>
        Atendimento com <strong>{{ $appointment->professional?->display_name ?? 'Barbeiro' }}</strong><br>
        <span style="font-size:12px; color:#94a3b8;">{{ $appointment->services_summary }}</span>
    </x-slot:subtitle>

    @if(session('rated_success') || $appointment->rating)
        <div style="background:rgba(16,185,129,0.12); border:1px solid rgba(16,185,129,0.3); border-radius:14px; padding:22px 16px; margin-bottom:20px;">
            <div style="font-size:28px; margin-bottom:8px;">
                {{ str_repeat('⭐', (int) ($appointment->rating ?: 5)) }}
            </div>
            <div style="font-size:16px; font-weight:700; color:#34d399; margin-bottom:4px;">
                Obrigado pela sua avaliação!
            </div>
            <div style="font-size:13px; color:#94a3b8;">
                Sua opinião ajuda a manter nosso padrão de excelência.
            </div>
        </div>

        @if($barbershop)
            <a href="{{ url('/' . $barbershop->slug) }}" class="btn-submit" style="display:inline-block; text-decoration:none; background:{{ $brandColor }}; color:#ffffff;">
                Agendar Novo Horário
            </a>
        @endif
    @else
        <form method="POST" action="{{ route('appointment.rate.submit', $appointment->id) }}">
            @csrf
            {{-- Campo Honeypot anti-spam invisível --}}
            <div style="display:none; position:absolute; left:-9999px;">
                <input type="text" name="website_url" tabindex="-1" autocomplete="off">
            </div>

            <p style="margin:0 0 14px; font-size:14px; font-weight:600; color:#e2e8f0;">
                Que nota você dá para o seu atendimento hoje?
            </p>

            <div id="star-container" style="display:flex; justify-content:center; gap:10px; margin-bottom:20px;">
                @for($i = 1; $i <= 5; $i++)
                    <label style="cursor:pointer; font-size:34px; transition:transform 0.15s;" onclick="selectStar({{ $i }})">
                        <input type="radio" name="rating" value="{{ $i }}" {{ $i === 5 ? 'checked' : '' }} style="display:none;">
                        <span class="star-icon" data-val="{{ $i }}" style="color:#fbbf24;">★</span>
                    </label>
                @endfor
            </div>

            <div style="text-align:left; margin-bottom:18px;">
                <label style="display:block; font-size:12px; font-weight:600; color:#94a3b8; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.05em;">
                    Comentário ou Elogio (Opcional)
                </label>
                <textarea name="rating_comment" rows="3" placeholder="Conte como foi sua experiência..." style="width:100%; box-sizing:border-box; background:#0f172a; border:1px solid #334155; border-radius:10px; padding:12px; color:#ffffff; font-size:14px; resize:none;">{{ old('rating_comment') }}</textarea>
            </div>

            <x-turnstile action="rating" theme="dark" class="mb-4" />

            <button type="submit" class="btn-submit" style="background:{{ $brandColor }}; color:#ffffff;">
                Enviar Avaliação ⭐
            </button>
        </form>

        <x-slot:scripts>
            <script>
                function selectStar(val) {
                    document.querySelectorAll('.star-icon').forEach(function(el) {
                        var starVal = parseInt(el.getAttribute('data-val'));
                        el.style.color = starVal <= val ? '#fbbf24' : '#334155';
                    });
                }
            </script>
        </x-slot:scripts>
    @endif
</x-layouts.auth-card>
