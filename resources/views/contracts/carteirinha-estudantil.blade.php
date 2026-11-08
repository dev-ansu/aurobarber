<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carteirinha Estudantil - {{ $contract->student?->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 20mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: Arial, sans-serif;
            color: #1e293b;
            background-color: #f1f5f9;
            margin: 0;
            padding: 20px;
        }

        .no-print {
            max-width: 800px;
            margin: 0 auto 20px auto;
            background: #ffffff;
            border-radius: 8px;
            padding: 12px 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
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
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #1e3a8a;
            color: #ffffff;
        }

        .btn-primary:hover {
            background-color: #1e40af;
        }

        .btn-secondary {
            background-color: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }

        .cards-container {
            max-width: 800px;
            margin: 30px auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 25px;
        }

        .card-row {
            display: flex;
            gap: 20px;
            justify-content: center;
            align-items: center;
        }

        /* Dimensões padrão CR-80 (85.6mm x 53.98mm) */
        .id-card {
            width: 86mm;
            height: 54mm;
            border-radius: 4mm;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 1px solid #cbd5e1;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 3.5mm;
        }

        .card-front {
            background: linear-gradient(135deg, #ffffff 60%, #eff6ff 100%);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1.5px solid #1e3a8a;
            padding-bottom: 2mm;
        }

        .card-logo {
            max-height: 7mm;
            max-width: 35mm;
            object-fit: contain;
        }

        .card-school-name {
            font-size: 7pt;
            font-weight: 800;
            color: #1e3a8a;
            text-transform: uppercase;
            text-align: right;
            max-width: 45mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-title-pill {
            background: #1e3a8a;
            color: #ffffff;
            font-size: 5.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1px 6px;
            border-radius: 3px;
            display: inline-block;
            margin-top: 1mm;
        }

        .card-main {
            display: flex;
            gap: 3mm;
            align-items: center;
            margin: 2mm 0;
        }

        .photo-box {
            width: 19mm;
            height: 24mm;
            border-radius: 2mm;
            border: 1px solid #94a3b8;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 6pt;
            color: #94a3b8;
            text-align: center;
            flex-shrink: 0;
            overflow: hidden;
        }

        .student-avatar {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #475569;
            font-size: 14pt;
            font-weight: bold;
        }

        .student-data {
            flex-grow: 1;
            font-size: 6.5pt;
            line-height: 1.35;
        }

        .data-label {
            font-size: 5pt;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            display: block;
        }

        .student-name {
            font-size: 8pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            line-height: 1.1;
            margin-bottom: 1.5mm;
            max-height: 6mm;
            overflow: hidden;
        }

        .card-footer-front {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            border-top: 1px dashed #cbd5e1;
            padding-top: 1.5mm;
            font-size: 5.5pt;
            color: #64748b;
        }

        .badge-matricula {
            font-family: monospace;
            font-size: 6.5pt;
            font-weight: bold;
            color: #1e3a8a;
        }

        /* VERSO */
        .card-back {
            background: #ffffff;
            font-size: 6pt;
            line-height: 1.3;
        }

        .back-grid {
            display: grid;
            grid-template-columns: 1fr 18mm;
            gap: 2mm;
            align-items: center;
        }

        .qr-placeholder {
            width: 18mm;
            height: 18mm;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 1mm;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 4.5pt;
            color: #475569;
        }

        .legal-notice {
            font-size: 4.8pt;
            color: #64748b;
            text-align: justify;
            line-height: 1.25;
            margin-top: 1.5mm;
            border-top: 1px solid #e2e8f0;
            padding-top: 1.5mm;
        }

        .back-signatures {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 1.5mm;
        }

        .sig-box {
            text-align: center;
            width: 48%;
        }

        .sig-line {
            border-top: 0.8px solid #334155;
            margin-bottom: 0.5mm;
        }

        .sig-text {
            font-size: 4.5pt;
            color: #475569;
            text-transform: uppercase;
        }

        .cut-notice {
            font-size: 10pt;
            color: #64748b;
            text-align: center;
            font-style: italic;
        }

        @media print {
            body {
                background: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .cards-container {
                margin: 20mm auto;
            }
            .id-card {
                box-shadow: none;
                border: 1px dashed #94a3b8;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <div>
            <strong>Documento de Identificação:</strong> Carteirinha Estudantil Oficial
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Imprimir Carteirinha
            </button>
            <button onclick="window.close()" class="btn btn-secondary">
                Fechar
            </button>
        </div>
    </div>

    @php
        $student = $contract->student;
        $studentName = $student?->name ?? 'Não informado';
        $studentCpf = $student?->documents?->firstWhere('type', 'cpf')?->document_number
            ?: ($student?->document_number ?? 'Não informado');
        $studentRg = $student?->documents?->firstWhere('type', 'rg')?->document_number ?: 'Não informado';
        $birthDate = $student?->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('d/m/Y') : 'Não informada';

        $schoolName = $school['trade_name'] ?? ($school['name'] ?? config('app.name', 'Aurosoft LMS'));
        $schoolLegal = $school['legal_name'] ?? $schoolName;
        $schoolCnpj = $school['cnpj'] ?? 'Não informado';
        $schoolAddress = $school['address'] ?? '';
        $schoolPhone = $school['phone'] ?? '';
        $schoolLogo = $school['logo_url'] ?? null;

        $initials = collect(explode(' ', $studentName))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('');
        $validDateFormatted = \Carbon\Carbon::parse($validUntil)->format('d/m/Y');
    @endphp

    <div class="cards-container">
        <div class="card-row">
            {{-- FRENTE DO CARTÃO --}}
            <div class="id-card card-front">
                <div>
                    <div class="card-header">
                        @if($schoolLogo)
                            <img src="{{ $schoolLogo }}" alt="{{ $schoolName }}" class="card-logo">
                        @else
                            <span style="font-size: 7.5pt; font-weight: bold; color: #1e3a8a;">{{ $schoolName }}</span>
                        @endif
                        <span class="card-title-pill">Identificação Estudantil</span>
                    </div>

                    <div class="card-main">
                        <div class="photo-box">
                            <div class="student-avatar">{{ $initials }}</div>
                        </div>

                        <div class="student-data">
                            <span class="data-label">Aluno(a)</span>
                            <div class="student-name">{{ $studentName }}</div>

                            <span class="data-label">Curso / Formação</span>
                            <div style="font-weight: bold; color: #1e3a8a; margin-bottom: 1mm; max-height: 4.5mm; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $courseTitle }}
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1mm;">
                                <div>
                                    <span class="data-label">CPF</span>
                                    <span style="font-weight: 600;">{{ $studentCpf }}</span>
                                </div>
                                <div>
                                    <span class="data-label">Nascimento</span>
                                    <span style="font-weight: 600;">{{ $birthDate }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer-front">
                    <div>
                        <span class="data-label">Matrícula</span>
                        <span class="badge-matricula">#{{ $contract->contract_number }}</span>
                    </div>
                    <div>
                        <span class="data-label">Validade</span>
                        <span style="font-weight: bold; color: #b91c1c;">{{ $validDateFormatted }}</span>
                    </div>
                </div>
            </div>

            {{-- VERSO DO CARTÃO --}}
            <div class="id-card card-back">
                <div>
                    <div style="border-bottom: 1px solid #1e3a8a; padding-bottom: 1mm; margin-bottom: 1.5mm; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: bold; color: #1e3a8a; font-size: 6.5pt; text-transform: uppercase;">{{ $schoolName }}</span>
                        <span style="font-size: 5pt; color: #64748b;">CNPJ: {{ $schoolCnpj }}</span>
                    </div>

                    <div class="back-grid">
                        <div>
                            <div style="font-size: 5.2pt; color: #475569; margin-bottom: 1mm;">
                                @if($schoolAddress) {{ $schoolAddress }}<br> @endif
                                @if($schoolPhone) Atendimento: {{ $schoolPhone }} @endif
                            </div>

                            <div style="background: #f1f5f9; padding: 1mm; border-radius: 1mm; font-size: 5pt; color: #334155;">
                                <strong>Vínculo:</strong> Contrato Ativo nº #{{ $contract->contract_number }}<br>
                                <strong>Emissão:</strong> {{ now()->format('d/m/Y') }}
                            </div>
                        </div>

                        <div class="qr-placeholder">
                            <svg viewBox="0 0 24 24" style="width: 13mm; height: 13mm;" fill="none" stroke="#1e293b" stroke-width="2">
                                <rect x="3" y="3" width="7" height="7"/>
                                <rect x="14" y="3" width="7" height="7"/>
                                <rect x="3" y="14" width="7" height="7"/>
                                <path d="M14 14h2v2h-2zM18 14h3v3h-3zM14 18h3v3h-3zM19 19h2v2h-2z"/>
                            </svg>
                            <span style="font-size: 3.8pt; font-family: monospace;">#{{ $contract->contract_number }}</span>
                        </div>
                    </div>

                    <div class="legal-notice">
                        Documento de uso pessoal e intransferível, válido para identificação institucional perante os laboratórios e salas de aula desta instituição de ensino e comprovação de matrícula estudantil na forma da legislação vigente.
                    </div>
                </div>

                <div class="back-signatures">
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-text">Assinatura do Aluno</div>
                    </div>
                    <div class="sig-box">
                        <div class="sig-line"></div>
                        <div class="sig-text">Secretaria Escolar</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="cut-notice no-print">
            ✂️ <em>Dica de impressão: Imprima em papel cartão, dobre ao meio ou recorte frente e verso para plastificar.</em>
        </div>
    </div>

</body>
</html>

