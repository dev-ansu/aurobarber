<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Contrato #{{ $contract->contract_number }} - {{ $contract->student->name ?? 'Aluno' }}</title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
            color: #222;
            margin: 0;
            padding: 0;
            background: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .header h1 {
            font-size: 18px;
            margin: 6px 0 2px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 2px 0;
            font-size: 12px;
            color: #444;
        }
        .badge-contract {
            font-size: 12px;
            margin-top: 4px;
            color: #333;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f0f0f0;
            border-left: 4px solid #333;
            padding: 4px 8px;
            margin-top: 16px;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .card-box {
            border: 1px solid #ddd;
            padding: 8px 12px;
            margin-bottom: 10px;
            border-radius: 3px;
            font-size: 12px;
        }
        .grid-2 {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 4px;
        }
        .grid-2 > div {
            flex: 1;
        }
        .grid-3 {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 4px;
        }
        .grid-3 > div {
            flex: 1;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 14px;
            font-size: 12px;
        }
        table, th, td {
            border: 1px solid #ccc;
        }
        th, td {
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .content-body {
            margin-top: 12px;
            margin-bottom: 16px;
            text-align: justify;
            font-size: 12.5px;
            line-height: 1.6;
        }
        .content-body p {
            margin-bottom: 8px;
        }
        .signatures-container {
            margin-top: 35px;
            page-break-inside: avoid;
        }
        .signatures-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            gap: 20px;
        }
        .signature-line {
            flex: 1;
            text-align: center;
            border-top: 1px solid #222;
            padding-top: 5px;
            font-size: 12px;
        }
        .witnesses-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 25px;
            text-align: center;
        }
        .avoid-break {
            page-break-inside: avoid;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px; text-align: right; background: #fafafa; padding: 12px; border-bottom: 1px solid #e5e5e5;">
        <button onclick="window.print()" style="padding: 9px 18px; background: #000; color: #fff; border: none; cursor: pointer; border-radius: 4px; font-weight: bold;">
            Imprimir Contrato
        </button>
    </div>

    @php
        $schoolTrade = $school['trade_name'] ?? $school['legal_name'] ?? 'Escola';
        $schoolCnpj = $school['cnpj'] ?? null;
        $schoolAddress = collect([
            trim(($school['street'] ?? '').' '.($school['number'] ?? '')),
            $school['complement'] ?? null,
            $school['neighborhood'] ?? null,
            trim(($school['city'] ?? '').(! empty($school['state']) ? ' - '.$school['state'] : '')),
            $school['postal_code'] ?? null,
        ])->filter()->join(', ');

        $student = $contract->student;
        $studentAddr = $student?->addresses->sortByDesc('is_primary')->first();
        $studentAddrText = $studentAddr ? collect([
            trim(($studentAddr->street ?? '').' '.($studentAddr->number ?? '')),
            $studentAddr->complement ?? null,
            $studentAddr->neighborhood ?? null,
            trim(($studentAddr->city?->name ?? '').(($stateAcr = $studentAddr->city?->state?->acronym ?? $studentAddr->city?->state?->abbreviation) ? ' - '.$stateAcr : '')),
            $studentAddr->zip_code ? "CEP: {$studentAddr->zip_code}" : null,
        ])->filter()->join(', ') : 'Endereço não cadastrado';
        $studentRg = $student?->documents->firstWhere('type', 'rg')?->number ?? $student?->documents->first()?->number;
        $studentPhone = $student?->contacts->first()?->value ?? $student?->email ?? '—';

        $responsible = $contract->responsible;
        $respPerson = $responsible?->person;
        $respAddr = $respPerson?->addresses->sortByDesc('is_primary')->first();
        $respAddrText = $respAddr ? collect([
            trim(($respAddr->street ?? '').' '.($respAddr->number ?? '')),
            $respAddr->complement ?? null,
            $respAddr->neighborhood ?? null,
            trim(($respAddr->city?->name ?? '').(($respStateAcr = $respAddr->city?->state?->acronym ?? $respAddr->city?->state?->abbreviation) ? ' - '.$respStateAcr : '')),
            $respAddr->zip_code ? "CEP: {$respAddr->zip_code}" : null,
        ])->filter()->join(', ') : $studentAddrText;
        $respRg = $respPerson?->documents->firstWhere('type', 'rg')?->number ?? $respPerson?->documents->first()?->number;
        $respPhone = $respPerson?->contacts->first()?->value ?? $respPerson?->email ?? '—';
        $respRelationship = $responsible?->relationship?->getLabel() ?? 'Responsável';

        $weekdays = [
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            7 => 'Domingo',
        ];
    @endphp

    <div class="header">
        @php
            $schoolLogo = !empty($school['logo_url']) ? $school['logo_url'] : (!empty($school['logo_path']) ? \Illuminate\Support\Facades\Storage::disk('public')->url($school['logo_path']) : null);
        @endphp
        @if ($schoolLogo)
            <div style="margin-bottom: 8px;">
                <img src="{{ $schoolLogo }}" alt="Logotipo da Escola" style="max-height: 60px; max-width: 220px; object-fit: contain;">
            </div>
        @endif
        <p><strong>{{ $schoolTrade }}</strong></p>
        @if ($schoolCnpj)
            <p>CNPJ: {{ $schoolCnpj }}</p>
        @endif
        @if ($schoolAddress)
            <p>{{ $schoolAddress }}</p>
        @endif
        <h1>{{ $contract->contractType->name ?? 'Contrato de Prestação de Serviços Educacionais' }}</h1>
        <div class="badge-contract">
            Contrato nº: <strong>{{ $contract->contract_number }}</strong>
            | Data de Adesão: <strong>{{ \Carbon\Carbon::parse($contract->start_date)->format('d/m/Y') }}</strong>
            | Versão: <strong>{{ $contract->contractType->version ?? '1.0' }}</strong>
        </div>
    </div>

    <!-- 1. QUALIFICAÇÃO DAS PARTES -->
    <div class="section-title">1. Qualificação das Partes</div>

    <div class="card-box">
        <div style="font-weight: bold; margin-bottom: 4px; text-transform: uppercase; color: #111;">
            CONTRATADA (ESCOLA):
        </div>
        <div>
            <strong>{{ $schoolTrade }}</strong>, inscrita no CNPJ sob o nº <strong>{{ $schoolCnpj ?? '—' }}</strong>, com sede em {{ $schoolAddress ?: 'endereço da escola' }}, doravante denominada simplesmente <strong>CONTRATADA</strong>.
        </div>
    </div>

    @if ($respPerson && $respPerson->id !== $student?->id)
        <div class="card-box">
            <div style="font-weight: bold; margin-bottom: 4px; text-transform: uppercase; color: #111;">
                CONTRATANTE / RESPONSÁVEL FINANCEIRO:
            </div>
            <div class="grid-2">
                <div><strong>Nome:</strong> {{ $respPerson->name }}</div>
                <div><strong>Parentesco:</strong> {{ $respRelationship }}</div>
            </div>
            <div class="grid-3">
                <div><strong>CPF:</strong> {{ $respPerson->cpf ?: 'Não informado' }}</div>
                <div><strong>RG:</strong> {{ $respRg ?: 'Não informado' }}</div>
                <div><strong>Telefone:</strong> {{ $respPhone }}</div>
            </div>
            <div>
                <strong>Endereço:</strong> {{ $respAddrText }}
            </div>
            <div style="margin-top: 4px; font-size: 11px; color: #555;">
                Doravante denominado(a) simplesmente <strong>CONTRATANTE</strong>.
            </div>
        </div>
    @endif

    <div class="card-box">
        <div style="font-weight: bold; margin-bottom: 4px; text-transform: uppercase; color: #111;">
            ALUNO(A) BENEFICIÁRIO(A):
        </div>
        <div class="grid-2">
            <div><strong>Nome do Aluno:</strong> {{ $student->name ?? '-' }}</div>
            <div><strong>Data de Nascimento:</strong> {{ $student?->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('d/m/Y') : 'Não informada' }}</div>
        </div>
        <div class="grid-3">
            <div><strong>CPF:</strong> {{ $student?->cpf ?: 'Não informado' }}</div>
            <div><strong>RG:</strong> {{ $studentRg ?: 'Não informado' }}</div>
            <div><strong>Contato:</strong> {{ $studentPhone }}</div>
        </div>
        <div>
            <strong>Endereço:</strong> {{ $studentAddrText }}
        </div>
        <div style="margin-top: 6px; padding: 6px 10px; background-color: #f8fafc; border: 1px dashed #94a3b8; border-radius: 4px; font-size: 11px; color: #1e293b;">
            🔑 <strong>Credenciais de Acesso ao Portal do Aluno & Totem de Presença:</strong> Matrícula: <strong>{{ $contract->contract_number }}</strong> (ou CPF) · Senha Inicial: <strong>{{ $student?->birth_date ? \Carbon\Carbon::parse($student->birth_date)->format('d/m/Y') : 'Data de Nascimento' }}</strong>
        </div>
        <div style="margin-top: 4px; font-size: 11px; color: #555;">
            Doravante denominado(a) simplesmente <strong>ALUNO(A)</strong>.
        </div>
    </div>

    <!-- 2. OBJETO DO CONTRATO: FORMAÇÃO, CURSOS E HORÁRIOS -->
    <div class="section-title">2. Objeto do Contrato (Cursos e Horários)</div>

    @forelse($contract->items as $item)
        <div class="card-box">
            <div class="grid-2" style="margin-bottom: 6px;">
                <div><strong>Formação / Pacote:</strong> <span style="font-size: 13px; font-weight: bold;">{{ $item->formation?->name ?? 'Curso Livre' }}</span></div>
                @php
                    $courses = $item->contractCourses->isNotEmpty()
                        ? $item->contractCourses->map(fn($cc) => $cc->course)->filter()
                        : ($item->formation?->courses ?? collect());
                    $totalWorkload = $courses->sum('workload');
                @endphp
                <div class="text-right"><strong>Carga Horária Total:</strong> {{ $totalWorkload }} horas</div>
            </div>

            @if($courses->isNotEmpty())
                <div style="margin-top: 6px;">
                    <strong>Módulos / Cursos inclusos:</strong>
                    <div style="font-size: 11.5px; color: #333; margin-top: 3px;">
                        {{ $courses->pluck('name')->join(', ') }}
                    </div>
                </div>
            @endif

            @if($item->schedules->isNotEmpty())
                <div style="margin-top: 8px; border-top: 1px dashed #ccc; padding-top: 6px;">
                    <strong>Turmas e Horários Agendados:</strong>
                    <ul style="margin: 4px 0 0 0; padding-left: 20px; font-size: 12px;">
                        @foreach($item->schedules as $sched)
                            <li>
                                <strong>{{ $weekdays[$sched->weekday] ?? "Dia {$sched->weekday}" }}</strong>
                                das <strong>{{ substr($sched->start_hour, 0, 5) }}</strong> às <strong>{{ substr($sched->end_hour, 0, 5) }}</strong>
                                @if($sched->classroom)
                                    — Sala: {{ $sched->classroom->name }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @empty
        <p style="font-size: 12px; color: #666; margin: 6px 0;">Nenhum curso vinculado a este contrato.</p>
    @endforelse

    <!-- 3. VALORES E CONDIÇÕES FINANCEIRAS -->
    <div class="section-title avoid-break">3. Valores e Condições de Pagamento</div>

    <div class="grid-3 card-box avoid-break">
        <div><strong>Valor Global Contratado:</strong> R$ {{ number_format($contract->total_value, 2, ',', '.') }}</div>
        <div><strong>Desconto Concedido:</strong> R$ {{ number_format($contract->discount_value, 2, ',', '.') }}</div>
        <div><strong>Valor Líquido:</strong> R$ {{ number_format($contract->total_value - $contract->discount_value, 2, ',', '.') }}</div>
    </div>

    <table class="avoid-break">
        <thead>
            <tr>
                <th width="15%">Parcela</th>
                <th width="35%">Descrição / Tipo</th>
                <th width="20%">Vencimento</th>
                <th width="20%" class="text-right">Valor</th>
                <th width="10%" class="text-center">Situação</th>
            </tr>
        </thead>
        <tbody>
            @foreach($contract->installments as $installment)
                <tr>
                    <td>{{ $installment->installment_number ? sprintf('%02d / %02d', $installment->installment_number, $installment->total_installments) : 'Avulsa' }}</td>
                    <td>{{ $installment->financialType->name ?? 'Mensalidade' }}</td>
                    <td>{{ \Carbon\Carbon::parse($installment->due_date)->format('d/m/Y') }}</td>
                    <td class="text-right">R$ {{ number_format($installment->amount, 2, ',', '.') }}</td>
                    <td class="text-center">{{ match($installment->status) { 'paid' => 'Pago', 'partial' => 'Parcial', default => 'Pendente' } }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- 4. CLÁUSULAS CONTRATUAIS -->
    <div class="section-title">4. Cláusulas e Termos Gerais</div>

    @php
        $rawMinuta = $contract->contractType->content ?? '<p>Pelo presente instrumento particular, as partes acima qualificadas têm entre si justo e acordado o presente Contrato de Prestação de Serviços Educacionais, que se regerá pelas disposições legais e regulamento interno da CONTRATADA.</p>';
        // Substituição amigável de tags se existirem no texto da minuta
        $replacedMinuta = strtr($rawMinuta, [
            '{aluno}' => $student->name ?? '',
            '{aluno_nome}' => $student->name ?? '',
            '{cpf_aluno}' => $student?->cpf ?? '',
            '{responsavel}' => $respPerson->name ?? ($student->name ?? ''),
            '{responsavel_nome}' => $respPerson->name ?? ($student->name ?? ''),
            '{cpf_responsavel}' => $respPerson?->cpf ?? ($student?->cpf ?? ''),
            '{escola}' => $schoolTrade,
            '{cnpj_escola}' => $schoolCnpj ?? '',
            '{valor_total}' => 'R$ '.number_format($contract->total_value, 2, ',', '.'),
            '{numero_contrato}' => $contract->contract_number,
        ]);
    @endphp

    <div class="content-body">
        {!! $replacedMinuta !!}
    </div>

    <!-- 5. LOCAL, DATA E ASSINATURAS COM 2 TESTEMUNHAS -->
    <div class="signatures-container avoid-break">
        <p style="text-align: right; margin-bottom: 35px; font-size: 12px;">
            {{ $school['city'] ?? 'Local' }} - {{ $school['state'] ?? 'UF' }}, {{ \Carbon\Carbon::parse($contract->start_date)->translatedFormat('d \d\e F \d\e Y') }}.
        </p>

        <div class="signatures-row">
            <div class="signature-line">
                <strong>{{ $respPerson->name ?? $student->name }}</strong><br>
                <span>Contratante / Responsável Legal</span><br>
                <span style="font-size: 11px; color: #555;">CPF: {{ $respPerson->cpf ?? $student?->cpf ?? '_________________________' }}</span>
            </div>
            <div class="signature-line">
                <strong>{{ $schoolTrade }}</strong><br>
                <span>{{ $schoolRepresentative ?? 'Representante Legal' }}</span><br>
                <span style="font-size: 11px; color: #555;">CNPJ: {{ $schoolCnpj ?? '_________________________' }}</span>
            </div>
        </div>

        <div class="witnesses-title">
            Testemunhas (Art. 784, III do Código de Processo Civil - Título Executivo Extrajudicial)
        </div>

        <div class="signatures-row" style="margin-bottom: 10px;">
            <div class="signature-line">
                <span>Nome: _________________________________________</span><br>
                <span style="font-size: 11px; color: #555;">CPF: __________________________________________</span>
            </div>
            <div class="signature-line">
                <span>Nome: _________________________________________</span><br>
                <span style="font-size: 11px; color: #555;">CPF: __________________________________________</span>
            </div>
        </div>
    </div>

</body>
</html>