<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Avaliar Atendimento • {{ $appointment->barbershop?->name ?? 'AuroBarber' }}</title>
</head>
<body style="margin:0; padding:24px; min-height:100vh; background:#090d16; color:#f8fafc; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display:flex; align-items:center; justify-content:center; box-sizing:border-box;">
    @php
        $brandColor = $appointment->barbershop?->brand_color ?: '#d97706';
    @endphp
    <div style="width:100%; max-width:460px; background:#111827; border:1px solid rgba(255,255,255,0.08); border-radius:20px; padding:32px 26px; box-shadow:0 25px 50px -12px rgba(0,0,0,0.6); text-align:center;">
        @if($appointment->barbershop?->logo_url)
            <img src="{{ asset('storage/' . $appointment->barbershop->logo_url) }}" alt="{{ $appointment->barbershop->name }}" style="height:64px; width:auto; border-radius:12px; margin-bottom:14px; object-fit:contain;">
        @else
            <div style="width:56px; height:56px; border-radius:14px; background:{{ $brandColor }}; color:#090d16; font-weight:800; font-size:24px; display:inline-flex; align-items:center; justify-content:center; margin-bottom:14px;">
                ✂️
            </div>
        @endif

        <h1 style="margin:0 0 6px; font-size:22px; font-weight:800; color:#ffffff;">
            {{ $appointment->barbershop?->name ?? 'Barbearia' }}
        </h1>
        <p style="margin:0 0 22px; font-size:14px; color:#94a3b8;">
            Atendimento com <strong style="color:#f8fafc;">{{ $appointment->professional?->display_name ?? 'Barbeiro' }}</strong>
            • {{ $appointment->services_summary }}
        </p>

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
            @if($appointment->barbershop)
                <a href="{{ url('/' . $appointment->barbershop->slug) }}" style="display:inline-block; width:100%; box-sizing:border-box; padding:13px 18px; border-radius:12px; background:{{ $brandColor }}; color:#ffffff; font-weight:700; text-decoration:none; font-size:14px;">
                    Agendar Novo Horário
                </a>
            @endif
        @else
            <form method="POST" action="{{ route('appointment.rate.submit', $appointment->id) }}">
                @csrf
                <p style="margin:0 0 12px; font-size:14px; font-weight:600; color:#e2e8f0;">
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
                    <textarea name="rating_comment" rows="3" placeholder="Conte como foi sua experiência..." style="width:100%; box-sizing:border-box; background:#1e293b; border:1px solid rgba(255,255,255,0.12); border-radius:10px; padding:12px; color:#ffffff; font-size:14px; resize:none;"></textarea>
                </div>

                <button type="submit" style="width:100%; padding:14px 20px; border:none; border-radius:12px; background:{{ $brandColor }}; color:#ffffff; font-weight:700; font-size:15px; cursor:pointer;">
                    Enviar Avaliação ⭐
                </button>
            </form>

            <script>
                function selectStar(val) {
                    document.querySelectorAll('.star-icon').forEach(function(el) {
                        var starVal = parseInt(el.getAttribute('data-val'));
                        el.style.color = starVal <= val ? '#fbbf24' : '#334155';
                    });
                }
            </script>
        @endif
    </div>
</body>
</html>
