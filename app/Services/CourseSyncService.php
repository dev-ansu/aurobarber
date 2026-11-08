<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CourseSyncService
{
    public function __construct(private readonly OuroModernoService $ouroModerno) {}

    public function syncCourses(?int $schoolId = null): int
    {
        $api = $schoolId !== null ? $this->ouroModerno->forSchool($schoolId) : $this->ouroModerno;
        $courses = $this->normalizeCourses($api->courses());

        DB::transaction(function () use ($courses): void {
            foreach ($courses as $course) {
                Course::syncingFromOuroModerno(fn (): Course => Course::updateOrCreate(
                    ['ouro_moderno_id' => (int) $course['id']],
                    [
                        'name' => trim((string) $course['curso']),
                        'workload' => isset($course['carga']) && is_numeric($course['carga'])
                            ? (int) $course['carga']
                            : null,
                    ],
                ));
            }
        });

        return count($courses);
    }

    /** @param array<mixed> $payload
     * @return array<int, array{id: int, curso: string, carga?: int|float|string|null}>
     */
    private function normalizeCourses(array $payload): array
    {
        $courses = array_is_list($payload)
            ? $payload
            : ($payload['cursos'] ?? $payload['data'] ?? null);

        if (! is_array($courses) || ! array_is_list($courses)) {
            throw new RuntimeException('A lista de cursos da Ouro Moderno veio em um formato inesperado; nenhuma alteração foi salva.');
        }

        foreach ($courses as $course) {
            if (
                ! is_array($course)
                || ! isset($course['id'], $course['curso'])
                || ! is_numeric($course['id'])
                || trim((string) $course['curso']) === ''
            ) {
                throw new RuntimeException('Um curso retornado pela Ouro Moderno está sem ID ou nome válido; nenhuma alteração foi salva.');
            }
        }

        return $courses;
    }
}
