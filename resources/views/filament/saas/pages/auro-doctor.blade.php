<x-filament-panels::page>
    <div style="width: 100%; display: flex; flex-direction: column; gap: 24px; color: #f8fafc; font-family: inherit;">

        {{-- 1. BANNER PRINCIPAL DO AURODOCTOR --}}
        <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border: 1px solid #334155; border-radius: 20px; padding: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); display: flex; flex-direction: column; gap: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
                <div style="display: flex; flex-direction: column; gap: 6px; max-width: 650px;">
                    <div style="display: inline-flex; align-items: center; gap: 8px; width: fit-content; background: rgba(217, 119, 6, 0.2); border: 1px solid rgba(217, 119, 6, 0.5); padding: 4px 12px; border-radius: 9999px; color: #fbbf24; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                        <span>🩺</span>
                        <span>AuroDoctor • Robô Clínico Multi-Tenant</span>
                    </div>
                    <h2 style="margin: 4px 0 0; font-size: 24px; font-weight: 900; color: #ffffff; letter-spacing: -0.02em;">
                        Diagnóstico Geral & Saúde da Plataforma
                    </h2>
                    <p style="margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.6;">
                        Execute varreduras clínicas automáticas: audite a conectividade do banco PostgreSQL, permissões de disco, integridade das 6 tabelas centrais do AuroBarber, motor de agendamentos e rotas de acesso.
                    </p>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px; min-width: 280px;">
                    <div>
                        <label style="display: block; font-size: 11px; font-weight: 800; color: #fbbf24; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                            Alvo do Diagnóstico
                        </label>
                        <select wire:model.live="selectedTenant"
                                style="width: 100%; background: #0b1120; border: 1px solid #475569; color: #f8fafc; font-size: 13px; font-weight: 700; padding: 10px 14px; border-radius: 12px; outline: none; cursor: pointer;">
                            @foreach($this->getAvailableTenants() as $key => $label)
                                <option value="{{ $key }}" style="background: #0b1120; color: #ffffff;">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button wire:click="runDiagnostics"
                                wire:loading.attr="disabled"
                                type="button"
                                style="flex: 1; background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; font-weight: 800; font-size: 13px; padding: 12px 18px; border-radius: 12px; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 14px rgba(217, 119, 6, 0.4); transition: all 0.2s ease;">
                            <span wire:loading.remove wire:target="runDiagnostics">⚡ Examinar Agora</span>
                            <span wire:loading wire:target="runDiagnostics">Examinando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if($report)
            {{-- 2. STATUS GERAL / SCORE --}}
            @php
                $isHealthy = $report['status'] === 'healthy';
                $isWarning = $report['status'] === 'warning';
                $accentColor = $isHealthy ? '#10b981' : ($isWarning ? '#f59e0b' : '#ef4444');
                $accentBg = $isHealthy ? 'rgba(16, 185, 129, 0.12)' : ($isWarning ? 'rgba(245, 158, 11, 0.12)' : 'rgba(239, 68, 68, 0.12)');
                $accentBorder = $isHealthy ? 'rgba(16, 185, 129, 0.35)' : ($isWarning ? 'rgba(245, 158, 11, 0.35)' : 'rgba(239, 68, 68, 0.35)');
            @endphp

            <div style="background: {{ $accentBg }}; border: 1px solid {{ $accentBorder }}; border-radius: 20px; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="display: flex; align-items: center; gap: 18px;">
                    <div style="width: 60px; height: 60px; border-radius: 16px; background: {{ $accentColor }}; display: flex; align-items: center; justify-content: center; color: #ffffff; font-size: 28px; flex-shrink: 0; box-shadow: 0 6px 18px rgba(0,0,0,0.3);">
                        @if($isHealthy)
                            ✓
                        @elseif($isWarning)
                            !
                        @else
                            ✕
                        @endif
                    </div>
                    <div>
                        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                            <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: {{ $accentColor }}; letter-spacing: 0.05em;">
                                Diagnóstico Clínico do Sistema
                            </span>
                            <span style="font-size: 11px; color: #94a3b8;">• Executado em {{ \Carbon\Carbon::parse($report['executed_at'])->format('d/m/Y H:i:s') }} ({{ $report['duration_seconds'] }}s)</span>
                        </div>
                        <h3 style="margin: 4px 0 0; font-size: 20px; font-weight: 900; color: #ffffff;">
                            @if($isHealthy)
                                🎉 O AuroBarber está 100% saudável e operacional!
                            @elseif($isWarning)
                                ⚠️ Sistema operacional com alertas de atenção
                            @else
                                🚨 Foram encontradas falhas que necessitam correção imediata!
                            @endif
                        </h3>
                    </div>
                </div>

                <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 16px; padding: 14px 24px; text-align: center; min-width: 140px;">
                    <span style="display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em;">Score de Saúde</span>
                    <div style="font-size: 32px; font-weight: 900; color: {{ $accentColor }}; margin-top: 2px;">
                        {{ $report['health_score'] }}%
                    </div>
                </div>
            </div>

            {{-- 3. GRADE DE METRICAS RAPIDAS --}}
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 16px; padding: 18px 20px;">
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em;">Total de Checagens</span>
                    <div style="font-size: 26px; font-weight: 900; color: #ffffff; margin-top: 6px;">{{ $report['total_checks'] }}</div>
                </div>

                <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 16px; padding: 18px 20px;">
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #10b981; letter-spacing: 0.05em;">Aprovados</span>
                    <div style="font-size: 26px; font-weight: 900; color: #10b981; margin-top: 6px;">{{ $report['passed'] }}</div>
                </div>

                <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 16px; padding: 18px 20px;">
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #f59e0b; letter-spacing: 0.05em;">Alertas</span>
                    <div style="font-size: 26px; font-weight: 900; color: #f59e0b; margin-top: 6px;">{{ $report['warnings'] }}</div>
                </div>

                <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 16px; padding: 18px 20px;">
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #ef4444; letter-spacing: 0.05em;">Falhas Críticas</span>
                    <div style="font-size: 26px; font-weight: 900; color: {{ $report['failed'] > 0 ? '#ef4444' : '#64748b' }}; margin-top: 6px;">{{ $report['failed'] }}</div>
                </div>
            </div>

            {{-- 4. FILTROS DE CATEGORIA --}}
            <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px;">
                @php
                    $categories = [
                        'all' => 'Todas as Checagens',
                        'database' => '🗄️ Banco & Estrutura',
                        'crud' => '💈 Barbearias & Agendamentos',
                        'financial' => '💰 Motor Financeiro',
                        'routes' => '🌐 Rotas & Telas',
                        'telemetry' => '🛡️ Telemetria & Incidentes',
                    ];
                @endphp

                @foreach($categories as $key => $label)
                    <button wire:click="setCategory('{{ $key }}')"
                            type="button"
                            style="padding: 8px 16px; border-radius: 12px; font-size: 12px; font-weight: 800; cursor: pointer; white-space: nowrap; border: 1px solid {{ $activeCategory === $key ? '#f59e0b' : '#1e293b' }}; background: {{ $activeCategory === $key ? '#f59e0b' : '#0b1120' }}; color: {{ $activeCategory === $key ? '#0f172a' : '#cbd5e1' }}; transition: all 0.2s ease;">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- 5. LISTA DE CHECAGENS DETALHADAS --}}
            <div style="display: flex; flex-direction: column; gap: 12px;">
                @foreach($report['checks'] as $check)
                    @if($activeCategory === 'all' || $activeCategory === $check['category'])
                        @php
                            $cPassed = $check['status'] === 'passed';
                            $cWarning = $check['status'] === 'warning';
                            $cColor = $cPassed ? '#10b981' : ($cWarning ? '#f59e0b' : '#ef4444');
                            $cBorder = $cPassed ? '#1e293b' : ($cWarning ? 'rgba(245, 158, 11, 0.4)' : 'rgba(239, 68, 68, 0.5)');
                            $cBg = $cPassed ? '#0b1120' : ($cWarning ? 'rgba(245, 158, 11, 0.08)' : 'rgba(239, 68, 68, 0.08)');
                        @endphp

                        <div style="background: {{ $cBg }}; border: 1px solid {{ $cBorder }}; border-radius: 16px; padding: 18px 22px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                            <div style="display: flex; align-items: flex-start; gap: 16px; flex: 1;">
                                <div style="width: 32px; height: 32px; border-radius: 10px; background: rgba(255, 255, 255, 0.05); border: 1px solid {{ $cBorder }}; display: flex; align-items: center; justify-content: center; color: {{ $cColor }}; font-weight: 900; font-size: 15px; flex-shrink: 0; margin-top: 2px;">
                                    @if($cPassed)
                                        ✓
                                    @elseif($cWarning)
                                        !
                                    @else
                                        ✕
                                    @endif
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                        <h4 style="margin: 0; font-size: 14px; font-weight: 800; color: #ffffff;">
                                            {{ $check['name'] }}
                                        </h4>
                                        <span style="font-size: 10px; font-family: monospace; background: rgba(255, 255, 255, 0.06); padding: 2px 8px; border-radius: 6px; color: #94a3b8;">
                                            {{ $check['duration_ms'] }} ms
                                        </span>
                                    </div>
                                    <p style="margin: 0; font-size: 13px; color: {{ $check['status'] === 'failed' ? '#fca5a5' : '#94a3b8' }}; line-height: 1.5;">
                                        {{ $check['message'] }}
                                    </p>
                                    @if(!empty($check['error']))
                                        <div style="margin-top: 6px; padding: 10px 14px; border-radius: 10px; background: rgba(0, 0, 0, 0.4); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; font-family: monospace; font-size: 11px;">
                                            {{ $check['error'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <span style="padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; background: rgba(255, 255, 255, 0.06); border: 1px solid {{ $cBorder }}; color: {{ $cColor }};">
                                {{ $cPassed ? 'Aprovado' : ($cWarning ? 'Alerta' : 'Falhou') }}
                            </span>
                        </div>
                    @endif
                @endforeach
            </div>
        @else
            {{-- ESTADO INICIAL --}}
            <div style="background: #0b1120; border: 1px dashed #334155; border-radius: 20px; padding: 48px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 16px;">
                <div style="width: 64px; height: 64px; border-radius: 20px; background: rgba(217, 119, 6, 0.15); border: 1px solid rgba(217, 119, 6, 0.3); display: flex; align-items: center; justify-content: center; font-size: 28px; color: #fbbf24;">
                    🩺
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 18px; font-weight: 900; color: #ffffff;">Nenhum diagnóstico registrado recentemente</h3>
                    <p style="margin: 6px 0 0; font-size: 13px; color: #94a3b8; max-width: 440px;">
                        Clique no botão abaixo para o robô percorrer o sistema, auditar o banco e emitir o relatório de integridade completo.
                    </p>
                </div>
                <button wire:click="runDiagnostics"
                        type="button"
                        style="background: linear-gradient(135deg, #d97706, #b45309); color: #ffffff; font-weight: 800; font-size: 14px; padding: 12px 28px; border-radius: 12px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(217, 119, 6, 0.4);">
                    Executar Diagnóstico Agora
                </button>
            </div>
        @endif

    </div>
</x-filament-panels::page>
