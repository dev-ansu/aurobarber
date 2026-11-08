<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pauta de Chamada e Assinaturas - {{ $date->format('d/m/Y') }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 10mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background-color: #f8fafc;
            margin: 0;
            padding: 15px;
        }

        .no-print {
            max-width: 900px;
            margin: 0 auto 15px auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 12px 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: #0f172a;
            color: #ffffff;
        }

        .btn-secondary {
            background-color: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }

        .sheet-container {
            max-width: 900px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 10mm;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .school-title {
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
        }

        .school-subtitle {
            font-size: 9px;
            color: #64748b;
            margin-top: 2px;
        }

        .sheet-title-box {
            text-align: right;
        }

        .sheet-title {
            font-size: 14px;
            font-weight: 800;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meta-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 14px;
            font-size: 10px;
        }

        .meta-item strong {
            display: block;
            font-size: 8px;
            text-transform: uppercase;
            color: #64748b;
        }

        .meta-item span {
            font-weight: 700;
            color: #0f172a;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th, td {
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            vertical-align: middle;
        }

        th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 9px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .sign-cell {
            width: 250px;
            height: 32px;
            vertical-align: bottom;
            padding-bottom: 4px;
        }

        .sign-line {
            border-bottom: 1px solid #94a3b8;
            height: 18px;
        }

        .checkbox-cell {
            text-align: center;
            white-space: nowrap;
            width: 80px;
            font-size: 9px;
            font-weight: 700;
        }

        .footer-signatures {
            margin-top: 35px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .sign-block {
            width: 45%;
            text-align: center;
            border-top: 1px solid #334155;
            padding-top: 5px;
            font-size: 10px;
            color: #475569;
        }

        .sign-block strong {
            display: block;
            color: #0f172a;
            font-size: 11px;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .sheet-container {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: none !important;
            }
        }
    </style>
</head>
<body>

    @php
        $schoolTrade = $school?->trade_name ?: $school?->legal_name ?: 'Aurosoft Educacional';
        $schoolCnpj = $school?->cnpj ? 'CNPJ: '.$school->cnpj : null;
        $schoolPhone = $school?->phone ? 'Tel: '.$school->phone : null;

        $weekdays = [
            0 => 'Domingo',
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
        ];
        $diaSemana = $weekdays[$date->dayOfWeek] ?? '';
    @endphp

    <div class="no-print">
        <div>
            <strong>Pauta de Chamada:</strong> {{ $date->format('d/m/Y') }} ({{ $diaSemana }})
            @if($classroom) · Sala: {{ $classroom->name }} @endif
            @if($startHour) · Horário: {{ $startHour }} @endif
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Imprimir Pauta
            </button>
            <a href="javascript:window.close()" class="btn btn-secondary">Fechar</a>
        </div>
    </div>

    <div class="sheet-container">
        <!-- CABEÇALHO -->
        <div class="header">
            <div>
                <div class="school-title">{{ $schoolTrade }}</div>
                <div class="school-subtitle">
                    @if($schoolCnpj) {{ $schoolCnpj }} @endif
                    @if($schoolPhone) · {{ $schoolPhone }} @endif
                </div>
            </div>
            <div class="sheet-title-box">
                <div class="sheet-title">Pauta de Chamada</div>
                <div style="font-size: 9px; color: #64748b;">Registro de Frequência e Assinatura</div>
            </div>
        </div>

        <!-- FAIXA DE METADADOS DA SESSÃO -->
        <div class="meta-strip">
            <div class="meta-item">
                <strong>Data da Aula</strong>
                <span>{{ $date->format('d/m/Y') }} ({{ $diaSemana }})</span>
            </div>
            <div class="meta-item">
                <strong>Sala / Espaço</strong>
                <span>{{ $classroom ? $classroom->name : 'Todas as Salas' }}</span>
            </div>
            <div class="meta-item">
                <strong>Horário</strong>
                <span>{{ $startHour ?: 'Todos os horários' }}</span>
            </div>
            <div class="meta-item">
                <strong>Total Alunos</strong>
                <span>{{ $attendances->count() }} matriculado(s)</span>
            </div>
        </div>

        <!-- TABELA DE CHAMADA -->
        <table>
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">Nº</th>
                    <th>Nome do Aluno</th>
                    <th style="width: 90px; text-align: center;">Horário</th>
                    <th>Formação / Curso</th>
                    <th class="checkbox-cell">Presença</th>
                    <th style="width: 230px;">Assinatura do Aluno</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $index => $item)
                    @php
                        if ($isFuture) {
                            $schedule = $item;
                            $student = $schedule->contractItem?->contract?->student;
                            $timeStr = \Carbon\Carbon::parse($schedule->start_hour)->format('H:i').' - '.\Carbon\Carbon::parse($schedule->end_hour)->format('H:i');
                            $courseName = $schedule->contractItem?->formation?->name
                                ?? $schedule->contractItem?->contractCourses?->first()?->course?->name
                                ?? 'Curso Regular';
                            $status = null;
                        } else {
                            $attendance = $item;
                            $schedule = $attendance->studentSchedule;
                            $student = $schedule?->contractItem?->contract?->student;
                            $session = $attendance->session;
                            $timeStr = $session ? \Carbon\Carbon::parse($session->start_hour)->format('H:i').' - '.\Carbon\Carbon::parse($session->end_hour)->format('H:i') : '—';
                            $courseName = $schedule?->contractItem?->formation?->name
                                ?? $schedule?->contractItem?->contractCourses?->first()?->course?->name
                                ?? 'Curso Regular';
                            $status = $attendance->status;
                        }
                    @endphp
                    <tr>
                        <td style="text-align: center; font-weight: 700; color: #64748b;">
                            {{ $loop->iteration }}
                        </td>
                        <td>
                            <strong style="color: #0f172a; font-size: 11px;">{{ $student?->name ?? 'Aluno não identificado' }}</strong>
                            <div style="font-size: 8px; color: #64748b;">Cód: {{ $student?->ouro_moderno_cod_int ?: $student?->id }}</div>
                        </td>
                        <td style="text-align: center; font-weight: 600; color: #334155;">
                            {{ $timeStr }}
                        </td>
                        <td style="color: #334155;">
                            {{ $courseName }}
                        </td>
                        <td class="checkbox-cell">
                            [ &nbsp; ] P &nbsp;&nbsp; [ &nbsp; ] F
                        </td>
                        <td class="sign-cell">
                            <div class="sign-line"></div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 25px; color: #64748b;">
                            Nenhum aluno agendado para esta data e filtros selecionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- RESUMO E ASSINATURAS DO PROFESSOR/COORDENAÇÃO -->
        <div class="footer-signatures">
            <div class="sign-block">
                <strong>Assinatura do(a) Instrutor(a) / Professor(a)</strong>
                <span>Responsável pela aplicação da aula</span>
            </div>
            <div class="sign-block">
                <strong>Secretaria Escolar / Coordenação Pedagógica</strong>
                <span>Visto e conferência da pauta</span>
            </div>
        </div>
    </div>

</body>
</html>
