<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Collaborator;
use App\Models\Course;
use App\Models\Person;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class OuroModernoEducatorSyncService
{
    public function __construct(private readonly OuroModernoService $ouroModerno) {}

    /**
     * Sincroniza o colaborador/educador diretamente com o LMS Ouro Moderno
     * sem a necessidade de criar um contrato fantasma no sistema.
     *
     * @return array{educator_code: int, username: string, courses_enrolled: int}
     */
    public function sync(Collaborator $collaborator, ?string $password = null, ?int $schoolId = null): array
    {
        $targetSchoolId = $schoolId;
        if (! $targetSchoolId && class_exists(Filament::class)) {
            try {
                $targetSchoolId = Filament::getTenant()?->id;
            } catch (\Throwable) {
                $targetSchoolId = null;
            }
        }
        if (! $targetSchoolId) {
            $targetSchoolId = $collaborator->person?->schools()->first()?->id
                ?? $collaborator->user?->schools()->first()?->id;
        }

        $api = $targetSchoolId !== null
            ? $this->ouroModerno->forSchool($targetSchoolId)
            : $this->ouroModerno;

        if (! $api->isConfigured()) {
            throw new RuntimeException('A integração com o LMS Ouro Moderno não está configurada nesta unidade escolar.');
        }

        $person = $collaborator->person;
        if (! $person instanceof Person || blank($person->name)) {
            throw new RuntimeException('O colaborador não possui um cadastro de pessoa física com nome válido.');
        }

        // Define o código de integração único (faixa 900.000 para educadores da escola se não houver)
        $integrationCode = (int) ($person->ouro_moderno_cod_int ?: (900000 + $person->id));

        // Define usuário se não existir
        $username = trim((string) $person->ouro_moderno_usuario);
        if ($username === '') {
            $firstName = Str::slug(explode(' ', trim($person->name))[0]);
            $username = 'prof.'.$firstName.$person->id;
        }

        // Senha
        $effectivePassword = filled($password)
            ? (string) $password
            : (string) ($person->ouro_moderno_senha ?: 'prof123');

        // Se o colaborador possuir cursos específicos vinculados, matricula nestes;
        // Se estiver em branco: libera todos os cursos apenas se for Educador. Não-educadores não recebem cursos se estiver em branco.
        $hasSpecificCourses = $collaborator->courses()->exists();
        if ($hasSpecificCourses) {
            $courseIds = $collaborator->courses()
                ->whereNotNull('ouro_moderno_id')
                ->pluck('ouro_moderno_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if (empty($courseIds)) {
                throw new RuntimeException('Os cursos vinculados a este colaborador não possuem código da Ouro Moderno.');
            }
        } elseif ($collaborator->is_educator) {
            $courseIds = Course::query()
                ->whereNotNull('ouro_moderno_id')
                ->where('active', true)
                ->pluck('ouro_moderno_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            if (empty($courseIds)) {
                throw new RuntimeException('Nenhum curso ativo com código Ouro Moderno foi encontrado no catálogo da escola.');
            }
        } else {
            throw new RuntimeException('Este colaborador não está marcado como Educador e não possui cursos específicos vinculados. Para liberar cursos a colaboradores não-educadores, selecione os cursos autorizados na ficha do colaborador.');
        }

        // Monta carga de dados para a API Ouro Moderno
        $studentData = [
            'cod_int' => $integrationCode,
            'nome' => $person->name,
            'usuario' => $username,
            'senha' => $effectivePassword,
            'email' => $person->email ?: $collaborator->user?->email,
        ];

        if (filled($person->cpf)) {
            $studentData['cpf'] = preg_replace('/\D/', '', (string) $person->cpf);
        }

        $isAlreadyRegistered = $person->ouro_moderno_registered_at !== null;

        if (! $isAlreadyRegistered) {
            $api->createStudent($studentData);
        } else {
            try {
                $response = $api->updateStudent($integrationCode, $studentData);
                if (isset($response['error']) || isset($response['erro']) || in_array($response['code'] ?? null, [0, '0'], true)) {
                    $api->createStudent($studentData);
                }
            } catch (\Throwable) {
                $api->createStudent($studentData);
            }
        }

        // Matricula o educador em 100% dos cursos Ouro Moderno
        $api->enrollCourses($integrationCode, $courseIds);

        // Salva os dados de acesso diretamente no registro Person do educador
        DB::transaction(function () use ($person, $integrationCode, $username, $effectivePassword): void {
            $person->forceFill([
                'ouro_moderno_cod_int' => $integrationCode,
                'ouro_moderno_usuario' => $username,
                'ouro_moderno_senha' => $effectivePassword,
                'ouro_moderno_registered_at' => now(),
            ])->save();
        });

        return [
            'educator_code' => $integrationCode,
            'username' => $username,
            'courses_enrolled' => count($courseIds),
        ];
    }
}
