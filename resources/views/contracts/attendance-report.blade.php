<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Presenças e Faltas - {{ $contract->student?->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #0f172a;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
            font-size: 10pt;
            line-height: 1.45;
        }

        .no-print {
            max-width: 850px;
            margin: 0 auto 20px auto;
            background: #ffffff;
            border-radius: 10px;
            padding: 14px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .filter-input {
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 12px;
            color: #1e293b;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #0f172a;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #1e293b;
        }

        .btn-secondary {
            background-color: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }

        .doc-page {
            max-width: 850px;
            min-height: 280mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 18mm 18mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .school-header {
            text-align: center;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }

        .school-name {
            font-size: 16pt;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 3px;
        }

        .school-info {
            font-size: 8.5pt;
            color: #475569;
        }

        .doc-title-box {
            text-align: center;
            margin: 12px 0 18px 0;
        }

        .doc-title {
            font-size: 13.5pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .doc-subtitle {
            font-size: 9pt;
            color: #64748b;
            margin-top: 2px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 16px;
            font-size: 9.5pt;
        }

        .info-item strong {
            color: #334155;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .kpi-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 12px;
            text-align: center;
            background: #ffffff;
        }

        .kpi-value {
            font-size: 15pt;
            font-weight: bold;
            color: #0f172a;
        }

        .kpi-label {
            font-size: 8pt;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            margin-top: 2px;
        }

        table.attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 8.8pt;
        }

        table.attendance-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            text-align: left;
            font-weight: 700;
            padding: 7px 8px;
            border: 1px solid #cbd5e1;
            text-transform: uppercase;
            font-size: 7.8pt;
        }

        table.attendance-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: middle;
        }

        table.attendance-table tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            font-size: 7.5pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-present {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }

        .badge-absent {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .signatures-area {
            display: flex;
            justify-content: space-around;
            text-align: center;
            margin-top: 35px;
        }

        .sign-box {
            width: 42%;
            border-top: 1px solid #1f2937;
            padding-top: 6px;
            font-size: 9.5pt;
        }

        .sign-box .role {
            font-size: 8pt;
            color: #64748b;
            text-transform: uppercase;
        }

        .doc-footer {
            border-top: 1px dashed #cbd5e1;
            padding-top: 8px;
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            font-size: 8pt;
            color: #64748b;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .doc-page {
                box-shadow: none !important;
                padding: 5mm 8mm !important;
                margin: 0 !important;
                min-height: auto !important;
            }
        }
    </style>
</head>
<body>

    @php
        $schoolTrade = $school['trade_name'] ?? $school['legal_name'] ?? 'Aurosoft Cursos Profissionalizantes';
        $schoolLogo = !empty($school['logo_url']) ? $school['logo_url'] : (!empty($school['logo_path']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($school['logo_path']) : null);
        $schoolCnpj = $school['cnpj'] ?? null;
        $schoolPhone = $school['phone'] ?? null;
        $schoolEmail = $school['email'] ?? null;

        $schoolAddress = collect([
            trim(($school['street'] ?? '').' '.($school['number'] ?? '')),
            $school['complement'] ?? null,
            $school['neighborhood'] ?? null,
            trim(($school['city'] ?? '').(!empty($school['state']) ? ' - '.$school['state'] : '')),
        ])->filter()->join(', ');

        $studentCpf = $contract->student?->cpf
            ?? $contract->student?->documents?->firstWhere('type', 'cpf')?->value;
    @endphp

    <div class="no-print">
        <form method="GET" action="{{ route('contracts.attendance-report', $contract) }}" class="filter-form">
            <strong>Filtrar período:</strong>
            <input type="date" name="from" value="{{ $from }}" class="filter-input" title="Data inicial">
            <span>até</span>
            <input type="date" name="until" value="{{ $until }}" class="filter-input" title="Data final">
            <select name="status" class="filter-input">
                <option value="">Todas (Presenças e Faltas)</option>
                <option value="present" @selected($statusFilter === 'present')>Somente Presenças</option>
                <option value="absent" @selected($statusFilter === 'absent')>Somente Faltas</option>
            </select>
            <button type="submit" class="btn btn-secondary">Filtrar</button>
            @if($from || $until || $statusFilter)
                <a href="{{ route('contracts.attendance-report', $contract) }}" class="btn btn-secondary">Limpar</a>
            @endif
        </form>

        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                Imprimir / Salvar PDF
            </button>
            <a href="javascript:window.close()" class="btn btn-secondary">Fechar</a>
        </div>
    </div>

    <div class="doc-page">
        <div>
            <!-- CABEÇALHO -->
            <div class="school-header">
                @if ($schoolLogo)
                    <div style="margin-bottom: 6px;">
                        <img src="{{ $schoolLogo }}" alt="Logotipo da Escola" style="max-height: 46px; max-width: 170px; object-fit: contain;">
                    </div>
                @endif
                <div class="school-name">{{ $schoolTrade }}</div>
                <div class="school-info">
                    @if($schoolCnpj) CNPJ: {{ $schoolCnpj }} · @endif
                    @if($schoolAddress) {{ $schoolAddress }} · @endif
                    @if($schoolPhone) Tel: {{ $schoolPhone }} @endif
                    @if($schoolEmail) · E-mail: {{ $schoolEmail }} @endif
                </div>
            </div>

            <!-- TÍTULO -->
            <div class="doc-title-box">
                <div class="doc-title">Relatório Individual de Presenças e Faltas</div>
                <div class="doc-subtitle">
                    Extrato pedagógico de assiduidade vinculado ao Contrato #{{ $contract->contract_number }}
                    @if($from || $until)
                        · Período: {{ $from ? \Carbon\Carbon::parse($from)->format('d/m/Y') : 'Início' }} até {{ $until ? \Carbon\Carbon::parse($until)->format('d/m/Y') : 'Hoje' }}
                    @endif
                </div>
            </div>

            <!-- DADOS DO ALUNO E CONTRATO -->
            <div class="info-grid">
                <div class="info-item"><strong>Aluno(a):</strong> {{ $contract->student?->name ?? '—' }}</div>
                <div class="info-item"><strong>CPF:</strong> {{ $studentCpf ?: '—' }}</div>
                <div class="info-item"><strong>Contrato:</strong> #{{ $contract->contract_number }} ({{ $contract->start_date ? $contract->start_date->format('d/m/Y') : '—' }})</div>
                <div class="info-item"><strong>Formação / Curso:</strong> {{ $courseTitle }}</div>
                <div class="info-item" style="grid-column: span 2;">
                    <strong>Grade Horária Semanal:</strong> {{ $scheduleSummary ?: 'Horário flexível / Não agendado' }}
                </div>
            </div>

            <!-- INDICADORES RESUMIDOS -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-value">{{ $totalClasses }}</div>
                    <div class="kpi-label">Aulas Registradas</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value" style="color: #166534;">{{ $presentCount }}</div>
                    <div class="kpi-label">Presenças</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value" style="color: #991b1b;">{{ $absentCount }}</div>
                    <div class="kpi-label">Faltas</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-value" style="color: #1e3a8a;">{{ number_format($attendanceRate, 1, ',', '.') }}%</div>
                    <div class="kpi-label">Frequência</div>
                </div>
            </div>

            <!-- TABELA DE HISTÓRICO -->
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th style="width: 11%;">Data</th>
                        <th style="width: 13%;">Horário</th>
                        <th style="width: 24%;">Formação</th>
                        <th style="width: 14%;">Sala</th>
                        <th style="width: 11%; text-align: center;">Situação</th>
                        <th style="width: 15%;">Registrado por</th>
                        <th style="width: 12%;">Observação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $att)
                        @php
                            $start = $att->session?->start_hour ? substr((string) $att->session->start_hour, 0, 5) : ($att->studentSchedule?->start_hour ? substr((string) $att->studentSchedule->start_hour, 0, 5) : '—');
                            $end = $att->session?->end_hour ? substr((string) $att->session->end_hour, 0, 5) : ($att->studentSchedule?->end_hour ? substr((string) $att->studentSchedule->end_hour, 0, 5) : '—');
                            $registeredBy = $att->markedBy?->person?->name
                                ?? $att->markedBy?->name
                                ?? ($att->marking_source === 'kiosk' ? 'Totem / Kiosk' : 'Sistema');
                        @endphp
                        <tr>
                            <td><strong>{{ $att->attendance_date?->format('d/m/Y') ?? '—' }}</strong></td>
                            <td>{{ $start }} às {{ $end }}</td>
                            <td>{{ $att->studentSchedule?->contractItem?->formation?->name ?? $courseTitle }}</td>
                            <td>{{ $att->session?->classroom?->name ?? '—' }}</td>
                            <td style="text-align: center;">
                                @if($att->status === 'present')
                                    <span class="badge badge-present">Presente</span>
                                @else
                                    <span class="badge badge-absent">Falta</span>
                                @endif
                            </td>
                            <td>
                                {{ $registeredBy }}
                                @if($att->marked_at)
                                    <div style="font-size: 7pt; color: #64748b;">{{ $att->marked_at->format('d/m/Y H:i') }}</div>
                                @endif
                            </td>
                            <td>{{ $att->note ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: #64748b;">
                                Nenhum registro de presença ou falta encontrado para este contrato no período selecionado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- ASSINATURAS E RODAPÉ -->
        <div>
            <div class="signatures-area">
                <div class="sign-box">
                    <div style="font-weight: bold; color: #0f172a;">{{ $schoolRepresentative ?: $schoolTrade }}</div>
                    <div class="role">Coordenação Pedagógica / Secretaria</div>
                </div>
                <div class="sign-box">
                    <div style="font-weight: bold; color: #0f172a;">{{ $contract->student?->name ?? 'Aluno(a) / Responsável' }}</div>
                    <div class="role">Ciência do Aluno ou Responsável</div>
                </div>
            </div>

            <div class="doc-footer">
                <div>Contrato: #{{ $contract->contract_number }} · Aluno: {{ $contract->student?->name }}</div>
                <div>Relatório emitido via Aurosoft Gestão Escolar</div>
                <div>Emissão: {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

</body>
</html>
