<div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans select-none antialiased"
     x-on:hotspot-auto-advance.window="setTimeout(() => { $wire.nextStep(); }, 1000);"
     x-data="{
         elapsedSeconds: @entangle('timeSpentSeconds'),
         isExpanded: false,
         isFullscreen: false,
         isMiniPlayer: false,
         miniPlayerWidth: 'large',
         fontSizeLevel: 1,
         highContrast: false,
         pipWindow: null,
         formatTimer(totalSeconds) {
             const h = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
             const m = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
             const s = String(totalSeconds % 60).padStart(2, '0');
             return `${h}:${m}:${s}`;
         },
         toggleExpanded() {
             this.isExpanded = !this.isExpanded;
         },
         toggleFontSize() {
             this.fontSizeLevel = (this.fontSizeLevel + 1) % 4;
             this.syncPipStyles();
         },
         toggleHighContrast() {
             this.highContrast = !this.highContrast;
             this.syncPipStyles();
         },
         cycleWidth() {
             if (this.miniPlayerWidth === 'normal') this.miniPlayerWidth = 'large';
             else if (this.miniPlayerWidth === 'large') this.miniPlayerWidth = 'wide';
             else this.miniPlayerWidth = 'normal';
             if (this.pipWindow) {
                 const targetW = this.miniPlayerWidth === 'wide' ? 740 : (this.miniPlayerWidth === 'large' ? 580 : 440);
                 try { this.pipWindow.resizeTo(targetW, this.pipWindow.outerHeight || 320); } catch (e) {}
             }
         },
         syncPipStyles() {
             if (!this.pipWindow) return;
             const w = this.pipWindow.document.getElementById('aurosoft-mini-player-widget');
             const bodyEl = this.pipWindow.document.getElementById('aurosoft-mini-player-body');
             const cardEl = this.pipWindow.document.getElementById('aurosoft-mini-player-card');
             if (bodyEl) {
                 bodyEl.className = 'flex-1 overflow-y-auto space-y-2 pr-1 ' + (
                     this.fontSizeLevel === 0 ? 'text-xs leading-relaxed' :
                     this.fontSizeLevel === 1 ? 'text-sm leading-relaxed' :
                     this.fontSizeLevel === 2 ? 'text-base font-medium leading-relaxed' :
                     'text-lg font-bold leading-relaxed'
                 );
             }
             if (w) {
                 if (this.highContrast) {
                     w.style.backgroundColor = '#000000';
                     w.style.color = '#fde047';
                     w.style.borderColor = '#facc15';
                 } else {
                     w.style.backgroundColor = '#0f172a';
                     w.style.color = '#f1f5f9';
                     w.style.borderColor = 'rgba(16, 185, 129, 0.8)';
                 }
             }
             if (cardEl) {
                 if (this.highContrast) {
                     cardEl.style.backgroundColor = '#000000';
                     cardEl.style.borderColor = '#facc15';
                     cardEl.style.color = '#fef08a';
                 } else {
                     cardEl.style.backgroundColor = 'rgba(2, 6, 23, 0.85)';
                     cardEl.style.borderColor = '#1e293b';
                     cardEl.style.color = '#e2e8f0';
                 }
             }
         },
         returnWidgetFromPip() {
             if (!this.pipWindow) return;
             const widget = this.pipWindow.document.getElementById('aurosoft-mini-player-widget');
             const slot = document.getElementById('aurosoft-mini-player-slot');
             if (slot && widget) {
                 widget.style.backgroundColor = '';
                 widget.style.color = '';
                 widget.style.borderColor = '';
                 slot.appendChild(widget);
             }
             try { this.pipWindow.close(); } catch (e) {}
             this.pipWindow = null;
         },
         async openMiniPlayerPip() {
             this.isMiniPlayer = true;
             await this.$nextTick();
             if (!this.pipWindow && 'documentPictureInPicture' in window) {
                 await this.toggleAlwaysOnTop();
             }
         },
         async toggleAlwaysOnTop() {
             if ('documentPictureInPicture' in window) {
                 if (this.pipWindow) {
                     this.returnWidgetFromPip();
                     return;
                 }
                 try {
                     const widget = document.getElementById('aurosoft-mini-player-widget');
                     const targetW = this.miniPlayerWidth === 'wide' ? 720 : (this.miniPlayerWidth === 'large' ? 560 : 430);
                     const measuredH = widget && widget.offsetHeight > 100 ? widget.offsetHeight + 8 : 290;
                     const targetH = Math.max(240, Math.min(420, measuredH));

                     const pip = await window.documentPictureInPicture.requestWindow({
                         width: targetW,
                         height: targetH,
                     });
                     this.pipWindow = pip;

                     [...document.styleSheets].forEach((sheet) => {
                         try {
                             const css = [...sheet.cssRules].map(r => r.cssText).join('');
                             const style = pip.document.createElement('style');
                             style.textContent = css;
                             pip.document.head.appendChild(style);
                         } catch(e) {
                             const link = pip.document.createElement('link');
                             link.rel = 'stylesheet';
                             link.type = sheet.type;
                             link.media = sheet.media;
                             link.href = sheet.href;
                             pip.document.head.appendChild(link);
                         }
                     });

                     const pipCustomStyle = pip.document.createElement('style');
                     pipCustomStyle.textContent = `
                         html, body {
                             margin: 0 !important;
                             padding: 0 !important;
                             width: 100% !important;
                             height: 100% !important;
                             overflow: hidden !important;
                             background: #0f172a !important;
                         }
                         #aurosoft-mini-player-widget {
                             position: static !important;
                             inset: auto !important;
                             bottom: auto !important;
                             right: auto !important;
                             width: 100% !important;
                             max-width: 100% !important;
                             height: 100vh !important;
                             margin: 0 !important;
                             border-radius: 0 !important;
                             border: 2px solid rgba(16, 185, 129, 0.7) !important;
                             display: flex !important;
                             flex-direction: column !important;
                             justify-content: space-between !important;
                             box-sizing: border-box !important;
                             padding: 14px 16px !important;
                         }
                         #aurosoft-mini-player-body {
                             flex: 1 1 auto !important;
                             max-height: none !important;
                             overflow-y: auto !important;
                             display: flex !important;
                             flex-direction: column !important;
                         }
                         #aurosoft-mini-player-card {
                             flex: 1 1 auto !important;
                             display: flex !important;
                             flex-direction: column !important;
                         }
                     `;
                     pip.document.head.appendChild(pipCustomStyle);

                     pip.document.body.className = 'bg-slate-900 text-slate-100 antialiased font-sans select-text';
                     if (widget) {
                         pip.document.body.appendChild(widget);
                     }

                     pip.document.addEventListener('click', (e) => {
                         const btn = e.target.closest('[data-pip-action]');
                         if (!btn) return;
                         e.preventDefault();
                         e.stopPropagation();
                         const action = btn.getAttribute('data-pip-action');
                         if (action === 'toggle-pip' || action === 'expand') {
                             this.returnWidgetFromPip();
                         } else if (action === 'close') {
                             this.returnWidgetFromPip();
                             this.isMiniPlayer = false;
                         } else if (action === 'font-size') {
                             this.toggleFontSize();
                         } else if (action === 'contrast') {
                             this.toggleHighContrast();
                         } else if (action === 'cycle-width') {
                             this.cycleWidth();
                         } else if (action === 'request-help') {
                             $wire.requestHelp();
                         } else if (action === 'cancel-help') {
                             $wire.cancelHelpRequest();
                         } else if (action === 'exit-lesson') {
                             this.returnWidgetFromPip();
                             $wire.exitLesson();
                         } else if (action === 'complete-challenge') {
                             this.returnWidgetFromPip();
                             this.isMiniPlayer = false;
                             $wire.completeChallenge();
                         } else if (action === 'complete-theory') {
                             this.returnWidgetFromPip();
                             $wire.completeTheoryStep();
                         } else if (action === 'next-step') {
                             this.returnWidgetFromPip();
                             $wire.nextStep();
                         }
                     });

                     pip.addEventListener('pagehide', () => {
                         const slot = document.getElementById('aurosoft-mini-player-slot');
                         if (slot && widget && widget.parentElement !== slot) {
                             widget.style.backgroundColor = '';
                             widget.style.color = '';
                             widget.style.borderColor = '';
                             slot.appendChild(widget);
                         }
                         this.pipWindow = null;
                     });
                 } catch(err) {
                     console.error('Falha ao abrir Picture-in-Picture:', err);
                 }
             } else {
                 alert('Seu navegador não possui suporte ao modo flutuante nativo (Document Picture-in-Picture). Recomendamos usar o Google Chrome, Edge ou Brave para manter a janela flutuante sobre outros programas do computador.');
             }
         },
         toggleFullscreen() {
             if (!document.fullscreenElement) {
                 document.documentElement.requestFullscreen().catch(() => {});
                 this.isFullscreen = true;
             } else {
                 if (document.exitFullscreen) {
                     document.exitFullscreen().catch(() => {});
                 }
                 this.isFullscreen = false;
             }
         }
     }"
     @fullscreenchange.window="isFullscreen = !!document.fullscreenElement"
     x-init="
         let focusLostTimer = null;
         let focusLostTimestamp = null;

         setInterval(() => {
             elapsedSeconds += 1;
             if (elapsedSeconds % 15 === 0) {
                 $wire.incrementTimeSpent(15);
             }
         }, 1000);

         const handleFocusLoss = () => {
             if (@js($isPreview) || !@js($progressId) || isMiniPlayer || pipWindow) return;
             if (!focusLostTimestamp) {
                 focusLostTimestamp = Date.now();
             }
             if (!focusLostTimer) {
                 focusLostTimer = setTimeout(() => {
                     if (document.hidden || !document.hasFocus()) {
                         $wire.reportFocusLoss();
                     }
                 }, 15000);
             }
         };

         const handleFocusGain = () => {
             if (focusLostTimer) {
                 clearTimeout(focusLostTimer);
                 focusLostTimer = null;
             }
             if (focusLostTimestamp) {
                 const awaySec = Math.round((Date.now() - focusLostTimestamp) / 1000);
                 focusLostTimestamp = null;
                 if (awaySec >= 15 && !@js($isPreview) && @js($progressId)) {
                     $wire.reportFocusGain(awaySec);
                 }
             }
         };

         document.addEventListener('visibilitychange', () => {
             if (document.hidden) handleFocusLoss();
             else handleFocusGain();
         });
         window.addEventListener('blur', handleFocusLoss);
         window.addEventListener('focus', handleFocusGain);

         window.addEventListener('beforeunload', () => {
             if (@js($progressId) && !@js($isPreview)) {
                 navigator.sendBeacon('/lms/player/beacon-exit/' + @js($progressId));
             }
         });
     "
>
    {{-- MODAL DE COMEMORAÇÃO DE CONQUISTAS (NOTA 100% / CONCLUSÃO DE MÓDULO) --}}
    @if($showCelebrationModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-md animate-fade-in">
            <div class="relative max-w-md w-full overflow-hidden rounded-3xl border-2 border-amber-400/50 bg-gradient-to-b from-slate-900 via-slate-900 to-indigo-950/90 p-8 text-center shadow-2xl">
                <div class="mx-auto mb-5 flex h-24 w-24 items-center justify-center rounded-3xl bg-amber-400/15 border border-amber-400/40 text-5xl shadow-lg shadow-amber-500/20 animate-bounce">
                    @if($celebrationType === 'module_completed')
                        🏆
                    @else
                        🌟
                    @endif
                </div>

                <div class="inline-flex items-center gap-1.5 rounded-full bg-amber-400/20 px-3.5 py-1 text-xs font-extrabold uppercase tracking-wider text-amber-300 border border-amber-400/30 mb-3">
                    🎉 Conquista Desbloqueada!
                </div>

                <h2 class="text-2xl font-black text-white mb-2">
                    {{ $celebrationTitle }}
                </h2>

                <p class="text-sm text-slate-300 leading-relaxed mb-6">
                    {{ $celebrationMessage }}
                </p>

                <button type="button"
                        wire:click="dismissCelebration"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-amber-400 to-emerald-400 px-6 py-3.5 text-sm font-black text-slate-950 shadow-xl shadow-amber-500/25 hover:from-amber-300 hover:to-emerald-300 transition cursor-pointer">
                    <span>🚀</span>
                    <span>Continuar com Orgulho!</span>
                </button>
            </div>
        </div>
    @endif

    {{-- MODAL DE ZOOM DE IMAGEM --}}
    @if($showZoomModal && $zoomImageUrl)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-md"
             wire:click="closeZoom">
            <div class="relative max-w-5xl max-h-[90vh] overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl"
                 @click.stop>
                <button type="button"
                        wire:click="closeZoom"
                        class="absolute top-4 right-4 z-10 rounded-full bg-slate-800/80 p-2 text-slate-300 hover:bg-slate-700 hover:text-white transition">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <img src="{{ $zoomImageUrl }}" alt="Imagem ampliada da aula" class="max-h-[85vh] w-auto object-contain mx-auto">
            </div>
        </div>
    @endif

    {{-- TELA DE BLOQUEIO (ADMINISTRATIVO / HORÁRIO / TRAVA DIÁRIA / PRÉ-REQUISITOS) --}}
    @if($isLocked)
        @php
            $isAdministrative = $lockReason === 'administrative_check';
            $isScheduleLock = in_array($lockReason, ['early_arrival', 'after_hours', 'no_schedule_today', 'no_schedule_registered', 'formation_not_scheduled_today'], true);
        @endphp
        <div class="flex-1 flex items-center justify-center p-6">
            <div class="max-w-md w-full rounded-3xl border border-amber-500/30 bg-slate-900/90 p-8 text-center shadow-2xl backdrop-blur-xl">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-400 border border-amber-500/20 text-3xl">
                    @if($isAdministrative)
                        📋
                    @elseif($lockReason === 'early_arrival')
                        ⏰
                    @elseif($lockReason === 'after_hours')
                        ⏳
                    @elseif($isScheduleLock)
                        📅
                    @else
                        <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    @endif
                </div>

                <div class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-300 border border-amber-500/20 mb-3">
                    @if($isAdministrative)
                        Secretaria Escolar • Validação Cadastral
                    @elseif($lockReason === 'early_arrival')
                        Horário de Aula • Aguardando Início
                    @elseif($lockReason === 'after_hours')
                        Horário de Aula • Turno Finalizado
                    @elseif($isScheduleLock)
                        Horário Letivo Agendado
                    @else
                        Método Jesuítico • Pacing Pedagógico
                    @endif
                </div>

                <h2 class="text-xl font-bold text-white mb-2">
                    @if($isAdministrative)
                        Atualização Cadastral Necessária 📋
                    @elseif($lockReason === 'early_arrival')
                        Você Chegou Antes do Horário! ⏰
                    @elseif($lockReason === 'after_hours')
                        Horário Letivo Finalizado ⏳
                    @elseif($lockReason === 'no_schedule_today' || $lockReason === 'formation_not_scheduled_today')
                        Sem Aula Agendada Hoje 📅
                    @elseif($lockReason === 'no_schedule_registered')
                        Horário de Aula Não Cadastrado 📅
                    @elseif($lockReason === 'daily_limit_reached')
                        Meta Diária Concluída! 🎓
                    @elseif(in_array($lockReason, ['previous_lesson_incomplete', 'previous_course_incomplete'], true))
                        Pré-requisito Pendente
                    @else
                        Acesso Indisponível
                    @endif
                </h2>

                <p class="text-sm text-slate-300 leading-relaxed mb-6">
                    {{ $lockMessage }}
                </p>

                @if(! $isAdministrative && ! $isScheduleLock)
                    <div class="rounded-2xl bg-slate-800/60 p-4 text-xs text-slate-300 border border-slate-700/50 mb-6 text-left">
                        <p class="font-semibold text-amber-300 mb-1">💡 Princípio Inaciano do Ritmo:</p>
                        <p>O cérebro consolida sinapses durante o intervalo entre as aulas. Não avançar mais de 1 aula por dia evita a sobrecarga cognitiva e assegura a retenção de longo prazo.</p>
                    </div>
                @endif

                <div class="space-y-2.5">
                    @if($isAdministrative || $isScheduleLock)
                        <a href="{{ route('student.portal') }}"
                           class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/30 hover:bg-indigo-500 transition cursor-pointer">
                            <span>➔ Voltar ao Portal do Aluno</span>
                        </a>
                    @else
                        {{-- LIBERAÇÃO POR EDUCADOR --}}
                        @if(Auth::user()?->can('View:LmsLesson') || Auth::user()?->hasRole('super_admin'))
                            <button type="button"
                                    wire:click="educatorUnlockLesson"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-purple-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-purple-600/30 hover:bg-purple-500 transition cursor-pointer">
                                <span>🔓</span>
                                <span>Liberar Esta Aula Agora (Educador)</span>
                            </button>
                        @else
                            <button type="button"
                                    wire:click="$set('showEducatorAuthModal', true)"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-800 border border-slate-700 px-5 py-2.5 text-xs font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition cursor-pointer">
                                <span>🔑</span>
                                <span>Solicitar Liberação Presencial do Professor</span>
                            </button>
                        @endif
                    @endif

                    @if($isPreview)
                        <a href="{{ route('filament.studio.pages.lms-studio', ['course_id' => $lesson->module->course_id ?? null]) }}"
                           class="inline-flex w-full items-center justify-center rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                            Voltar ao Estúdio LMS
                        </a>
                    @else
                        <a href="{{ route('student.portal', ['tab' => 'courses']) }}"
                           class="inline-flex w-full items-center justify-center rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                            Voltar ao Portal do Aluno
                        </a>
                    @endif
                </div>
            </div>
        </div>

    {{-- TELA DE CONCLUSÃO COM EXAMEN SOMATIVO --}}
    @elseif($isLessonCompleted)
        <div class="flex-1 flex items-center justify-center p-6">
            <div class="max-w-xl w-full rounded-3xl border border-emerald-500/30 bg-slate-900/95 p-8 text-center shadow-2xl backdrop-blur-xl">
                <div class="mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-3xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-inner">
                    <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3.5 py-1 text-xs font-semibold text-emerald-300 border border-emerald-500/20 mb-3">
                    Examen Somativo • Maestria Inaciana
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-white mb-2">
                    Aula Concluída com Maestria!
                </h1>

                <p class="text-sm text-slate-400 mb-6">
                    Você percorreu com disciplina os 5 blocos da metodologia jesuítica: Praelectio, Experientia, Repetitio, Concertatio e Examen.
                </p>

                @if($feedbackMessage)
                    <div class="rounded-2xl border border-emerald-500/20 bg-emerald-950/20 px-4 py-2.5 mb-6 text-xs font-medium text-emerald-300">
                        {{ $feedbackMessage }}
                    </div>
                @endif

                {{-- MÉTRICAS DE DESEMPENHO --}}
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="rounded-2xl border border-slate-800 bg-slate-800/40 p-4">
                        <div class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Nota de Domínio</div>
                        <div class="text-2xl font-black text-emerald-400 mt-1">
                            {{ number_format($finalScore ?? 100, 1) }}%
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Nota de Corte: {{ $lesson->passing_score }}%</div>
                    </div>

                    <div class="rounded-2xl border border-slate-800 bg-slate-800/40 p-4">
                        <div class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Tempo de Estudo</div>
                        <div class="text-2xl font-black text-amber-400 mt-1" x-text="formatTimer(elapsedSeconds)">
                            {{ sprintf('%02d:%02d', floor($timeSpentSeconds / 60), $timeSpentSeconds % 60) }}
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">Meta: {{ $lesson->estimated_duration_minutes }} min</div>
                    </div>
                </div>

                {{-- REFLEXÃO DO EXAMEN --}}
                <div class="rounded-2xl bg-slate-800/60 p-4 text-xs text-slate-300 border border-slate-700/50 mb-5 text-left">
                    <div class="flex items-center gap-2 font-semibold text-amber-300 mb-1">
                        <span>🎯 Examen Inaciano (Reflexão):</span>
                    </div>
                    <p class="italic">"A maestria prática não decorre da pressa, mas da repetição deliberada com atenção aos detalhes."</p>
                </div>

                {{-- MICRO-FEEDBACK: AVALIAÇÃO RÁPIDA DA AULA (1 A 5 ESTRELAS) --}}
                <div class="rounded-2xl border border-amber-500/30 bg-amber-950/15 p-4 mb-6 text-left">
                    @if($lessonRatingSubmitted)
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold text-amber-300 flex items-center gap-1.5">
                                    <span>⭐</span>
                                    <span>Obrigado por avaliar esta aula! ({{ $lessonRating }}/5 estrelas)</span>
                                </p>
                                @if($lessonRatingComment)
                                    <p class="text-[11px] text-slate-300 mt-1 italic">"{{ $lessonRatingComment }}"</p>
                                @endif
                            </div>
                            <button type="button"
                                    wire:click="$set('lessonRatingSubmitted', false)"
                                    class="text-[11px] font-semibold text-amber-400 hover:underline cursor-pointer shrink-0">
                                Alterar
                            </button>
                        </div>
                    @else
                        <div class="space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div>
                                    <p class="text-xs font-bold text-white">⭐ O que você achou desta aula?</p>
                                    <p class="text-[11px] text-slate-400">Sua avaliação ajuda a escola a melhorar cada vez mais as aulas.</p>
                                </div>
                                <div class="flex items-center gap-1">
                                    @for($star = 1; $star <= 5; $star++)
                                        <button type="button"
                                                wire:click="setLessonRating({{ $star }})"
                                                class="h-9 w-9 rounded-xl border transition flex items-center justify-center text-lg cursor-pointer {{ $lessonRating >= $star ? 'bg-amber-500/20 border-amber-400 text-amber-400 scale-105 shadow-sm' : 'bg-slate-800/80 border-slate-700 text-slate-500 hover:text-amber-300' }}"
                                                title="{{ $star }} {{ $star === 1 ? 'estrela' : 'estrelas' }}">
                                            ★
                                        </button>
                                    @endfor
                                </div>
                            </div>

                            @if($lessonRating > 0)
                                <div class="space-y-2 pt-1">
                                    <input type="text"
                                           wire:model="lessonRatingComment"
                                           placeholder="Comentário opcional (ex: Gostei do exercício prático, ou tive dúvida no passo 3)..."
                                           class="w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-none" />
                                    <div class="flex justify-end">
                                        <button type="button"
                                                wire:click="submitLessonRating"
                                                class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 px-4 py-1.5 text-xs font-bold text-slate-950 shadow transition cursor-pointer">
                                            <span>✓ Enviar Avaliação ({{ $lessonRating }} ★)</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <button type="button"
                            wire:click="enterReviewMode"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/25 hover:bg-indigo-500 transition cursor-pointer">
                        <span>📖</span>
                        <span>Revisar Conteúdo da Aula</span>
                    </button>

                    @if($isPreview)
                        <a href="{{ route('filament.studio.pages.lms-studio', ['course_id' => $lesson->module->course_id ?? null]) }}"
                           class="w-full inline-flex items-center justify-center rounded-xl bg-emerald-500 px-6 py-3.5 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-400 transition">
                            Voltar ao Estúdio
                        </a>
                    @else
                        <a href="{{ route('student.portal', ['tab' => 'courses']) }}"
                           class="w-full inline-flex items-center justify-center rounded-xl bg-emerald-500 px-6 py-3.5 text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/25 hover:bg-emerald-400 transition">
                            Voltar ao Portal
                        </a>
                    @endif
                </div>
            </div>
        </div>

    {{-- TELA DE BLOQUEIO DE DIGITAÇÃO OBRIGATÓRIA (PRIMEIROS 15 MINUTOS) --}}
    @elseif($needsTypingWarmup)
        <div class="flex-1 flex items-center justify-center p-6">
            <div class="max-w-md w-full rounded-3xl border border-emerald-500/30 bg-slate-900/90 p-8 text-center shadow-2xl backdrop-blur-xl">
                <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-4xl shadow-lg">
                    ⌨️
                </div>

                <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-300 border border-emerald-500/20 mb-3">
                    Etapa 1 de 2 • Treino Pré-Aula Obrigatório
                </div>

                <h2 class="text-xl font-bold text-white mb-2">
                    Aquecimento de Digitação (15 min)
                </h2>

                <p class="text-sm text-slate-400 leading-relaxed mb-6">
                    Conforme a metodologia vocacional da escola, os primeiros 15 minutos de aula são reservados para a prática e aperfeiçoamento de digitação no teclado físico.
                </p>

                <div class="rounded-2xl bg-slate-800/60 p-4 text-xs text-slate-300 border border-slate-700/50 mb-6 text-left">
                    <p class="font-semibold text-emerald-300 mb-1">🎯 Por que treinar digitação antes da aula?</p>
                    <p>O treino exercita a memória muscular, desenvolve velocidade em PPM e fixa a posição correta de acentos (agudo, circunflexo, crase, til), cedilha e pontuação para você realizar as atividades do curso com rapidez.</p>
                </div>

                <div class="space-y-2.5">
                    <a href="{{ route('student.typing', ['contract_id' => $contractId, 'lesson_id' => $lesson->id, 'mandatory' => 1]) }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-emerald-500/25 hover:from-emerald-400 hover:to-emerald-500 transition cursor-pointer">
                        <span>⌨️</span>
                        <span>Fazer Treino de Digitação (15 min)</span>
                    </a>

                    @if(Auth::user()?->can('View:LmsLesson') || Auth::user()?->hasRole('super_admin'))
                        <button type="button"
                                wire:click="educatorUnlockLesson"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-purple-600 px-5 py-2.5 text-xs font-bold text-white shadow-md hover:bg-purple-500 transition cursor-pointer">
                            <span>🔓</span>
                            <span>Liberar Conteúdo sem Digitação (Educador)</span>
                        </button>
                    @else
                        <button type="button"
                                wire:click="$set('showEducatorAuthModal', true)"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-800 border border-slate-700 px-5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition cursor-pointer">
                            <span>🔑</span>
                            <span>Solicitar Liberação Presencial do Professor</span>
                        </button>
                    @endif

                    <a href="{{ route('student.portal', ['tab' => 'courses']) }}"
                       class="inline-flex w-full items-center justify-center gap-2 rounded-xl px-5 py-2 text-xs font-medium text-slate-400 hover:text-white transition">
                        Voltar ao Portal do Aluno
                    </a>
                </div>
            </div>
        </div>

    {{-- PLAYER PRINCIPAL DA AULA --}}
    @else
        {{-- TOP BAR --}}
        <header class="border-b border-slate-800/80 bg-slate-900/90 px-4 py-3 sm:px-6 backdrop-blur sticky top-0 z-40">
            <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-3">
                {{-- ESQUERDA: TÍTULO DA AULA E CURSO --}}
                <div class="flex items-center gap-3 min-w-0">
                    @if($isPreview)
                        <a href="{{ route('filament.studio.pages.lms-studio', ['course_id' => $lesson->module->course_id ?? null]) }}"
                           class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-800 text-slate-400 hover:bg-slate-700 hover:text-white transition"
                           title="Voltar ao Estúdio LMS">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                        </a>
                    @else
                        <button type="button"
                                wire:click="exitLesson"
                                class="flex h-9 items-center gap-1.5 px-3 rounded-xl bg-slate-800 text-slate-300 hover:bg-rose-950/40 hover:text-rose-300 hover:border-rose-500/30 border border-transparent transition cursor-pointer text-xs font-semibold"
                                title="Sair da aula e voltar ao portal">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span class="hidden sm:inline">Sair da Aula</span>
                        </button>
                    @endif
                    <div class="truncate">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-slate-400 truncate">{{ $lesson->module->course->name ?? 'Curso LMS' }}</span>
                            <span class="text-slate-600">•</span>
                            <span class="text-xs text-slate-400 truncate">{{ $lesson->module->title ?? 'Módulo' }}</span>
                        </div>
                        <h1 class="text-sm sm:text-base font-bold text-white truncate">{{ $lesson->title }}</h1>
                    </div>
                </div>

                {{-- CENTRO: FASE JESUÍTICA E PROGRESSO --}}
                @php
                    $step = $this->currentStep;
                    $phase = $step?->phase ?? 'praelectio';
                    $phaseConfig = match($phase) {
                        'praelectio' => ['label' => '1. Praelectio • Contexto & Apresentação', 'badge' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/30'],
                        'practice' => ['label' => '2. Experientia • Prática Guiada no Simulador', 'badge' => 'bg-amber-500/10 text-amber-400 border-amber-500/30'],
                        'repetitio' => ['label' => '3. Repetitio • Fixação Ativa & Raciocínio', 'badge' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/30'],
                        'concertatio' => ['label' => '4. Concertatio • Desafio Autônomo', 'badge' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30'],
                        'examen' => ['label' => '5. Examen • Avaliação de Maestria', 'badge' => 'bg-rose-500/10 text-rose-400 border-rose-500/30'],
                        default => ['label' => 'Passo de Aprendizagem', 'badge' => 'bg-slate-800 text-slate-300 border-slate-700'],
                    };
                @endphp

                <div class="hidden md:flex flex-col items-center">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-0.5 text-xs font-medium border {{ $phaseConfig['badge'] }}">
                        {{ $phaseConfig['label'] }}
                    </span>
                    <div class="flex items-center gap-2 mt-1.5">
                        <div class="h-1.5 w-36 rounded-full bg-slate-800 overflow-hidden">
                            @php
                                $totalSteps = max(1, $steps->count());
                                $pct = min(100, round((($currentStepIndex + 1) / $totalSteps) * 100));
                            @endphp
                            <div class="h-full bg-gradient-to-r from-amber-500 to-emerald-500 transition-all duration-300" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-400">{{ $currentStepIndex + 1 }}/{{ $totalSteps }}</span>
                    </div>
                </div>

                {{-- DIREITA: TIMER, CONTROLES DE EXPANSÃO E AJUDA --}}
                <div class="flex items-center gap-2 sm:gap-3">
                    {{-- CHAMAR EDUCADOR (INTEGRAÇÃO COM MONITOR DE SALA) --}}
                    @if(! $isPreview)
                        @if($helpRequested)
                            <button type="button"
                                    wire:click="cancelHelpRequest"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-rose-500/50 bg-rose-500/20 px-2.5 sm:px-3 py-1.5 text-xs font-bold text-rose-300 hover:bg-rose-500/30 transition animate-pulse cursor-pointer">
                                <span>🚨</span>
                                <span class="hidden sm:inline">Educador Chamado</span>
                                <span class="text-[10px] bg-rose-500 text-white rounded px-1.5 py-0.5">Cancelar</span>
                            </button>
                        @else
                            <button type="button"
                                    wire:click="requestHelp"
                                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800 hover:border-amber-500/40 hover:bg-amber-500/10 px-2.5 sm:px-3 py-1.5 text-xs font-semibold text-slate-300 hover:text-amber-300 transition cursor-pointer"
                                    title="Solicitar auxílio presencial do professor">
                                <span>🙋</span>
                                <span class="hidden sm:inline">Chamar Educador</span>
                            </button>
                        @endif
                    @endif

                    {{-- EXPANDIR / RECOLHER VISUALIZAÇÃO --}}
                    <button type="button"
                            @click="toggleExpanded()"
                            class="hidden md:inline-flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition cursor-pointer"
                            title="Expandir largura do player">
                        <span x-text="isExpanded ? '⤡ Padrão' : '⤢ Expandir'"></span>
                    </button>

                    {{-- MODO TELA CHEIA --}}
                    <button type="button"
                            @click="toggleFullscreen()"
                            class="hidden md:inline-flex items-center gap-1 rounded-xl border border-slate-800 bg-slate-900 px-2.5 py-1.5 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition cursor-pointer"
                            title="Tela cheia">
                        <span x-text="isFullscreen ? '🗗 Janela' : '⛶ Tela Cheia'"></span>
                    </button>

                    @if($isPreview)
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500/20 px-2.5 py-1 text-xs font-semibold text-amber-300 border border-amber-500/30">
                            👁️ Modo Educador
                        </span>
                    @endif

                    @if($isReviewMode)
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-500/20 px-2.5 py-1 text-xs font-bold text-indigo-300 border border-indigo-500/30">
                                📖 Modo Revisão ({{ number_format($finalScore ?? 100, 1) }}%)
                            </span>
                            <button type="button"
                                    wire:click="exitReviewMode"
                                    class="rounded-lg bg-slate-800 px-2.5 py-1 text-xs font-semibold text-slate-300 hover:bg-slate-700 hover:text-white transition cursor-pointer">
                                ✕ Concluir
                            </button>
                        </div>
                    @endif

                    <div class="flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-900 px-3 py-1.5 text-xs text-slate-300">
                        <svg class="h-4 w-4 text-amber-400 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="font-mono font-bold text-amber-400" x-text="formatTimer(elapsedSeconds)">
                            {{ sprintf('%02d:%02d:%02d', floor($timeSpentSeconds / 3600), floor(($timeSpentSeconds % 3600) / 60), $timeSpentSeconds % 60) }}
                        </span>
                        <span class="text-slate-500">/ {{ $lesson->estimated_duration_minutes }}m</span>
                    </div>
                </div>
            </div>
        </header>

        {{-- CORPO DA LIÇÃO / ARENA INTERATIVA --}}
        <main class="flex-1 w-full mx-auto p-4 sm:p-6 lg:p-8 flex flex-col transition-all duration-300"
              :class="isExpanded ? 'max-w-7xl px-4 sm:px-8 lg:px-14' : 'max-w-5xl'">
            @if(! $step)
                <div class="flex-1 flex items-center justify-center text-center p-8">
                    <div class="max-w-md">
                        <div class="text-4xl mb-3">📝</div>
                        <h2 class="text-lg font-bold text-white mb-1">Nenhum passo cadastrado nesta aula</h2>
                        <p class="text-xs text-slate-400 mb-4">Adicione passos no Estúdio LMS ou utilize o Gerador com IA para estruturar os 5 blocos do método jesuítico.</p>
                        <a href="{{ route('filament.studio.pages.lms-studio', ['course_id' => $lesson->module->course_id ?? null]) }}"
                           class="inline-flex items-center rounded-xl bg-primary-600 px-4 py-2 text-xs font-bold text-white hover:bg-primary-500">
                            Abrir Estúdio LMS
                        </a>
                    </div>
                </div>
            @else
                <div
                    x-data="{
                        stayRemaining: @js($this->stepRequiredSeconds),
                        timer: null,
                        init() {
                            this.startStayTimer();
                        },
                        startStayTimer() {
                            if (this.timer) clearInterval(this.timer);
                            if (@js($isReviewMode || $isPreview)) {
                                this.stayRemaining = 0;
                                return;
                            }
                            this.stayRemaining = @js($this->stepRequiredSeconds);
                            if (this.stayRemaining <= 0) return;
                            this.timer = setInterval(() => {
                                if (this.stayRemaining > 0) {
                                    this.stayRemaining--;
                                } else {
                                    clearInterval(this.timer);
                                }
                            }, 1000);
                        }
                    }"
                    class="rounded-3xl border border-slate-800/80 bg-slate-900/60 p-6 sm:p-8 shadow-2xl backdrop-blur-md flex-1 flex flex-col justify-between"
                >
                    <div>
                        {{-- CABEÇALHO DO PASSO --}}
                        <div class="mb-6">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                    Passo {{ $this->stepDisplayNumber }} de {{ $steps->count() }}
                                </span>
                                <span class="md:hidden inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-medium border {{ $phaseConfig['badge'] }}">
                                    {{ $phaseConfig['label'] }}
                                </span>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-extrabold text-white leading-tight">
                                {{ $step->title }}
                            </h2>
                        </div>

                        {{-- SEÇÃO DE MÍDIA (SE HOUVER) --}}
                        @php
                            $mediaType = $step->content['media_type'] ?? null;
                            $mediaUrl = $step->content['media_url'] ?? null;
                        @endphp

                        @if($mediaType && $mediaUrl && !($mediaType === 'image' && in_array($step->step_type, ['hotspot_simulation', 'guided_simulation'])))
                            <div class="mb-6">
                                @if($mediaType === 'image')
                                    <div class="relative group overflow-hidden rounded-2xl border border-slate-800 bg-slate-950/50 p-2">
                                        <img src="{{ $mediaUrl }}" alt="{{ $step->title }}" class="max-h-96 w-full object-contain rounded-xl mx-auto">
                                        <button type="button"
                                                wire:click="openZoom('{{ $mediaUrl }}')"
                                                class="absolute bottom-4 right-4 rounded-xl bg-slate-900/90 px-3 py-1.5 text-xs font-semibold text-slate-200 border border-slate-700 shadow-lg backdrop-blur hover:bg-slate-800 transition flex items-center gap-1.5">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                            </svg>
                                            Ampliar Imagem
                                        </button>
                                    </div>
                                @elseif($mediaType === 'audio')
                                    <div class="rounded-2xl border border-indigo-500/30 bg-indigo-950/20 p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-400">
                                                🎙️
                                            </span>
                                            <span class="text-xs font-bold text-indigo-300">Locução Explicativa da Aula</span>
                                        </div>
                                        <audio controls class="w-full">
                                            <source src="{{ $mediaUrl }}" type="audio/mpeg">
                                            Seu navegador não suporta a reprodução deste áudio.
                                        </audio>
                                    </div>
                                @elseif($mediaType === 'video')
                                    @php
                                        $embedUrl = $this->getEmbedVideoUrl($mediaUrl);
                                        $isIframe = str_contains($embedUrl, 'youtube.com') || str_contains($embedUrl, 'vimeo.com');
                                    @endphp
                                    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 shadow-2xl">
                                        @if($isIframe)
                                            <div class="aspect-video w-full">
                                                <iframe src="{{ $embedUrl }}"
                                                        class="h-full w-full"
                                                        frameborder="0"
                                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                        allowfullscreen>
                                                </iframe>
                                            </div>
                                        @else
                                            {{-- PLAYER SEGURO ANTI-AVANÇO (ANTI-SKIP) --}}
                                            <div x-data="{
                                                    isPlaying: false,
                                                    currentTime: 0,
                                                    duration: 0,
                                                    progress: 0,
                                                    isMuted: false,
                                                    togglePlay() {
                                                        const video = this.$refs.videoElement;
                                                        if (video.paused) {
                                                            video.play();
                                                            this.isPlaying = true;
                                                        } else {
                                                            video.pause();
                                                            this.isPlaying = false;
                                                        }
                                                    },
                                                    rewind10() {
                                                        const video = this.$refs.videoElement;
                                                        video.currentTime = Math.max(0, video.currentTime - 10);
                                                    },
                                                    toggleMute() {
                                                        const video = this.$refs.videoElement;
                                                        video.muted = !video.muted;
                                                        this.isMuted = video.muted;
                                                    },
                                                    onTimeUpdate() {
                                                        const video = this.$refs.videoElement;
                                                        this.currentTime = video.currentTime;
                                                        this.duration = video.duration || 0;
                                                        if (this.duration > 0) {
                                                            this.progress = (this.currentTime / this.duration) * 100;
                                                        }
                                                    },
                                                    formatTime(sec) {
                                                        if (!sec || isNaN(sec)) return '00:00';
                                                        const m = String(Math.floor(sec / 60)).padStart(2, '0');
                                                        const s = String(Math.floor(sec % 60)).padStart(2, '0');
                                                        return `${m}:${s}`;
                                                    }
                                                 }"
                                                 @contextmenu.prevent
                                                 class="relative bg-black group select-none"
                                            >
                                                <video x-ref="videoElement"
                                                       @timeupdate="onTimeUpdate()"
                                                       @ended="isPlaying = false; $wire.markVideoCompleted()"
                                                       @play="isPlaying = true"
                                                       @pause="isPlaying = false"
                                                       @click="togglePlay()"
                                                       class="w-full aspect-video max-h-[60vh] mx-auto object-contain cursor-pointer">
                                                    <source src="{{ $embedUrl }}">
                                                    Seu navegador não suporta este formato de vídeo.
                                                </video>

                                                {{-- BARRA DE PROGRESSO NÃO-CLICÁVEL (ANTI-SKIP) --}}
                                                <div class="h-1.5 w-full bg-slate-800 overflow-hidden pointer-events-none">
                                                    <div class="h-full bg-gradient-to-r from-amber-500 to-emerald-500 transition-all duration-200"
                                                         :style="'width: ' + progress + '%'"></div>
                                                </div>

                                                {{-- CONTROLES CUSTOMIZADOS DO PLAYER --}}
                                                <div class="flex items-center justify-between p-3 bg-slate-900/95 border-t border-slate-800 text-xs text-slate-300">
                                                    <div class="flex items-center gap-2">
                                                        <button type="button"
                                                                @click="togglePlay()"
                                                                class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold transition cursor-pointer shadow">
                                                            <span x-show="!isPlaying">▶</span>
                                                            <span x-show="isPlaying">❚❚</span>
                                                        </button>

                                                        <button type="button"
                                                                @click="rewind10()"
                                                                title="Voltar 10 segundos para rever"
                                                                class="flex items-center gap-1 rounded-xl bg-slate-800 hover:bg-slate-700 px-2.5 py-1.5 text-xs text-slate-300 transition cursor-pointer">
                                                            <span>↺</span>
                                                            <span>-10s</span>
                                                        </button>

                                                        <div class="font-mono text-slate-400 text-xs ml-2">
                                                            <span class="text-white font-bold" x-text="formatTime(currentTime)">00:00</span>
                                                            <span> / </span>
                                                            <span x-text="formatTime(duration)">00:00</span>
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center gap-3">
                                                        <span class="hidden sm:inline-flex items-center gap-1 text-[11px] text-amber-400 bg-amber-400/10 px-2 py-0.5 rounded border border-amber-400/20">
                                                            🔒 Reprodução contínua (anti-avanço)
                                                        </span>

                                                        <button type="button"
                                                                @click="toggleMute()"
                                                                class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition cursor-pointer">
                                                            <span x-show="!isMuted">🔊</span>
                                                            <span x-show="isMuted">🔇</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @elseif($mediaType === 'pdf')
                                    <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 shadow-2xl">
                                        <div class="w-full aspect-[16/10] sm:aspect-[16/9] max-h-[70vh]">
                                            <iframe src="{{ asset($mediaUrl) }}#toolbar=0&navpanes=0"
                                                    class="h-full w-full border-0"
                                                    title="Apresentação Teórica de Slides">
                                            </iframe>
                                        </div>
                                        <div class="flex items-center justify-between p-3 bg-slate-900 border-t border-slate-800 text-xs text-slate-400">
                                            <div class="flex items-center gap-2">
                                                <span>📑</span>
                                                <span class="font-semibold text-slate-300">Apresentação Teórica da Aula (Slides PDF)</span>
                                            </div>
                                            <a href="{{ asset($mediaUrl) }}"
                                               target="_blank"
                                               class="inline-flex items-center gap-1 text-indigo-400 hover:text-indigo-300 underline font-medium">
                                                <span>Abrir Apresentação em Nova Aba</span>
                                                <span>↗</span>
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- CONTEÚDO ESPECÍFICO POR TIPO DE PASSO --}}

                        {{-- 1. TEORIA (PRAELECTIO) COM RENDERIZAÇÃO RICA (NEGRITO, ITÁLICO, SUBLINHADO, ATALHOS) --}}
                        @if($step->step_type === 'theory')
                            <div class="space-y-4 text-slate-200 leading-relaxed">
                                <div class="rounded-2xl border border-slate-800/80 bg-slate-950/60 p-6 sm:p-8 shadow-inner">
                                    <div class="prose prose-invert prose-indigo max-w-3xl sm:max-w-4xl mx-auto text-slate-200 text-sm sm:text-base leading-relaxed space-y-4 font-normal [&_strong]:text-white [&_strong]:font-black [&_b]:text-white [&_b]:font-black [&_u]:underline [&_u]:decoration-indigo-400 [&_u]:decoration-2 [&_kbd]:px-2 [&_kbd]:py-0.5 [&_kbd]:text-xs [&_kbd]:font-mono [&_kbd]:bg-slate-800 [&_kbd]:border [&_kbd]:border-slate-700 [&_kbd]:rounded-md [&_kbd]:text-amber-300 [&_blockquote]:border-l-4 [&_blockquote]:border-amber-400 [&_blockquote]:bg-amber-400/10 [&_blockquote]:p-3 [&_blockquote]:rounded-r-xl [&_blockquote]:text-amber-200">
                                        {!! \Illuminate\Support\Str::markdown($step->content['body'] ?? 'Sem conteúdo teórico registrado.') !!}
                                    </div>
                                </div>

                                <div class="rounded-2xl bg-indigo-500/10 border border-indigo-500/20 p-4 text-xs text-indigo-300 flex items-start gap-3">
                                    <span class="text-base">💡</span>
                                    <div>
                                        <p class="font-bold text-indigo-200 mb-0.5">Dica de Fixação Inaciana (Praelectio):</p>
                                        <p>Concentre-se em entender o *porquê* desta ferramenta existir no mundo real. Nos próximos passos, você a executará na prática.</p>
                                    </div>
                                </div>
                            </div>

                        {{-- 2. SIMULAÇÃO PRÁTICA GUIADA (EXPERIENTIA) --}}
                        @elseif(in_array($step->step_type, ['hotspot_simulation', 'guided_simulation']))
                            @php
                                $activeSim = $this->activeHotspotStepData;
                                $hotspotData = $activeSim['hotspot'] ?? ($step->content['hotspot'] ?? null);
                                $simMediaUrl = $activeSim['media_url'] ?? ($step->content['media_url'] ?? null);
                                $hasImage = !empty($simMediaUrl) && (($step->content['media_type'] ?? 'image') === 'image' || !empty($activeSim['media_url']));
                                $instructionsText = !empty(trim($activeSim['instruction'] ?? ''))
                                    ? $activeSim['instruction']
                                    : (!empty(trim($step->content['instructions'] ?? ''))
                                        ? $step->content['instructions']
                                        : (!empty(trim($step->content['body'] ?? ''))
                                            ? $step->content['body']
                                            : 'Acione a ferramenta indicada na interface para concluir o procedimento.'));
                                $totalSubSteps = (int) ($activeSim['total_sub_steps'] ?? 1);
                                $currentSubStepNum = (int) ($activeSim['current_sub_step'] ?? 1);
                            @endphp

                            <div class="space-y-4">
                                <div class="rounded-2xl border border-amber-500/30 bg-amber-950/20 p-5 text-amber-100 flex items-start justify-between gap-4 shadow-sm">
                                    <div class="flex items-start gap-3.5 flex-1">
                                        <span class="text-2xl mt-0.5">🛠️</span>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-xs uppercase tracking-wider text-amber-300 mb-1.5 flex flex-wrap items-center gap-2">
                                                <span>Instruções Práticas da Simulação</span>
                                                <span class="text-[10px] font-normal normal-case px-2 py-0.5 rounded-full bg-amber-500/20 border border-amber-500/30 text-amber-200">Experientia Inaciana</span>
                                                @if($totalSubSteps > 1)
                                                    <span class="text-[10px] font-bold normal-case px-2.5 py-0.5 rounded-full bg-indigo-500/30 border border-indigo-400/40 text-indigo-200">
                                                        🎯 Etapa Guiada {{ $currentSubStepNum }} de {{ $totalSubSteps }}
                                                    </span>
                                                @endif
                                            </p>
                                            <div class="prose prose-invert prose-amber max-w-none text-slate-100 text-sm leading-relaxed font-normal [&_strong]:text-white [&_strong]:font-black [&_b]:text-white [&_b]:font-black [&_em]:italic [&_em]:text-amber-200 [&_u]:underline [&_u]:decoration-amber-400 [&_u]:decoration-2 [&_kbd]:px-2 [&_kbd]:py-0.5 [&_kbd]:text-xs [&_kbd]:font-mono [&_kbd]:bg-slate-800 [&_kbd]:border [&_kbd]:border-slate-700 [&_kbd]:rounded-md [&_kbd]:text-amber-300 [&_blockquote]:border-l-4 [&_blockquote]:border-amber-400 [&_blockquote]:bg-amber-400/10 [&_blockquote]:p-3 [&_blockquote]:rounded-r-xl [&_blockquote]:text-amber-200 [&_ul]:list-disc [&_ul]:pl-5 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-2 last:[&_p]:mb-0">
                                                {!! \Illuminate\Support\Str::markdown($instructionsText) !!}
                                            </div>
                                        </div>
                                    </div>
                                    @if($hotspotMistakeCount >= 2 && !empty($hotspotData))
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/20 border border-amber-500/40 px-3 py-1 text-[11px] font-bold text-amber-300 animate-pulse shrink-0">
                                            💡 Dica Inaciana Ativada
                                        </span>
                                    @endif
                                </div>

                                {{-- WORKSPACE INTERATIVO SIMULADO --}}
                                <div class="rounded-2xl border border-slate-700/80 bg-slate-950 shadow-2xl overflow-hidden">
                                    {{-- BARRA DE TÍTULO DA JANELA SIMULADA --}}
                                    <div class="flex items-center justify-between border-b border-slate-800 bg-slate-900/90 px-4 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <div class="h-3 w-3 rounded-full bg-rose-500/80"></div>
                                            <div class="h-3 w-3 rounded-full bg-amber-500/80"></div>
                                            <div class="h-3 w-3 rounded-full bg-emerald-500/80"></div>
                                            <span class="text-xs font-semibold text-slate-300 ml-2">Simulador de Execução Prática Aurosoft</span>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            @if($hasImage)
                                                <button type="button"
                                                        wire:click="openZoom('{{ $simMediaUrl }}')"
                                                        class="rounded-lg bg-slate-800/80 px-2 py-1 text-[11px] font-semibold text-slate-300 hover:text-white hover:bg-slate-700 transition flex items-center gap-1 border border-slate-700/60 shadow-sm">
                                                    🔍 Zoom
                                                </button>
                                            @endif
                                            <span class="text-[11px] font-mono text-slate-400">
                                            @php
                                                $actionType = $hotspotData['interaction_type'] ?? 'click';
                                            @endphp
                                            @if($hasImage && !empty($hotspotData))
                                                @if($actionType === 'select_text')
                                                    ↔️ Selecione o texto arrastando
                                                @elseif($actionType === 'type_text')
                                                    ⌨️ Digite o comando/texto
                                                @elseif($actionType === 'double_click')
                                                    🖱️🖱️ Duplo clique no alvo
                                                @elseif($actionType === 'right_click')
                                                    🖱️ Clique com botão direito
                                                @else
                                                    🎯 Clique no software
                                                @endif
                                            @else
                                                Ambiente Interativo
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                @if($hasImage)
                                        {{-- SIMULAÇÃO POR IMAGEM REAL COM COORDENADAS E INTERAÇÕES REATIVAS --}}
                                        <div
                                            wire:key="sim-canvas-{{ $step->id }}-sub-{{ $currentSubStepIndex }}"
                                            x-data="{
                                                interactionType: '{{ $hotspotData['interaction_type'] ?? 'click' }}',
                                                typedValue: '',
                                                isDragging: false,
                                                dragStartX: 0,
                                                dragStartY: 0,
                                                currentX: 0,
                                                currentY: 0,
                                                handleMouseDown(event) {
                                                    const rect = $el.getBoundingClientRect();
                                                    const x = parseFloat((((event.clientX - rect.left) / rect.width) * 100).toFixed(2));
                                                    const y = parseFloat((((event.clientY - rect.top) / rect.height) * 100).toFixed(2));
                                                    if (this.interactionType === 'select_text') {
                                                        this.isDragging = true;
                                                        this.dragStartX = x;
                                                        this.dragStartY = y;
                                                        this.currentX = x;
                                                        this.currentY = y;
                                                    }
                                                },
                                                handleMouseMove(event) {
                                                    if (!this.isDragging || this.interactionType !== 'select_text') return;
                                                    const rect = $el.getBoundingClientRect();
                                                    this.currentX = parseFloat((((event.clientX - rect.left) / rect.width) * 100).toFixed(2));
                                                    this.currentY = parseFloat((((event.clientY - rect.top) / rect.height) * 100).toFixed(2));
                                                },
                                                handleMouseUp(event) {
                                                    if (!this.isDragging || this.interactionType !== 'select_text') return;
                                                    this.isDragging = false;
                                                    const rect = $el.getBoundingClientRect();
                                                    const endX = parseFloat((((event.clientX - rect.left) / rect.width) * 100).toFixed(2));
                                                    const endY = parseFloat((((event.clientY - rect.top) / rect.height) * 100).toFixed(2));
                                                    $wire.dragHotspotCoordinates(this.dragStartX, this.dragStartY, endX, endY);
                                                },
                                                handleClick(event) {
                                                    if (this.interactionType === 'select_text' || this.interactionType === 'right_click') return;
                                                    const rect = $el.getBoundingClientRect();
                                                    const clickX = parseFloat((((event.clientX - rect.left) / rect.width) * 100).toFixed(2));
                                                    const clickY = parseFloat((((event.clientY - rect.top) / rect.height) * 100).toFixed(2));
                                                    $wire.clickHotspotCoordinates(clickX, clickY);
                                                },
                                                handleDblClick(event) {
                                                    if (this.interactionType !== 'double_click') return;
                                                    const rect = $el.getBoundingClientRect();
                                                    const clickX = parseFloat((((event.clientX - rect.left) / rect.width) * 100).toFixed(2));
                                                    const clickY = parseFloat((((event.clientY - rect.top) / rect.height) * 100).toFixed(2));
                                                    $wire.doubleClickHotspotCoordinates(clickX, clickY);
                                                },
                                                handleContextMenu(event) {
                                                    if (this.interactionType !== 'right_click') return;
                                                    const rect = $el.getBoundingClientRect();
                                                    const clickX = parseFloat((((event.clientX - rect.left) / rect.width) * 100).toFixed(2));
                                                    const clickY = parseFloat((((event.clientY - rect.top) / rect.height) * 100).toFixed(2));
                                                    $wire.rightClickHotspotCoordinates(clickX, clickY);
                                                }
                                            }"
                                            @mousedown="handleMouseDown($event)"
                                            @mousemove="handleMouseMove($event)"
                                            @mouseup="handleMouseUp($event)"
                                            @click="handleClick($event)"
                                            @dblclick="handleDblClick($event)"
                                            @contextmenu.prevent="handleContextMenu($event)"
                                            :class="interactionType === 'select_text' ? 'cursor-text' : 'cursor-crosshair'"
                                            class="relative w-full overflow-hidden bg-slate-900 select-none min-h-[280px] flex items-center justify-center"
                                        >
                                            <img
                                                src="{{ $simMediaUrl }}"
                                                alt="Interface do Software"
                                                class="w-full h-auto object-contain max-h-[550px] pointer-events-none"
                                            />

                                            {{-- CAIXA DE SELEÇÃO DINÂMICA AO ARRASTAR (WORD / OFFICE STYLE) --}}
                                            <div
                                                x-show="isDragging"
                                                class="absolute bg-blue-500/35 border-2 border-blue-400 rounded-sm pointer-events-none transition-none shadow-md"
                                                :style="`left: ${Math.min(dragStartX, currentX)}%; top: ${Math.min(dragStartY, currentY)}%; width: ${Math.max(1, Math.abs(currentX - dragStartX))}%; height: ${Math.max(2, Math.abs(currentY - dragStartY))}%;`"
                                            ></div>

                                            {{-- DESTAQUE DA SELEÇÃO DE TEXTO APÓS CONCLUSÃO DO ARRASTO --}}
                                            @if($lastDragStartX !== null && $lastDragEndX !== null)
                                                @php
                                                    $boxLeft = min($lastDragStartX, $lastDragEndX);
                                                    $boxTop = min($lastDragStartY, $lastDragEndY ?? $lastDragStartY);
                                                    $boxWidth = max(2, abs($lastDragEndX - $lastDragStartX));
                                                    $boxHeight = max(3, abs(($lastDragEndY ?? $lastDragStartY) - $lastDragStartY));
                                                @endphp
                                                <div
                                                    class="absolute {{ $hotspotHit ? 'bg-blue-500/40 border-2 border-blue-400 ring-2 ring-blue-500/30' : 'bg-rose-500/30 border-2 border-rose-400' }} rounded-sm pointer-events-none flex items-center justify-center transition-all duration-200"
                                                    style="left: {{ $boxLeft }}%; top: {{ $boxTop }}%; width: {{ $boxWidth }}%; height: {{ $boxHeight }}%;"
                                                >
                                                    @if($hotspotHit)
                                                        <span class="text-[10px] font-bold text-white bg-blue-900/90 border border-blue-400/40 px-2 py-0.5 rounded shadow-lg backdrop-blur">
                                                            ✓ Texto Selecionado
                                                        </span>
                                                    @else
                                                        <span class="text-[10px] font-bold text-rose-200 bg-rose-950/90 border border-rose-500/40 px-2 py-0.5 rounded shadow-lg backdrop-blur">
                                                            ✕ Seleção Incorreta
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif

                                            {{-- CAMPO DE DIGITAÇÃO INTERATIVO SOBRE A SCREENSHOT (SE FOR TYPE_TEXT) --}}
                                            @if(($hotspotData['interaction_type'] ?? '') === 'type_text')
                                                <div
                                                    class="absolute transform -translate-x-1/2 -translate-y-1/2 z-30"
                                                    style="left: {{ $hotspotData['x'] ?? 50 }}%; top: {{ $hotspotData['y'] ?? 50 }}%; width: {{ $hotspotData['input_width'] ?? 35 }}%; min-width: 190px; max-width: 90%;"
                                                    @click.stop
                                                    @mousedown.stop
                                                >
                                                    <div class="relative flex items-center shadow-2xl">
                                                        <input
                                                            type="text"
                                                            x-ref="typingInput"
                                                            x-model="typedValue"
                                                            @keydown.enter.prevent="$wire.submitTypedText(typedValue)"
                                                            placeholder="Digite aqui e pressione Enter..."
                                                            :disabled="$wire.hotspotHit"
                                                            class="w-full rounded-xl border-2 bg-white/95 px-3 py-2 text-xs font-mono font-bold text-slate-950 shadow-2xl backdrop-blur focus:outline-none transition-all duration-150"
                                                            :class="$wire.hotspotHit ? 'border-emerald-500 bg-emerald-50 text-emerald-950 ring-2 ring-emerald-400' : ($wire.hotspotHit === false ? 'border-rose-500 bg-rose-50 text-rose-950 ring-2 ring-rose-400' : 'border-indigo-500 ring-2 ring-indigo-400/50')"
                                                            autofocus
                                                        />
                                                        <button
                                                            type="button"
                                                            @click="$wire.submitTypedText(typedValue)"
                                                            :disabled="$wire.hotspotHit"
                                                            class="absolute right-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 px-2.5 py-1 text-[11px] font-bold text-white shadow transition cursor-pointer"
                                                        >
                                                            ↵ Enter
                                                        </button>
                                                    </div>
                                                </div>
                                            @endif

                                            {{-- INDICADOR DE CLIQUE DO ALUNO (QUANDO CLIQUE PONTUAL) --}}
                                            @if($lastClickX !== null && $lastClickY !== null && !in_array($hotspotData['interaction_type'] ?? 'click', ['select_text', 'type_text']))
                                                <div
                                                    class="absolute pointer-events-none transform -translate-x-1/2 -translate-y-1/2 flex items-center justify-center transition-all duration-200"
                                                    style="left: {{ $lastClickX }}%; top: {{ $lastClickY }}%;"
                                                >
                                                    @if($hotspotHit)
                                                        {{-- ACERTO: ANEL VERDE PULSANTE COM CHECKMARK --}}
                                                        <div class="h-10 w-10 rounded-full border-4 border-emerald-400 bg-emerald-500/40 animate-ping"></div>
                                                        <div class="absolute flex h-8 w-8 items-center justify-center rounded-full bg-emerald-500 text-white font-black text-sm shadow-xl ring-2 ring-white">
                                                            ✓
                                                        </div>
                                                        <span class="absolute top-full mt-1.5 whitespace-nowrap rounded-lg bg-emerald-950/90 border border-emerald-500/40 px-2.5 py-1 text-xs font-bold text-emerald-200 shadow-xl backdrop-blur">
                                                            ✓ Ação Correta!
                                                        </span>
                                                    @else
                                                        {{-- ERRO: PINO VERMELHO COM FEEDBACK --}}
                                                        <div class="h-9 w-9 rounded-full border-2 border-rose-500 bg-rose-500/30 animate-pulse"></div>
                                                        <div class="absolute flex h-7 w-7 items-center justify-center rounded-full bg-rose-600 text-white font-bold text-xs shadow-xl ring-2 ring-white">
                                                            ✕
                                                        </div>
                                                        <span class="absolute top-full mt-1.5 whitespace-nowrap rounded-lg bg-rose-950/90 border border-rose-500/40 px-2.5 py-1 text-xs font-bold text-rose-200 shadow-xl backdrop-blur">
                                                            Ação incorreta. Tente novamente!
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif

                                            {{-- DICA PEDAGÓGICA (SCAFFOLDING JESUÍTICO) APÓS 2 ERROS --}}
                                            @if($hotspotMistakeCount >= 2 && !empty($hotspotData['x']))
                                                @if(($hotspotData['interaction_type'] ?? 'click') === 'select_text' && !empty($hotspotData['end_x']))
                                                    @php
                                                        $tStartX = (float) $hotspotData['x'];
                                                        $tStartY = (float) $hotspotData['y'];
                                                        $tEndX = (float) $hotspotData['end_x'];
                                                        $tEndY = (float) ($hotspotData['end_y'] ?? $tStartY);
                                                        $guideLeft = min($tStartX, $tEndX);
                                                        $guideTop = min($tStartY, $tEndY);
                                                        $guideWidth = max(4, abs($tEndX - $tStartX));
                                                        $guideHeight = max(4, abs($tEndY - $tStartY));
                                                    @endphp
                                                    <!-- RETÂNGULO GUIA DOURADO COM SETA DIRECIONAL -->
                                                    <div
                                                        class="absolute border-2 border-dashed border-amber-400 bg-amber-400/20 rounded-md pointer-events-none flex items-center justify-between px-2 shadow-2xl animate-pulse"
                                                        style="left: {{ $guideLeft }}%; top: {{ $guideTop }}%; width: {{ $guideWidth }}%; height: {{ $guideHeight }}%;"
                                                    >
                                                        <span class="text-[9px] font-bold text-amber-300 bg-amber-950/90 px-1 rounded shadow">1. Início</span>
                                                        <span class="text-xs text-amber-300 font-black animate-bounce">➔</span>
                                                        <span class="text-[9px] font-bold text-amber-300 bg-amber-950/90 px-1 rounded shadow">2. Solte</span>
                                                    </div>
                                                @elseif(!empty($hotspotData['y']))
                                                    @php
                                                        $targetX = $hotspotData['x'];
                                                        $targetY = $hotspotData['y'];
                                                        $radius = (float) ($hotspotData['radius'] ?? 6.0);
                                                    @endphp
                                                    <div
                                                        class="absolute pointer-events-none transform -translate-x-1/2 -translate-y-1/2 flex items-center justify-center"
                                                        style="left: {{ $targetX }}%; top: {{ $targetY }}%;"
                                                    >
                                                        <div
                                                            class="rounded-full border-4 border-amber-400 bg-amber-400/20 animate-pulse shadow-2xl"
                                                            style="width: {{ max(36, $radius * 5) }}px; height: {{ max(36, $radius * 5) }}px;"
                                                        ></div>
                                                        <span class="absolute bottom-full mb-1.5 whitespace-nowrap rounded-lg bg-amber-950/90 border border-amber-400 px-2.5 py-1 text-xs font-bold text-amber-200 shadow-xl backdrop-blur">
                                                            💡 Dica: interaja aqui {{ !empty($hotspotData['label']) ? '(' . $hotspotData['label'] . ')' : '' }}
                                                        </span>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @else
                                        {{-- FALLBACK PARA SIMULADOR TOOLBAR INTERATIVO (QUANDO SEM SCREENSHOT) --}}
                                        <div class="border-b border-slate-800 bg-slate-900/50 px-4 py-2 flex items-center gap-2 text-xs">
                                            <button type="button"
                                                    wire:click="triggerSimulationHotspot(false)"
                                                    class="rounded-lg px-2.5 py-1 text-slate-400 hover:bg-slate-800 hover:text-white transition">
                                                Arquivo
                                            </button>
                                            <button type="button"
                                                    wire:click="triggerSimulationHotspot(false)"
                                                    class="rounded-lg px-2.5 py-1 text-slate-400 hover:bg-slate-800 hover:text-white transition">
                                                Editar
                                            </button>
                                            <button type="button"
                                                    wire:click="triggerSimulationHotspot(true)"
                                                    class="relative rounded-lg px-3 py-1 text-xs font-bold transition flex items-center gap-1.5 {{ $simulationHotspotClicked ? 'bg-emerald-500 text-slate-950 ring-2 ring-emerald-400' : 'bg-amber-500/20 text-amber-300 border border-amber-500/40 hover:bg-amber-500 hover:text-slate-950 animate-pulse' }}">
                                                <span>⚡</span>
                                                <span>Executar Ferramenta Principal</span>
                                            </button>
                                            <button type="button"
                                                    wire:click="triggerSimulationHotspot(false)"
                                                    class="rounded-lg px-2.5 py-1 text-slate-400 hover:bg-slate-800 hover:text-white transition">
                                                Exibir
                                            </button>
                                        </div>

                                        <div wire:click="triggerSimulationHotspot(false)"
                                             class="h-64 sm:h-80 flex flex-col items-center justify-center p-6 text-center cursor-crosshair bg-radial from-slate-900 to-slate-950">
                                            @if($simulationHotspotClicked)
                                                <div class="flex flex-col items-center gap-2 text-emerald-400 animate-bounce">
                                                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500/20 border border-emerald-500/40">
                                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </div>
                                                    <span class="text-sm font-bold">Comando Executado com Sucesso!</span>
                                                    <span class="text-xs text-slate-400">Você já pode avançar para a fixação.</span>
                                                </div>
                                            @else
                                                <div class="max-w-sm text-slate-500 pointer-events-none">
                                                    <div class="text-3xl mb-2">🖱️</div>
                                                    <p class="text-xs">Observe a barra de ferramentas acima e clique na ferramenta solicitada para simular o comando prático.</p>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- BARRA DE ENTRADA DE DIGITAÇÃO DEDICADA (SE TYPE_TEXT) --}}
                                    @if(($hotspotData['interaction_type'] ?? '') === 'type_text')
                                        <div class="border-t border-slate-800 bg-slate-900/95 p-3 flex flex-col sm:flex-row items-center gap-3">
                                            <div class="flex items-center gap-2 shrink-0">
                                                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-500/20 text-indigo-400 text-xs">
                                                    ⌨️
                                                </span>
                                                <span class="text-xs font-bold text-slate-300">Teclado Virtual / Digitação:</span>
                                            </div>
                                            <div class="flex-1 w-full flex items-center gap-2">
                                                <input
                                                    type="text"
                                                    x-model="typedValue"
                                                    @keydown.enter.prevent="$wire.submitTypedText(typedValue)"
                                                    :disabled="$wire.hotspotHit"
                                                    placeholder="Digite o texto ou comando e pressione Enter..."
                                                    class="flex-1 rounded-xl border border-slate-700 bg-slate-950 px-3.5 py-2 text-xs font-mono text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                                                />
                                                <button
                                                    type="button"
                                                    @click="$wire.submitTypedText(typedValue)"
                                                    :disabled="$wire.hotspotHit"
                                                    class="rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 px-4 py-2 text-xs font-bold text-white transition shadow cursor-pointer shrink-0"
                                                >
                                                    Confirmar (Enter)
                                                </button>
                                            </div>
                                        </div>

                                        @if($hotspotMistakeCount >= 2 && !empty($hotspotData['expected_text']))
                                            <div class="p-3 bg-amber-500/10 border-t border-amber-500/20 text-xs text-amber-200 flex items-center justify-between gap-3 animate-pulse">
                                                <div class="flex items-center gap-2">
                                                    <span>💡</span>
                                                    <span><strong>Dica Inaciana:</strong> O comando/texto exato é: <code class="bg-amber-950/80 px-1.5 py-0.5 rounded text-amber-300 font-mono font-bold">{{ $hotspotData['expected_text'] }}</code></span>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="typedValue = '{{ addslashes($hotspotData['expected_text']) }}'; $refs.typingInput?.focus()"
                                                    class="text-[11px] font-bold text-amber-300 underline hover:text-amber-100 shrink-0 cursor-pointer"
                                                >
                                                    Preencher Dica
                                                </button>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                            </div>

                        {{-- 3. QUIZ / BANCO DE QUESTÕES COM SORTEIO ALEATÓRIO & ANTI-COLA --}}
                        @elseif($step->step_type === 'quiz')
                            @php
                                $activeQuiz = $this->currentQuizQuestion ?? $step->content;
                                $quizQuestionText = $activeQuiz['question'] ?? ($step->content['question'] ?? $step->title);
                                $options = $activeQuiz['options'] ?? ($step->content['options'] ?? []);
                                $totalQuizQuestions = is_array($activeQuizQuestions ?? null) ? count($activeQuizQuestions) : 0;
                                $correctSoFar = is_array($quizPoolAnswers ?? null) ? count(array_filter($quizPoolAnswers, fn ($a) => !empty($a['is_correct']))) : 0;
                            @endphp
                            <div class="space-y-4">
                                <div class="rounded-2xl border border-slate-800 bg-slate-950/40 p-5 sm:p-6 shadow-inner">
                                    @if($totalQuizQuestions > 1)
                                        <div class="flex items-center justify-between mb-3 pb-2.5 border-b border-slate-800/80">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-cyan-500/15 border border-cyan-500/30 px-3 py-1 text-xs font-bold text-cyan-300">
                                                🎲 Questão Sorteada {{ ($activeQuizQuestionIndex ?? 0) + 1 }} de {{ $totalQuizQuestions }}
                                            </span>
                                            <span class="text-xs text-slate-400">
                                                Acertos até agora: <strong class="text-emerald-400">{{ $correctSoFar }}</strong>
                                            </span>
                                        </div>
                                    @endif

                                    <p class="text-base sm:text-lg font-bold text-white mb-4">
                                        {{ $quizQuestionText }}
                                    </p>

                                    {{-- LISTA DE ALTERNATIVAS --}}
                                    <div class="space-y-2.5">
                                        @foreach($options as $option)
                                            @php
                                                $optId = $option['id'] ?? '';
                                                $isSelected = $selectedOptionId === $optId;
                                            @endphp

                                            <button type="button"
                                                    wire:click="selectOption('{{ $optId }}')"
                                                    class="w-full text-left rounded-2xl border p-4 transition flex items-center justify-between gap-3 {{ $isSelected ? 'border-primary-500 bg-primary-500/10 text-white shadow-md' : 'border-slate-800 bg-slate-900/50 text-slate-300 hover:border-slate-700 hover:bg-slate-800/60' }}">
                                                <div class="flex items-center gap-3">
                                                    <span class="flex h-8 w-8 items-center justify-center rounded-xl font-bold text-xs {{ $isSelected ? 'bg-primary-500 text-white' : 'bg-slate-800 text-slate-400' }}">
                                                        {{ $optId }}
                                                    </span>
                                                    <span class="text-sm font-medium">{{ $option['text'] ?? '' }}</span>
                                                </div>

                                                @if($lastAnswerCorrect !== null && $isSelected)
                                                    @if($lastAnswerCorrect)
                                                        <span class="text-emerald-400 text-sm font-bold flex items-center gap-1">
                                                            ✓ Correto
                                                        </span>
                                                    @else
                                                        <span class="text-rose-400 text-sm font-bold flex items-center gap-1">
                                                            ✕ Incorreto
                                                        </span>
                                                    @endif
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                        {{-- 4. DESAFIO AUTÔNOMO (CONCERTATIO) --}}
                        @elseif($step->step_type === 'challenge')
                            @php
                                $challengeScenario = !empty(trim($step->content['scenario'] ?? ''))
                                    ? $step->content['scenario']
                                    : (!empty(trim($step->content['body'] ?? ''))
                                        ? $step->content['body']
                                        : 'Você recebeu uma demanda urgente da empresa para aplicar este procedimento de forma totalmente autônoma.');
                                $challengeInstructions = !empty(trim($step->content['instructions'] ?? ''))
                                    ? $step->content['instructions']
                                    : 'Execute todos os passos sem assistência e valide o resultado final.';
                            @endphp
                            <div class="space-y-4">
                                <div class="rounded-2xl border border-emerald-500/30 bg-emerald-950/10 p-5 sm:p-6">
                                    <div class="flex items-center gap-2 text-emerald-400 text-xs font-bold uppercase tracking-wider mb-2">
                                        <span>⚔️</span>
                                        <span>Cenário Real do Mercado de Trabalho</span>
                                    </div>
                                    <div class="prose prose-invert prose-emerald max-w-none text-slate-200 text-sm sm:text-base leading-relaxed font-medium mb-4 [&_strong]:text-white [&_strong]:font-black [&_b]:text-white [&_b]:font-black [&_u]:underline [&_u]:decoration-emerald-400 [&_u]:decoration-2 [&_kbd]:px-2 [&_kbd]:py-0.5 [&_kbd]:text-xs [&_kbd]:font-mono [&_kbd]:bg-slate-800 [&_kbd]:border [&_kbd]:border-slate-700 [&_kbd]:rounded-md [&_kbd]:text-emerald-300">
                                        {!! \Illuminate\Support\Str::markdown($challengeScenario) !!}
                                    </div>

                                    <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-4 text-xs text-slate-300">
                                        <p class="font-bold text-white mb-1.5">Sua Missão Autônoma:</p>
                                        <div class="prose prose-invert prose-slate max-w-none text-slate-300 text-xs leading-relaxed [&_strong]:text-white [&_strong]:font-bold [&_b]:text-white [&_b]:font-bold [&_u]:underline [&_kbd]:px-1.5 [&_kbd]:py-0.5 [&_kbd]:text-[11px] [&_kbd]:bg-slate-800 [&_kbd]:rounded">
                                            {!! \Illuminate\Support\Str::markdown($challengeInstructions) !!}
                                        </div>
                                    </div>

                                    {{-- ATIVAÇÃO DO MINI-PLAYER PARA PRÁTICA LOCAL --}}
                                    <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-3 p-3.5 rounded-xl bg-slate-900/90 border border-slate-800">
                                        <div class="flex items-center gap-2.5 text-xs text-slate-300">
                                            <span class="text-base">🖥️</span>
                                            <span>Praticando em outro software no computador da escola (Word, Excel, Windows)?</span>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto justify-end shrink-0">
                                            <button type="button"
                                                    @click="isMiniPlayer = true"
                                                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 px-3.5 py-2 text-xs font-bold text-slate-200 transition cursor-pointer">
                                                <span>🗗</span>
                                                <span>Abrir no Modo Mini-Player</span>
                                            </button>
                                            <button type="button"
                                                    @click="openMiniPlayerPip()"
                                                    class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 px-4 py-2 text-xs font-bold text-white shadow-md shadow-emerald-600/30 transition cursor-pointer">
                                                <span>📌</span>
                                                <span>Sempre no Topo (PiP)</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        {{-- 5. ENVIO DE TRABALHO PRÁTICO / ARQUIVO PARA VISTO DO EDUCADOR (ASSIGNMENT) --}}
                        @elseif($step->step_type === 'assignment')
                            @php
                                $assignInstructions = !empty(trim($step->content['instructions'] ?? ''))
                                    ? $step->content['instructions']
                                    : (!empty(trim($step->content['body'] ?? ''))
                                        ? $step->content['body']
                                        : 'Salve o arquivo prático criado nesta aula no seu computador e envie-o abaixo para visto do educador.');
                                $allowedExts = $step->content['allowed_extensions'] ?? 'xlsx,docx,pdf,png,jpg,zip';
                                $submittedAssign = $this->submittedAssignment;
                            @endphp
                            <div class="space-y-4">
                                <div class="rounded-2xl border border-indigo-500/30 bg-indigo-950/15 p-5 sm:p-6 space-y-4">
                                    <div class="flex items-center justify-between flex-wrap gap-2">
                                        <div class="flex items-center gap-2 text-indigo-300 text-xs font-bold uppercase tracking-wider">
                                            <span>📤</span>
                                            <span>Entrega de Exercício Prático (Visto do Educador)</span>
                                        </div>
                                        <span class="text-[11px] font-mono text-indigo-300 bg-indigo-500/15 border border-indigo-500/30 px-2.5 py-0.5 rounded-full">
                                            Formatos: {{ $allowedExts }}
                                        </span>
                                    </div>

                                    <div class="prose prose-invert prose-indigo max-w-none text-slate-200 text-sm leading-relaxed">
                                        {!! \Illuminate\Support\Str::markdown($assignInstructions) !!}
                                    </div>

                                    @if($submittedAssign)
                                        <div class="rounded-xl border border-emerald-500/40 bg-emerald-950/30 p-4 text-xs text-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <div class="space-y-1">
                                                <p class="font-bold text-emerald-300 flex items-center gap-1.5">
                                                    <span>✅</span>
                                                    <span>Arquivo Enviado: {{ $submittedAssign->original_name }}</span>
                                                </p>
                                                <p class="text-slate-300">
                                                    Status:
                                                    @if($submittedAssign->status === 'approved')
                                                        <strong class="text-emerald-400">Aprovado pelo Educador (Nota: {{ number_format((float) $submittedAssign->score, 1, ',', '.') }})</strong>
                                                    @elseif($submittedAssign->status === 'revision_requested')
                                                        <strong class="text-amber-400">Revisão Solicitada pelo Educador</strong>
                                                    @else
                                                        <strong class="text-indigo-300">Aguardando Visto do Educador</strong>
                                                    @endif
                                                </p>
                                                @if($submittedAssign->educator_feedback)
                                                    <p class="text-amber-200 mt-1 bg-slate-900/80 p-2.5 rounded-lg border border-slate-800">
                                                        💬 <strong>Feedback do Professor:</strong> {{ $submittedAssign->educator_feedback }}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 space-y-3 text-xs">
                                        <div>
                                            <label class="block font-bold text-white mb-1.5">
                                                {{ $submittedAssign ? 'Substituir Arquivo Prático:' : 'Selecionar Arquivo Criado na Aula (.xlsx, .docx, .png, .pdf, .zip):' }}
                                            </label>
                                            <input
                                                type="file"
                                                wire:model="assignmentFile"
                                                class="block w-full text-xs text-slate-300 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-600 file:text-white hover:file:bg-indigo-500 transition cursor-pointer"
                                            />
                                            @error('assignmentFile')
                                                <p class="text-rose-400 font-bold mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label class="block font-semibold text-slate-300 mb-1">Observações para o Professor (Opcional):</label>
                                            <textarea
                                                wire:model="assignmentNotes"
                                                rows="2"
                                                placeholder="Ex: Concluí a planilha com todas as fórmulas PROCV solicitadas..."
                                                class="w-full rounded-xl border border-slate-700 bg-slate-950 p-3 text-xs text-white placeholder-slate-500 focus:border-indigo-500"
                                            ></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- BANNER DE FEEDBACK IMEDIATO E REPETITIO INACIANA --}}
                        @if($lastAnswerCorrect !== null)
                            <div class="mt-6 rounded-2xl border p-4 sm:p-5 transition {{ $lastAnswerCorrect ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-rose-500/30 bg-rose-500/10 text-rose-300' }}">
                                <div class="flex items-start gap-3">
                                    <span class="text-xl">
                                        {{ $lastAnswerCorrect ? '🎉' : '🔄' }}
                                    </span>
                                    <div class="flex-1 text-xs sm:text-sm">
                                        <p class="font-bold mb-1">
                                            @if($lastAnswerCorrect)
                                                Excelente! Passo validado com sucesso.
                                            @else
                                                Repetitio Inaciana • O erro é o início da compreensão
                                            @endif
                                        </p>
                                        <p class="text-slate-300 leading-relaxed">
                                            {{ $feedbackMessage }}
                                        </p>

                                        @if(! $lastAnswerCorrect)
                                            <div class="mt-3">
                                                <button type="button"
                                                        wire:click="resetAttemptForRepetitio"
                                                        class="inline-flex items-center gap-1.5 rounded-xl bg-rose-500/20 px-3.5 py-1.5 text-xs font-bold text-rose-300 border border-rose-500/30 hover:bg-rose-500 hover:text-white transition">
                                                    🔄 Tentar Novamente (Repetir para Fixar)
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- FOOTER / AÇÕES DE AVANÇO --}}
                    <div class="mt-8 pt-6 border-t border-slate-800/80 flex flex-wrap items-center justify-between gap-4">
                        {{-- CONTROLES PARA PROFESSOR (PREVIEW) OU MODO REVISÃO --}}
                        @if($isPreview || $isReviewMode)
                            <div class="flex items-center gap-2">
                                <button type="button"
                                        wire:click="prevStep"
                                        @disabled($currentStepIndex === 0)
                                        class="rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 disabled:opacity-40 transition cursor-pointer">
                                    ← Anterior
                                </button>
                                <span class="text-xs text-slate-400">Passo {{ $currentStepIndex + 1 }} de {{ $steps->count() }}</span>
                                <button type="button"
                                        wire:click="nextStep"
                                        @disabled($currentStepIndex === $steps->count() - 1)
                                        class="rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-700 disabled:opacity-40 transition cursor-pointer">
                                    Próximo →
                                </button>
                                @if($isReviewMode)
                                    <button type="button"
                                            wire:click="exitReviewMode"
                                            class="rounded-xl bg-indigo-500/20 border border-indigo-500/40 px-3 py-2 text-xs font-bold text-indigo-300 hover:bg-indigo-500 hover:text-white transition cursor-pointer">
                                        ✓ Concluir Revisão
                                    </button>
                                @endif
                            </div>
                        @else
                            <div class="flex items-center gap-1.5">
                                @foreach($steps as $idx => $st)
                                    <div class="h-2 w-2 rounded-full {{ $idx === $currentStepIndex ? 'bg-amber-400 ring-2 ring-amber-400/30' : ($idx < $currentStepIndex ? 'bg-emerald-500' : 'bg-slate-800') }}"></div>
                                @endforeach
                            </div>
                        @endif

                        {{-- BOTÃO PRINCIPAL DE AVANÇO CONFORME O TIPO --}}
                        <div>
                            @if($step->step_type === 'theory')
                                <button type="button"
                                        wire:click="completeTheoryStep"
                                        :disabled="stayRemaining > 0"
                                        class="inline-flex items-center gap-2 rounded-xl px-6 py-3 text-xs sm:text-sm font-bold transition shadow-lg"
                                        :class="stayRemaining > 0 ? 'bg-slate-800 text-slate-400 border border-slate-700 cursor-not-allowed opacity-60' : 'bg-indigo-500 text-white shadow-indigo-500/20 hover:bg-indigo-400 cursor-pointer'">
                                    <template x-if="stayRemaining > 0">
                                        <span class="flex items-center gap-2">
                                            <span class="animate-spin">⏳</span>
                                            <span>Leia atentamente (<span x-text="stayRemaining"></span>s)</span>
                                        </span>
                                    </template>
                                    <template x-if="stayRemaining === 0">
                                        <span class="flex items-center gap-2">
                                            <span>Compreendi o Conceito → Avançar</span>
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                        </span>
                                    </template>
                                </button>

                            @elseif(in_array($step->step_type, ['hotspot_simulation', 'guided_simulation']))
                                <button type="button"
                                        wire:click="nextStep"
                                        @disabled(! $simulationHotspotClicked)
                                        class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-6 py-3 text-xs sm:text-sm font-bold text-slate-950 shadow-lg shadow-amber-500/20 hover:bg-amber-400 disabled:opacity-40 disabled:cursor-not-allowed transition">
                                    <span>Avançar para a Fixação</span>
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                    </svg>
                                </button>

                            @elseif($step->step_type === 'quiz')
                                @if($lastAnswerCorrect)
                                    <button type="button"
                                            wire:click="nextStep"
                                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-6 py-3 text-xs sm:text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/20 hover:bg-emerald-400 transition">
                                        <span>Avançar para a Próxima Etapa</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </button>
                                @else
                                    <button type="button"
                                            wire:click="submitQuizAnswer"
                                            @disabled(! $selectedOptionId)
                                            class="inline-flex items-center gap-2 rounded-xl bg-cyan-500 px-6 py-3 text-xs sm:text-sm font-bold text-slate-950 shadow-lg shadow-cyan-500/20 hover:bg-cyan-400 disabled:opacity-40 disabled:cursor-not-allowed transition">
                                        <span>Confirmar Resposta</span>
                                    </button>
                                @endif

                            @elseif($step->step_type === 'challenge')
                                @if($lastAnswerCorrect)
                                    <button type="button"
                                            wire:click="nextStep"
                                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-6 py-3 text-xs sm:text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/20 hover:bg-emerald-400 transition">
                                        <span>Finalizar e Ir para o Examen</span>
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </button>
                                @else
                                    <button type="button"
                                            wire:click="completeChallenge"
                                            :disabled="stayRemaining > 0"
                                            class="inline-flex items-center gap-2 rounded-xl px-6 py-3 text-xs sm:text-sm font-bold transition shadow-lg"
                                            :class="stayRemaining > 0 ? 'bg-slate-800 text-slate-400 border border-slate-700 cursor-not-allowed opacity-60' : 'bg-emerald-500 text-slate-950 shadow-emerald-500/20 hover:bg-emerald-400 cursor-pointer'">
                                        <template x-if="stayRemaining > 0">
                                            <span class="flex items-center gap-2">
                                                <span class="animate-spin">⏳</span>
                                                <span>Analise o Cenário (<span x-text="stayRemaining"></span>s)</span>
                                            </span>
                                        </template>
                                        <template x-if="stayRemaining === 0">
                                            <span>Concluir Desafio Prático</span>
                                        </template>
                                    </button>
                                @endif

                            @elseif($step->step_type === 'assignment')
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            wire:click="submitAssignment"
                                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-xs sm:text-sm font-bold text-white shadow-lg shadow-indigo-600/25 hover:bg-indigo-500 transition cursor-pointer">
                                        <span>📤 Enviar Arquivo para Visto</span>
                                    </button>
                                    @if($lastAnswerCorrect || $this->submittedAssignment)
                                        <button type="button"
                                                wire:click="nextStep"
                                                class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-xs sm:text-sm font-bold text-slate-950 shadow-lg shadow-emerald-500/20 hover:bg-emerald-400 transition cursor-pointer">
                                            <span>Avançar Etapa →</span>
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </main>
    @endif

    {{-- MODAL DE AUTORIZAÇÃO DO EDUCADOR PARA LIBERAÇÃO PRESENCIAL --}}
    <div x-cloak x-show="$wire.showEducatorAuthModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
        <div class="w-full max-w-md rounded-3xl border border-slate-800 bg-slate-900 p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-500/20 text-purple-400 border border-purple-500/30">
                        🔓
                    </span>
                    <h3 class="text-sm font-bold text-white">Autorização do Educador</h3>
                </div>
                <button type="button" wire:click="$set('showEducatorAuthModal', false)" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <p class="text-xs text-slate-400 leading-relaxed">
                O professor ou coordenador pedagógico deve inserir suas credenciais para liberar esta aula para o aluno neste terminal.
            </p>

            @if($educatorAuthError)
                <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-300">
                    {{ $educatorAuthError }}
                </div>
            @endif

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block font-bold text-slate-300 mb-1">E-mail ou Usuário do Educador</label>
                    <input type="text" wire:model="educatorEmail" placeholder="professor@escola.com ou usuário" class="block w-full rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2.5 text-xs text-white placeholder:text-slate-500 focus:border-purple-500">
                </div>
                <div>
                    <label class="block font-bold text-slate-300 mb-1">Senha</label>
                    <input type="password" wire:model="educatorPassword" placeholder="••••••••" class="block w-full rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-2.5 text-xs text-white placeholder:text-slate-500 focus:border-purple-500">
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-800">
                <button type="button" wire:click="$set('showEducatorAuthModal', false)" class="rounded-xl border border-slate-700 px-4 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="button" wire:click="verifyAndUnlockWithCredentials" class="rounded-xl bg-purple-600 px-5 py-2 text-xs font-bold text-white hover:bg-purple-500 shadow-md transition">
                    Confirmar e Liberar Aula
                </button>
            </div>
        </div>
    </div>

    {{-- SLOT E FLOATING MINI-PLAYER WIDGET COM SUPORTE A ALWAYS-ON-TOP (PICTURE-IN-PICTURE) E BAIXA VISÃO --}}
    @if(! $isLocked && ! $needsTypingWarmup && ! $isLessonCompleted && !empty($step))
    <div id="aurosoft-mini-player-slot">
        <div x-cloak
             id="aurosoft-mini-player-widget"
             x-show="isMiniPlayer"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-8 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-8 scale-95"
             :class="{
                 'w-96 sm:w-[440px]': miniPlayerWidth === 'normal',
                 'w-[95vw] sm:w-[560px]': miniPlayerWidth === 'large',
                 'w-[95vw] sm:w-[720px]': miniPlayerWidth === 'wide',
                 '!border-yellow-400 !bg-black !text-yellow-300 ring-4 ring-yellow-400': highContrast
             }"
             class="fixed bottom-5 right-5 z-50 max-w-[calc(100vw-1.5rem)] rounded-3xl border-2 border-emerald-500/80 bg-slate-900/98 p-4 shadow-2xl backdrop-blur-2xl flex flex-col justify-between gap-3 select-text transition-all duration-200"
        >
            {{-- BARRA SUPERIOR DO MINI-PLAYER COM FERRAMENTAS DE ACESSIBILIDADE E ALWAYS-ON-TOP --}}
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-800 shrink-0"
                 :class="{'!border-yellow-400/50': highContrast}">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-500/20 text-emerald-400 text-xs shrink-0"
                          :class="{'!bg-yellow-400 !text-black font-black': highContrast}">
                        ⚔️
                    </span>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 block"
                                  :class="{'!text-yellow-400': highContrast}">
                                Mini-Player Autônomo
                            </span>
                            <span x-show="pipWindow" class="rounded-full bg-indigo-500/30 text-indigo-300 px-1.5 py-0.2 text-[9px] font-bold border border-indigo-500/40">
                                📌 Sempre no Topo Ativo
                            </span>
                        </div>
                        <h4 class="text-xs font-bold text-white truncate"
                            :class="{'!text-yellow-200 text-sm font-black': highContrast}">
                            {{ $step?->title ?? 'Desafio Prático' }}
                        </h4>
                    </div>
                </div>

                {{-- CONTROLES DO MINI-PLAYER --}}
                <div class="flex items-center gap-1 shrink-0">
                    {{-- BOTÃO FIXAR SEMPRE NO TOPO (PICTURE-IN-PICTURE NATIVO DO NAVEGADOR) --}}
                    <button type="button"
                            data-pip-action="toggle-pip"
                            @click="toggleAlwaysOnTop()"
                            :title="pipWindow ? 'Retornar janela para a página' : 'Fixar flutuante por cima do Word/Excel mesmo com navegador minimizado'"
                            :class="pipWindow ? 'bg-indigo-600 text-white ring-2 ring-indigo-400' : 'bg-slate-800 text-indigo-300 hover:bg-slate-700 hover:text-white border border-indigo-500/30'"
                            class="rounded-lg px-2 py-1 text-[11px] font-bold transition cursor-pointer flex items-center gap-1 shadow-sm">
                        <span>📌</span>
                        <span class="hidden md:inline" x-text="pipWindow ? 'Fixado no Topo' : 'Sempre no Topo'"></span>
                    </button>

                    {{-- ZOOM DE TEXTO (ACESSIBILIDADE / BAIXA VISÃO) --}}
                    <button type="button"
                            data-pip-action="font-size"
                            @click="toggleFontSize()"
                            title="Alterar tamanho da fonte (Acessibilidade para Baixa Visão)"
                            class="rounded-lg bg-slate-800 px-2 py-1.5 text-slate-300 hover:bg-slate-700 hover:text-white transition cursor-pointer text-[11px] font-bold font-mono"
                            :class="{'!bg-yellow-400 !text-black font-black': highContrast}">
                        A+
                    </button>

                    {{-- ALTO CONTRASTE (WCAG AAA / BAIXA VISÃO) --}}
                    <button type="button"
                            data-pip-action="contrast"
                            @click="toggleHighContrast()"
                            title="Alternar modo de alto contraste (Baixa Visão)"
                            :class="highContrast ? 'bg-yellow-400 text-black font-black' : 'bg-slate-800 text-slate-300 hover:bg-slate-700 hover:text-white'"
                            class="rounded-lg p-1.5 transition cursor-pointer text-xs">
                        🌓
                    </button>

                    {{-- CICLAR LARGURA (NORMAL / GRANDE / AMPLO) --}}
                    <button type="button"
                            data-pip-action="cycle-width"
                            @click="cycleWidth()"
                            title="Alternar largura da janela (Normal / Grande / Amplo)"
                            class="rounded-lg bg-slate-800 p-1.5 text-slate-300 hover:bg-slate-700 hover:text-white transition cursor-pointer text-xs">
                        ↔️
                    </button>

                    {{-- FECHAR / EXPANDIR DE VOLTA --}}
                    <button type="button"
                            data-pip-action="expand"
                            @click="returnWidgetFromPip(); isMiniPlayer = false;"
                            title="Expandir de volta para tela completa"
                            class="rounded-lg bg-slate-800 p-1.5 text-slate-300 hover:bg-slate-700 hover:text-white transition cursor-pointer text-xs">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </button>
                    <button type="button"
                            data-pip-action="close"
                            @click="returnWidgetFromPip(); isMiniPlayer = false;"
                            title="Fechar mini-player"
                            class="rounded-lg bg-slate-800 p-1.5 text-slate-400 hover:bg-slate-700 hover:text-white transition cursor-pointer text-xs">
                        ✕
                    </button>
                </div>
            </div>

            {{-- CORPO COM INSTRUÇÕES DA ATIVIDADE FORMATADAS COM ACESSIBILIDADE --}}
            <div id="aurosoft-mini-player-body"
                 class="max-h-72 overflow-y-auto space-y-2 pr-1 flex-1"
                 :class="{
                     'text-xs leading-relaxed': fontSizeLevel === 0,
                     'text-sm leading-relaxed': fontSizeLevel === 1,
                     'text-base font-medium leading-relaxed': fontSizeLevel === 2,
                     'text-lg font-bold leading-relaxed': fontSizeLevel === 3
                 }">
                @if($step?->step_type === 'challenge')
                    <div id="aurosoft-mini-player-card"
                         class="p-3.5 rounded-2xl bg-slate-950/85 border border-slate-800 h-full"
                         :class="{'!bg-black !border-yellow-400 !text-yellow-100': highContrast, 'text-slate-200': !highContrast}">
                        <div class="flex items-center justify-between mb-1.5 pb-1 border-b border-slate-800/60"
                             :class="{'!border-yellow-400/50': highContrast}">
                            <span class="font-bold text-emerald-400 block"
                                  :class="{'!text-yellow-300 text-base font-black': highContrast}">
                                📋 Roteiro da Missão Prática:
                            </span>
                            <span class="text-[10px] uppercase font-mono px-2 py-0.5 rounded bg-slate-800 text-slate-300"
                                  :class="{'!bg-yellow-400 !text-black font-bold': highContrast}">
                                Execute no PC da escola
                            </span>
                        </div>
                        <div class="prose prose-invert max-w-none [&_strong]:text-white [&_strong]:font-black [&_b]:text-white [&_b]:font-black [&_u]:underline [&_kbd]:px-1.5 [&_kbd]:py-0.5 [&_kbd]:text-xs [&_kbd]:bg-slate-800 [&_kbd]:rounded [&_kbd]:text-amber-300"
                             :class="{'!text-yellow-200 [&_strong]:!text-yellow-300 [&_b]:!text-yellow-300 [&_kbd]:!bg-yellow-400 [&_kbd]:!text-black': highContrast}">
                            {!! \Illuminate\Support\Str::markdown($challengeInstructions ?? $step->content['instructions'] ?? $step->content['body'] ?? 'Execute os procedimentos solicitados no software da aula.') !!}
                        </div>
                    </div>
                @else
                    <div id="aurosoft-mini-player-card"
                         class="p-3.5 rounded-2xl bg-slate-950/85 border border-slate-800 text-slate-200 h-full"
                         :class="{'!bg-black !border-yellow-400 !text-yellow-100': highContrast}">
                        <span class="font-bold text-indigo-400 block mb-1"
                              :class="{'!text-yellow-300': highContrast}">
                            {{ $step?->title }}:
                        </span>
                        <div class="prose prose-invert max-w-none text-slate-200"
                             :class="{'!text-yellow-200': highContrast}">
                            {!! \Illuminate\Support\Str::markdown($step?->content['body'] ?? '') !!}
                        </div>
                    </div>
                @endif
            </div>

            {{-- RODAPÉ DE AÇÕES DO MINI-PLAYER (CHAMAR PROFESSOR, SAIR E CONCLUIR) --}}
            <div class="pt-2.5 border-t border-slate-800 flex items-center justify-between gap-2 shrink-0"
                 :class="{'!border-yellow-400/50': highContrast}">
                <div class="flex items-center gap-1.5">
                    @if($helpRequested)
                        <button type="button"
                                data-pip-action="cancel-help"
                                wire:click="cancelHelpRequest"
                                class="rounded-xl bg-rose-500/20 border border-rose-500/40 text-rose-300 px-3 py-1.5 text-xs font-bold hover:bg-rose-500/30 transition cursor-pointer animate-pulse"
                                :class="{'!bg-rose-600 !text-white font-black': highContrast}">
                            🚨 Educador Chamado
                        </button>
                    @else
                        <button type="button"
                                data-pip-action="request-help"
                                wire:click="requestHelp"
                                class="rounded-xl bg-slate-800 border border-slate-700 text-slate-300 hover:text-amber-300 px-3 py-1.5 text-xs font-semibold transition cursor-pointer flex items-center gap-1"
                                :class="{'!bg-yellow-400 !text-black font-bold': highContrast}">
                            <span>🙋</span>
                            <span>Chamar Educador</span>
                        </button>
                    @endif

                    @if(! $isPreview)
                        <button type="button"
                                data-pip-action="exit-lesson"
                                wire:click="exitLesson"
                                class="rounded-xl bg-slate-800/80 border border-slate-700 text-slate-400 hover:text-rose-400 hover:bg-slate-800 px-2.5 py-1.5 text-xs font-semibold transition cursor-pointer"
                                title="Sair da aula e voltar ao portal">
                            🚪 Sair
                        </button>
                    @endif
                </div>

                <div>
                    @if($step?->step_type === 'challenge')
                        <button type="button"
                                data-pip-action="complete-challenge"
                                wire:click="completeChallenge"
                                class="rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 px-4 py-2 text-xs font-bold shadow-md shadow-emerald-500/20 transition cursor-pointer flex items-center gap-1.5"
                                :class="{'!bg-yellow-400 !text-black !font-black !text-sm': highContrast}">
                            <span>✓</span>
                            <span>Concluir Prática Autônoma</span>
                        </button>
                    @elseif($step?->step_type === 'theory')
                        <button type="button"
                                data-pip-action="complete-theory"
                                wire:click="completeTheoryStep"
                                class="rounded-xl bg-indigo-500 hover:bg-indigo-400 text-white px-4 py-2 text-xs font-bold shadow-md transition cursor-pointer">
                            Avançar →
                        </button>
                    @else
                        <button type="button"
                                data-pip-action="next-step"
                                wire:click="nextStep"
                                class="rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 px-4 py-2 text-xs font-bold shadow-md transition cursor-pointer">
                            Próximo →
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL DE ALERTA / MENSAGEM DIRETA DO PROFESSOR NA TELA DO ALUNO --}}
    @if(!empty($activeEducatorMessage))
        <div x-data="{
                init() {
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                        osc.frequency.setValueAtTime(880, ctx.currentTime + 0.14);
                        gain.gain.setValueAtTime(0.15, ctx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start();
                        osc.stop(ctx.currentTime + 0.45);
                    } catch (e) {}
                }
             }"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/85 backdrop-blur-md">
            <div class="w-full max-w-md rounded-3xl border-2 border-indigo-500/70 bg-slate-900 p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3 pb-3 border-b border-slate-800">
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-500/20 text-indigo-400 border border-indigo-500/40 text-xl shrink-0">
                        💬
                    </span>
                    <div>
                        <span class="text-[10px] font-black uppercase tracking-wider text-indigo-400 block">
                            Mensagem Direta do Laboratório
                        </span>
                        <h3 class="text-base font-extrabold text-white">
                            {{ $activeEducatorSender ?: 'Professor' }} enviou um recado:
                        </h3>
                    </div>
                </div>

                <div class="rounded-2xl border border-indigo-500/30 bg-indigo-950/30 p-4 text-sm font-semibold text-white leading-relaxed">
                    "{{ $activeEducatorMessage }}"
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button"
                            wire:click="acknowledgeEducatorMessage"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 px-6 py-2.5 text-xs font-extrabold text-white shadow-lg shadow-indigo-600/30 transition cursor-pointer">
                        <span>✓ Entendido, Professor!</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>


