<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha Cadastral de Matrícula - {{ $contract->student?->name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 12mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
            color: #0f172a;
            background-color: #f3f4f6;
            margin: 0;
            padding: 15px;
            font-size: 8.5pt;
        }

        .no-print {
            max-width: 850px;
            margin: 0 auto 15px auto;
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
            margin: 0 auto;
            background: #ffffff;
            padding: 8mm 10mm;
            box-shadow: 0 3px 12px rgba(0,0,0,0.1);
        }

        /* Cabeçalho */
        .sheet-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        .school-title {
            font-size: 13pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .school-meta {
            font-size: 7.5pt;
            color: #475569;
            margin-top: 1px;
        }

        .sheet-title-badge {
            background: #0f172a;
            color: #ffffff;
            font-size: 9pt;
            font-weight: 800;
            text-transform: uppercase;
            padding: 4px 10px;
            border-radius: 3px;
            letter-spacing: 0.5px;
            text-align: right;
        }

        /* Seções e Grades */
        .section-box {
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            margin-bottom: 8px;
            overflow: hidden;
        }

        .section-header {
            background: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            padding: 3px 8px;
            font-size: 8pt;
            font-weight: 800;
            text-transform: uppercase;
            color: #1e293b;
            letter-spacing: 0.5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-content {
            padding: 6px 8px;
        }

        .data-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 4px 8px;
        }

        .col-12 { grid-column: span 12; }
        .col-8 { grid-column: span 8; }
        .col-7 { grid-column: span 7; }
        .col-6 { grid-column: span 6; }
        .col-5 { grid-column: span 5; }
        .col-4 { grid-column: span 4; }
        .col-3 { grid-column: span 3; }
        .col-2 { grid-column: span 2; }

        .field {
            line-height: 1.15;
        }

        .field .lbl {
            font-size: 6.5pt;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            display: block;
        }

        .field .val {
            font-size: 8.5pt;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Foto 3x4 Box */
        .photo-box {
            width: 25mm;
            height: 33mm;
            border: 1.5px dashed #94a3b8;
            border-radius: 3px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            font-size: 7pt;
            color: #94a3b8;
            background: #f8fafc;
            margin-left: auto;
        }

        /* Termos e Cláusulas */
        .term-clause {
            font-size: 7pt;
            color: #334155;
            line-height: 1.35;
            margin-bottom: 5px;
            text-align: justify;
        }

        .term-clause strong {
            color: #0f172a;
        }

        /* Assinaturas */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 15px;
            padding: 0 10px;
        }

        .sign-col {
            text-align: center;
        }

        .sign-line {
            border-top: 1px solid #1e293b;
            padding-top: 3px;
            font-size: 8pt;
            font-weight: 700;
            color: #0f172a;
        }

        .sign-sub {
            font-size: 7pt;
            color: #64748b;
            text-transform: uppercase;
        }

        .sheet-footer {
            border-top: 1px dashed #cbd5e1;
            padding-top: 4px;
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
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
                padding: 0 !important;
                margin: 0 !important;
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
        $schoolCity = ! empty($school['city'])
            ? $school['city']
            : ($contract->student?->addresses?->first()?->city?->name ?? 'Município');
        $schoolState = ! empty($school['state'])
            ? $school['state']
            : ($contract->student?->addresses?->first()?->city?->state?->acronym ?? 'UF');

        $schoolAddress = collect([
            trim(($school['street'] ?? '').' '.($school['number'] ?? '')),
            $school['complement'] ?? null,
            $school['neighborhood'] ?? null,
            trim(($school['city'] ?? '').(!empty($school['state']) ? ' - '.$school['state'] : '')),
        ])->filter()->join(', ');

        $student = $contract->student;
        $studentCpf = $student?->cpf
            ?? $student?->documents?->firstWhere('type', 'cpf')?->value ?? '—';
        $studentRg = $student?->documents?->firstWhere('type', 'rg')?->value ?? '—';
        $studentBirth = $student?->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('d/m/Y') : '—';
        $studentGender = $student?->gender ? ucfirst($student->gender->value ?? $student->gender) : '—';

        $studentPhone = $student?->contacts?->firstWhere('type', 'phone')?->value
            ?? $student?->contacts?->firstWhere('type', 'whatsapp')?->value
            ?? $student?->phone ?? '—';
        $studentEmail = $student?->email ?? '—';

        $studentAddressObj = $student?->addresses?->first();
        $studentStreet = $studentAddressObj ? trim(($studentAddressObj->street ?? '').' '.($studentAddressObj->number ?? '')) : '—';
        $studentComp = $studentAddressObj?->complement ?? '';
        $studentBairro = $studentAddressObj?->neighborhood ?? '—';
        $studentCep = $studentAddressObj?->postal_code ?? '—';
        $studentCidadeUf = $studentAddressObj?->city ? ($studentAddressObj->city->name . (!empty($studentAddressObj->city->state?->uf) ? '/' . $studentAddressObj->city->state->uf : '')) : '—';

        $responsible = $contract->responsible?->person;
        $respRelationship = $contract->responsible?->relationship ? ($contract->responsible->relationship->value ?? $contract->responsible->relationship) : 'Responsável Financeiro';
        $respName = $responsible?->name ?? 'Próprio Aluno';
        $respCpf = $responsible?->cpf
            ?? $responsible?->documents?->firstWhere('type', 'cpf')?->value ?? $studentCpf;
        $respRg = $responsible?->documents?->firstWhere('type', 'rg')?->value ?? '—';
        $respPhone = $responsible?->contacts?->firstWhere('type', 'phone')?->value
            ?? $responsible?->contacts?->firstWhere('type', 'whatsapp')?->value
            ?? $responsible?->phone ?? $studentPhone;

        $startDateStr = $contract->start_date ? $contract->start_date->format('d/m/Y') : now()->format('d/m/Y');
        $endDateStr = $contract->end_date ? $contract->end_date->format('d/m/Y') : ($contract->finished_at ? $contract->finished_at->format('d/m/Y') : 'Em andamento');
    @endphp

    <div class="no-print">
        <div>
            <strong>Dossiê Cadastral:</strong> Ficha de Matrícula · {{ $student?->name }} (Contrato #{{ $contract->contract_number }})
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                    <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/>
                </svg>
                Imprimir Ficha de Matrícula
            </button>
            <a href="javascript:window.close()" class="btn btn-secondary">Fechar</a>
        </div>
    </div>

    <div class="doc-page">
        <!-- CABEÇALHO -->
        <div class="sheet-header">
            <div style="display: flex; align-items: center; gap: 10px;">
                @if ($schoolLogo)
                    <img src="{{ $schoolLogo }}" alt="Logo" style="max-height: 40px; max-width: 120px; object-fit: contain;">
                @endif
                <div>
                    <div class="school-title">{{ $schoolTrade }}</div>
                    <div class="school-meta">
                        @if($schoolCnpj) CNPJ: {{ $schoolCnpj }} · @endif
                        @if($schoolAddress) {{ $schoolAddress }} · @endif
                        @if($schoolPhone) Tel: {{ $schoolPhone }} @endif
                    </div>
                </div>
            </div>
            <div class="sheet-title-badge">
                FICHA CADASTRAL DE MATRÍCULA · DOSSIÊ DO ALUNO #{{ $contract->contract_number }}
            </div>
        </div>

        <!-- 1. DADOS PESSOAIS DO ALUNO -->
        <div class="section-box">
            <div class="section-header">
                <span>1. Dados Pessoais do Aluno</span>
                <span style="font-size: 7pt; color: #64748b;">Matrícula: {{ $student?->ouro_moderno_cod_int ?: $student?->id }}</span>
            </div>
            <div class="section-content">
                <div style="display: flex; gap: 12px; align-items: flex-start;">
                    <div style="flex: 1;">
                        <div class="data-grid">
                            <div class="field col-8">
                                <span class="lbl">Nome Completo do Aluno</span>
                                <div class="val">{{ $student?->name ?? '—' }}</div>
                            </div>
                            <div class="field col-4">
                                <span class="lbl">Data de Nascimento</span>
                                <div class="val">{{ $studentBirth }}</div>
                            </div>

                            <div class="field col-4">
                                <span class="lbl">CPF</span>
                                <div class="val">{{ $studentCpf }}</div>
                            </div>
                            <div class="field col-4">
                                <span class="lbl">RG</span>
                                <div class="val">{{ $studentRg }}</div>
                            </div>
                            <div class="field col-4">
                                <span class="lbl">Sexo</span>
                                <div class="val">{{ $studentGender }}</div>
                            </div>

                            <div class="field col-6">
                                <span class="lbl">Telefone / WhatsApp</span>
                                <div class="val">{{ $studentPhone }}</div>
                            </div>
                            <div class="field col-6">
                                <span class="lbl">E-mail</span>
                                <div class="val">{{ $studentEmail }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="photo-box">
                        <span>FOTO 3X4</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. ENDEREÇO RESIDENCIAL -->
        <div class="section-box">
            <div class="section-header">
                <span>2. Endereço Residencial</span>
            </div>
            <div class="section-content">
                <div class="data-grid">
                    <div class="field col-7">
                        <span class="lbl">Logradouro / Número</span>
                        <div class="val">{{ $studentStreet }}</div>
                    </div>
                    <div class="field col-5">
                        <span class="lbl">Complemento</span>
                        <div class="val">{{ $studentComp ?: '—' }}</div>
                    </div>

                    <div class="field col-4">
                        <span class="lbl">Bairro</span>
                        <div class="val">{{ $studentBairro }}</div>
                    </div>
                    <div class="field col-3">
                        <span class="lbl">CEP</span>
                        <div class="val">{{ $studentCep }}</div>
                    </div>
                    <div class="field col-5">
                        <span class="lbl">Município / UF</span>
                        <div class="val">{{ $studentCidadeUf }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. RESPONSÁVEL LEGAL / FINANCEIRO -->
        <div class="section-box">
            <div class="section-header">
                <span>3. Responsável Legal & Financeiro</span>
                <span style="font-size: 7pt; color: #64748b;">Parentesco: {{ $respRelationship }}</span>
            </div>
            <div class="section-content">
                <div class="data-grid">
                    <div class="field col-7">
                        <span class="lbl">Nome do Responsável</span>
                        <div class="val">{{ $respName }}</div>
                    </div>
                    <div class="field col-5">
                        <span class="lbl">Parentesco / Vínculo</span>
                        <div class="val">{{ $respRelationship }}</div>
                    </div>

                    <div class="field col-4">
                        <span class="lbl">CPF</span>
                        <div class="val">{{ $respCpf }}</div>
                    </div>
                    <div class="field col-4">
                        <span class="lbl">RG</span>
                        <div class="val">{{ $respRg }}</div>
                    </div>
                    <div class="field col-4">
                        <span class="lbl">Telefone Contato</span>
                        <div class="val">{{ $respPhone }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. DADOS DA MATRÍCULA E DO CURSO -->
        <div class="section-box">
            <div class="section-header">
                <span>4. Dados da Matrícula e Formação Contratada</span>
            </div>
            <div class="section-content">
                <div class="data-grid">
                    <div class="field col-8">
                        <span class="lbl">Curso / Formação Contratada</span>
                        <div class="val" title="{{ $courseTitle }}">{{ $courseTitle }}</div>
                    </div>
                    <div class="field col-4">
                        <span class="lbl">Carga Horária Total</span>
                        <div class="val">{{ $totalWorkload }} horas</div>
                    </div>

                    <div class="field col-4">
                        <span class="lbl">Data de Matrícula</span>
                        <div class="val">{{ $startDateStr }}</div>
                    </div>
                    <div class="field col-4">
                        <span class="lbl">Previsão de Término</span>
                        <div class="val">{{ $endDateStr }}</div>
                    </div>
                    <div class="field col-4">
                        <span class="lbl">Contrato</span>
                        <div class="val">#{{ $contract->contract_number }}</div>
                    </div>

                    <div class="field col-12">
                        <span class="lbl">Dias e Horários de Aulas Agendadas</span>
                        <div class="val">{{ $scheduleSummary ?: 'A combinar com a coordenação pedagógica' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. TERMOS DE RESPONSABILIDADE, LGPD E USO DE IMAGEM -->
        <div class="section-box">
            <div class="section-header">
                <span>5. Termos de Compromisso, LGPD e Autorizações</span>
            </div>
            <div class="section-content">
                <div class="term-clause">
                    <strong>1. Assiduidade e Compromisso Escolar:</strong> O(a) contratante/aluno compromete-se a comparecer pontualmente às aulas nos dias e horários pactuados, zelando pelo patrimônio, equipamentos do laboratório de informática e materiais didáticos da instituição. Faltas deverão ser justificadas na secretaria em até 48 horas.
                </div>
                <div class="term-clause">
                    <strong>2. Tratamento de Dados Pessoais (LGPD - Lei 13.709/2018):</strong> O titular autoriza a instituição a coletar e tratar os dados cadastrais deste formulário com a finalidade exclusiva de gestão acadêmica, emissão de carnês/contratos e cumprimento de obrigações legais e regulatórias educacionais.
                </div>
                <div class="term-clause">
                    <strong>3. Autorização de Uso de Imagem e Voz:</strong> O(a) signatário(a) autoriza a título gratuito a utilização de sua imagem e voz em fotografias, murais pedagógicos e mídias institucionais da escola, estritamente para divulgação de formaturas, projetos e atividades pedagógicas.
                </div>
            </div>
        </div>

        <!-- ASSINATURAS FÍSICAS -->
        <div style="margin-top: 15px;">
            <div style="text-align: right; font-style: italic; font-size: 8pt; margin-bottom: 20px;">
                {{ $schoolCity }} - {{ $schoolState }}, {{ now()->format('d/m/Y') }}.
            </div>

            <div class="signatures-grid">
                <div class="sign-col">
                    <div class="sign-line">{{ $respName }}</div>
                    <div class="sign-sub">Assinatura do Aluno(a) ou Responsável Legal</div>
                </div>
                <div class="sign-col">
                    <div class="sign-line">Secretaria Escolar / Atendimento</div>
                    <div class="sign-sub">{{ $schoolTrade }}</div>
                </div>
            </div>
        </div>

        <div class="sheet-footer">
            <div>Ficha arquivada na pasta individual do aluno · Contrato #{{ $contract->contract_number }}</div>
            <div>Emitido via Aurosoft Gestão Escolar</div>
            <div>Data de Emissão: {{ now()->format('d/m/Y H:i') }}</div>
        </div>
    </div>

</body>
</html>
