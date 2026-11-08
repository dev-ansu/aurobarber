<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $barbershop->name }} • Agendamento Online</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600,700,900&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --brand-color: {{ $barbershop->brand_color ?: '#d97706' }};
        }
        .bg-brand { background-color: var(--brand-color) !important; }
        .text-brand { color: var(--brand-color) !important; }
        .border-brand { border-color: var(--brand-color) !important; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased selection:bg-amber-500 selection:text-slate-950 min-h-screen flex flex-col justify-between"
      x-data="{
          selectedService: null,
          selectedBarber: null,
          selectedDate: '{{ date('Y-m-d') }}',
          selectedTime: '14:00',
          clientName: '',
          clientPhone: '',
          bookingOpen: false,
          openBooking(service) {
              this.selectedService = service;
              this.bookingOpen = true;
          },
          submitBooking() {
              if (!this.clientName || !this.clientPhone) {
                  alert('Por favor, informe seu nome e telefone.');
                  return;
              }
              const phone = '{{ $barbershop->formatted_whatsapp }}';
              const barberName = this.selectedBarber ? this.selectedBarber.name : 'Primeiro disponível';
              const msg = encodeURIComponent(`Olá! Gostaria de confirmar meu agendamento na *{{ $barbershop->name }}*:\n\n✂️ *Serviço:* ${this.selectedService.name} (R$ ${this.selectedService.price})\n💈 *Profissional:* ${barberName}\n📅 *Data:* ${this.selectedDate}\n⏰ *Horário:* ${this.selectedTime}\n👤 *Cliente:* ${this.clientName} (${this.clientPhone})`);
              
              window.open(`https://api.whatsapp.com/send?phone=${phone}&text=${msg}`, '_blank');
              this.bookingOpen = false;
          }
      }">

    {{-- CABEÇALHO DA BARBEARIA --}}
    <header class="bg-slate-900/90 border-b border-slate-800/80 backdrop-blur sticky top-0 z-40">
        <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if ($barbershop->logo_url)
                    <img src="{{ $barbershop->logo_url }}" alt="{{ $barbershop->name }}" class="h-10 w-10 rounded-full object-cover border border-slate-700" />
                @else
                    <div class="h-10 w-10 rounded-2xl bg-brand text-slate-950 flex items-center justify-center font-black text-lg">
                        {{ strtoupper(mb_substr($barbershop->name, 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h1 class="text-base sm:text-lg font-black text-white leading-tight">{{ $barbershop->name }}</h1>
                    @if ($barbershop->city)
                        <span class="text-xs text-slate-400">📍 {{ $barbershop->city }}</span>
                    @endif
                </div>
            </div>

            @if ($barbershop->formatted_whatsapp)
                <a href="https://api.whatsapp.com/send?phone={{ $barbershop->formatted_whatsapp }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-3.5 py-2 transition">
                    <span>WhatsApp</span>
                </a>
            @endif
        </div>
    </header>

    {{-- BANNER HERO DE APRESENTAÇÃO --}}
    <main class="max-w-4xl mx-auto px-4 py-8 w-full">
        <div class="rounded-3xl border border-slate-800 bg-slate-900/60 p-6 sm:p-8 mb-8 text-center relative overflow-hidden">
            <div class="pointer-events-none absolute -top-24 left-1/2 -translate-x-1/2 h-48 w-96 rounded-full bg-brand/20 blur-3xl opacity-50"></div>
            
            <h2 class="text-2xl sm:text-3xl font-black text-white mb-2 relative z-10">{{ $barbershop->headline ?: 'Agende seu horário online em segundos' }}</h2>
            <p class="text-sm text-slate-400 max-w-xl mx-auto relative z-10">{{ $barbershop->description ?: 'Escolha o serviço desejado, selecione seu barbeiro de preferência e garanta seu atendimento sem filas.' }}</p>
        </div>

        {{-- SEÇÃO DE PROFISSIONAIS --}}
        @if ($barbershop->professionals->isNotEmpty())
            <div class="mb-10">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                    <span>💈</span>
                    <span>Nossa Equipe</span>
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @foreach ($barbershop->professionals as $pro)
                        <div class="rounded-2xl border border-slate-800/80 bg-slate-900/40 p-4 text-center hover:border-slate-700 transition">
                            <div class="h-16 w-16 mx-auto rounded-full bg-slate-800 border-2 border-brand/50 mb-3 flex items-center justify-center font-bold text-lg text-white overflow-hidden">
                                @if ($pro->photo_url)
                                    <img src="{{ $pro->photo_url }}" alt="{{ $pro->name }}" class="h-full w-full object-cover" />
                                @else
                                    {{ strtoupper(mb_substr($pro->name, 0, 1)) }}
                                @endif
                            </div>
                            <h4 class="text-sm font-bold text-white">{{ $pro->display_name }}</h4>
                            <span class="text-[11px] text-slate-400 block">Barbeiro Especialista</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- LISTA DE SERVIÇOS & BOTÃO DE AGENDAR --}}
        <div>
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center gap-2">
                <span>✂️</span>
                <span>Serviços Disponíveis</span>
            </h3>

            <div class="space-y-3">
                @forelse ($barbershop->services as $service)
                    <div class="rounded-2xl border border-slate-800/90 bg-slate-900/80 p-4 sm:p-5 flex items-center justify-between gap-4 hover:border-slate-700 transition">
                        <div>
                            <h4 class="text-base font-bold text-white">{{ $service->name }}</h4>
                            @if ($service->description)
                                <p class="text-xs text-slate-400 mt-0.5">{{ $service->description }}</p>
                            @endif
                            <div class="flex items-center gap-3 mt-2 text-xs text-slate-400">
                                <span>⏱️ {{ $service->duration_minutes }} minutos</span>
                                <span>&middot;</span>
                                <span class="text-base font-black text-brand">{{ $service->formatted_price }}</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            @click="openBooking({ id: {{ $service->id }}, name: '{{ $service->name }}', price: '{{ number_format($service->price, 2, ',', '.') }}' })"
                            class="rounded-xl bg-brand hover:brightness-110 text-slate-950 font-black text-xs px-4 py-2.5 transition shrink-0 cursor-pointer shadow-lg"
                        >
                            Agendar
                        </button>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-slate-800 p-8 text-center text-slate-400 text-sm">
                        Nenhum serviço cadastrado no momento.
                    </div>
                @endforelse
            </div>
        </div>
    </main>

    {{-- MODAL DE CONFIRMAÇÃO DE AGENDAMENTO (ALPINE.JS) --}}
    <div x-show="bookingOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="bg-slate-900 border border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl relative"
             @click.outside="bookingOpen = false">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-black text-white">Confirmar Agendamento</h3>
                <button type="button" @click="bookingOpen = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="mb-4 p-3 rounded-xl bg-slate-950 border border-slate-800 text-sm">
                <span class="text-xs text-slate-400 block">Serviço Escolhido:</span>
                <span class="font-bold text-white" x-text="selectedService?.name"></span>
                <span class="text-brand font-black ml-2" x-text="'R$ ' + selectedService?.price"></span>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Seu Nome</label>
                    <input type="text" x-model="clientName" placeholder="Ex: Carlos Silva" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand" />
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Seu WhatsApp</label>
                    <input type="tel" x-model="clientPhone" placeholder="Ex: (85) 99999-9999" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none focus:border-brand" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Data</label>
                        <input type="date" x-model="selectedDate" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Horário</label>
                        <input type="time" x-model="selectedTime" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:border-brand" />
                    </div>
                </div>

                <button
                    type="button"
                    @click="submitBooking()"
                    class="w-full mt-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black py-3 text-xs uppercase tracking-wider transition shadow-lg flex items-center justify-center gap-2 cursor-pointer"
                >
                    <span>Confirmar no WhatsApp</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- FOOTER DISCRETO --}}
    <footer class="py-6 border-t border-slate-900 text-center text-xs text-slate-500">
        <p>Tecnologia <a href="https://barber.aurosoft.com.br" class="text-slate-400 hover:text-white font-bold">AuroBarber</a> &middot; Sistema para Barbearias</p>
    </footer>

</body>
</html>
