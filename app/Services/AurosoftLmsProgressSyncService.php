<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\Course;
use App\Models\LmsStudentLessonProgress;

class AurosoftLmsProgressSyncService
{
    /**
     * Sincroniza o progresso do aluno no curso nativo Aurosoft LMS,
     * calculando status das aulas, notas dos testes, média do curso e conclusão.
     */
    public function sync(ContractCourse $contractCourse): array
    {
        $contract = $contractCourse->contractItem?->contract;
        $course = $contractCourse->course;

        if (! $contract || ! $course || ! $course->isAurosoftLms()) {
            return $this->feedback($contractCourse);
        }

        $course->loadMissing(['lmsModules.lessons.steps']);
        $hasLmsLessons = $course->lmsModules->contains(fn ($m) => $m->lessons->isNotEmpty());
        if (! $hasLmsLessons) {
            return $this->feedback($contractCourse);
        }

        $now = now();
        $summary = $this->calculateCourseProgress($contract, $course);

        $contractCourse->forceFill([
            'status' => $summary['local_status'] ?? $contractCourse->status,
            'grade' => $summary['average'] ?? null,
            'completed_at' => $summary['completed_at'] ?? $contractCourse->completed_at,
            'progress_source' => 'aurosoft',
            'lms_synced_at' => $now,
            'ouro_moderno_status' => [
                'situacao' => $summary['course_status'],
                'status' => $summary['course_status'],
                'media' => $summary['average'],
                'aulas_concluidas' => $summary['completed_lessons'],
                'total_aulas' => $summary['total_lessons'],
            ],
            'ouro_moderno_status_checked_at' => $now,
            'ouro_moderno_lesson_status' => $summary['lesson_status'],
            'ouro_moderno_lesson_status_checked_at' => $now,
            'ouro_moderno_test_status' => $summary['test_status'],
            'ouro_moderno_test_status_checked_at' => $now,
        ])->save();

        return $summary;
    }

    /**
     * Sincroniza a partir de contrato e curso diretamente (ex: ao finalizar aula no player)
     */
    public function syncForContractAndCourse(Contract $contract, Course $course): ?array
    {
        $contractCourse = ContractCourse::query()
            ->whereIn('contract_item_id', $contract->items()->select('id'))
            ->where('course_id', $course->id)
            ->first();

        if (! $contractCourse) {
            return null;
        }

        return $this->sync($contractCourse);
    }

    /**
     * Sincroniza todos os cursos Aurosoft LMS de um contrato
     */
    public function syncAllForContract(Contract $contract): int
    {
        $contractCourses = ContractCourse::query()
            ->whereIn('contract_item_id', $contract->items()->select('id'))
            ->with(['course.lmsModules.lessons.steps', 'contractItem.contract'])
            ->get()
            ->filter(fn (ContractCourse $cc) => $cc->course?->isAurosoftLms());

        $count = 0;
        foreach ($contractCourses as $contractCourse) {
            $this->sync($contractCourse);
            $count++;
        }

        return $count;
    }

    /**
     * Retorna o resumo do progresso do curso (igual ao feedback de Ouro Moderno)
     */
    public function feedback(ContractCourse $contractCourse): array
    {
        $contract = $contractCourse->contractItem?->contract;
        $course = $contractCourse->course;

        if ($contract && $course && $course->isAurosoftLms()) {
            return $this->calculateCourseProgress($contract, $course);
        }

        return [
            'course_status' => $contractCourse->status === 'completed' ? 'concluido' : ($contractCourse->status === 'in_progress' ? 'cursando' : 'nao_iniciado'),
            'course_status_label' => match ($contractCourse->status) {
                'completed' => 'Concluído',
                'in_progress' => 'Em andamento',
                default => 'Não iniciado',
            },
            'local_status' => $contractCourse->status,
            'current_lesson' => null,
            'completed_lessons' => 0,
            'total_lessons' => 0,
            'average' => $contractCourse->grade !== null ? (float) $contractCourse->grade : null,
            'lesson_status' => $contractCourse->ouro_moderno_lesson_status ?? [],
            'test_status' => $contractCourse->ouro_moderno_test_status ?? [],
        ];
    }

    /**
     * Calcula detalhadamente aulas, testes, notas e situação
     */
    private function calculateCourseProgress(Contract $contract, Course $course): array
    {
        $course->loadMissing(['lmsModules.lessons.steps']);

        $lessons = $course->lmsModules
            ->flatMap(fn ($module) => $module->lessons)
            ->sortBy('sort_order')
            ->values();

        $lessonIds = $lessons->pluck('id')->all();

        $progressRecords = LmsStudentLessonProgress::query()
            ->where('contract_id', $contract->id)
            ->whereIn('lesson_id', $lessonIds)
            ->with(['attempts'])
            ->get()
            ->keyBy('lesson_id');

        $lessonStatus = [];
        $testStatus = [];
        $completedLessons = 0;
        $currentLesson = null;
        $completedScores = [];
        $latestCompletedAt = null;

        foreach ($lessons as $index => $lesson) {
            $lessonNumber = $lesson->sort_order ?: ($index + 1);
            /** @var LmsStudentLessonProgress|null $prog */
            $prog = $progressRecords->get($lesson->id);

            $status = $prog?->status ?? 'not_started';
            $isComp = $status === 'completed';
            $isInProgress = $status === 'in_progress';

            if ($isComp) {
                $completedLessons++;
                if ($prog->score !== null) {
                    $completedScores[] = (float) $prog->score;
                }
                if ($prog->completed_at && (! $latestCompletedAt || $prog->completed_at->isAfter($latestCompletedAt))) {
                    $latestCompletedAt = $prog->completed_at;
                }
            } elseif ($isInProgress && $currentLesson === null) {
                $currentLesson = $lessonNumber;
            }

            $lessonStatus[] = [
                'idaula' => (string) $lesson->id,
                'id_aula' => (string) $lesson->id,
                'aula' => (string) $lessonNumber,
                'numero_aula' => (string) $lessonNumber,
                'nome_aula' => $lesson->title,
                'titulo_aula' => $lesson->title,
                'situacao' => $isComp ? 'concluida' : ($isInProgress ? 'cursando' : 'nao_iniciado'),
                'status' => $isComp ? 'concluido' : ($isInProgress ? 'cursando' : 'nao_iniciado'),
                'data_inico' => $prog?->started_at?->format('Y-m-d H:i:s'),
                'data_inicio' => $prog?->started_at?->format('Y-m-d H:i:s'),
                'data_fim' => $prog?->completed_at?->format('Y-m-d H:i:s'),
                'fim' => $prog?->completed_at?->format('Y-m-d H:i:s'),
            ];

            // Avaliação / Teste prático da lição
            $hasScorable = $lesson->steps->contains(fn ($s) => in_array($s->step_type, ['quiz', 'hotspot_simulation', 'guided_simulation', 'challenge', 'assignment']));
            if ($hasScorable || $prog?->score !== null) {
                $testStatus[] = [
                    'idaula' => (string) $lesson->id,
                    'id_aula' => (string) $lesson->id,
                    'aula' => (string) $lessonNumber,
                    'numero_aula' => (string) $lessonNumber,
                    'nome_teste' => 'Avaliação / Prática: ' . $lesson->title,
                    'titulo_teste' => 'Avaliação / Prática: ' . $lesson->title,
                    'nome_aula' => $lesson->title,
                    'media' => $prog?->score !== null ? (float) $prog->score : null,
                    'nota' => $prog?->score !== null ? (float) $prog->score : null,
                    'execucoes' => $prog ? $prog->attempts->count() : 0,
                    'tentativas' => $prog ? $prog->attempts->count() : 0,
                    'data' => $prog?->completed_at?->format('Y-m-d H:i:s') ?? $prog?->started_at?->format('Y-m-d H:i:s'),
                    'inicio' => $prog?->started_at?->format('Y-m-d H:i:s'),
                    'data_fim' => $prog?->completed_at?->format('Y-m-d H:i:s'),
                    'fim' => $prog?->completed_at?->format('Y-m-d H:i:s'),
                    'situacao' => $isComp ? 'aprovado' : ($prog?->status === 'failed' ? 'reprovado' : ($isInProgress ? 'cursando' : 'pendente')),
                    'status' => $isComp ? 'aprovado' : ($prog?->status === 'failed' ? 'reprovado' : ($isInProgress ? 'cursando' : 'pendente')),
                ];
            }
        }

        $totalLessons = $lessons->count();
        $isAllCompleted = $totalLessons > 0 && $completedLessons >= $totalLessons;

        if ($currentLesson === null && ! $isAllCompleted && $completedLessons < $totalLessons) {
            $currentLesson = $completedLessons + 1;
        }

        $average = count($completedScores) > 0
            ? round(array_sum($completedScores) / count($completedScores), 2)
            : null;

        $localStatus = $isAllCompleted
            ? 'completed'
            : ($completedLessons > 0 || $currentLesson !== null ? 'in_progress' : 'not_started');

        $courseStatus = match ($localStatus) {
            'completed' => 'concluido',
            'in_progress' => 'cursando',
            default => 'nao_iniciado',
        };

        return [
            'course_status' => $courseStatus,
            'course_status_label' => match ($courseStatus) {
                'concluido' => 'Concluído',
                'cursando' => 'Em andamento',
                default => 'Não iniciado',
            },
            'local_status' => $localStatus,
            'current_lesson' => $currentLesson,
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
            'average' => $average,
            'completed_at' => $isAllCompleted ? ($latestCompletedAt ?? now()) : null,
            'lesson_status' => $lessonStatus,
            'test_status' => $testStatus,
        ];
    }
}
