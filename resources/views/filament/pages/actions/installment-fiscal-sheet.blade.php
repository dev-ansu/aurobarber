@php
    $studentPerson = $student ?? $contract?->student;
    $responsibleRecord = $responsible ?? $contract?->responsible;
    $responsiblePerson = $responsibleRecord?->person;
    $hasResponsible = $responsiblePerson !== null;

    // No ISS Fortaleza, o tomador fiscal da NFS-e é quem paga a formação:
    // Havendo responsável financeiro cadastrado no contrato, ele é o tomador titular;
    // Não havendo responsável, o próprio aluno é o tomador titular.
    $tomador = $hasResponsible ? $responsiblePerson : $studentPerson;
    $tomadorTipo = $hasResponsible 
        ? 'Responsável Financeiro (' . ($responsibleRecord->relationship?->getLabel() ?? 'Responsável') . ')' 
        : 'O Próprio Aluno (Sem Responsável Vinculado)';

    $tomadorAddress = $tomador?->addresses?->firstWhere('is_primary', true) ?? $tomador?->addresses?->first();
    $tomadorPhone = $tomador?->contacts?->firstWhere('is_primary', true)?->value ?? $tomador?->contacts?->first()?->value ?? 'Não informado';
    
    $studentAddress = $studentPerson?->addresses?->firstWhere('is_primary', true) ?? $studentPerson?->addresses?->first();
    $studentPhone = $studentPerson?->contacts?->firstWhere('is_primary', true)?->value ?? $studentPerson?->contacts?->first()?->value ?? 'Não informado';

    $responsibleAddress = $responsiblePerson?->addresses?->firstWhere('is_primary', true) ?? $responsiblePerson?->addresses?->first();
    $responsiblePhone = $responsiblePerson?->contacts?->firstWhere('is_primary', true)?->value ?? $responsiblePerson?->contacts?->first()?->value ?? 'Não informado';

    $studentCpfFormatted = \App\Filament\Pages\ReceivedInstallmentsReport::formatCpf($studentPerson?->cpf);
    $responsibleCpfFormatted = \App\Filament\Pages\ReceivedInstallmentsReport::formatCpf($responsiblePerson?->cpf);
    $tomadorCpfFormatted = \App\Filament\Pages\ReceivedInstallmentsReport::formatCpf($tomador?->cpf);

    $valorPago = number_format((float) ($installment?->amount_paid ?? $installment?->amount ?? $totalPaid ?? 0), 2, ',', '.');
    $tipoNome = $installment?->financialType?->name ?? 'Serviços Educacionais';
    $numContrato = $contract?->contract_number ?? '';
    $parcelaStr = $installment?->installment_number ? "{$installment->installment_number}/{$installment->total_installments}" : ($installment ? 'Entrada' : 'Geral');

    $discriminacaoParts = [
        "Prestação de serviços educacionais e formação profissional.",
        "Aluno(a): {$studentPerson?->name}" . ($studentCpfFormatted ? " (CPF: {$studentCpfFormatted})" : ""),
    ];

    if ($hasResponsible) {
        $relLabel = $responsibleRecord->relationship?->getLabel() ?? 'Responsável';
        $discriminacaoParts[] = "Tomador / Responsável Financeiro ({$relLabel}): {$responsiblePerson->name}" . ($responsibleCpfFormatted ? " (CPF: {$responsibleCpfFormatted})" : "");
    }

    $discriminacaoParts[] = "Cobrança: {$tipoNome} (Parcela: {$parcelaStr}).";
    $discriminacaoParts[] = "Contrato de Matrícula nº {$numContrato}.";
    $discriminacaoParts[] = "Valor Quitado: R$ {$valorPago}.";

    $discriminacaoIss = implode(' ', $discriminacaoParts);
@endphp

<div class="space-y-6 text-xs text-gray-700 dark:text-gray-300">
    {{-- Alerta de CPF Pendente no Tomador --}}
    @if(empty($tomador?->cpf))
        <div class="p-3.5 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl flex items-start gap-3">
            <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" />
            <div>
                <h4 class="font-bold text-amber-900 dark:text-amber-200">Atenção: CPF do tomador não cadastrado</h4>
                <p class="text-amber-700 dark:text-amber-300 mt-0.5">
                    O sistema do ISS Fortaleza exige o CPF do tomador para a emissão correta da NFS-e. Cadastre o CPF do {{ $hasResponsible ? 'responsável financeiro' : 'aluno' }} antes de transmitir a nota fiscal.
                </p>
            </div>
        </div>
    @endif

    {{-- Seção 1: Dados do Tomador Fiscal para o ISS Fortaleza (Quem vai na Nota) --}}
    <div class="p-4 bg-indigo-50/50 dark:bg-indigo-950/40 rounded-xl border-2 border-indigo-200 dark:border-indigo-800 space-y-3">
        <div class="flex items-center justify-between border-b border-indigo-200 dark:border-indigo-800/80 pb-2">
            <div class="flex items-center gap-2">
                <x-heroicon-o-building-library class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                <div>
                    <h3 class="font-bold text-indigo-950 dark:text-indigo-100 uppercase tracking-wider text-[11px]">
                        Tomador do Serviço (Dados para o Portal do ISS Fortaleza)
                    </h3>
                    <p class="text-[10px] text-indigo-700 dark:text-indigo-300">
                        Pessoa física ou jurídica a quem a nota fiscal de serviços (NFS-e) será emitida.
                    </p>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-indigo-600 text-white shadow-sm">
                {{ $tomadorTipo }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 pt-1">
            <div class="p-2.5 bg-white dark:bg-gray-900 rounded-lg border border-indigo-100 dark:border-indigo-900/50">
                <span class="text-gray-400 text-[10px] uppercase font-bold block">Nome Completo do Tomador:</span>
                <span class="font-bold text-gray-900 dark:text-white text-sm select-all cursor-pointer" title="Clique para selecionar e copiar">
                    {{ $tomador?->name ?? 'Não identificado' }}
                </span>
            </div>

            <div class="p-2.5 bg-white dark:bg-gray-900 rounded-lg border border-indigo-100 dark:border-indigo-900/50">
                <span class="text-gray-400 text-[10px] uppercase font-bold block">CPF do Tomador (ISS Fortaleza):</span>
                <span class="font-mono font-extrabold text-indigo-700 dark:text-indigo-300 text-sm select-all cursor-pointer" title="Clique para selecionar e copiar">
                    {{ $tomadorCpfFormatted ?: '⚠️ NÃO INFORMADO NO CADASTRO' }}
                </span>
            </div>

            <div class="p-2.5 bg-white dark:bg-gray-900 rounded-lg border border-indigo-100 dark:border-indigo-900/50">
                <span class="text-gray-400 text-[10px] uppercase font-bold block">E-mail:</span>
                <span class="font-medium text-gray-800 dark:text-gray-200 select-all cursor-pointer">
                    {{ $tomador?->email ?? 'Não cadastrado' }}
                </span>
            </div>

            <div class="p-2.5 bg-white dark:bg-gray-900 rounded-lg border border-indigo-100 dark:border-indigo-900/50">
                <span class="text-gray-400 text-[10px] uppercase font-bold block">Telefone / WhatsApp:</span>
                <span class="font-medium text-gray-800 dark:text-gray-200 select-all cursor-pointer">
                    {{ $tomadorPhone }}
                </span>
            </div>

            <div class="md:col-span-2 p-2.5 bg-white dark:bg-gray-900 rounded-lg border border-indigo-100 dark:border-indigo-900/50">
                <span class="text-gray-400 text-[10px] uppercase font-bold block">Endereço Residencial do Tomador:</span>
                <span class="font-medium text-gray-800 dark:text-gray-200 select-all cursor-pointer">
                    @if($tomadorAddress)
                        {{ $tomadorAddress->street }}, {{ $tomadorAddress->number }} {{ $tomadorAddress->complement }} - Bairro {{ $tomadorAddress->neighborhood }} - {{ $tomadorAddress->city?->name ?? 'Fortaleza' }}/{{ $tomadorAddress->city?->state?->code ?? 'CE' }} (CEP: {{ $tomadorAddress->zip_code ?? 'N/A' }})
                    @else
                        Endereço não cadastrado no perfil da pessoa.
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- Seção 2: Dados do Responsável Financeiro / Legal --}}
    <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 space-y-3">
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-2">
            <div class="flex items-center gap-2">
                <x-heroicon-o-user-group class="w-4 h-4 text-purple-500" />
                <h3 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[11px]">
                    Dados do Responsável Financeiro (Contratante)
                </h3>
            </div>
            @if($hasResponsible)
                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300">
                    {{ $responsibleRecord->relationship?->getLabel() ?? 'Responsável' }}
                </span>
            @endif
        </div>

        @if($hasResponsible)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <span class="text-gray-400 text-[10px] uppercase font-bold block">Nome do Responsável:</span>
                    <span class="font-bold text-gray-900 dark:text-white select-all cursor-pointer">
                        {{ $responsiblePerson->name }}
                    </span>
                </div>

                <div>
                    <span class="text-gray-400 text-[10px] uppercase font-bold block">CPF do Responsável:</span>
                    <span class="font-mono font-bold text-gray-900 dark:text-white select-all cursor-pointer">
                        {{ $responsibleCpfFormatted ?: '⚠️ NÃO INFORMADO' }}
                    </span>
                </div>

                <div>
                    <span class="text-gray-400 text-[10px] uppercase font-bold block">Vínculo com o Aluno:</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200">
                        {{ $responsibleRecord->relationship?->getLabel() ?? 'Responsável Financeiro' }}
                    </span>
                </div>

                <div>
                    <span class="text-gray-400 text-[10px] uppercase font-bold block">Telefone / WhatsApp:</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200 select-all cursor-pointer">
                        {{ $responsiblePhone }}
                    </span>
                </div>

                <div>
                    <span class="text-gray-400 text-[10px] uppercase font-bold block">E-mail:</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200 select-all cursor-pointer">
                        {{ $responsiblePerson->email ?? 'Não cadastrado' }}
                    </span>
                </div>

                <div>
                    <span class="text-gray-400 text-[10px] uppercase font-bold block">Condição no Contrato:</span>
                    <span class="inline-flex items-center gap-1 font-semibold text-emerald-700 dark:text-emerald-400">
                        <x-heroicon-m-check-badge class="w-3.5 h-3.5 text-emerald-600" />
                        Titular Financeiro da Matrícula
                    </span>
                </div>

                <div class="md:col-span-3 pt-1">
                    <span class="text-gray-400 text-[10px] uppercase font-bold block">Endereço do Responsável:</span>
                    <span class="font-medium text-gray-800 dark:text-gray-200 select-all cursor-pointer">
                        @if($responsibleAddress)
                            {{ $responsibleAddress->street }}, {{ $responsibleAddress->number }} {{ $responsibleAddress->complement }} - Bairro {{ $responsibleAddress->neighborhood }} - {{ $responsibleAddress->city?->name ?? 'Fortaleza' }}/{{ $responsibleAddress->city?->state?->code ?? 'CE' }} (CEP: {{ $responsibleAddress->zip_code ?? 'N/A' }})
                        @else
                            Endereço não cadastrado no cadastro do responsável.
                        @endif
                    </span>
                </div>
            </div>
        @else
            <div class="p-3 bg-gray-50 dark:bg-gray-800/40 rounded-lg flex items-center gap-3">
                <x-heroicon-o-information-circle class="w-5 h-5 text-gray-400 shrink-0" />
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Este contrato não possui responsável cadastrado. O próprio aluno é maior de idade e titular financeiro da matrícula e da nota fiscal.
                </p>
            </div>
        @endif
    </div>

    {{-- Seção 3: Dados do Aluno (Beneficiário) --}}
    <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 space-y-3">
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-800 pb-2">
            <x-heroicon-o-academic-cap class="w-4 h-4 text-emerald-500" />
            <h3 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[11px]">
                Dados do Aluno (Beneficiário do Ensino)
            </h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <span class="text-gray-400 text-[10px] uppercase font-bold block">Nome do Aluno:</span>
                <span class="font-bold text-gray-900 dark:text-white select-all cursor-pointer">{{ $studentPerson?->name ?? 'N/A' }}</span>
            </div>
            <div>
                <span class="text-gray-400 text-[10px] uppercase font-bold block">CPF do Aluno:</span>
                <span class="font-mono font-medium text-gray-900 dark:text-white select-all cursor-pointer">
                    {{ $studentCpfFormatted ?: 'Não informado' }}
                </span>
            </div>
            <div>
                <span class="text-gray-400 text-[10px] uppercase font-bold block">Nascimento & Idade:</span>
                <span class="font-medium text-gray-800 dark:text-gray-200">
                    {{ $studentPerson?->birth_date ? $studentPerson->birth_date->format('d/m/Y') . ' (' . $studentPerson->birth_date->age . ' anos)' : 'Não informada' }}
                </span>
            </div>
            <div>
                <span class="text-gray-400 text-[10px] uppercase font-bold block">Telefone / Contato do Aluno:</span>
                <span class="font-medium text-gray-800 dark:text-gray-200 select-all cursor-pointer">{{ $studentPhone }}</span>
            </div>
            <div>
                <span class="text-gray-400 text-[10px] uppercase font-bold block">E-mail do Aluno:</span>
                <span class="font-medium text-gray-800 dark:text-gray-200 select-all cursor-pointer">{{ $studentPerson?->email ?? 'Não cadastrado' }}</span>
            </div>
            <div>
                <span class="text-gray-400 text-[10px] uppercase font-bold block">Matrícula Vinculada:</span>
                <span class="font-mono font-semibold text-gray-800 dark:text-gray-200">Contrato #{{ $numContrato }}</span>
            </div>
        </div>
    </div>

    {{-- Seção 4: Resumo Financeiro da Matrícula / Contrato --}}
    <div class="p-4 bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 space-y-3">
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-2">
            <div class="flex items-center gap-2">
                <x-heroicon-o-calculator class="w-4 h-4 text-amber-500" />
                <h3 class="font-bold text-gray-900 dark:text-white uppercase tracking-wider text-[11px]">
                    Gestão Financeira da Matrícula (Contrato #{{ $numContrato }})
                </h3>
            </div>
            <span class="text-xs text-gray-500">
                Início: {{ $contract?->start_date ? $contract->start_date->format('d/m/Y') : 'N/A' }}
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            <div class="p-3 bg-sky-50 dark:bg-sky-950/40 rounded-lg border border-sky-100 dark:border-sky-900">
                <span class="text-[10px] uppercase font-bold text-sky-700 dark:text-sky-300 block">Entrada / Matrícula</span>
                <span class="text-base font-extrabold font-mono text-sky-800 dark:text-sky-200 mt-1 block">
                    R$ {{ number_format($downPayment, 2, ',', '.') }}
                </span>
                <span class="text-[10px] text-sky-600 dark:text-sky-400">Total liquidado no ato</span>
            </div>

            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-lg border border-emerald-100 dark:border-emerald-900">
                <span class="text-[10px] uppercase font-bold text-emerald-700 dark:text-emerald-300 block">Total Já Pago</span>
                <span class="text-base font-extrabold font-mono text-emerald-800 dark:text-emerald-200 mt-1 block">
                    R$ {{ number_format($totalPaid, 2, ',', '.') }}
                </span>
                <span class="text-[10px] text-emerald-600 dark:text-emerald-400">Entrada + Mensalidades</span>
            </div>

            <div class="p-3 bg-gray-50 dark:bg-gray-800/40 rounded-lg border border-gray-200 dark:border-gray-700">
                <span class="text-[10px] uppercase font-bold text-gray-600 dark:text-gray-300 block">Valor do Contrato</span>
                <span class="text-base font-extrabold font-mono text-gray-900 dark:text-white mt-1 block">
                    R$ {{ number_format((float) ($contract?->total_value ?? 0), 2, ',', '.') }}
                </span>
                <span class="text-[10px] text-gray-500">Valor global acordado</span>
            </div>

            <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-lg border border-amber-100 dark:border-amber-900">
                <span class="text-[10px] uppercase font-bold text-amber-700 dark:text-amber-300 block">Saldo Restante</span>
                @php
                    $saldo = max(0, (float) ($contract?->total_value ?? 0) - $totalPaid);
                @endphp
                <span class="text-base font-extrabold font-mono text-amber-800 dark:text-amber-200 mt-1 block">
                    R$ {{ number_format($saldo, 2, ',', '.') }}
                </span>
                <span class="text-[10px] text-amber-600 dark:text-amber-400">A vencer no contrato</span>
            </div>
        </div>
    </div>

    {{-- Seção 5: Texto Pronto para o Campo de Discriminação de Serviços (NFS-e ISS Fortaleza) --}}
    <div class="p-4 bg-indigo-50/70 dark:bg-indigo-950/30 rounded-xl border border-indigo-200 dark:border-indigo-900/60 space-y-2">
        <div class="flex items-center justify-between">
            <span class="font-bold text-indigo-950 dark:text-indigo-200 text-[11px] uppercase tracking-wider flex items-center gap-1.5">
                <x-heroicon-o-document-text class="w-4 h-4 text-indigo-600" />
                Discriminação dos Serviços (Texto Pronto para Colar no ISS Fortaleza)
            </span>
            <span class="text-[10px] text-indigo-700 dark:text-indigo-300 font-medium">Clique no texto para selecionar tudo (Ctrl+C)</span>
        </div>
        <textarea
            readonly
            rows="3"
            class="w-full text-xs font-mono rounded-lg border-indigo-200 dark:border-indigo-800 dark:bg-gray-900 text-gray-800 dark:text-gray-200 p-2.5 focus:ring-indigo-500 focus:border-indigo-500 select-all"
        >{{ $discriminacaoIss }}</textarea>
    </div>
</div>
