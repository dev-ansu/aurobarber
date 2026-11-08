<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\Course;
use App\Models\Formation;
use App\Models\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OuroModernoContractSyncService
{
    public function __construct(private readonly OuroModernoService $ouroModerno) {}

    private function api(?Contract $contract = null): OuroModernoService
    {
        return $contract?->school_id
            ? $this->ouroModerno->forSchool($contract->school_id)
            : $this->ouroModerno;
    }

    /**
     * @return array{student_code: int, courses_enrolled: int, schedules_reviewed: int}
     */
    public function sync(Contract $contract, string $mode, ?string $password = null): array
    {
        $api = $this->api($contract);

        if ($contract->status !== 'active') {
            throw new RuntimeException('Somente contratos ativos podem ser enviados ao LMS.');
        }

        if (! in_array($mode, ['create', 'update'], true)) {
            throw new RuntimeException('Selecione se o aluno é novo ou já existe na Ouro Moderno.');
        }

        $contract->loadMissing([
            'student.contacts',
            'student.documents',
            'student.addresses.city.state',
            'items.contractCourses.course',
            'items.schedules.classroom',
        ]);

        $student = $contract->student;

        if (! $student instanceof Person || blank($student->name)) {
            throw new RuntimeException('O contrato não possui um aluno com nome válido.');
        }

        $username = trim((string) $contract->contract_number);

        if ($username === '') {
            throw new RuntimeException('O contrato precisa ter um número para definir o usuário do aluno no LMS.');
        }

        if ($mode === 'create' && blank($password)) {
            throw new RuntimeException('Informe uma senha inicial para cadastrar o aluno no LMS.');
        }

        if ($mode === 'create' && $student->ouro_moderno_registered_at !== null) {
            $alreadySyncedInSameSchool = $contract->school_id === null
                || Contract::withoutGlobalScopes()
                    ->where('student_id', $student->id)
                    ->where('school_id', $contract->school_id)
                    ->where('id', '!=', $contract->id)
                    ->whereHas('items.contractCourses', fn ($q) => $q->whereNotNull('lms_synced_at'))
                    ->exists()
                || $contract->items()->whereHas('contractCourses', fn ($q) => $q->whereNotNull('lms_synced_at'))->exists();

            if ($alreadySyncedInSameSchool) {
                throw new RuntimeException('Este aluno já foi marcado como cadastrado na Ouro Moderno. Escolha a opção de aluno existente.');
            }
        }

        $courseLinks = $contract->items
            ->flatMap(fn ($item) => $item->contractCourses)
            ->values();

        if ($courseLinks->isEmpty()) {
            throw new RuntimeException('Adicione ao menos um curso ao contrato antes de enviar ao LMS.');
        }

        $ouroModernoLinks = $courseLinks->filter(fn ($link): bool => $link->course?->ouro_moderno_id !== null);
        $aurosoftLinks = $courseLinks->filter(fn ($link): bool => $link->course?->ouro_moderno_id === null);

        if ($ouroModernoLinks->isEmpty()) {
            throw new RuntimeException('Este contrato contém apenas cursos nativos do LMS Aurosoft. Não há cursos vinculados à Ouro Moderno para sincronizar.');
        }

        $integrationCode = (int) ($student->ouro_moderno_cod_int ?: $student->getKey());
        $studentData = $this->studentData($student, $integrationCode, $username);

        if ($mode === 'create') {
            $api->createStudent([
                ...$studentData,
                'senha' => (string) $password,
            ]);
        } else {
            unset($studentData['estado'], $studentData['cidade']);
            if (filled($password)) {
                $studentData['senha'] = (string) $password;
            }
            $api->updateStudent($integrationCode, $studentData);
        }

        DB::transaction(function () use ($student, $integrationCode, $username, $password): void {
            $dataToFill = [
                'ouro_moderno_cod_int' => $integrationCode,
                'ouro_moderno_usuario' => $username,
                'ouro_moderno_registered_at' => now(),
            ];

            if (filled($password)) {
                $dataToFill['ouro_moderno_senha'] = (string) $password;
            }

            $student->forceFill($dataToFill)->save();
        });

        // IDs de cursos Ouro Moderno efetivamente vinculados aos contratos ativos do aluno nesta mesma unidade escolar
        $studentActiveOmCourseIds = ContractCourse::query()
            ->whereHas('contractItem.contract', function ($q) use ($student, $contract) {
                $q->withoutGlobalScopes()
                    ->where('student_id', $student->id)
                    ->when($contract->school_id !== null, fn ($sq) => $sq->where('school_id', $contract->school_id))
                    ->where('status', 'active');
            })
            ->with('course')
            ->get()
            ->pluck('course.ouro_moderno_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->all();

        // Verifica cursos no LMS Ouro Moderno e desativa (disponivel = 0) os que foram desvinculados
        $coursesRemoved = 0;
        try {
            $remoteCourseStatus = $api->studentCourseStatus($integrationCode);
            $remoteCourseRows = $this->records($remoteCourseStatus, ['cursos', 'courses', 'dados', 'data']);

            foreach ($remoteCourseRows as $remoteRow) {
                $remoteCourseId = $remoteRow['idcurso'] ?? $remoteRow['curso_id'] ?? null;
                if (! is_numeric($remoteCourseId)) {
                    continue;
                }
                $remoteCourseId = (int) $remoteCourseId;

                // Se o curso está na Ouro Moderno mas não está mais contratado/ativo no Aurosoft
                if (! in_array($remoteCourseId, $studentActiveOmCourseIds, true)) {
                    $isAvailable = (int) ($remoteRow['disponivel'] ?? 1);
                    if ($isAvailable === 1) {
                        $api->updateStudentCourse($integrationCode, $remoteCourseId, [
                            'disponivel' => 0,
                        ]);
                        $coursesRemoved++;
                    }
                } else {
                    // Se o curso está contratado mas estava marcado como indisponível, reativa
                    $isAvailable = (int) ($remoteRow['disponivel'] ?? 1);
                    if ($isAvailable === 0) {
                        $api->updateStudentCourse($integrationCode, $remoteCourseId, [
                            'disponivel' => 1,
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Não foi possível verificar/desativar cursos desvinculados no LMS Ouro Moderno: {$e->getMessage()}");
        }

        $pendingLinks = $ouroModernoLinks->filter(fn ($link): bool => $link->lms_synced_at === null);

        if ($pendingLinks->isNotEmpty()) {
            $api->enrollCourses(
                $integrationCode,
                $pendingLinks->map(fn ($link): int => (int) $link->course->ouro_moderno_id)->all(),
            );

            DB::transaction(function () use ($pendingLinks): void {
                foreach ($pendingLinks as $link) {
                    $link->forceFill([
                        'lms_synced_at' => now(),
                        'progress_source' => 'ouro_moderno',
                    ])->save();
                }
            });
        }

        return [
            'student_code' => $integrationCode,
            'courses_enrolled' => $pendingLinks->count(),
            'courses_removed' => $coursesRemoved,
            'ouro_moderno_courses_count' => $ouroModernoLinks->count(),
            'aurosoft_courses_count' => $aurosoftLinks->count(),
            'schedules_reviewed' => $contract->items->sum(fn ($item): int => $item->schedules->count()),
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

    /** @return array<string, int|string> */
    private function studentData(Person $student, int $integrationCode, string $username): array
    {
        $phone = $student->contacts
            ->sortByDesc('is_primary')
            ->first(fn ($contact): bool => in_array($contact->type?->value ?? $contact->type, ['cellphone', 'phone', 'whatsapp'], true));

        $address = $student->addresses->sortByDesc('is_primary')->first();
        $rg = $student->documents
            ->sortByDesc('is_primary')
            ->first(fn ($document): bool => ($document->type?->value ?? $document->type) === 'rg');

        $addressParts = array_filter([
            trim((string) $address?->street),
            filled($address?->number) ? (string) $address->number : null,
            filled($address?->complement) ? (string) $address->complement : null,
            filled($address?->neighborhood) ? (string) $address->neighborhood : null,
        ]);

        $data = [
            'cod_int' => $integrationCode,
            'nome' => (string) $student->name,
            'usuario' => $username,
            // API requires numeric codes; zero means no remote location mapping was supplied.
            'estado' => 0,
            'cidade' => 0,
            'ativo' => 'S',
            'nivel_acesso' => 0,
        ];

        foreach ([
            'email' => $student->email,
            'telefone' => $phone?->value,
            'endereco' => implode(', ', $addressParts),
            'cpf' => $student->cpf,
            'rg' => $rg?->value,
            'nascimento' => $student->birth_date?->format('Y-m-d'),
        ] as $key => $value) {
            if (filled($value)) {
                $data[$key] = (string) $value;
            }
        }

        return $data;
    }

    /**
     * Importa os cursos já cadastrados na Ouro Moderno para o contrato do aluno,
     * criando uma formação compatível e inserindo os cursos com a situação atual.
     */
    public function importStudentCoursesFromLms(Contract $contract): int
    {
        $api = $this->api($contract);

        $student = $contract->student;
        if (! $student instanceof Person || ! $student->ouro_moderno_cod_int) {
            return 0;
        }

        $codInt = (int) $student->ouro_moderno_cod_int;

        try {
            $courseResponse = $api->studentCourseStatus($codInt);
            $courseRows = $this->records($courseResponse, ['cursos', 'courses', 'dados', 'data']);

            // Se o aluno foi criado diretamente na OM, seu cod_int pode estar vazio na tabela do LMS.
            // Sincronizamos o cod_int na OM via updateStudentById / syncStudentIntegrationCode e tentamos novamente.
            if (empty($courseRows)) {
                try {
                    $api->updateStudentById($codInt, ['cod_int' => $codInt]);
                    $courseResponse = $api->studentCourseStatus($codInt);
                    $courseRows = $this->records($courseResponse, ['cursos', 'courses', 'dados', 'data']);
                } catch (\Throwable $updateError) {
                    try {
                        $api->syncStudentIntegrationCode($codInt, $codInt, [
                            'nome' => $student->name,
                            'usuario' => $student->ouro_moderno_usuario ?? (string) $codInt,
                        ]);
                        $courseResponse = $api->studentCourseStatus($codInt);
                        $courseRows = $this->records($courseResponse, ['cursos', 'courses', 'dados', 'data']);
                    } catch (\Throwable $syncError) {
                        Log::info("Tentativa de vincular cod_int {$codInt} retornou: {$syncError->getMessage()}");
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Não foi possível buscar cursos do aluno {$codInt} no LMS Ouro Moderno: {$e->getMessage()}");

            return 0;
        }

        if (empty($courseRows)) {
            return 0;
        }

        $formation = Formation::firstOrCreate(
            ['name' => 'Formação Ouro Moderno'],
            [
                'description' => 'Formação gerada a partir dos cursos vinculados ao aluno no LMS Ouro Moderno.',
                'active' => true,
            ]
        );

        $importedCoursesCount = 0;

        DB::transaction(function () use ($contract, $formation, $courseRows, &$importedCoursesCount): void {
            $contractItem = $contract->items()->where('formation_id', $formation->id)->first();
            if (! $contractItem) {
                $nextOrder = ($contract->items()->max('order') ?? 0) + 1;
                $contractItem = $contract->items()->create([
                    'formation_id' => $formation->id,
                    'order' => $nextOrder,
                ]);
            }

            $currentOrder = ($contractItem->contractCourses()->max('order') ?? 0) + 1;

            foreach ($courseRows as $row) {
                $omCourseId = $row['idcurso'] ?? $row['curso_id'] ?? null;
                if (! is_numeric($omCourseId)) {
                    continue;
                }
                $omCourseId = (int) $omCourseId;

                $course = Course::where('ouro_moderno_id', $omCourseId)->first();

                if (! $course) {
                    $courseName = trim((string) ($row['curso'] ?? $row['nome_curso'] ?? $row['nome'] ?? "Curso OM #{$omCourseId}"));
                    $workload = isset($row['carga']) && is_numeric($row['carga']) ? (int) $row['carga'] : 20;

                    $course = Course::syncingFromOuroModerno(fn () => Course::create([
                        'ouro_moderno_id' => $omCourseId,
                        'name' => $courseName,
                        'workload' => $workload,
                        'active' => true,
                    ]));
                }

                $formation->courses()->syncWithoutDetaching([$course->id => ['order' => $currentOrder]]);

                if ($contractItem->contractCourses()->where('course_id', $course->id)->exists()) {
                    continue;
                }

                $rawStatus = mb_strtolower(trim((string) ($row['situacao'] ?? $row['status'] ?? '')));
                $localStatus = match ($rawStatus) {
                    'concluida', 'concluído', 'concluido', 'completed', 'aprovado' => 'completed',
                    'cursando', 'em andamento', 'in_progress' => 'in_progress',
                    default => 'not_started',
                };

                $isCompleted = ($localStatus === 'completed');
                $grade = isset($row['media']) && is_numeric($row['media'])
                    ? (float) $row['media']
                    : (isset($row['nota']) && is_numeric($row['nota']) ? (float) $row['nota'] : null);

                ContractCourse::create([
                    'contract_item_id' => $contractItem->id,
                    'course_id' => $course->id,
                    'order' => $currentOrder++,
                    'status' => $localStatus,
                    'grade' => $grade,
                    'completed_at' => $isCompleted ? now() : null,
                    'progress_source' => 'ouro_moderno',
                    'lms_synced_at' => now(),
                    'ouro_moderno_status' => $row,
                    'ouro_moderno_status_checked_at' => now(),
                ]);

                $importedCoursesCount++;
            }

            if ($importedCoursesCount > 0) {
                $contractItem->syncFormationCompletion();
            }
        });

        return $importedCoursesCount;
    }
}
