<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\GenerateStudentAttendancesForDate;
use App\Enums\RelationshipEnum;
use App\Models\Classroom;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\ContractItem;
use App\Models\Course;
use App\Models\Formation;
use App\Models\IntegrationSetting;
use App\Models\LmsLesson;
use App\Models\LmsModule;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedMultiTenantKioskTestContractsCommand extends Command
{
    protected $signature = 'kiosk:seed-test-contracts {--reset-today : Reinicia a presença de hoje dos contratos de teste para ausente}';

    protected $description = 'Cria contratos de teste ativos com horários agendados (00h às 23h, todos os dias) para cada escola cadastrada, permitindo testar o Totem de Presença e o auto-login multi-tenant.';

    public function handle(): int
    {
        $schools = School::withoutGlobalScopes()->orderBy('id')->get();

        if ($schools->isEmpty()) {
            $matriz = School::create([
                'legal_name' => 'Escola Matriz Ltda',
                'trade_name' => 'Unidade Matriz',
            ]);
            $schools = collect([$matriz]);
        }

        if ($schools->count() < 2) {
            $filial = School::create([
                'legal_name' => 'Escola Filial Y Ltda',
                'trade_name' => 'Unidade Filial Y',
            ]);
            $schools->push($filial);
        }

        // Garante que todos os super_admins tenham vínculo com todas as escolas
        $superAdmins = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->get();
        $allSchoolIds = $schools->pluck('id')->all();
        foreach ($superAdmins as $admin) {
            $admin->schools()->syncWithoutDetaching($allSchoolIds);
        }

        // 1. Curso e Formação Ouro Moderno para teste
        $omCourse = Course::syncingFromOuroModerno(fn (): Course => Course::updateOrCreate(
            ['ouro_moderno_id' => 9901],
            [
                'name' => 'Informática Profissional (Teste Ouro Moderno)',
                'workload' => 40,
                'active' => true,
            ]
        ));

        $omFormation = Formation::firstOrCreate(
            ['name' => 'Formação Teste Ouro Moderno'],
            [
                'description' => 'Formação de teste para validação do Totem e Auto-login Ouro Moderno por escola.',
                'active' => true,
            ]
        );
        $omFormation->courses()->syncWithoutDetaching([$omCourse->id => ['order' => 1]]);

        // 2. Curso e Formação nativa do Portal Aurosoft LMS para teste
        $portalCourse = Course::firstOrCreate(
            ['name' => 'Introdução Digital (Teste Portal Aurosoft)', 'ouro_moderno_id' => null],
            [
                'workload' => 20,
                'active' => true,
            ]
        );

        $module = LmsModule::firstOrCreate(
            ['course_id' => $portalCourse->id, 'title' => 'Módulo Inicial de Teste'],
            ['sort_order' => 1, 'is_active' => true]
        );

        LmsLesson::firstOrCreate(
            ['module_id' => $module->id, 'title' => 'Aula 01 — Boas-vindas ao Portal'],
            [
                'sort_order' => 1,
                'is_active' => true,
                'estimated_duration_minutes' => 60,
            ]
        );

        $portalFormation = Formation::firstOrCreate(
            ['name' => 'Formação Teste Portal Aurosoft'],
            [
                'description' => 'Formação de teste para validação do Totem e Auto-login no Portal do Aluno (/aluno).',
                'active' => true,
            ]
        );
        $portalFormation->courses()->syncWithoutDetaching([$portalCourse->id => ['order' => 1]]);

        $rows = [];

        foreach ($schools as $school) {
            $schoolName = $school->getFilamentName();

            $classroom = Classroom::withoutGlobalScopes()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => "Lab Teste Totem ({$schoolName})",
                ],
                [
                    'capacity' => 50,
                    'opening_time' => '00:00:00',
                    'closing_time' => '23:00:00',
                    'active' => true,
                ]
            );

            // A) Contrato Teste Ouro Moderno da Escola
            $omContractNumber = sprintf('TESTE-OM-ESC%d', $school->id);
            $omCpf = sprintf('91000000%03d', $school->id);
            $omEmail = sprintf('teste.om.escola%d@aurosoft.local', $school->id);
            $omCodInt = 910000 + (int) $school->id;

            $omStudent = Person::withoutGlobalScopes()->updateOrCreate(
                ['email' => $omEmail],
                [
                    'name' => "Aluno Teste Ouro ({$schoolName})",
                    'cpf' => $omCpf,
                    'birth_date' => '2000-05-15',
                    'ouro_moderno_cod_int' => $omCodInt,
                    'ouro_moderno_usuario' => $omContractNumber,
                    'ouro_moderno_senha' => '123456',
                    'ouro_moderno_registered_at' => now(),
                ]
            );

            $omResponsible = Responsible::firstOrCreate(
                ['person_id' => $omStudent->id, 'financial_responsible' => true],
                ['relationship' => RelationshipEnum::Legal_guardian]
            );

            $omContract = Contract::withoutGlobalScopes()->updateOrCreate(
                ['contract_number' => $omContractNumber],
                [
                    'school_id' => $school->id,
                    'student_id' => $omStudent->id,
                    'responsible_id' => $omResponsible->id,
                    'status' => 'active',
                    'start_date' => today()->subDays(7),
                    'total_value' => 1200.00,
                ]
            );

            $omItem = ContractItem::updateOrCreate(
                ['contract_id' => $omContract->id, 'formation_id' => $omFormation->id],
                ['order' => 1, 'formation_completed_at' => null]
            );

            ContractCourse::updateOrCreate(
                ['contract_item_id' => $omItem->id, 'course_id' => $omCourse->id],
                [
                    'order' => 1,
                    'status' => 'in_progress',
                    'progress_source' => 'ouro_moderno',
                    'lms_synced_at' => now(),
                ]
            );

            $this->ensureFullDaySchedules($school->id, $omItem->id, $classroom->id);

            // B) Contrato Teste Portal do Aluno Aurosoft da Escola
            $portalContractNumber = sprintf('TESTE-PORTAL-ESC%d', $school->id);
            $portalCpf = sprintf('92000000%03d', $school->id);
            $portalEmail = sprintf('teste.portal.escola%d@aurosoft.local', $school->id);

            $portalStudent = Person::withoutGlobalScopes()->updateOrCreate(
                ['email' => $portalEmail],
                [
                    'name' => "Aluno Teste Portal ({$schoolName})",
                    'cpf' => $portalCpf,
                    'birth_date' => '2001-08-20',
                    'ouro_moderno_cod_int' => null,
                    'ouro_moderno_usuario' => null,
                    'ouro_moderno_senha' => null,
                    'ouro_moderno_registered_at' => null,
                ]
            );

            $portalResponsible = Responsible::firstOrCreate(
                ['person_id' => $portalStudent->id, 'financial_responsible' => true],
                ['relationship' => RelationshipEnum::Legal_guardian]
            );

            $portalContract = Contract::withoutGlobalScopes()->updateOrCreate(
                ['contract_number' => $portalContractNumber],
                [
                    'school_id' => $school->id,
                    'student_id' => $portalStudent->id,
                    'responsible_id' => $portalResponsible->id,
                    'status' => 'active',
                    'start_date' => today()->subDays(7),
                    'total_value' => 950.00,
                ]
            );

            $portalItem = ContractItem::updateOrCreate(
                ['contract_id' => $portalContract->id, 'formation_id' => $portalFormation->id],
                ['order' => 1, 'formation_completed_at' => null]
            );

            ContractCourse::updateOrCreate(
                ['contract_item_id' => $portalItem->id, 'course_id' => $portalCourse->id],
                [
                    'order' => 1,
                    'status' => 'in_progress',
                    'progress_source' => 'aurosoft',
                ]
            );

            $this->ensureFullDaySchedules($school->id, $portalItem->id, $classroom->id);

            // Gera chamadas de hoje para esta unidade
            app(GenerateStudentAttendancesForDate::class)->execute(now(), $school->id, ignoreTenantScope: true);

            if ($this->option('reset-today')) {
                StudentAttendance::withoutGlobalScopes()
                    ->whereDate('attendance_date', today())
                    ->whereHas('studentSchedule', fn ($q) => $q->withoutGlobalScopes()->whereIn('contract_item_id', [$omItem->id, $portalItem->id]))
                    ->update([
                        'status' => 'absent',
                        'marked_at' => null,
                        'marking_source' => null,
                    ]);
            }

            $omSetting = IntegrationSetting::query()
                ->where('provider', 'ouro_moderno')
                ->where('school_id', $school->id)
                ->first();

            $omUrlStatus = $omSetting?->base_url ?: '(Configurar URL Tailscale desta unidade)';

            $rows[] = [
                "#{$school->id} - {$schoolName}",
                $omContractNumber,
                $omCpf,
                'Ouro Moderno (senha: 123456)',
                'Seg a Dom • 00:00 às 23:00',
                $omUrlStatus,
            ];

            $rows[] = [
                "#{$school->id} - {$schoolName}",
                $portalContractNumber,
                $portalCpf,
                'Portal Aurosoft (/aluno)',
                'Seg a Dom • 00:00 às 23:00',
                'Interno (/aluno)',
            ];
        }

        $this->table(
            ['Unidade Escolar', 'Nº Contrato (Totem)', 'CPF Alternativo', 'Destino Auto-login', 'Horário Agendado', 'URL LMS da Escola'],
            $rows
        );

        return self::SUCCESS;
    }

    private function ensureFullDaySchedules(int $schoolId, int $contractItemId, int $classroomId): void
    {
        $existing = StudentSchedule::withoutGlobalScopes()
            ->where('contract_item_id', $contractItemId)
            ->get(['weekday', 'start_hour'])
            ->mapWithKeys(fn (StudentSchedule $s): array => ["{$s->weekday}|{$s->start_hour}" => true])
            ->all();

        $now = now();
        $toInsert = [];

        for ($weekday = 0; $weekday <= 6; $weekday++) {
            for ($hour = 0; $hour < 23; $hour++) {
                $startHour = sprintf('%02d:00:00', $hour);
                $endHour = sprintf('%02d:00:00', $hour + 1);
                $key = "{$weekday}|{$startHour}";

                if (! isset($existing[$key])) {
                    $toInsert[] = [
                        'school_id' => $schoolId,
                        'contract_item_id' => $contractItemId,
                        'classroom_id' => $classroomId,
                        'weekday' => $weekday,
                        'start_hour' => $startHour,
                        'end_hour' => $endHour,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if ($toInsert !== []) {
            DB::table('student_schedules')->insert($toInsert);
        }
    }
}
