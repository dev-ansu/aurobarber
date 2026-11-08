@props(['title', 'subtitle' => null, 'school' => null])

@php
    $school ??= \App\Models\School::query()->first();
    $schoolName = $school?->trade_name ?: $school?->legal_name ?: 'Aurosoft Educacional';
    $address = collect([
        trim(implode(' ', array_filter([$school?->street, $school?->number]))),
        $school?->complement,
        $school?->neighborhood,
        trim(implode(' - ', array_filter([$school?->city, $school?->state]))),
        $school?->postal_code ? 'CEP '.$school->postal_code : null,
    ])->filter()->implode(' · ');
@endphp

<style>
    .report-document-header--print-only { display: none; }

    @media print {
        .report-document-header--print-only {
            display: block !important;
            margin: 0 0 14px;
            padding: 0 0 9px;
            border-bottom: 1px solid #9ca3af;
            color: #111827;
            font: 10px/1.4 Arial, sans-serif;
        }

        .report-document-header--print-only h1 { margin: 0 0 3px; font-size: 16px; }
        .report-document-header--print-only p { margin: 2px 0; }
        .report-document-header--print-only .report-document-header__school { font-size: 13px; font-weight: 700; }
    }
</style>

<header class="report-document-header report-document-header--print-only">
    <p class="report-document-header__school">{{ $schoolName }}</p>
    @if ($school?->cnpj || $school?->phone || $school?->email || $school?->website)
        <p>{{ collect([
            $school?->cnpj ? 'CNPJ '.$school->cnpj : null,
            $school?->phone,
            $school?->email,
            $school?->website,
        ])->filter()->implode(' · ') }}</p>
    @endif
    @if ($address !== '')
        <p>{{ $address }}</p>
    @endif
    <h1>{{ $title }}</h1>
    @if ($subtitle)
        <p>{{ $subtitle }}</p>
    @endif
    <p>Emitido pelo Aurosoft em {{ now()->format('d/m/Y H:i') }}</p>
</header>
