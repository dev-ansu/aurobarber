@props([
    'data' => [],
])

@php
    $title = $data['title'] ?? null;
    $width = $data['container_width'] ?? 'normal';
    $content = $data['content'] ?? '';

    $containerClass = match($width) {
        'narrow' => 'max-w-3xl',
        'full' => 'max-w-7xl',
        default => 'max-w-5xl',
    };
@endphp

<section class="py-16 sm:py-24 bg-slate-950 border-b border-slate-800/80">
    <div class="{{ $containerClass }} mx-auto px-4 sm:px-6 lg:px-8">
        @if(!empty($title))
            <h2 class="text-2xl sm:text-4xl font-black text-white mb-8 tracking-tight">
                {{ $title }}
            </h2>
        @endif

        <div class="prose prose-invert prose-amber max-w-none text-slate-300 leading-relaxed">
            {!! $content !!}
        </div>
    </div>
</section>
