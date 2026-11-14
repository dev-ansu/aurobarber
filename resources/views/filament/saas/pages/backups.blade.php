<x-filament-panels::page>
    @php
        $backups = $this->backups;
        $totalCount = $backups->count();
        $totalBytes = $backups->sum('size_bytes');
        $totalFormatted = $totalBytes < 1048576 
            ? round($totalBytes / 1024, 1) . ' KB' 
            : ($totalBytes < 1073741824 ? round($totalBytes / 1048576, 2) . ' MB' : round($totalBytes / 1073741824, 2) . ' GB');
        $latest = $backups->first();
        $globalCount = $backups->filter(fn($b) => $b['is_global'])->count();
        $tenantCount = $backups->filter(fn($b) => !$b['is_global'])->count();
    @endphp

    <div style="display: flex; flex-direction: column; gap: 24px;">

        {{-- 1. HEADER DO QUADRO GERAL DE BACKUPS --}}
        <div style="background: linear-gradient(135deg, #0b1120 0%, #1e293b 100%); border: 1px solid #334155; border-radius: 20px; padding: 24px 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
            <div style="display: flex; flex-direction: column; gap: 6px; max-width: 720px;">
                <div style="display: inline-flex; align-items: center; gap: 8px; width: fit-content; padding: 4px 12px; border-radius: 9999px; background: rgba(251, 191, 36, 0.12); border: 1px solid rgba(251, 191, 36, 0.3); color: #fbbf24; font-size: 11px; font-weight: 800; letter-spacing: 0.05em; text-transform: uppercase;">
                    <span style="width: 8px; height: 8px; border-radius: 9999px; background: #fbbf24; display: inline-block;"></span>
                    <span>Segurança & Resiliência dos Dados</span>
                </div>
                <h2 style="font-size: 22px; font-weight: 900; color: #ffffff; margin: 4px 0 0 0;">Quadro Geral de Backups & Restauração</h2>
                <p style="font-size: 13px; color: #94a3b8; margin: 0; line-height: 1.4;">
                    Dumps em formato binário nativo PostgreSQL comprimidos em ZIP. Toda restauração cria automaticamente uma cópia pré-restauração por segurança.
                </p>
            </div>

            <div style="display: flex; align-items: center; justify-content: center; width: 64px; height: 64px; border-radius: 18px; background: rgba(251, 191, 36, 0.12); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.25);">
                <x-filament::icon icon="heroicon-o-server-stack" style="width: 32px; height: 32px;" />
            </div>
        </div>

        {{-- 2. CARDS DE KPIS --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
            {{-- Card 1: Total de Backups --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 18px; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.05em;">Total de Backups</span>
                    <div style="font-size: 28px; font-weight: 900; color: #ffffff; font-family: monospace;">{{ $totalCount }} <span style="font-size: 12px; font-weight: 400; color: #94a3b8;">arquivos</span></div>
                    <span style="font-size: 12px; color: #64748b;">Espaço em disco: <strong style="color: #cbd5e1;">{{ $totalFormatted }}</strong></span>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(251, 191, 36, 0.1); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.25); display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-circle-stack" style="width: 24px; height: 24px;" />
                </div>
            </div>

            {{-- Card 2: Segurança Pré-Restauração --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 18px; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #10b981; letter-spacing: 0.05em;">Garantia Pré-Restauração</span>
                    <div style="font-size: 28px; font-weight: 900; color: #10b981;">Ativa</div>
                    <span style="font-size: 12px; color: #64748b;">Cópia preventiva gerada antes do restore</span>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.25); display: flex; align-items: center; justify-content: center;">
                    <x-filament::icon icon="heroicon-o-shield-check" style="width: 24px; height: 24px;" />
                </div>
            </div>

            {{-- Card 3: Último Backup --}}
            <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 18px; padding: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-direction: column; gap: 4px; min-width: 0;">
                    <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; color: #38bdf8; letter-spacing: 0.05em;">Último Arquivo Gerado</span>
                    <div style="font-size: 16px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $latest ? $latest['created_at_formatted'] : 'Nenhum backup' }}
                    </div>
                    <span style="font-size: 11px; color: #64748b; font-family: monospace; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">
                        {{ $latest ? $latest['filename'] : 'Clique em Gerar Novo Backup' }}
                    </span>
                </div>
                <div style="width: 48px; height: 48px; border-radius: 14px; background: rgba(56, 189, 248, 0.1); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.25); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <x-filament::icon icon="heroicon-o-clock" style="width: 24px; height: 24px;" />
                </div>
            </div>
        </div>

        {{-- 3. FILTROS DE VISUALIZAÇÃO --}}
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
            <div style="display: inline-flex; background: #0f172a; border: 1px solid #1e293b; border-radius: 14px; padding: 4px; gap: 4px;">
                <button 
                    wire:click="$set('filterScope', 'all')"
                    type="button"
                    style="padding: 8px 16px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer; border: none; transition: all 0.2s; {{ $filterScope === 'all' ? 'background: #d97706; color: #ffffff; box-shadow: 0 2px 8px rgba(217, 119, 6, 0.4);' : 'background: transparent; color: #94a3b8;' }}">
                    Todos os Backups ({{ $totalCount }})
                </button>
                <button 
                    wire:click="$set('filterScope', 'global')"
                    type="button"
                    style="padding: 8px 16px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer; border: none; transition: all 0.2s; {{ $filterScope === 'global' ? 'background: #d97706; color: #ffffff; box-shadow: 0 2px 8px rgba(217, 119, 6, 0.4);' : 'background: transparent; color: #94a3b8;' }}">
                    🌐 Base Completa ({{ $globalCount }})
                </button>
                <button 
                    wire:click="$set('filterScope', 'tenants')"
                    type="button"
                    style="padding: 8px 16px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer; border: none; transition: all 0.2s; {{ $filterScope === 'tenants' ? 'background: #d97706; color: #ffffff; box-shadow: 0 2px 8px rgba(217, 119, 6, 0.4);' : 'background: transparent; color: #94a3b8;' }}">
                    💈 Por Barbearia ({{ $tenantCount }})
                </button>
            </div>

            <div style="font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 6px;">
                <x-filament::icon icon="heroicon-o-information-circle" style="width: 16px; height: 16px; color: #fbbf24;" />
                <span>Formato nativo <code style="background: #1e293b; padding: 2px 6px; border-radius: 6px; color: #cbd5e1; font-family: monospace;">PostgreSQL Custom Dump (-Fc)</code> com compressão zlib.</span>
            </div>
        </div>

        {{-- 4. TABELA DE BACKUPS --}}
        <div style="background: #0b1120; border: 1px solid #1e293b; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.4);">
            @if($backups->isEmpty())
                <div style="padding: 48px 24px; text-align: center;">
                    <div style="width: 64px; height: 64px; margin: 0 auto 16px auto; border-radius: 18px; background: #0f172a; display: flex; align-items: center; justify-content: center; color: #64748b; border: 1px solid #1e293b;">
                        <x-filament::icon icon="heroicon-o-archive-box" style="width: 32px; height: 32px;" />
                    </div>
                    <h4 style="font-size: 16px; font-weight: 800; color: #ffffff; margin: 0 0 6px 0;">Nenhum backup encontrado neste filtro</h4>
                    <p style="font-size: 13px; color: #94a3b8; max-width: 420px; margin: 0 auto;">
                        Utilize o botão "+ Gerar Novo Backup" no topo da página para criar o primeiro dump.
                    </p>
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; color: #cbd5e1;">
                        <thead>
                            <tr style="border-bottom: 1px solid #1e293b; background: rgba(15, 23, 42, 0.7); color: #94a3b8; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">
                                <th style="padding: 14px 20px; white-space: nowrap;">Tipo</th>
                                <th style="padding: 14px 20px; white-space: nowrap;">Barbearia / Escopo</th>
                                <th style="padding: 14px 20px; white-space: nowrap;">Nome do Arquivo</th>
                                <th style="padding: 14px 20px; white-space: nowrap;">Tamanho</th>
                                <th style="padding: 14px 20px; white-space: nowrap;">Criado em</th>
                                <th style="padding: 14px 20px; text-align: right; white-space: nowrap;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backups as $item)
                                <tr style="border-bottom: 1px solid #1e293b;">
                                    <td style="padding: 16px 20px; white-space: nowrap;">
                                        @if($item['is_global'])
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 9999px; font-size: 10px; font-weight: 800; background: rgba(251, 191, 36, 0.12); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3);">
                                                🌐 GLOBAL
                                            </span>
                                        @else
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 9999px; font-size: 10px; font-weight: 800; background: rgba(16, 185, 129, 0.12); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);">
                                                💈 BARBEARIA
                                            </span>
                                        @endif
                                    </td>
                                    <td style="padding: 16px 20px;">
                                        <div style="font-weight: 800; color: #ffffff; font-size: 14px;">
                                            {{ $item['barbershop_name'] }}
                                        </div>
                                        @if(!$item['is_global'])
                                            <span style="display: block; font-size: 11px; font-family: monospace; color: #94a3b8; margin-top: 2px;">slug: /{{ $item['scope'] }}</span>
                                        @endif
                                    </td>
                                    <td style="padding: 16px 20px; font-family: monospace; font-size: 12px; color: #cbd5e1;">
                                        <span style="padding: 4px 8px; border-radius: 8px; background: #0f172a; border: 1px solid #1e293b; display: inline-block;">
                                            {{ $item['filename'] }}
                                        </span>
                                    </td>
                                    <td style="padding: 16px 20px; font-weight: 800; color: #f1f5f9; white-space: nowrap;">
                                        {{ $item['size_formatted'] }}
                                    </td>
                                    <td style="padding: 16px 20px; color: #94a3b8; font-weight: 500; white-space: nowrap;">
                                        {{ $item['created_at_formatted'] }}
                                    </td>
                                    <td style="padding: 16px 20px; text-align: right; white-space: nowrap;">
                                        <div style="display: inline-flex; align-items: center; gap: 8px;">
                                            <button
                                                wire:click="restoreBackup(@js($item['filename']))"
                                                wire:confirm="Tem certeza de que deseja restaurar este backup? Uma cópia de segurança preventiva será gerada automaticamente antes da restauração."
                                                type="button"
                                                style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 10px; background: rgba(251, 191, 36, 0.12); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.35); font-weight: 800; font-size: 12px; cursor: pointer; transition: all 0.2s;"
                                                onmouseover="this.style.background='rgba(251, 191, 36, 0.25)'"
                                                onmouseout="this.style.background='rgba(251, 191, 36, 0.12)'">
                                                <x-filament::icon icon="heroicon-m-arrow-uturn-left" style="width: 14px; height: 14px;" />
                                                <span>Restaurar</span>
                                            </button>
                                            <button
                                                wire:click="deleteBackup(@js($item['filename']))"
                                                wire:confirm="Excluir este arquivo de backup permanentemente do disco da VPS?"
                                                type="button"
                                                style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 10px; background: rgba(239, 68, 68, 0.12); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.35); font-weight: 800; font-size: 12px; cursor: pointer; transition: all 0.2s;"
                                                onmouseover="this.style.background='rgba(239, 68, 68, 0.25)'"
                                                onmouseout="this.style.background='rgba(239, 68, 68, 0.12)'">
                                                <x-filament::icon icon="heroicon-m-trash" style="width: 14px; height: 14px;" />
                                                <span>Excluir</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>