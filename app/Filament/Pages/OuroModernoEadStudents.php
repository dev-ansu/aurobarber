<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Contract;
use App\Models\Person;
use App\Models\School;
use App\Models\User;
use App\Services\FinancialCalculationService;
use App\Services\OuroModernoEadService;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\WithPagination;
use Throwable;
use UnitEnum;

class OuroModernoEadStudents extends Page
{
    use WithPagination;

    protected static string|UnitEnum|null $navigationGroup = 'Pedagógico';

    protected static ?string $navigationLabel = 'Alunos EAD (Ouro Moderno)';

    protected static ?string $title = 'Alunos EAD (Ouro Moderno Online)';

    protected static ?string $slug = 'alunos-ouro-moderno-ead';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 6;

    protected string $view = 'filament.pages.ouro-moderno-ead-students';

    public string $activeTab = 'ead_platform';

    public string $search = '';

    public string $progressFilter = 'all';

    public ?string $lastUpdated = null;

    public array $studentReport = [];

    public function getMaxContentWidth(): ?string
    {
        return 'full';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->hasRole('super_admin')
            || $user->can('ViewAny:OuroModernoEad');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProgressFilter(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    private function currentSchool(): ?School
    {
        $tenant = Filament::getTenant();

        if ($tenant instanceof School) {
            return $tenant;
        }

        $schoolId = session('filament_tenant_id') ?? auth()->user()?->school_id;

        return $schoolId ? School::find($schoolId) : School::latest('id')->first();
    }

    private function cacheKey(): string
    {
        $schoolId = $this->currentSchool()?->id ?? 0;

        return "ouro_moderno_ead_students_list_{$schoolId}";
    }

    public function totalCachedCount(): int
    {
        return count(Cache::get($this->cacheKey(), []));
    }

    /**
     * Consulta os alunos diretamente na API da Ouro Moderno EAD e armazena em cache (sem cadastrar no banco).
     */
    public function loadStudents(): void
    {
        $school = $this->currentSchool();
        $apiKey = $school?->ouro_moderno_ead_api_key;

        if (empty($apiKey)) {
            Notification::make()
                ->title('Chave EAD Não Configurada')
                ->body('Configure a chave da API do EAD da Ouro Moderno nas configurações da escola.')
                ->warning()
                ->send();

            return;
        }

        try {
            $service = app(OuroModernoEadService::class);
            $rawStudents = $service->listEadStudents($apiKey);

            if (empty($rawStudents)) {
                Notification::make()
                    ->title('Nenhum aluno retornado')
                    ->body('A API do EAD respondeu com sucesso, mas não retornou alunos cadastrados.')
                    ->info()
                    ->send();

                Cache::forget($this->cacheKey());
                $this->lastUpdated = now()->format('d/m/Y H:i');

                return;
            }

            // Mapeia alunos já existentes no Aurosoft (por documento, e-mail ou código EAD)
            $existingContracts = Contract::withoutGlobalScopes()
                ->where('school_id', $school->id)
                ->where('is_ead', true)
                ->pluck('id', 'ead_student_id')
                ->all();

            $existingPeople = Person::query()
                ->get(['id', 'name', 'email']);

            $peopleByEmail = [];
            foreach ($existingPeople as $p) {
                if (filled($p->email)) {
                    $peopleByEmail[mb_strtolower($p->email)] = $p->id;
                }
            }

            $students = collect($rawStudents)->map(function (array $row) use ($existingContracts, $peopleByEmail): array {
                $eadId = (string) ($row['id'] ?? $row['id_aluno'] ?? '');
                $email = ! empty($row['email']) ? trim((string) $row['email']) : '';
                $name = trim((string) ($row['nome'] ?? $row['name'] ?? 'Aluno EAD'));
                $cpf = ! empty($row['cpf']) ? preg_replace('/\D/', '', (string) $row['cpf']) : '';
                $phone = ! empty($row['telefone'] ?? $row['celular']) ? preg_replace('/\D/', '', (string) ($row['telefone'] ?? $row['celular'])) : '';
                $birthDate = ! empty($row['data_nascimento']) ? $row['data_nascimento'] : null;

                $personId = $email !== '' ? ($peopleByEmail[mb_strtolower($email)] ?? null) : null;
                $contractId = $eadId !== '' ? ($existingContracts[$eadId] ?? null) : null;

                return [
                    'id_aluno' => $eadId,
                    'name' => $name,
                    'email' => $email,
                    'cpf' => $cpf,
                    'phone' => $phone,
                    'birth_date' => $birthDate,
                    'person_id' => $personId,
                    'contract_id' => $contractId,
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

            Cache::put($this->cacheKey(), $students, now()->addMinutes(25));
            $this->studentReport = [];
            $this->lastUpdated = now()->format('d/m/Y H:i');

            Notification::make()
                ->title('Alunos EAD Consultados!')
                ->body(count($students).' alunos consultados com sucesso. Nenhum contrato foi criado automaticamente.')
                ->success()
                ->send();

            $this->resetPage();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Erro ao Consultar EAD')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Retorna os alunos da plataforma EAD paginados para exibição da consulta.
     */
    public function getEadPlatformStudentsProperty(): LengthAwarePaginator
    {
        $cached = Cache::get($this->cacheKey(), []);
        $items = collect($cached);

        if ($this->search !== '') {
            $term = mb_strtolower(trim($this->search));
            $items = $items->filter(function (array $st) use ($term): bool {
                return str_contains(mb_strtolower($st['name'] ?? ''), $term)
                    || str_contains(mb_strtolower($st['email'] ?? ''), $term)
                    || str_contains((string) ($st['id_aluno'] ?? ''), $term)
                    || str_contains((string) ($st['cpf'] ?? ''), $term);
            });
        }

        $page = $this->getPage();
        $perPage = 15;
        $total = $items->count();
        $sliced = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator($sliced, $total, $perPage, $page, [
            'path' => request()->url(),
            'pageName' => 'page',
        ]);
    }

    /**
     * Cadastra a Pessoa no Aurosoft e opcionalmente redireciona para a criação do Contrato.
     */
    public function importAndCreateContract(string $eadId, bool $redirectToContract): void
    {
        $school = $this->currentSchool();
        if (! $school) {
            return;
        }

        $cachedStudents = collect(Cache::get($this->cacheKey(), []));
        $studentData = $cachedStudents->firstWhere('id_aluno', $eadId);

        if (! $studentData) {
            Notification::make()
                ->title('Aluno não localizado')
                ->body('Não foi possível localizar os dados deste aluno no cache da consulta.')
                ->warning()
                ->send();

            return;
        }

        try {
            $service = app(OuroModernoEadService::class);
            $person = $service->registerEadStudent($school, $studentData);

            // Atualiza status no cache
            $updated = $cachedStudents->map(function (array $s) use ($eadId, $person) {
                if ((string) ($s['id_aluno'] ?? '') === $eadId) {
                    $s['person_id'] = $person->id;
                }

                return $s;
            })->all();

            Cache::put($this->cacheKey(), $updated, now()->addMinutes(25));

            if ($redirectToContract) {
                Notification::make()
                    ->title('Pessoa Cadastrada no Aurosoft!')
                    ->body("{$person->name} foi cadastrado(a). Redirecionando para a criação do Contrato...")
                    ->success()
                    ->send();

                $this->redirect(route('filament.admin.resources.contracts.create', [
                    'tenant' => $school->id,
                    'student_id' => $person->id,
                    'is_ead' => true,
                    'ead_student_id' => $eadId,
                ]));
            } else {
                Notification::make()
                    ->title('Pessoa Cadastrada com Sucesso!')
                    ->body("Cadastro de {$person->name} concluído. Redirecionando para completar a ficha...")
                    ->success()
                    ->send();

                $this->redirect(route('filament.admin.resources.people.edit', [
                    'tenant' => $school->id,
                    'record' => $person->id,
                ]));
            }
        } catch (Throwable $e) {
            Notification::make()
                ->title('Erro ao Cadastrar Aluno')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Consulta o boletim / cursos do aluno no EAD em tempo real.
     */
    public function loadStudentReport(string $eadId): void
    {
        $school = $this->currentSchool();
        $apiKey = $school?->ouro_moderno_ead_api_key;

        if (empty($apiKey)) {
            Notification::make()
                ->title('Chave EAD Não Configurada')
                ->warning()
                ->send();

            return;
        }

        try {
            $service = app(OuroModernoEadService::class);
            $courses = $service->getStudentCourses($apiKey, $eadId);

            $cachedStudents = collect(Cache::get($this->cacheKey(), []));
            $studentData = $cachedStudents->firstWhere('id_aluno', $eadId) ?? ['id_aluno' => $eadId, 'name' => 'Aluno EAD'];

            $this->studentReport = [
                'student' => $studentData,
                'courses' => $courses,
                'loaded_at' => now()->format('d/m/Y H:i:s'),
            ];

            Notification::make()
                ->title('Boletim EAD Carregado!')
                ->body(count($courses).' curso(s) retornado(s) pela API da Ouro Moderno.')
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Erro ao Carregar Boletim')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Retorna a lista de contratos EAD ativos vinculados no Aurosoft.
     */
    public function getContractsProperty(): LengthAwarePaginator
    {
        $school = $this->currentSchool();

        if (! $school) {
            return new LengthAwarePaginator([], 0, 15);
        }

        $query = Contract::query()
            ->where('school_id', $school->id)
            ->where('is_ead', true)
            ->with(['student.contacts', 'student.documents'])
            ->when($this->search, function (Builder $q) {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $sub) use ($term) {
                    $sub->where('contract_number', 'like', $term)
                        ->orWhere('ead_student_id', 'like', $term)
                        ->orWhereHas('student', fn ($sq) => $sq->where('name', 'like', $term)->orWhere('email', 'like', $term));
                });
            })
            ->when($this->progressFilter !== 'all', function (Builder $q) {
                match ($this->progressFilter) {
                    'not_started' => $q->where('ead_last_progress_percentage', '<=', 0),
                    'in_progress' => $q->whereBetween('ead_last_progress_percentage', [0.01, 99.99]),
                    'completed' => $q->where('ead_last_progress_percentage', '>=', 100),
                    default => null,
                };
            })
            ->latest('id');

        return $query->paginate(15);
    }

    public function syncSingleStudent(int $contractId): void
    {
        $contract = Contract::findOrFail($contractId);
        $school = $contract->school;
        $apiKey = $school?->ouro_moderno_ead_api_key;

        if (empty($apiKey) || empty($contract->ead_student_id)) {
            Notification::make()
                ->title('Atenção')
                ->body('O aluno não possui ID do EAD vinculado ou a chave da escola não foi definida.')
                ->warning()
                ->send();

            return;
        }

        $service = app(OuroModernoEadService::class);
        $res = $service->syncStudentProgress($contract, $apiKey);

        if ($res['success']) {
            Notification::make()
                ->title('Progresso EAD Atualizado')
                ->body($res['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Erro ao Sincronizar')
                ->body($res['message'])
                ->danger()
                ->send();
        }
    }

    public function openStudentAutoLogin(int $contractId): void
    {
        $contract = Contract::findOrFail($contractId);
        $service = app(OuroModernoEadService::class);
        $loginUrl = $service->getStudentAutoLoginUrl($contract);

        if ($loginUrl) {
            $this->js("window.open('{$loginUrl}', '_blank');");
        } else {
            Notification::make()
                ->title('Não foi possível gerar acesso')
                ->body('Verifique se o aluno está matriculado e se o ID EAD é válido.')
                ->warning()
                ->send();
        }
    }

    public function sendWhatsAppEncouragement(int $contractId): void
    {
        $contract = Contract::with(['student.contacts'])->findOrFail($contractId);
        $calcService = app(FinancialCalculationService::class);

        $phone = $calcService->resolveContractContactPhone($contract);

        if (! $phone) {
            Notification::make()
                ->title('Telefone Não Encontrado')
                ->body('Cadastre um número de telefone ou WhatsApp para o aluno.')
                ->warning()
                ->send();

            return;
        }

        $studentName = $contract->student?->name ?? 'Aluno';
        $progress = number_format((float) ($contract->ead_last_progress_percentage ?? 0), 0);

        $schoolName = $contract->school?->trade_name
            ?: ($contract->school?->legal_name
                ?: ($this->currentSchool()?->trade_name ?: 'nossa escola'));

        $msg = "Olá, {$studentName}! 👋 Tudo bem com você?\n\n";
        $msg .= "Aqui é da secretaria da {$schoolName}.\n";
        $msg .= "Estamos acompanhando seus estudos no portal EAD. Você já concluiu {$progress}% do seu conteúdo!\n\n";
        $msg .= "Passando para te desejar bons estudos e lembrar que qualquer dúvida com a matéria você pode falar com a nossa equipe de educadores. Conte conosco!";

        $cleanPhone = $calcService->formatInternationalPhone($phone);
        $encodedMsg = rawurlencode($msg);
        $waUrl = "https://wa.me/{$cleanPhone}?text={$encodedMsg}";

        $this->js("window.open('{$waUrl}', '_blank');");
    }
}
