@props(['contractCourse'])

@php
    $isAurosoft = $contractCourse->course?->isAurosoftLms() ?? true;
    $feedback = $isAurosoft
        ? app(\App\Services\AurosoftLmsProgressSyncService::class)->feedback($contractCourse)
        : app(\App\Services\OuroModernoProgressSyncService::class)->feedback($contractCourse);
@endphp

<div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $feedback['course_status_label'] }}</span>
    @if ($feedback['current_lesson'] !== null)
        <span class="text-gray-600 dark:text-gray-400">Aula atual {{ $feedback['current_lesson'] }}</span>
    @endif
    @if ($feedback['total_lessons'] > 0)
        <span class="text-gray-600 dark:text-gray-400">{{ $feedback['completed_lessons'] }}/{{ $feedback['total_lessons'] }} aulas concluídas</span>
    @endif
    @if ($feedback['average'] !== null)
        <span class="rounded-full bg-primary-50 px-2 py-1 font-semibold text-primary-700 dark:bg-primary-400/10 dark:text-primary-300">
            Média do curso: {{ rtrim(rtrim(number_format($feedback['average'], 2, ',', ''), '0'), ',') }}
        </span>
    @endif
    @if ($contractCourse->lms_synced_at)
        <span class="text-gray-500 dark:text-gray-400">Sincronizado em {{ $contractCourse->lms_synced_at->format('d/m/Y H:i') }}</span>
    @endif
</div>
