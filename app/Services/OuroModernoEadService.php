<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Person;
use App\Models\School;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OuroModernoEadService
{
    private string $baseUrl = 'https://meuappdecursos.com.br/ws/v2';

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Testa a chave da API do EAD buscando as unidades vinculadas.
     *
     * @return array{success: bool, unidade: array<string, mixed>|null, message: string}
     */
    public function testConnection(string $apiKey): array
    {
        try {
            $response = Http::withBasicAuth(trim($apiKey), '')
                ->timeout(12)
                ->acceptJson()
                ->get("{$this->baseUrl}/unidades");

            if (! $response->successful()) {
                return [
                    'success' => false,
                    'unidade' => null,
                    'message' => 'Falha na autenticação da API Ouro Moderno EAD (Código: '.$response->status().'). Verifique a chave.',
                ];
            }

            $data = $response->json();
            $unidades = $data['data'] ?? [];

            if (empty($unidades)) {
                return [
                    'success' => false,
                    'unidade' => null,
                    'message' => 'Nenhuma unidade escolar encontrada vinculada a esta chave da API.',
                ];
            }

            $first = $unidades[0];

            return [
                'success' => true,
                'unidade' => $first,
                'message' => 'Conexão realizada com sucesso! Unidade identificada: '.($first['nome'] ?? 'Unidade').' (ID: '.($first['id'] ?? '—').')',
            ];
        } catch (Throwable $e) {
            Log::error('Erro ao testar conexão Ouro Moderno EAD: '.$e->getMessage());

            return [
                'success' => false,
                'unidade' => null,
                'message' => 'Erro ao conectar à API Ouro Moderno EAD: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Obtém o token da unidade na plataforma EAD.
     */
    public function getUnitToken(string $apiKey, string $unitId): ?string
    {
        try {
            $response = Http::withBasicAuth(trim($apiKey), '')
                ->timeout(12)
                ->acceptJson()
                ->get("{$this->baseUrl}/unidades/token/{$unitId}");

            if ($response->successful()) {
                return $response->json()['data']['token'] ?? null;
            }
        } catch (Throwable $e) {
            Log::error("Erro ao obter token da unidade EAD {$unitId}: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Lista os cursos liberados para a unidade no EAD.
     */
    public function getUnitCourses(string $apiKey, string $unitId): array
    {
        try {
            $response = Http::withBasicAuth(trim($apiKey), '')
                ->timeout(15)
                ->acceptJson()
                ->get("{$this->baseUrl}/unidades/cursos/{$unitId}");

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
        } catch (Throwable $e) {
            Log::error('Erro ao buscar cursos da unidade EAD: '.$e->getMessage());
        }

        return [];
    }

    /**
     * Busca os logs de acesso e atividades recentes dos alunos no EAD.
     * Retorna a lista de alunos com status online (atividade recente nos últimos minutos).
     *
     * @return list<array<string, mixed>>
     */
    public function getLiveStudentActivities(School $school, ?string $date = null): array
    {
        $apiKey = $school->ouro_moderno_ead_api_key;
        $unitId = (string) ($school->ouro_moderno_ead_unidade_id ?? '1');

        if (empty($apiKey) || empty($unitId)) {
            return [];
        }

        $targetDate = $date ?: today()->toDateString();

        try {
            $response = Http::withBasicAuth(trim($apiKey), '')
                ->timeout(15)
                ->acceptJson()
                ->get("{$this->baseUrl}/relatorio/alunos_log", [
                    'id_unidade' => $unitId,
                    'data_inicial' => $targetDate,
                    'data_final' => $targetDate,
                ]);

            if (! $response->successful()) {
                return [];
            }

            $rawLogs = $response->json();
            if (! is_array($rawLogs)) {
                return [];
            }

            $activities = [];
            $now = Carbon::now();

            foreach ($rawLogs as $log) {
                if (! is_array($log)) {
                    continue;
                }

                $studentName = $log['autor'] ?? $log['nome'] ?? 'Aluno EAD';
                $studentLogin = (string) ($log['aluno'] ?? $log['login'] ?? '');
                $lesson = (string) ($log['aula'] ?? '');
                $courseId = (string) ($log['curso'] ?? '');
                $action = (string) ($log['conteudo'] ?? 'Acesso ao portal EAD');
                $ip = (string) ($log['IP'] ?? $log['ip'] ?? '');
                $dateStr = (string) ($log['data'] ?? '');

                $carbonDate = null;
                if ($dateStr) {
                    try {
                        $carbonDate = Carbon::createFromFormat('d/m/Y H:i:s', $dateStr);
                    } catch (Throwable) {
                        try {
                            $carbonDate = Carbon::parse($dateStr);
                        } catch (Throwable) {
                            $carbonDate = null;
                        }
                    }
                }

                $minutesAgo = $carbonDate ? $carbonDate->diffInMinutes($now) : null;
                $isOnline = $minutesAgo !== null && $minutesAgo <= 25;

                $key = $studentLogin ?: $studentName;
                if (! isset($activities[$key]) || ($carbonDate && isset($activities[$key]['datetime']) && $carbonDate->gt($activities[$key]['datetime']))) {
                    $activities[$key] = [
                        'name' => $studentName,
                        'login' => $studentLogin,
                        'lesson' => $lesson,
                        'course_id' => $courseId,
                        'action' => $action,
                        'ip' => $ip,
                        'date' => $dateStr,
                        'datetime' => $carbonDate,
                        'time_ago' => $carbonDate ? $carbonDate->diffForHumans() : '',
                        'minutes_ago' => $minutesAgo,
                        'is_online' => $isOnline,
                    ];
                }
            }

            return collect($activities)
                ->sortByDesc(fn ($item) => $item['datetime']?->timestamp ?? 0)
                ->values()
                ->all();
        } catch (Throwable $e) {
            Log::error('Erro ao buscar logs ao vivo do EAD: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Lista todos os alunos cadastrados no EAD da Ouro Moderno.
     */
    public function listEadStudents(string $apiKey): array
    {
        try {
            $response = Http::withBasicAuth(trim($apiKey), '')
                ->timeout(25)
                ->acceptJson()
                ->get("{$this->baseUrl}/alunos");

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
        } catch (Throwable $e) {
            Log::error('Erro ao listar alunos do EAD: '.$e->getMessage());
        }

        return [];
    }

    /**
     * Cadastra ou atualiza o aluno na plataforma EAD da Ouro Moderno.
     */
    public function enrollStudent(Contract $contract, ?string $apiKey = null, ?string $unitToken = null): array
    {
        $school = $contract->school;
        $apiKey = $apiKey ?: $school?->ouro_moderno_ead_api_key;
        $unitId = (string) ($school?->ouro_moderno_ead_unidade_id ?? '1');

        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'Chave da API EAD da escola não configurada.'];
        }

        $student = $contract->student;
        if (! $student) {
            return ['success' => false, 'message' => 'Aluno não vinculado a este contrato.'];
        }

        $unitToken = $unitToken ?: $this->getUnitToken($apiKey, $unitId);
        if (empty($unitToken)) {
            return ['success' => false, 'message' => 'Não foi possível obter o token da unidade EAD.'];
        }

        $cpfDoc = $student->documents()->whereIn('type', ['cpf', 'CPF'])->first()?->value;
        $cleanCpf = $cpfDoc ? preg_replace('/\D/', '', $cpfDoc) : null;

        $rgDoc = $student->documents()->whereIn('type', ['rg', 'RG'])->first()?->value;
        $cleanRg = $rgDoc ?: '00000000';

        $phone = $student->contacts()->whereIn('type', ['whatsapp', 'cellphone', 'phone'])->first()?->value;
        $cleanPhone = $phone ? preg_replace('/\D/', '', $phone) : '0000000000';

        $birthDate = $student->birth_date ? Carbon::parse($student->birth_date)->format('Y-m-d') : '2000-01-01';

        // Endereço
        $addr = $student->addresses()->with('city.state')->first();
        $cidade = $addr?->city?->name ?? 'Fortaleza';
        $uf = $addr?->city?->state?->acronym ?? 'CE';
        $endereco = $addr?->street ? ($addr->street.', '.($addr->number ?? 'S/N')) : 'Rua Principal, 100';
        $bairro = $addr?->neighborhood ?? 'Centro';
        $cep = $addr?->zip_code ? preg_replace('/\D/', '', $addr->zip_code) : '60000000';

        $payload = [
            'token' => $unitToken,
            'nome' => $student->name,
            'data_nascimento' => $birthDate,
            'email' => $student->email ?: ('aluno_'.$student->id.'@escola.ead'),
            'fone' => $cleanPhone,
            'celular' => $cleanPhone,
            'doc_cpf' => $cleanCpf ?: '00000000000',
            'doc_rg' => $cleanRg,
            'pais' => 'Brasil',
            'uf' => $uf,
            'cidade' => $cidade,
            'endereco' => $endereco,
            'complemento' => '',
            'bairro' => $bairro,
            'cep' => $cep,
            'modalidade' => 1, // 1 = À Distância
        ];

        try {
            $response = Http::withBasicAuth(trim($apiKey), '')
                ->timeout(20)
                ->asForm()
                ->post("{$this->baseUrl}/alunos", $payload);

            if (! $response->successful()) {
                // Se falhou porque o aluno já existe na Ouro Moderno, tenta localizá-lo por CPF/email
                $body = $response->body();
                if (str_contains(mb_strtolower($body), 'já cadastrado') || str_contains(mb_strtolower($body), 'duplicado')) {
                    $existing = collect($this->listEadStudents($apiKey))->first(function ($st) use ($cleanCpf, $student) {
                        return ($cleanCpf && ($st['cpf'] ?? '') === $cleanCpf)
                            || (filled($student->email) && mb_strtolower($st['email'] ?? '') === mb_strtolower($student->email));
                    });

                    if ($existing && ! empty($existing['id'] ?? $existing['id_aluno'])) {
                        $eadId = (string) ($existing['id'] ?? $existing['id_aluno']);

                        return [
                            'success' => true,
                            'ead_student_id' => $eadId,
                            'message' => "Aluno já existente no EAD identificado com sucesso! ID: {$eadId}",
                        ];
                    }
                }

                return [
                    'success' => false,
                    'message' => 'Erro retornado pela API da Ouro Moderno ('.$response->status().'): '.$response->body(),
                ];
            }

            $resData = $response->json();
            $eadId = $resData['data']['id'] ?? $resData['data']['id_aluno'] ?? null;

            return [
                'success' => true,
                'ead_student_id' => (string) $eadId,
                'message' => 'Aluno provisionado com sucesso no EAD Ouro Moderno! ID: '.$eadId,
            ];
        } catch (Throwable $e) {
            Log::error('Erro ao matricular aluno no EAD: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Matricula os cursos especificados para o aluno no EAD.
     *
     * @param list<int|string> $courseIds
     * @return array{success: bool, data?: array<string, mixed>, message: string}
     */
    public function enrollStudentCoursesInEad(string $apiKey, string $unitToken, string $eadStudentId, array $courseIds): array
    {
        try {
            $response = Http::withBasicAuth(trim($apiKey), '')
                ->timeout(20)
                ->asForm()
                ->post("{$this->baseUrl}/alunos/matricula/{$eadStudentId}", [
                    'token' => $unitToken,
                    'cursos' => implode(',', array_filter($courseIds)),
                ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()['data'] ?? [],
                    'message' => 'Cursos matriculados com sucesso no EAD!',
                ];
            }

            return [
                'success' => false,
                'message' => 'Erro ao matricular cursos no EAD: '.$response->body(),
            ];
        } catch (Throwable $e) {
            Log::error("Erro ao matricular cursos no EAD para aluno {$eadStudentId}: ".$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Migra um contrato presencial do Aurosoft para a modalidade EAD (Ouro Moderno).
     */
    public function migrateContractToEad(Contract $contract): array
    {
        $school = $contract->school;
        $apiKey = $school?->ouro_moderno_ead_api_key;
        $unitId = (string) ($school?->ouro_moderno_ead_unidade_id ?? '1');

        if (empty($apiKey) || empty($unitId)) {
            throw new RuntimeException('A integração EAD da Ouro Moderno não está configurada para esta escola.');
        }

        $unitToken = $this->getUnitToken($apiKey, $unitId);
        if (! $unitToken) {
            throw new RuntimeException('Não foi possível obter o token da unidade no EAD. Verifique a chave e unidade configuradas.');
        }

        // 1. Cadastra ou localiza o aluno no EAD
        $enrollRes = $this->enrollStudent($contract, $apiKey, $unitToken);
        if (! $enrollRes['success']) {
            throw new RuntimeException('Falha ao cadastrar aluno no EAD: '.$enrollRes['message']);
        }

        $eadId = (string) $enrollRes['ead_student_id'];

        // 2. Coleta cursos do contrato (que tenham ouro_moderno_id)
        $contract->loadMissing('items.contractCourses.course');
        $courseIds = $contract->items
            ->flatMap(fn ($item) => $item->contractCourses)
            ->map(fn ($link) => $link->course?->ouro_moderno_id)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // 3. Matricula os cursos no EAD
        $enrolledCoursesCount = 0;
        if (! empty($courseIds)) {
            $matriculaRes = $this->enrollStudentCoursesInEad($apiKey, $unitToken, $eadId, $courseIds);
            if ($matriculaRes['success']) {
                $enrolledCoursesCount = count($courseIds);
            }
        }

        // 4. Marca o contrato exclusivamente como EAD
        $contract->update([
            'is_ead' => true,
            'ead_student_id' => $eadId,
            'ead_synced_at' => now(),
            'ead_last_progress_percentage' => 0.0,
        ]);

        // 5. Gera link de auto-login
        $autoLoginUrl = $this->getStudentAutoLoginUrl($contract, $apiKey);

        return [
            'success' => true,
            'ead_student_id' => $eadId,
            'courses_enrolled' => $enrolledCoursesCount,
            'auto_login_url' => $autoLoginUrl,
            'message' => "Contrato migrado com sucesso para o EAD! ID EAD: #{$eadId}. {$enrolledCoursesCount} curso(s) matriculado(s).",
        ];
    }

    /**
     * Migra um contrato do EAD de volta para a modalidade Presencial (LMS Local).
     * Sincroniza todas as notas e progresso do EAD antes da transferência.
     */
    public function migrateContractToLocalLms(Contract $contract, ?string $password = null): array
    {
        // 1. Sincroniza progresso e notas do EAD antes de retirar
        if (! empty($contract->ead_student_id)) {
            $this->syncStudentProgress($contract);
        }

        $lastProgress = (float) ($contract->ead_last_progress_percentage ?? 0.0);

        // 2. Remove o contrato do EAD
        $contract->update([
            'is_ead' => false,
        ]);

        // 3. Se a escola possui LMS local configurado, envia para o LMS local
        $localSynced = false;
        $localMsg = '';
        if (app(OuroModernoService::class)->isConfigured()) {
            try {
                $syncService = app(OuroModernoContractSyncService::class);
                $mode = $contract->student?->ouro_moderno_registered_at ? 'update' : 'create';
                $localRes = $syncService->sync($contract, $mode, $password);
                $localSynced = true;
                $localMsg = " Aluno provisionado no LMS local da escola (Código: {$localRes['student_code']}).";
            } catch (\Throwable $e) {
                $localMsg = " Nota: Contrato transferido para Presencial no Aurosoft. (Aviso LMS local: {$e->getMessage()}).";
            }
        }

        return [
            'success' => true,
            'last_progress' => $lastProgress,
            'local_synced' => $localSynced,
            'message' => "Contrato migrado do EAD para Presencial com sucesso! Progresso e notas ({$lastProgress}%) preservados no histórico.{$localMsg}",
        ];
    }

    /**
     * Obtém o token e o link direto de auto-login do aluno no portal do EAD.
     */
    public function getStudentAutoLoginUrl(Contract $contract, ?string $apiKey = null): ?string
    {
        $school = $contract->school;
        $apiKey = $apiKey ?: $school?->ouro_moderno_ead_api_key;
        $eadId = $contract->ead_student_id;

        if (empty($apiKey) || empty($eadId)) {
            return null;
        }

        try {
            $response = Http::withBasicAuth(trim($apiKey), '')
                ->timeout(10)
                ->acceptJson()
                ->get("{$this->baseUrl}/alunos/token/{$eadId}");

            if ($response->successful()) {
                $token = $response->json()['data']['token'] ?? $response->json()['data'] ?? null;
                if ($token && is_string($token)) {
                    return "https://meuappdecursos.com.br/index.php?pag=entrar&token={$token}";
                }
            }
        } catch (Throwable $e) {
            Log::error('Erro ao gerar token EAD: '.$e->getMessage());
        }

        return 'https://meuappdecursos.com.br/portal';
    }

    /**
     * Sincroniza o progresso, notas e tempo de estudo do aluno no EAD.
     */
    public function syncStudentProgress(Contract $contract, ?string $apiKey = null): array
    {
        $school = $contract->school;
        $apiKey = $apiKey ?: $school?->ouro_moderno_ead_api_key;
        $eadId = $contract->ead_student_id;

        if (empty($apiKey) || empty($eadId)) {
            return ['success' => false, 'message' => 'Aluno ou chave EAD não configurados.'];
        }

        try {
            $response = Http::withBasicAuth(trim($apiKey), '')
                ->timeout(15)
                ->acceptJson()
                ->get("{$this->baseUrl}/alunos/cursos/{$eadId}");

            if (! $response->successful()) {
                return ['success' => false, 'message' => 'Falha ao buscar cursos do aluno no EAD.'];
            }

            $courses = $response->json()['data'] ?? [];
            $totalProgress = 0.0;
            $courseCount = count($courses);

            foreach ($courses as $c) {
                $prog = 0.0;
                if (is_array($c['progresso'] ?? null)) {
                    $prog = (float) ($c['progresso']['percentual'] ?? 0);
                } else {
                    $prog = (float) ($c['progresso'] ?? $c['porcentagem'] ?? $c['concluido'] ?? 0);
                }
                $totalProgress += $prog;
            }

            $avgProgress = $courseCount > 0 ? round($totalProgress / $courseCount, 2) : 0.0;

            $contract->update([
                'ead_last_progress_percentage' => $avgProgress,
                'ead_synced_at' => now(),
            ]);

            return [
                'success' => true,
                'progress_percentage' => $avgProgress,
                'courses' => $courses,
                'message' => 'Progresso sincronizado com sucesso: '.$avgProgress.'% concluído.',
            ];
        } catch (Throwable $e) {
            Log::error('Erro ao sincronizar progresso EAD: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Busca os cursos e atividades de um aluno na plataforma EAD.
     *
     * @return list<array<string, mixed>>
     */
    public function getStudentCourses(string $apiKey, string $eadId): array
    {
        try {
            $response = Http::withBasicAuth(trim($apiKey), '')
                ->timeout(15)
                ->acceptJson()
                ->get("{$this->baseUrl}/alunos/cursos/{$eadId}");

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
        } catch (Throwable $e) {
            Log::error("Erro ao buscar cursos do aluno EAD {$eadId}: ".$e->getMessage());
        }

        return [];
    }

    /**
     * Cadastra manualmente um aluno vindo da plataforma EAD no Aurosoft.
     * Cria a Pessoa (com data de nascimento, documento e contato) e vincula à escola.
     * NÃO cria contratos automaticamente.
     */
    public function registerEadStudent(School $school, array $studentData): Person
    {
        $eadId = (string) ($studentData['id'] ?? $studentData['id_aluno'] ?? '');
        $name = trim((string) ($studentData['nome'] ?? $studentData['name'] ?? 'Aluno EAD'));
        $email = ! empty($studentData['email']) ? trim((string) $studentData['email']) : null;
        $cpf = ! empty($studentData['cpf']) ? preg_replace('/\D/', '', (string) $studentData['cpf']) : null;
        $phone = ! empty($studentData['telefone'] ?? $studentData['celular']) ? preg_replace('/\D/', '', (string) ($studentData['telefone'] ?? $studentData['celular'])) : null;
        $birthDate = null;

        if (! empty($studentData['data_nascimento']) || ! empty($studentData['birth_date'])) {
            try {
                $rawDate = $studentData['data_nascimento'] ?? $studentData['birth_date'];
                $birthDate = Carbon::parse($rawDate)->format('Y-m-d');
            } catch (Throwable) {
                $birthDate = null;
            }
        }

        // Localiza por CPF, e-mail ou nome
        $person = null;
        if ($cpf) {
            $person = Person::whereCpf($cpf)->first();
            if (! $person) {
                $person = Person::whereHas('documents', function ($q) use ($cpf) {
                    $q->whereIn('type', ['cpf', 'CPF'])->whereDocumentValue($cpf);
                })->first();
            }
        }

        if (! $person && $email) {
            $person = Person::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->first();
        }

        if (! $person) {
            if ($email && Person::whereRaw('LOWER(email) = ?', [mb_strtolower($email)])->exists()) {
                $email = null;
            }

            $person = Person::create([
                'name' => $name,
                'email' => $email,
                'birth_date' => $birthDate,
            ]);

            if ($cpf) {
                Document::create([
                    'person_id' => $person->id,
                    'type' => 'cpf',
                    'value' => $cpf,
                ]);
            }

            if ($phone) {
                Contact::create([
                    'person_id' => $person->id,
                    'type' => 'whatsapp',
                    'value' => $phone,
                    'is_primary' => true,
                ]);
            }
        } else {
            if (empty($person->birth_date) && $birthDate) {
                $person->update(['birth_date' => $birthDate]);
            }
        }

        $person->schools()->syncWithoutDetaching([$school->id]);

        return $person;
    }

    /**
     * Cria manualmente um contrato EAD para um aluno já cadastrado.
     */
    public function createEadContract(School $school, Person $person, string $eadId, array $contractData = []): Contract
    {
        return Contract::create([
            'school_id' => $school->id,
            'student_id' => $person->id,
            'contract_number' => $contractData['contract_number'] ?? ('CTR-EAD-'.str_pad($eadId, 5, '0', STR_PAD_LEFT)),
            'status' => $contractData['status'] ?? 'active',
            'is_ead' => true,
            'ead_student_id' => $eadId,
            'ead_synced_at' => now(),
            'start_date' => $contractData['start_date'] ?? today(),
            'total_value' => $contractData['total_value'] ?? 0.00,
        ]);
    }
}
