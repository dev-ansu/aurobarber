<?php

namespace App\Filament\Pages;

use App\Enums\AddressTypeEnum;
use App\Enums\ContactTypeEnum;
use App\Enums\DocumentTypeEnum;
use App\Enums\RelationshipEnum;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\Person;
use App\Models\Responsible;
use App\Services\OuroModernoContractSyncService;
use App\Services\OuroModernoService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;
use UnitEnum;

class OuroModernoLmsStudents extends Page
{
    use WithPagination;

    protected static string|UnitEnum|null $navigationGroup = 'Pedagógico';

    protected static ?string $navigationLabel = 'Alunos no LMS sem cadastro';

    protected static ?string $title = 'Alunos da Ouro Moderno fora do Aurosoft';

    protected static ?string $slug = 'ouro-moderno-lms-students';

    protected static ?int $navigationSort = 7;

    protected string $view = 'filament.pages.ouro-moderno-lms-students';

    public string $search = '';

    public string $statusFilter = 'active';

    public string $registrationFilter = 'pending'; // 'pending', 'all', 'unregistered', 'without_contract', 'with_contract'

    public array $studentReport = [];

    public ?string $lastUpdated = null;

    public function updatedSearch(): void
    {
        $this->resetPage('lmsStudentsPage');
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage('lmsStudentsPage');
    }

    public function updatedRegistrationFilter(): void
    {
        $this->resetPage('lmsStudentsPage');
    }

    public function totalCachedStudentsCount(): int
    {
        return count(Cache::get($this->studentsCacheKey(), []));
    }

    public static function canAccess(): bool
    {
        return app(OuroModernoService::class)->isConfigured()
            && (auth()->user()?->can('View:OuroModernoLmsStudents') ?? false);
    }

    public function loadStudents(): void
    {
        abort_unless(auth()->user()?->can('View:OuroModernoLmsStudents') ?? false, 403);

        try {
            $tenantId = Filament::getTenant()?->id;
            $records = $this->extractStudentRecords(app(OuroModernoService::class)->forSchool($tenantId)->students());
            $people = Person::query()
                ->withoutGlobalScopes()
                ->get(['id', 'name', 'email', 'cpf', 'ouro_moderno_cod_int']);
            $tenantContracts = $tenantId
                ? Contract::withoutGlobalScopes()
                    ->where('school_id', $tenantId)
                    ->get(['id', 'contract_number', 'student_id', 'status'])
                    ->keyBy('student_id')
                : collect();

            $peopleByCode = [];
            $peopleByEmail = [];
            $peopleByCpf = [];

            foreach ($people as $person) {
                if ($person->ouro_moderno_cod_int !== null) {
                    $peopleByCode[(string) $person->ouro_moderno_cod_int] = $person->getKey();
                }

                if (filled($person->email)) {
                    $peopleByEmail[mb_strtolower($person->email)] = $person->getKey();
                }

                $personCpf = $this->normalizeDigits($person->cpf);

                if ($personCpf !== '') {
                    $peopleByCpf[$personCpf] = $person->getKey();
                }
            }

            $students = collect($records)
                ->map(function (array $row) use ($peopleByCode, $peopleByEmail, $peopleByCpf, $tenantContracts, $tenantId): array {
                    $code = $this->value($row, ['cod_int', 'codigo_integracao', 'integration_code']);
                    $idAluno = $this->value($row, ['id_aluno', 'user_id', 'id']);
                    $email = $this->value($row, ['email', 'e_mail']);
                    $cpf = $this->normalizeDigits($this->value($row, ['cpf']));
                    $personId = ($code !== null ? ($peopleByCode[$code] ?? null) : null)
                        ?? ($idAluno !== null ? ($peopleByCode[$idAluno] ?? null) : null)
                        ?? ($email !== null ? ($peopleByEmail[mb_strtolower($email)] ?? null) : null)
                        ?? ($cpf !== '' ? ($peopleByCpf[$cpf] ?? null) : null);

                    if ($personId && $tenantId) {
                        DB::table('school_person')->updateOrInsert(
                            ['school_id' => $tenantId, 'person_id' => $personId],
                            ['created_at' => now(), 'updated_at' => now()]
                        );
                    }

                    $contract = $personId ? $tenantContracts->get($personId) : null;

                    return [
                        'cod_int' => $code,
                        'id_aluno' => $idAluno,
                        'name' => $this->value($row, ['nome', 'nome_aluno', 'aluno', 'name', 'user_name']) ?? 'Aluno sem nome na resposta',
                        'email' => $email,
                        'username' => $this->value($row, ['usuario', 'username', 'user']),
                        'birth_date' => $this->value($row, ['nascimento', 'data_nascimento', 'birth_date']),
                        'start_date' => $this->value($row, ['data_inicio', 'inicio', 'data_cadastro', 'created_at']),
                        'end_date' => $this->value($row, ['data_fim', 'fim', 'data_conclusao', 'completed_at']),
                        'active' => $this->value($row, ['ativo', 'active', 'status']),
                        'person_id' => $personId,
                        'contract_id' => $contract?->id,
                        'contract_number' => $contract?->contract_number,
                        'contract_status' => $contract?->status,
                    ];
                })
                ->unique(fn (array $student): string => (string) ($student['cod_int'] ?? $student['id_aluno'] ?? $student['email'] ?? $student['name']))
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all();

            Cache::put($this->studentsCacheKey(), $students, now()->addMinutes(20));

            $this->studentReport = [];
            $this->lastUpdated = now()->format('d/m/Y H:i');
            $this->resetPage('lmsStudentsPage');

            Notification::make()
                ->title('Lista do LMS atualizada')
                ->body(count($students).' aluno(s) carregados da Ouro Moderno.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()
                ->title('Não foi possível carregar os alunos do LMS')
                ->body('Confira a configuração da integração e os logs do servidor para ver os detalhes.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    #[Computed]
    public function filteredStudents(): LengthAwarePaginator
    {
        $students = $this->matchingStudents();
        $perPage = 50;
        $page = LengthAwarePaginator::resolveCurrentPage('lmsStudentsPage');

        return new LengthAwarePaginator(
            $students->forPage($page, $perPage)->values(),
            $students->count(),
            $perPage,
            $page,
            [
                'pageName' => 'lmsStudentsPage',
                'path' => LengthAwarePaginator::resolveCurrentPath(),
            ],
        );
    }

    public function loadStudentReport(string $integrationCode): void
    {
        abort_unless(auth()->user()?->can('View:OuroModernoLmsStudents') ?? false, 403);
        abort_unless(ctype_digit($integrationCode), 404);

        try {
            $api = app(OuroModernoService::class);
            $remoteStudent = collect(Cache::get($this->studentsCacheKey(), []))
                ->first(fn (array $row): bool => (string) ($row['cod_int'] ?? '') === $integrationCode
                    || (string) ($row['id_aluno'] ?? '') === $integrationCode);

            abort_unless($remoteStudent !== null, 404);

            $remoteId = (int) ($this->value($remoteStudent, ['id_aluno', 'user_id', 'id']) ?? $integrationCode);
            $courseRows = $this->records($api->studentCourseStatus((int) $integrationCode), ['cursos', 'courses', 'dados', 'data']);

            if (empty($courseRows) && $remoteId) {
                try {
                    $api->updateStudentById($remoteId, ['cod_int' => (int) $integrationCode]);
                    $courseRows = $this->records($api->studentCourseStatus((int) $integrationCode), ['cursos', 'courses', 'dados', 'data']);
                } catch (Throwable) {
                    // Silently ignore if remote update fails
                }
            }

            $courses = [];

            foreach ($courseRows as $course) {
                $courseId = $course['idcurso'] ?? $course['curso_id'] ?? null;

                if (! is_numeric($courseId)) {
                    $courses[] = ['course' => $course, 'lessons' => [], 'tests' => []];

                    continue;
                }

                $courses[] = [
                    'course' => $course,
                    'lessons' => $api->studentLessonStatus((int) $integrationCode, (int) $courseId),
                    'tests' => $api->studentTestStatus((int) $integrationCode, (int) $courseId),
                ];
            }

            $this->studentReport = [
                'student' => [
                    'cod_int' => $integrationCode,
                    'id_aluno' => $this->value($remoteStudent, ['id_aluno', 'user_id', 'id']),
                    'name' => $this->value($remoteStudent, ['nome', 'nome_aluno', 'aluno', 'name', 'user_name']) ?? 'Aluno sem nome na resposta',
                    'email' => $this->value($remoteStudent, ['email', 'e_mail']),
                    'username' => $this->value($remoteStudent, ['usuario', 'username', 'user']),
                    'start_date' => $this->value($remoteStudent, ['data_inicio', 'inicio', 'data_cadastro', 'created_at']),
                    'end_date' => $this->value($remoteStudent, ['data_fim', 'fim', 'data_conclusao', 'completed_at']),
                ],
                'courses' => $courses,
                'loaded_at' => now()->format('d/m/Y H:i'),
            ];

            Notification::make()->title('Boletim do LMS carregado')->success()->send();
        } catch (Throwable $exception) {
            report($exception);
            Notification::make()
                ->title('Não foi possível consultar o boletim do LMS')
                ->body($exception->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    /**
     * Cadastra o aluno do LMS da Ouro Moderno como Pessoa no Aurosoft,
     * configura o responsável financeiro automático e redireciona para a criação do Contrato.
     */
    public function importAndCreateContract(string $identifier, bool $redirectToContract = true): void
    {
        abort_unless(
            auth()->user()?->can('Create:Person')
            || (method_exists(auth()->user(), 'canManageLmsOnboarding') && auth()->user()->canManageLmsOnboarding())
            || auth()->user()?->hasRole('super_admin'),
            403
        );

        $cachedStudents = collect(Cache::get($this->studentsCacheKey(), []));
        $studentData = $cachedStudents->first(function (array $s) use ($identifier) {
            return (string) ($s['cod_int'] ?? '') === $identifier
                || (string) ($s['id_aluno'] ?? '') === $identifier;
        });

        if (! $studentData) {
            Notification::make()
                ->title('Aluno não localizado')
                ->body('Os dados deste aluno não constam no cache atual. Por favor, clique em "Atualizar lista".')
                ->danger()
                ->send();

            return;
        }

        $codInt = ! empty($studentData['cod_int']) && is_numeric($studentData['cod_int'])
            ? (int) $studentData['cod_int']
            : (! empty($studentData['id_aluno']) && is_numeric($studentData['id_aluno']) ? (int) $studentData['id_aluno'] : null);
        $remoteId = ! empty($studentData['id_aluno']) && is_numeric($studentData['id_aluno'])
            ? (int) $studentData['id_aluno']
            : $codInt;

        $tenantId = Filament::getTenant()?->id;
        $omApi = app(OuroModernoService::class)->forSchool($tenantId);

        $email = ! empty($studentData['email']) ? trim($studentData['email']) : null;
        $cpf = ! empty($studentData['cpf']) ? $this->normalizeDigits($studentData['cpf']) : null;

        // 1. Tenta localizar se a pessoa já foi cadastrada previamente (respeitando unidade para cod_int local)
        $person = null;
        if ($codInt) {
            $person = Person::query()
                ->where('ouro_moderno_cod_int', $codInt)
                ->when($tenantId, fn ($q) => $q->whereHas('contracts', fn ($cq) => $cq->withoutGlobalScopes()->where('school_id', $tenantId)))
                ->first();
        }
        if (! $person && $email) {
            $person = Person::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
        }
        if (! $person && $cpf) {
            $person = Person::where('cpf', $cpf)->first();
        }

        // 2. Cria a pessoa caso não exista
        if (! $person) {
            $birthDate = null;
            if (! empty($studentData['birth_date'])) {
                try {
                    $birthDate = Carbon::parse($studentData['birth_date'])->format('Y-m-d');
                } catch (Throwable) {
                }
            }

            // Evita colisão de e-mail único duplicado
            if ($email && Person::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->exists()) {
                $email = null;
            }

            $codeCollides = $codInt && Person::where('ouro_moderno_cod_int', $codInt)->exists();

            $person = Person::create([
                'name' => $studentData['name'],
                'email' => $email,
                'birth_date' => $birthDate,
                'ouro_moderno_cod_int' => $codeCollides ? null : $codInt,
                'ouro_moderno_usuario' => $studentData['username'] ?: null,
                'ouro_moderno_registered_at' => now(),
            ]);

            if ($codeCollides || ! $codInt) {
                $codInt = (int) $person->id;
                $person->update(['ouro_moderno_cod_int' => $codInt]);
            }

            // Cria o responsável financeiro automático (o próprio aluno cadastrado como responsável inicial)
            Responsible::firstOrCreate(
                [
                    'person_id' => $person->id,
                    'financial_responsible' => true,
                ],
                [
                    'relationship' => RelationshipEnum::Legal_guardian,
                ]
            );
        } else {
            // Garante o vínculo do código da Ouro Moderno se ainda não estiver salvo
            if ($codInt && ! $person->ouro_moderno_cod_int) {
                $person->update(['ouro_moderno_cod_int' => $codInt]);
            }

            // Garante que a pessoa possua ao menos um registro como responsável financeiro
            Responsible::firstOrCreate(
                [
                    'person_id' => $person->id,
                    'financial_responsible' => true,
                ],
                [
                    'relationship' => RelationshipEnum::Legal_guardian,
                ]
            );
        }

        // Garante que o cod_int definitivo esteja gravado no banco de dados da Ouro Moderno desta escola
        $effectiveCodInt = (int) ($person->ouro_moderno_cod_int ?: $codInt);
        if ($effectiveCodInt && $remoteId) {
            try {
                $omApi->updateStudentById($remoteId, ['cod_int' => $effectiveCodInt]);
            } catch (Throwable $e) {
                Log::info("Tentativa de sincronizar cod_int no LMS Ouro Moderno: {$e->getMessage()}");
            }
        }

        // 3. Remove o aluno do cache de pendentes
        $updatedCached = $cachedStudents->reject(function (array $s) use ($identifier) {
            return (string) ($s['cod_int'] ?? '') === $identifier
                || (string) ($s['id_aluno'] ?? '') === $identifier;
        })->values()->all();
        Cache::put($this->studentsCacheKey(), $updatedCached, now()->addMinutes(20));

        if ($tenantId) {
            $person->schools()->syncWithoutDetaching([$tenantId]);
        }

        if ($redirectToContract) {
            Notification::make()
                ->title('Aluno cadastrado!')
                ->body("Cadastro de {$person->name} realizado. Redirecionando para a criação do Contrato...")
                ->success()
                ->send();

            $this->redirect(route('filament.admin.resources.contracts.create', array_filter([
                'tenant' => $tenantId,
                'student_id' => $person->id,
            ])));
        } else {
            Notification::make()
                ->title('Aluno cadastrado!')
                ->body("Cadastro de {$person->name} realizado. Redirecionando para completar dados da Pessoa...")
                ->success()
                ->send();

            $this->redirect(route('filament.admin.resources.people.edit', [
                'tenant' => $tenantId,
                'record' => $person->id,
            ]));
        }
    }

    /**
     * Importa o aluno do LMS, cadastra como Pessoa, sincroniza o cod_int na Ouro Moderno
     * e redireciona para a tela de edição da Pessoa com os Relation Managers nativos do Aurosoft.
     */
    public function importAndStartEnrollment(string $identifier): void
    {
        abort_unless(
            auth()->user()?->can('Create:Person')
            || (method_exists(auth()->user(), 'canManageLmsOnboarding') && auth()->user()->canManageLmsOnboarding())
            || auth()->user()?->hasRole('super_admin'),
            403
        );

        $cachedStudents = collect(Cache::get($this->studentsCacheKey(), []));
        $studentData = $cachedStudents->first(function (array $s) use ($identifier) {
            return (string) ($s['cod_int'] ?? '') === $identifier
                || (string) ($s['id_aluno'] ?? '') === $identifier;
        });

        if (! $studentData) {
            Notification::make()
                ->title('Aluno não localizado')
                ->body('Os dados deste aluno não constam no cache atual. Por favor, clique em "Atualizar lista".')
                ->danger()
                ->send();

            return;
        }

        $tenantId = Filament::getTenant()?->id;
        $omApi = app(OuroModernoService::class)->forSchool($tenantId);

        $codInt = ! empty($studentData['cod_int']) && is_numeric($studentData['cod_int'])
            ? (int) $studentData['cod_int']
            : (! empty($studentData['id_aluno']) && is_numeric($studentData['id_aluno']) ? (int) $studentData['id_aluno'] : null);
        $remoteId = ! empty($studentData['id_aluno']) && is_numeric($studentData['id_aluno'])
            ? (int) $studentData['id_aluno']
            : $codInt;

        $email = filled($studentData['email'] ?? null) ? trim($studentData['email']) : null;
        $cpf = filled($studentData['cpf'] ?? null) ? $this->normalizeDigits($studentData['cpf']) : null;
        $name = trim((string) ($studentData['name'] ?? 'Aluno Ouro Moderno'));
        $username = filled($studentData['username'] ?? null) ? trim($studentData['username']) : null;

        $birthDate = null;
        if (! empty($studentData['birth_date'])) {
            try {
                $birthDate = Carbon::parse($studentData['birth_date'])->format('Y-m-d');
            } catch (Throwable) {
            }
        }

        // Localiza ou cria a Pessoa do Aluno
        $person = null;
        if ($codInt) {
            $person = Person::query()
                ->where('ouro_moderno_cod_int', $codInt)
                ->when($tenantId, fn ($q) => $q->whereHas('schools', fn ($sq) => $sq->whereKey($tenantId)))
                ->first();
        }
        if (! $person && $email) {
            $person = Person::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
        }
        if (! $person && $cpf) {
            $person = Person::where('cpf', $cpf)->first();
        }

        if (! $person) {
            if ($email && Person::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->exists()) {
                $email = null;
            }

            $codeCollides = $codInt && Person::where('ouro_moderno_cod_int', $codInt)->exists();

            $person = Person::create([
                'name' => $name,
                'email' => $email,
                'cpf' => $cpf ?: null,
                'birth_date' => $birthDate,
                'ouro_moderno_cod_int' => $codeCollides ? null : $codInt,
                'ouro_moderno_usuario' => $username,
                'ouro_moderno_registered_at' => now(),
            ]);

            if ($codeCollides || ! $codInt) {
                $codInt = (int) $person->id;
                $person->update(['ouro_moderno_cod_int' => $codInt]);
            }
        } else {
            $person->update(array_filter([
                'name' => $person->name ?: $name,
                'cpf' => $person->cpf ?: $cpf,
                'birth_date' => $person->birth_date ?: $birthDate,
                'ouro_moderno_usuario' => $person->ouro_moderno_usuario ?: $username,
                'ouro_moderno_registered_at' => $person->ouro_moderno_registered_at ?? now(),
            ]));
        }

        if ($tenantId) {
            $person->schools()->syncWithoutDetaching([$tenantId]);
        }

        // Sincroniza o cod_int na Ouro Moderno
        $effectiveCodInt = (int) ($person->ouro_moderno_cod_int ?: $codInt ?: $person->id);
        if ($remoteId && $omApi->isConfigured()) {
            try {
                $omApi->syncStudentIntegrationCode($remoteId, $effectiveCodInt, [
                    'nome' => $person->name,
                    'usuario' => $person->ouro_moderno_usuario ?? (string) $effectiveCodInt,
                ]);
            } catch (Throwable $e) {
                Log::info("Erro syncStudentIntegrationCode: {$e->getMessage()}");
            }
        }

        // Atualiza o item no cache com o person_id
        $cachedStudents = collect(Cache::get($this->studentsCacheKey(), []));
        $updatedCached = $cachedStudents->map(function (array $s) use ($identifier, $person) {
            if ((string) ($s['cod_int'] ?? '') === $identifier || (string) ($s['id_aluno'] ?? '') === $identifier) {
                $s['person_id'] = $person->id;
            }

            return $s;
        })->values()->all();
        Cache::put($this->studentsCacheKey(), $updatedCached, now()->addMinutes(20));

        Notification::make()
            ->title('Aluno importado com sucesso!')
            ->body("Cadastro de {$person->name} realizado. Use as abas abaixo para preencher Contatos e Endereços e clique em 'Criar Contrato' no topo quando concluir.")
            ->success()
            ->send();

        $this->redirect(route('filament.admin.resources.people.edit', [
            'tenant' => $tenantId,
            'record' => $person->id,
            'flow' => 'lms_enrollment',
        ]));
    }

    public function deleteOrphanPerson(int $personId): void
    {
        abort_unless(
            auth()->user()?->can('Delete:Person')
            || (method_exists(auth()->user(), 'canManageLmsOnboarding') && auth()->user()->canManageLmsOnboarding())
            || auth()->user()?->hasRole('super_admin'),
            403
        );

        $person = Person::withoutGlobalScopes()->find($personId);
        if (! $person) {
            Notification::make()->title('Pessoa não encontrada.')->warning()->send();

            return;
        }

        if ($person->contracts()->withoutGlobalScopes()->exists()) {
            Notification::make()
                ->title('Não é possível excluir')
                ->body('Esta pessoa já possui contratos registrados.')
                ->danger()
                ->send();

            return;
        }

        try {
            $personName = $person->name;
            $person->delete();

            $this->loadStudents();

            Notification::make()
                ->title('Cadastro excluído com sucesso!')
                ->body("O cadastro de {$personName} foi removido.")
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Falha ao excluir pessoa')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function exportReport(): StreamedResponse
    {
        abort_unless(auth()->user()?->can('Export:OuroModernoLmsStudents') ?? false, 403);
        abort_if($this->lastUpdated === null, 422, 'Atualize a lista do LMS antes de exportar.');
        $students = $this->matchingStudents();

        return response()->streamDownload(function () use ($students): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['cod_int', 'ID aluno LMS', 'Nome', 'E-mail', 'Usuário', 'Nascimento', 'Situação LMS', 'Data de início', 'Data de fim'], ';', '"', '');

            foreach ($students as $student) {
                fputcsv($stream, [
                    $student['cod_int'], $student['id_aluno'], $student['name'], $student['email'],
                    $student['username'], $student['birth_date'], $student['active'], $student['start_date'], $student['end_date'],
                ], ';', '"', '');
            }

            fclose($stream);
        }, 'alunos-lms-sem-cadastro-'.today()->toDateString().'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return list<array<string, mixed>> */
    private function extractStudentRecords(array $payload): array
    {
        $records = [];
        $pending = [$payload];

        while ($pending !== []) {
            $record = array_pop($pending);

            if (! is_array($record)) {
                continue;
            }

            $hasStudentIdentity = filled($this->value($record, ['nome', 'nome_aluno', 'aluno', 'name', 'user_name']))
                && (array_key_exists('cod_int', $record)
                    || array_key_exists('codigo_integracao', $record)
                    || array_key_exists('integration_code', $record)
                    || array_key_exists('id', $record)
                    || array_key_exists('email', $record));

            if ($hasStudentIdentity) {
                $records[] = $record;
            }

            foreach ($record as $value) {
                if (is_array($value)) {
                    $pending[] = $value;
                }
            }
        }

        return collect($records)
            ->unique(fn (array $row): string => (string) (
                $this->value($row, ['cod_int', 'codigo_integracao', 'integration_code'])
                ?? $this->value($row, ['id_aluno', 'user_id', 'id'])
                ?? $this->value($row, ['email', 'e_mail'])
                ?? $this->value($row, ['nome', 'nome_aluno', 'aluno', 'name', 'user_name'])
            ))
            ->values()
            ->all();
    }

    private function normalizeDigits(?string $value): string
    {
        return $value === null ? '' : preg_replace('/\D+/', '', $value);
    }

    /** @return Collection<int, array<string, mixed>> */
    private function matchingStudents(): Collection
    {
        $students = collect(Cache::get($this->studentsCacheKey(), []));

        if ($students->isNotEmpty()) {
            $personIdsInCache = $students->pluck('person_id')->filter()->unique()->all();
            if (! empty($personIdsInCache)) {
                $existingPersonIds = Person::withoutGlobalScopes()
                    ->whereIn('id', $personIdsInCache)
                    ->pluck('id')
                    ->all();
                $existingSet = array_flip($existingPersonIds);

                $students = $students->map(function (array $student) use ($existingSet): array {
                    if (! empty($student['person_id']) && ! isset($existingSet[$student['person_id']])) {
                        $student['person_id'] = null;
                        $student['contract_id'] = null;
                        $student['contract_number'] = null;
                    }

                    return $student;
                });
            }
        }

        if ($this->statusFilter === 'active') {
            $students = $students->filter(fn (array $student): bool => $this->isStudentActive($student['active'] ?? null));
        } elseif ($this->statusFilter === 'inactive') {
            $students = $students->filter(fn (array $student): bool => ! $this->isStudentActive($student['active'] ?? null));
        }

        // Filtro de Matrícula no Aurosoft (registrationFilter)
        $students = $students->filter(function (array $student): bool {
            $hasContract = ! empty($student['contract_id']);
            $hasPerson = ! empty($student['person_id']);

            return match ($this->registrationFilter) {
                'pending' => ! $hasContract,
                'unregistered' => ! $hasPerson,
                'without_contract' => $hasPerson && ! $hasContract,
                'with_contract' => $hasContract,
                default => true,
            };
        });

        if (blank($this->search)) {
            return $students->values();
        }

        $term = mb_strtolower(trim($this->search));

        return $students
            ->filter(fn (array $student): bool => str_contains(mb_strtolower(implode(' ', [
                $student['name'] ?? '',
                $student['cod_int'] ?? '',
                $student['id_aluno'] ?? '',
                $student['email'] ?? '',
                $student['username'] ?? '',
                $student['contract_number'] ?? '',
            ])), $term))
            ->values();
    }

    private function isStudentActive(mixed $status): bool
    {
        return in_array(mb_strtoupper(trim((string) $status)), ['S', '1', 'SIM', 'ATIVO', 'ACTIVE'], true);
    }

    private function studentsCacheKey(): string
    {
        $tenantId = Filament::getTenant()?->id;

        return $tenantId !== null
            ? 'ouro-moderno:lms-students:'.auth()->id().':school:'.$tenantId
            : 'ouro-moderno:lms-students:'.auth()->id();
    }

    /** @param list<string> $keys */
    private function value(array $record, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($record[$key]) && is_scalar($record[$key]) && trim((string) $record[$key]) !== '') {
                return trim((string) $record[$key]);
            }
        }

        return null;
    }

    /** @param list<string> $containers @return list<array<string, mixed>> */
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
}
