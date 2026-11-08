<?php

namespace App\Services;

use App\Models\IntegrationSetting;
use App\Models\School;
use Filament\Facades\Filament;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class OuroModernoService
{
    private string $url;

    private string $token;

    private ?int $schoolId = null;

    private bool $hasExplicitSchoolScope = false;

    public function __construct()
    {
        $this->resetToDefaultConfig();
    }

    public function forSchool(int|School|null $school): self
    {
        $clone = clone $this;
        $clone->schoolId = $school instanceof School ? $school->id : $school;
        $clone->hasExplicitSchoolScope = $clone->schoolId !== null;
        $clone->resetToDefaultConfig();

        return $clone;
    }

    private function resetToDefaultConfig(): void
    {
        $this->url = rtrim((string) config('services.ouro_moderno.url'), '/');
        $this->token = (string) config('services.ouro_moderno.token');
    }

    public function isConfigured(): bool
    {
        $this->resetToDefaultConfig();

        try {
            $hasSettingsTable = Schema::hasTable('integration_settings');
        } catch (Throwable) {
            // Keep environment-based integration checks usable before migrations or during DB outages.
            $hasSettingsTable = false;
        }

        if ($hasSettingsTable) {
            $schoolId = $this->schoolId;
            if (! $this->hasExplicitSchoolScope && ! $schoolId && class_exists(Filament::class)) {
                try {
                    $schoolId = Filament::getTenant()?->id;
                } catch (Throwable) {
                    $schoolId = null;
                }
            }

            $query = IntegrationSetting::query()->where('provider', 'ouro_moderno');

            if ($schoolId) {
                // Isolamento multi-tenant estrito: usa a credencial da própria escola (ou global sem school_id),
                // jamais herdando o servidor local/Tailscale de outra unidade.
                $settings = (clone $query)->where('school_id', $schoolId)->first()
                    ?? (clone $query)->whereNull('school_id')->first();
            } else {
                $settings = (clone $query)->whereNull('school_id')->first()
                    ?? $query->first();
            }

            if ($settings !== null) {
                if (filled($settings->base_url)) {
                    $this->url = rtrim((string) $settings->base_url, '/');
                }
                try {
                    $tokenValue = $settings->token;
                    if (filled($tokenValue)) {
                        $this->token = (string) $tokenValue;
                    }
                } catch (Throwable) {
                    // Evita falhas 500 caso o token tenha sido criptografado com outra APP_KEY
                }
            }
        }

        return filled($this->url) && filled($this->token);
    }

    public function getBaseUrl(): string
    {
        $this->isConfigured();

        return $this->url;
    }

    public function getLoginUrl(): string
    {
        return $this->getBaseUrl().'/logar.php?acao=logar';
    }

    /** @return array<mixed> */
    public function validateToken(): array
    {
        return $this->post('/api/token');
    }

    /** @return array<mixed> */
    public function courses(): array
    {
        return $this->post('/api/cursos/lista');
    }

    /** @return array<mixed> */
    public function modules(): array
    {
        return $this->post('/api/modulos/lista');
    }

    /** @return array<mixed> */
    public function students(): array
    {
        return $this->post('/api/alunos/lista');
    }

    /** @return array<mixed> */
    public function verifyUsername(string $username): array
    {
        return $this->post('/api/alunos/verificarusuario', ['usuario' => $username]);
    }

    /** @return array<mixed> */
    public function verifyIntegrationCode(int $integrationCode): array
    {
        return $this->post('/api/alunos/verificarcodigo', ['cod_int' => $integrationCode]);
    }

    /** @param array<string, scalar|null> $student */
    public function createStudent(array $student): array
    {
        return $this->post('/api/alunos/cadastrar/', $student);
    }

    /** @param array<string, scalar|null> $changes */
    public function updateStudent(int $integrationCode, array $changes): array
    {
        return $this->post('/api/alunos/atualizar/', ['cod_int' => $integrationCode, ...$changes]);
    }

    /**
     * Atualiza os dados de um aluno na Ouro Moderno usando seu id nativo do LMS.
     * Usado principalmente para vincular o cod_int em alunos criados nativamente na OM.
     *
     * @param  int  $remoteStudentId  ID nativo do aluno na Ouro Moderno (id_aluno)
     * @param  array<string, scalar|null>  $data
     * @return array<mixed>
     */
    public function updateStudentById(int $remoteStudentId, array $data): array
    {
        $this->ensureConfigured();

        return $this->send(fn (): Response => Http::asForm()
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->put($this->url.'/api/aluno?'.http_build_query([
                'token' => $this->token,
                'id_aluno' => $remoteStudentId,
            ]), $data));
    }

    /**
     * Sincroniza o código de integração (cod_int) no banco de dados da Ouro Moderno.
     * Suporta tanto o endpoint REST PUT /api/aluno quanto POST /api/alunos/atualizar/ da API local.
     */
    public function syncStudentIntegrationCode(int|string $remoteStudentId, int|string $newCodInt, array $extra = []): bool
    {
        $this->ensureConfigured();

        $success = false;
        $remoteId = (int) $remoteStudentId;
        $codInt = (int) $newCodInt;

        // Tenta obter dados atuais do aluno na OM para manter integridade dos campos obrigatórios
        $current = [];
        try {
            $resp = $this->get('/api/aluno', ['id_aluno' => $remoteId]);
            $current = $resp['aluno'] ?? $resp['Aluno'] ?? $resp['data'] ?? (is_array($resp) ? $resp : []);
            if (! is_array($current)) {
                $current = [];
            }
        } catch (\Throwable) {
        }

        $studentName = $extra['nome'] ?? $current['nome'] ?? $current['name'] ?? 'Aluno';
        $username = $extra['usuario'] ?? $current['usuario'] ?? $current['username'] ?? (string) $codInt;
        $active = $extra['ativo'] ?? $current['ativo'] ?? 'S';
        $stateId = (int) ($extra['estado'] ?? $current['estado'] ?? 1);
        $cityId = (int) ($extra['cidade'] ?? $current['cidade'] ?? 1);

        $payload = [
            'cod_int' => $codInt,
            'nome' => $studentName,
            'usuario' => $username,
            'ativo' => $active,
            'estado' => $stateId ?: 1,
            'cidade' => $cityId ?: 1,
        ];

        if (! empty($extra['email']) || ! empty($current['email'])) {
            $payload['email'] = $extra['email'] ?? $current['email'];
        }
        if (! empty($extra['cpf']) || ! empty($current['cpf'])) {
            $payload['cpf'] = $extra['cpf'] ?? $current['cpf'];
        }

        // 1. Tenta PUT /api/aluno?id_aluno=... com o payload completo
        try {
            $res = $this->updateStudentById($remoteId, $payload);
            if (empty($res['error']) && empty($res['erro'])) {
                $success = true;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::info("syncStudentIntegrationCode PUT /api/aluno: {$e->getMessage()}");
        }

        // 2. Tenta POST /api/alunos/atualizar/ passando cod_int e campos
        try {
            $res = $this->post('/api/alunos/atualizar/', [
                'cod_int' => $codInt,
                'nome' => $studentName,
                'ativo' => $active,
            ]);
            if (empty($res['error']) && empty($res['erro'])) {
                $success = true;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::info("syncStudentIntegrationCode POST /api/alunos/atualizar/: {$e->getMessage()}");
        }

        return $success;
    }

    /** @param array<int, int|string> $courseIds */
    public function enrollCourses(int $integrationCode, array $courseIds): array
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));

        if ($courseIds === []) {
            throw new RuntimeException('Selecione ao menos um curso para matricular no LMS.');
        }

        return $this->post('/api/alunos/matricular/', [
            'cod_int' => $integrationCode,
            'cursos' => implode(',', $courseIds),
        ]);
    }

    /** @return array<mixed> */
    public function studentCourseStatus(int $integrationCode, ?int $courseId = null): array
    {
        return $this->post('/api/aluno/curso/situacao', array_filter([
            'cod_int' => $integrationCode,
            'curso' => $courseId,
        ], fn (mixed $value): bool => $value !== null));
    }

    /**
     * Edita o curso vinculado ao aluno no LMS Ouro Moderno (altera situação ou disponibilidade 0/1)
     *
     * @param  array{situacao?: string, disponivel?: int}  $params
     * @return array<mixed>
     */
    public function updateStudentCourse(int|string $integrationCode, int $courseId, array $params = []): array
    {
        return $this->post('/api/aluno/curso/editar', [
            'cod_int' => (string) $integrationCode,
            'curso' => $courseId,
            ...$params,
        ]);
    }

    /** @return array<mixed> */
    public function studentLessonStatus(int $integrationCode, int $courseId): array
    {
        return $this->post('/api/aluno/aulas/situacao', [
            'cod_int' => $integrationCode,
            'curso' => $courseId,
        ]);
    }

    /** @return array<mixed> */
    public function studentTestStatus(int $integrationCode, int $courseId): array
    {
        return $this->post('/api/aluno/testes/situacao', [
            'cod_int' => $integrationCode,
            'curso' => $courseId,
        ]);
    }

    /** @return array<mixed> */
    public function testsForLesson(int $courseId, int $lessonId): array
    {
        return $this->post('/ouromoderno/api/testes/lista', [
            'curso' => $courseId,
            'aula' => $lessonId,
        ]);
    }

    /** @param array{status?: string, cod_int?: int|string, data_inicio?: string, data_fim?: string, lido?: string} $filters
     * @return array<mixed>
     */
    public function actions(array $filters = []): array
    {
        return $this->post('/api/acoes/lista', $filters);
    }

    /** @return array<mixed> */
    public function actionTypes(): array
    {
        return $this->post('/api/acoes/tipos');
    }

    /** @return array<mixed> */
    public function gamificationPlayers(int $page = 1, int $limit = 50, string $search = '', string $status = 'todos'): array
    {
        return $this->get('/api/game/jogadores', [
            'page' => max(1, $page),
            'limit' => min(100, max(1, $limit)),
            'search' => $search,
            'status' => $status,
        ]);
    }

    /** @return array<mixed> */
    public function gamificationRanking(int $limit = 50): array
    {
        return $this->get('/api/game/ranking', ['limit' => min(100, max(1, $limit))]);
    }

    /** @return array<mixed> */
    public function gamificationStudentProfile(int $studentId): array
    {
        return $this->get('/api/game/aluno', ['id_aluno' => $studentId]);
    }

    /** @return array<mixed> */
    public function gamificationAddPoints(
        int $studentId,
        int $points,
        string $description,
        ?string $actionType = 'manual',
        ?string $referenceId = null,
    ): array {
        return $this->post('/api/game/pontos/adicionar', [
            'id_aluno' => $studentId,
            'pontos' => $points,
            'descricao' => $description,
            'action_type' => $actionType ?? 'manual',
            'reference_id' => $referenceId ?? ('manual-'.uniqid()),
        ]);
    }

    /** @return array<mixed> */
    public function gamificationRemovePoints(
        int $studentId,
        int $points,
        string $description,
        ?string $actionType = 'manual',
        ?string $referenceId = null,
    ): array {
        return $this->post('/api/game/pontos/remover', [
            'id_aluno' => $studentId,
            'pontos' => $points,
            'descricao' => $description,
            'action_type' => $actionType ?? 'manual',
            'reference_id' => $referenceId ?? ('manual-'.uniqid()),
        ]);
    }

    /** @return array<mixed> */
    public function gamificationAddCoins(
        int $studentId,
        int $coins,
        string $description,
        ?string $referenceId = null,
    ): array {
        return $this->post('/api/game/moedas/adicionar', [
            'id_aluno' => $studentId,
            'moedas' => $coins,
            'descricao' => $description,
            'reference_id' => $referenceId ?? '',
        ]);
    }

    /** @return array<mixed> */
    public function gamificationRemoveCoins(
        int $studentId,
        int $coins,
        string $description,
        ?string $referenceId = null,
    ): array {
        return $this->post('/api/game/moedas/remover', [
            'id_aluno' => $studentId,
            'moedas' => $coins,
            'descricao' => $description,
            'reference_id' => $referenceId ?? '',
        ]);
    }

    /** @return array<mixed> */
    public function gamificationPointsHistory(int $studentId, int $limit = 50, int $offset = 0): array
    {
        return $this->get('/api/game/aluno/historico', [
            'id_aluno' => $studentId,
            'limit' => min(100, max(1, $limit)),
            'offset' => max(0, $offset),
        ]);
    }

    /** @return array<mixed> */
    public function gamificationCoinsHistory(int $studentId, int $limit = 50, int $offset = 0): array
    {
        return $this->get('/api/game/aluno/historico-moedas', [
            'id_aluno' => $studentId,
            'limit' => min(100, max(1, $limit)),
            'offset' => max(0, $offset),
        ]);
    }

    /** @return array<mixed> */
    public function gamificationActions(): array
    {
        return $this->get('/api/game/acoes');
    }

    /** @param array{inicio?: string, fim?: string, filtro?: string, valor?: int|string, limite?: int} $filters
     * @return array<mixed>
     */
    public function studentLogs(int|string $integrationCode, array $filters = []): array
    {
        return $this->get('/api/aluno/logs', ['cod_int' => $integrationCode, ...$filters]);
    }

    public function findStudentIdByIntegrationCode(int $integrationCode): ?int
    {
        $payload = $this->students();
        $records = $payload['alunos'] ?? $payload['Alunos'] ?? $payload['students'] ?? $payload['data'] ?? $payload;

        if (! is_array($records)) {
            return null;
        }

        $pending = [$records];

        while ($pending !== []) {
            $record = array_pop($pending);

            if (! is_array($record)) {
                continue;
            }

            $remoteCode = $record['cod_int'] ?? $record['codigo_integracao'] ?? null;
            $remoteId = $record['id_aluno'] ?? $record['user_id'] ?? $record['id'] ?? null;

            if (is_numeric($remoteCode) && (int) $remoteCode === $integrationCode && is_numeric($remoteId)) {
                return (int) $remoteId;
            }

            foreach ($record as $value) {
                if (is_array($value)) {
                    $pending[] = $value;
                }
            }
        }

        return null;
    }

    /** @param array<string, scalar|null> $parameters
     * @return array<mixed>
     */
    private function post(string $path, array $parameters = []): array
    {
        $this->ensureConfigured();

        return $this->send(fn (): Response => Http::asForm()
            ->acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->post($this->url.$path, [...$parameters, 'token' => $this->token]));
    }

    /** @param array<string, scalar|null> $parameters
     * @return array<mixed>
     */
    private function get(string $path, array $parameters = []): array
    {
        $this->ensureConfigured();

        return $this->send(fn (): Response => Http::acceptJson()
            ->connectTimeout(10)
            ->timeout(30)
            ->get($this->url.$path, [...$parameters, 'token' => $this->token]));
    }

    /** @param callable(): Response $request
     * @return array<mixed>
     */
    private function send(callable $request): array
    {
        try {
            return $this->decode($request());
        } catch (ConnectionException) {
            throw new RuntimeException('Não foi possível conectar ao servidor da Ouro Moderno. Verifique a URL e a rede Tailscale.');
        }
    }

    /** @return array<mixed> */
    private function decode(Response $response): array
    {
        if ($response->failed()) {
            throw new RuntimeException("A API da Ouro Moderno respondeu com HTTP {$response->status()}.");
        }

        if (trim($response->body()) === '') {
            return ['_http_status' => $response->status()];
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('A API da Ouro Moderno retornou uma resposta JSON inválida.');
        }

        return $data;
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Configure OURO_MODERNO_URL e OURO_MODERNO_TOKEN antes de usar a integração.');
        }
    }
}
