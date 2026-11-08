<?php

namespace App\Filament\Widgets;

use App\Enums\ContactTypeEnum;
use App\Models\Contract;
use App\Models\StudentAttendance;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\Widget as BaseWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DropoutRiskRadarWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.dropout-risk-radar-widget';

    public static function canView(): bool
    {
        $user = filament()->auth()->user();

        return $user instanceof User
            && ($user->hasRole('super_admin') || $user->can('View:DropoutRiskRadarWidget') || $user->can('ViewAny:Contract') || $user->can('View:StudentAbsencesReport'));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function getAtRiskStudents(): Collection
    {
        $contracts = Contract::query()
            ->where('status', 'active')
            ->with([
                'student.contacts',
                'responsible.person.contacts',
                'items.formation',
                'schedules',
                'installments',
                'lmsLessonProgress',
            ])
            ->limit(200)
            ->get();

        $thirtyDaysAgo = today()->subDays(30)->toDateString();
        $overdueCutoff = today()->subDays(5)->toDateString();
        $now = now();

        $results = [];

        foreach ($contracts as $contract) {
            $student = $contract->student;
            if (! $student) {
                continue;
            }

            $isEad = (bool) $contract->is_ead;
            $scheduleIds = $contract->schedules->pluck('id')->all();

            // 1. Faltas nos últimos 30 dias (Presencial)
            $recentAbsences = 0;
            if ($scheduleIds !== []) {
                $recentAbsences = StudentAttendance::query()
                    ->whereIn('student_schedule_id', $scheduleIds)
                    ->where('status', 'absent')
                    ->where('attendance_date', '>=', $thirtyDaysAgo)
                    ->count();
            }

            // 2. Desempenho no LMS Presencial (média de notas em aulas avaliadas)
            $gradedProgress = $contract->lmsLessonProgress->filter(fn ($p) => (float) $p->score > 0);
            $avgLmsScore = $gradedProgress->isNotEmpty()
                ? round((float) $gradedProgress->avg('score'), 1)
                : null;

            // 3. Estagnação de Acesso no EAD (Ouro Moderno Online)
            $daysSinceLastEadAccess = null;
            if ($isEad) {
                if ($contract->ead_last_access_at) {
                    $daysSinceLastEadAccess = (int) $contract->ead_last_access_at->diffInDays($now);
                } else {
                    $daysSinceLastEadAccess = (int) $contract->created_at->diffInDays($now);
                }
            }

            // 4. Inadimplência: Parcelas vencidas há mais de 5 dias
            $overdueInstallments = $contract->installments
                ->filter(fn ($inst) => in_array($inst->status, ['pending', 'partial'], true)
                    && $inst->due_date
                    && $inst->due_date->toDateString() <= $overdueCutoff)
                ->count();

            $riskScore = 0;
            $reasons = [];

            // Regras de Risco Presencial
            if (! $isEad) {
                if ($recentAbsences >= 3) {
                    $riskScore += 50;
                    $reasons[] = ['label' => "{$recentAbsences} faltas (30d)", 'color' => 'rose'];
                } elseif ($recentAbsences >= 2) {
                    $riskScore += 30;
                    $reasons[] = ['label' => "{$recentAbsences} faltas (30d)", 'color' => 'amber'];
                }

                if ($avgLmsScore !== null && $avgLmsScore < 70.0) {
                    $riskScore += 30;
                    $reasons[] = ['label' => "Média LMS {$avgLmsScore}%", 'color' => 'amber'];
                }
            }

            // Regras de Risco EAD
            if ($isEad) {
                if ($daysSinceLastEadAccess !== null && $daysSinceLastEadAccess >= 15) {
                    $riskScore += 50;
                    $reasons[] = ['label' => "Sem acesso EAD há {$daysSinceLastEadAccess}d", 'color' => 'rose'];
                } elseif ($daysSinceLastEadAccess !== null && $daysSinceLastEadAccess >= 7) {
                    $riskScore += 30;
                    $reasons[] = ['label' => "Sem acesso EAD há {$daysSinceLastEadAccess}d", 'color' => 'amber'];
                }

                if ((float) $contract->ead_last_progress_percentage === 0.0 && (int) $contract->created_at->diffInDays($now) >= 7) {
                    $riskScore += 20;
                    $reasons[] = ['label' => '0% de progresso no EAD', 'color' => 'amber'];
                }
            }

            // Regras de Inadimplência
            if ($overdueInstallments >= 2) {
                $riskScore += 40;
                $reasons[] = ['label' => "{$overdueInstallments} parcelas vencidas", 'color' => 'rose'];
            } elseif ($overdueInstallments === 1) {
                $riskScore += 20;
                $reasons[] = ['label' => '1 parcela vencida', 'color' => 'amber'];
            }

            if ($riskScore === 0) {
                continue;
            }

            $phone = $student->contacts->firstWhere('type', ContactTypeEnum::WhatsApp)?->value
                ?? $student->contacts->firstWhere('type', ContactTypeEnum::Celular)?->value
                ?? $contract->responsible?->person?->contacts->firstWhere('type', ContactTypeEnum::WhatsApp)?->value
                ?? $contract->responsible?->person?->contacts->firstWhere('type', ContactTypeEnum::Celular)?->value;

            $cleanPhone = preg_replace('/\D/', '', (string) $phone);

            $school = $contract->school
                ?? (class_exists(\Filament\Facades\Filament::class) ? \Filament\Facades\Filament::getTenant() : null)
                ?? \App\Models\School::getActiveSchool();
            $schoolName = $school?->trade_name ?: ($school?->legal_name ?: 'sua escola');

            // Mensagem personalizada contextualizada para a modalidade
            if ($isEad) {
                $waMessage = "Olá {$student->name}, tudo bem? Aqui é da coordenação pedagógica da {$schoolName}. Notamos que faz alguns dias que você não acessa seus cursos no portal EAD. Aconteceu algo ou você está com alguma dúvida em alguma matéria? Estamos aqui para te ajudar a continuar seus estudos!";
            } else {
                $waMessage = "Olá {$student->name}, tudo bem? Aqui é da coordenação pedagógica da {$schoolName}. Sentimos sua falta nas últimas aulas! Queremos te ajudar a agendar uma reposição do conteúdo para você não ficar para trás. Como podemos te ajudar?";
            }

            $results[] = [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'student_name' => $student->name,
                'course_name' => $contract->items->first()?->formation?->name ?? 'Formação Regular',
                'is_ead' => $isEad,
                'recent_absences' => $recentAbsences,
                'avg_lms_score' => $avgLmsScore,
                'days_since_last_ead_access' => $daysSinceLastEadAccess,
                'overdue_installments' => $overdueInstallments,
                'risk_score' => min(100, $riskScore),
                'risk_level' => $riskScore >= 50 ? 'Crítico' : 'Alerta',
                'reasons' => $reasons,
                'clean_phone' => $cleanPhone,
                'wa_message' => $waMessage,
            ];
        }

        return collect($results)
            ->sortByDesc('risk_score')
            ->take(12)
            ->values();
    }

    public function getViewData(): array
    {
        $students = $this->getAtRiskStudents();

        return [
            'students' => $students,
            'criticalCount' => $students->where('risk_level', 'Crítico')->count(),
            'warningCount' => $students->where('risk_level', 'Alerta')->count(),
        ];
    }
}

