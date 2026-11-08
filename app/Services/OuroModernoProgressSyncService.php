<?php

namespace App\Services;

use App\Models\ContractCourse;

class OuroModernoProgressSyncService
{
    public function sync(ContractCourse $contractCourse, int $integrationCode, OuroModernoService $api): array
    {
        $courseId = (int) $contractCourse->course?->ouro_moderno_id;

        $courseStatus = $api->studentCourseStatus($integrationCode, $courseId);
        $lessonStatus = $api->studentLessonStatus($integrationCode, $courseId);
        $testStatus = $api->studentTestStatus($integrationCode, $courseId);
        $summary = $this->summarizePayloads($courseStatus, $lessonStatus, $testStatus, $courseId);
        $now = now();

        $contractCourse->forceFill([
            'status' => $summary['local_status'] ?? $contractCourse->status,
            'grade' => $summary['average'] ?? null,
            'progress_source' => 'ouro_moderno',
            'lms_synced_at' => $now,
            'ouro_moderno_status' => $courseStatus,
            'ouro_moderno_status_checked_at' => $now,
            'ouro_moderno_lesson_status' => $lessonStatus,
            'ouro_moderno_lesson_status_checked_at' => $now,
            'ouro_moderno_test_status' => $testStatus,
            'ouro_moderno_test_status_checked_at' => $now,
        ])->save();

        return $summary;
    }

    public function feedback(ContractCourse $contractCourse): array
    {
        $summary = $this->summarizePayloads(
            $contractCourse->ouro_moderno_status ?? [],
            $contractCourse->ouro_moderno_lesson_status ?? [],
            $contractCourse->ouro_moderno_test_status ?? [],
            (int) $contractCourse->course?->ouro_moderno_id,
        );

        if ($contractCourse->progress_source === 'ouro_moderno') {
            $summary['average'] = $contractCourse->grade === null ? null : (float) $contractCourse->grade;
        }

        return $summary;
    }

    /** @return array{course_status: ?string, course_status_label: string, local_status: ?string, current_lesson: int|string|null, completed_lessons: int, total_lessons: int, average: ?float} */
    private function summarizePayloads(array $coursePayload, array $lessonPayload, array $testPayload, int $courseId): array
    {
        $course = collect($this->records($coursePayload, ['cursos', 'courses', 'dados', 'data']))
            ->first(fn (array $row): bool => ! isset($row['idcurso']) || (string) $row['idcurso'] === (string) $courseId);
        $lessons = $this->records($lessonPayload, ['aulas', 'lessons', 'dados', 'data']);
        $tests = $this->records($testPayload, ['testes', 'tests', 'dados', 'data']);

        $courseStatus = $this->normalizeStatus($course['situacao'] ?? $course['status'] ?? null);
        $localStatus = match ($courseStatus) {
            'concluido' => 'completed',
            'cursando' => 'in_progress',
            'nao_iniciado' => 'not_started',
            default => null,
        };

        $completedLessons = array_values(array_filter($lessons, fn (array $lesson): bool => $this->isCompleted($lesson['situacao'] ?? $lesson['status'] ?? null)));
        $currentLesson = null;

        foreach ($lessons as $lesson) {
            if ($this->normalizeStatus($lesson['situacao'] ?? $lesson['status'] ?? null) === 'cursando') {
                $currentLesson = $lesson['aula'] ?? $lesson['numero_aula'] ?? null;

                break;
            }
        }

        if ($localStatus === null && $lessons !== []) {
            $localStatus = count($completedLessons) === count($lessons)
                ? 'completed'
                : ($currentLesson !== null || $completedLessons !== [] ? 'in_progress' : 'not_started');
        }

        $sum = 0.0;
        $hasScore = false;

        foreach ($completedLessons as $lesson) {
            $lessonNumber = $lesson['aula'] ?? $lesson['numero_aula'] ?? null;
            $test = collect($tests)->first(fn (array $row): bool => isset($row['aula'], $lessonNumber)
                && (string) $row['aula'] === (string) $lessonNumber);
            $media = $test['media'] ?? $test['nota'] ?? null;

            if (is_numeric($media)) {
                $sum += (float) $media;
                $hasScore = true;
            }
        }

        $average = $hasScore && count($completedLessons) > 0
            ? round($sum / count($completedLessons), 2)
            : null;

        return [
            'course_status' => $course['situacao'] ?? $course['status'] ?? null,
            'course_status_label' => $this->statusLabel($course['situacao'] ?? $course['status'] ?? null),
            'local_status' => $localStatus,
            'current_lesson' => $currentLesson,
            'completed_lessons' => count($completedLessons),
            'total_lessons' => count($lessons),
            'average' => $average,
        ];
    }

    /** @param array<mixed> $payload @param list<string> $containers @return list<array<string, mixed>> */
    private function records(array $payload, array $containers): array
    {
        if (isset($payload['error']) || isset($payload['erro']) || in_array($payload['code'] ?? null, [0, '0'], true) || in_array($payload['Code'] ?? null, [0, '0'], true)) {
            return [];
        }

        foreach ($containers as $container) {
            if (isset($payload[$container]) && is_array($payload[$container])) {
                $payload = $payload[$container];

                break;
            }
        }

        if (! array_is_list($payload)) {
            if (isset($payload['error']) || isset($payload['erro']) || in_array($payload['code'] ?? null, [0, '0'], true) || in_array($payload['Code'] ?? null, [0, '0'], true)) {
                return [];
            }

            return $payload === [] ? [] : [$payload];
        }

        return array_values(array_filter($payload, 'is_array'));
    }

    private function normalizeStatus(mixed $status): string
    {
        $status = mb_strtolower(trim((string) $status));

        return match ($status) {
            'concluida', 'concluído', 'concluido', 'completed', 'aprovado' => 'concluido',
            'cursando', 'em andamento', 'in_progress' => 'cursando',
            'nao iniciado', 'não iniciado', 'nao_iniciado', 'not_started' => 'nao_iniciado',
            default => $status,
        };
    }

    private function isCompleted(mixed $status): bool
    {
        return $this->normalizeStatus($status) === 'concluido';
    }

    private function statusLabel(mixed $status): string
    {
        return match ($this->normalizeStatus($status)) {
            'concluido' => 'Concluído',
            'cursando' => 'Em andamento',
            'nao_iniciado' => 'Não iniciado',
            '' => 'Sem situação',
            default => ucfirst((string) $status),
        };
    }
}
