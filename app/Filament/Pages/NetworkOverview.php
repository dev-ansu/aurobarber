<?php

namespace App\Filament\Pages;

use App\Models\CashRegister;
use App\Models\CommercialLead;
use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\Installment;
use App\Models\LmsStudentLessonProgress;
use App\Models\Payable;
use App\Models\SatisfactionSurveyAnswer;
use App\Models\School;
use App\Models\SchoolMessage;
use App\Models\StudentAttendance;
use App\Services\OuroModernoProgressSyncService;
use App\Services\SchoolTransferService;
use BackedEnum;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use UnitEnum;

class NetworkOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-globe-americas';

    protected static string|UnitEnum|null $navigationGroup = 'Escola';

    protected static ?string $navigationLabel = 'Visão Geral da Rede';

    protected static ?string $title = 'Painel Geral da Rede (Dono de Todas as Escolas)';

    protected static ?string $slug = 'visao-geral-da-rede';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.network-overview';

    public string $period = 'current_month';

    public string $activeTab = 'overview';

    public bool $showQuickTransferModal = false;

    public ?int $transferContractId = null;

    public ?int $transferTargetSchoolId = null;

    public string $transferReason = '';

    /**
     * @var array<string, mixed>
     */
    public array $totals = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $schoolsData = [];

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return $user->isSuperAdmin()
            || $user->can('page_NetworkOverview')
            || $user->can('View:NetworkOverview');
    }

    public function mount(): void
    {
        $this->loadMetrics();
    }

    public function updatedPeriod(): void
    {
        $this->loadMetrics();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function openQuickTransfer(?int $sourceSchoolId = null): void
    {
        $this->transferContractId = null;
        $this->transferTargetSchoolId = null;
        $this->transferReason = '';
        $this->showQuickTransferModal = true;
    }

    public function executeQuickTransfer(): void
    {
        $this->validate([
            'transferContractId' => ['required', 'integer'],
            'transferTargetSchoolId' => ['required', 'integer'],
        ], [
            'transferContractId.required' => 'Selecione o contrato/aluno que deseja transferir.',
            'transferTargetSchoolId.required' => 'Selecione a escola de destino.',
        ]);

        $contract = Contract::query()->withoutGlobalScopes()->findOrFail($this->transferContractId);
        $targetSchool = School::query()->withoutGlobalScopes()->findOrFail($this->transferTargetSchoolId);

        app(SchoolTransferService::class)->transferContract(
            contract: $contract,
            targetSchool: $targetSchool,
            reason: $this->transferReason ?: null,
            sendNotificationMessage: true,
        );

        $this->showQuickTransferModal = false;
        $this->loadMetrics();

        Notification::make()
            ->title('Transferência entre unidades concluída!')
            ->body("O contrato #{$contract->contract_number} foi transferido para {$targetSchool->getFilamentName()}.")
            ->success()
            ->send();
    }

    public function loadMetrics(): void
    {
        [$startDate, $endDate] = $this->resolveDateRange();

        $schools = School::query()
            ->withoutGlobalScopes()
            ->orderBy('id')
            ->get();

        $totalActiveStudents = 0;
        $totalNewEnrollments = 0;
        $totalRevenue = 0.0;
        $totalExpenses = 0.0;
        $totalOverdue = 0.0;
        $totalCashBalance = 0.0;

        // Novos consolidados 360º (Pedagógico, Retenção e Funil Comercial)
        $totalAttendancePresent = 0;
        $totalAttendanceAbsent = 0;
        $totalLmsCompletedLessons = 0;
        $totalLmsStudySeconds = 0;
        $allLmsScores = [];
        $totalAtRiskStudents = 0;
        $totalCompletedContracts = 0;
        $totalCanceledContracts = 0;
        $totalLeadsCount = 0;
        $allSatisfactionRatings = [];

        $rows = [];

        foreach ($schools as $school) {
            $schoolId = (int) $school->id;

            // 1. Alunos ativos (Contratos ativos na unidade)
            $activeContractsQuery = Contract::query()
                ->withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('status', 'active');

            $activeStudents = (clone $activeContractsQuery)->count();

            // 2. Novas matrículas no período
            $newEnrollments = Contract::query()
                ->withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->count();

            // 3. Receitas recebidas no período (Parcelas pagas atribuídas à unidade onde foram pagas)
            $revenue = (float) Installment::query()
                ->withoutGlobalScopes()
                ->where(function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId)
                        ->orWhere(function ($sub) use ($schoolId) {
                            $sub->whereNull('school_id')
                                ->whereHas('contract', fn ($cq) => $cq->withoutGlobalScopes()->where('school_id', $schoolId));
                        });
                })
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$startDate, $endDate])
                ->selectRaw('COALESCE(SUM(COALESCE(amount_paid, amount)), 0) as total')
                ->value('total');

            // 4. Despesas pagas no período (Payables pagos)
            $expenses = (float) Payable::query()
                ->withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('status', 'paid')
                ->whereBetween('payment_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->selectRaw('COALESCE(SUM(COALESCE(amount_paid, amount)), 0) as total')
                ->value('total');

            // 5. Inadimplência atual (Parcelas vencidas e não pagas pertencentes a esta unidade)
            $overdue = (float) Installment::query()
                ->withoutGlobalScopes()
                ->where(function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId)
                        ->orWhere(function ($sub) use ($schoolId) {
                            $sub->whereNull('school_id')
                                ->whereHas('contract', fn ($cq) => $cq->withoutGlobalScopes()->where('school_id', $schoolId));
                        });
                })
                ->whereIn('status', ['pending', 'overdue', 'partial'])
                ->whereDate('due_date', '<', today())
                ->sum('amount');

            // 6. Saldo em caixas abertos atualmente
            $cashBalance = (float) CashRegister::query()
                ->withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('status', 'open')
                ->sum('current_balance');

            $netBalance = $revenue - $expenses;

            // 7. Indicadores Pedagógicos: Frequência / Presença no período
            $attendancePresent = StudentAttendance::query()
                ->withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('status', 'present')
                ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->count();

            $attendanceAbsent = StudentAttendance::query()
                ->withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('status', 'absent')
                ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->count();

            $totalSchoolAttendances = $attendancePresent + $attendanceAbsent;
            $attendanceRate = $totalSchoolAttendances > 0
                ? round(($attendancePresent / $totalSchoolAttendances) * 100, 1)
                : 0.0;

            // 8. Indicadores Pedagógicos: Produtividade e Desempenho LMS (Ouro Moderno + Aurosoft LMS)
            $lmsProgressQuery = LmsStudentLessonProgress::query()
                ->withoutGlobalScopes()
                ->whereHas('contract', fn ($q) => $q->withoutGlobalScopes()->where('school_id', $schoolId));

            $completedLmsQuery = (clone $lmsProgressQuery)
                ->where('status', 'completed')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('completed_at', [$startDate, $endDate])
                        ->orWhere(function ($sq) use ($startDate, $endDate) {
                            $sq->whereNull('completed_at')
                                ->whereBetween('updated_at', [$startDate, $endDate]);
                        });
                });

            $nativeCompletedLessons = (clone $completedLmsQuery)->count();
            $nativeScores = (clone $completedLmsQuery)->whereNotNull('score')->pluck('score')->map(fn ($v) => (float) $v);

            // Cursos e Notas vindas do LMS Ouro Moderno (e cursos consolidados em ContractCourse)
            $contractCourses = ContractCourse::query()
                ->withoutGlobalScopes()
                ->with('course')
                ->whereHas('contractItem', function ($iq) use ($schoolId) {
                    $iq->withoutGlobalScopes()
                        ->whereHas('contract', fn ($cq) => $cq->withoutGlobalScopes()->where('school_id', $schoolId));
                })
                ->get();

            $omSyncService = app(OuroModernoProgressSyncService::class);
            $omCompletedLessons = 0;
            $courseGrades = collect();

            foreach ($contractCourses as $cc) {
                if ($cc->grade !== null && is_numeric($cc->grade)) {
                    $courseGrades->push((float) $cc->grade);
                }

                if (! empty($cc->ouro_moderno_lesson_status)) {
                    $fb = $omSyncService->feedback($cc);
                    $omCompletedLessons += (int) ($fb['completed_lessons'] ?? 0);
                    if ($cc->grade === null && isset($fb['average']) && is_numeric($fb['average'])) {
                        $courseGrades->push((float) $fb['average']);
                    }
                }
            }

            $lmsCompletedLessons = $nativeCompletedLessons + $omCompletedLessons;

            // Combina as notas consolidadas dos cursos (Ouro Moderno + Aurosoft) e notas de aulas nativas
            $combinedScores = $courseGrades->concat($nativeScores);
            $lmsAvgScoreRaw = $combinedScores->isNotEmpty() ? $combinedScores->avg() : null;
            $lmsAvgScore = $lmsAvgScoreRaw !== null ? round((float) $lmsAvgScoreRaw, 1) : 0.0;
            if ($lmsAvgScoreRaw !== null) {
                $allLmsScores[] = (float) $lmsAvgScoreRaw;
            }

            $lmsStudySeconds = (int) (clone $lmsProgressQuery)
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->sum('time_spent_seconds');
            $lmsStudyHours = round($lmsStudySeconds / 3600, 1);

            // 9. Retenção, Conclusões, Cancelamentos e Radar de Evasão
            $completedContracts = Contract::query()
                ->withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('status', 'finished')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('finished_at', [$startDate, $endDate])
                        ->orWhere(function ($sq) use ($startDate, $endDate) {
                            $sq->whereNull('finished_at')
                                ->whereBetween('updated_at', [$startDate, $endDate]);
                        });
                })
                ->count();

            $canceledContracts = Contract::query()
                ->withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('status', 'canceled')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('cancelled_at', [$startDate, $endDate])
                        ->orWhere(function ($sq) use ($startDate, $endDate) {
                            $sq->whereNull('cancelled_at')
                                ->whereBetween('updated_at', [$startDate, $endDate]);
                        });
                })
                ->count();

            $atRiskStudents = $this->countAtRiskStudentsForSchool($schoolId);

            // 10. Satisfação dos Alunos (Avaliações de aulas LMS + Pesquisas de Satisfação)
            $lmsRatingAvg = (clone $lmsProgressQuery)->whereNotNull('student_rating')->avg('student_rating');
            $surveyRatingAvg = SatisfactionSurveyAnswer::query()
                ->whereNotNull('rating_value')
                ->whereHas('question', fn ($q) => $q->where('type', 'rating_5'))
                ->whereHas('response.contract', fn ($q) => $q->withoutGlobalScopes()->where('school_id', $schoolId))
                ->avg('rating_value');

            $schoolRatings = array_filter([$lmsRatingAvg, $surveyRatingAvg], fn ($v) => $v !== null);
            $avgSatisfaction = count($schoolRatings) > 0
                ? round(array_sum($schoolRatings) / count($schoolRatings), 1)
                : 0.0;

            if ($avgSatisfaction > 0) {
                $allSatisfactionRatings[] = $avgSatisfaction;
            }

            // 11. Funil Comercial (Leads captados no período e Taxa de Conversão)
            $leadsCount = CommercialLead::query()
                ->withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('school_id', $schoolId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $conversionRate = $leadsCount > 0
                ? min(100.0, round(($newEnrollments / $leadsCount) * 100, 1))
                : ($newEnrollments > 0 ? 100.0 : 0.0);

            // 12. Mensagens não lidas da unidade
            $unreadMessagesCount = Schema::hasTable('school_messages')
                ? SchoolMessage::query()
                    ->withoutGlobalScopes()
                    ->rootMessages()
                    ->forSchoolInbox($schoolId)
                    ->whereNull('read_at')
                    ->count()
                : 0;

            $rows[] = [
                'id' => $school->id,
                'name' => $school->getFilamentName(),
                'city' => $school->city ? "{$school->city}/{$school->state}" : 'Não informada',
                'active_students' => $activeStudents,
                'new_enrollments' => $newEnrollments,
                'revenue' => $revenue,
                'expenses' => $expenses,
                'net_balance' => $netBalance,
                'overdue_amount' => $overdue,
                'cash_balance' => $cashBalance,
                // Novos indicadores 360º
                'attendance_present' => $attendancePresent,
                'attendance_absent' => $attendanceAbsent,
                'attendance_rate' => $attendanceRate,
                'lms_completed_lessons' => $lmsCompletedLessons,
                'lms_avg_score' => $lmsAvgScore,
                'lms_study_hours' => $lmsStudyHours,
                'at_risk_students' => $atRiskStudents,
                'completed_contracts' => $completedContracts,
                'canceled_contracts' => $canceledContracts,
                'avg_satisfaction' => $avgSatisfaction,
                'leads_count' => $leadsCount,
                'conversion_rate' => $conversionRate,
                'unread_messages_count' => $unreadMessagesCount,
            ];

            $totalActiveStudents += $activeStudents;
            $totalNewEnrollments += $newEnrollments;
            $totalRevenue += $revenue;
            $totalExpenses += $expenses;
            $totalOverdue += $overdue;
            $totalCashBalance += $cashBalance;

            $totalAttendancePresent += $attendancePresent;
            $totalAttendanceAbsent += $attendanceAbsent;
            $totalLmsCompletedLessons += $lmsCompletedLessons;
            $totalLmsStudySeconds += $lmsStudySeconds;
            $totalAtRiskStudents += $atRiskStudents;
            $totalCompletedContracts += $completedContracts;
            $totalCanceledContracts += $canceledContracts;
            $totalLeadsCount += $leadsCount;
        }

        $totalAttendancesAll = $totalAttendancePresent + $totalAttendanceAbsent;
        $networkAttendanceRate = $totalAttendancesAll > 0
            ? round(($totalAttendancePresent / $totalAttendancesAll) * 100, 1)
            : 0.0;

        $networkLmsAvgScore = count($allLmsScores) > 0
            ? round(array_sum($allLmsScores) / count($allLmsScores), 1)
            : 0.0;

        $networkSatisfaction = count($allSatisfactionRatings) > 0
            ? round(array_sum($allSatisfactionRatings) / count($allSatisfactionRatings), 1)
            : 0.0;

        $networkConversionRate = $totalLeadsCount > 0
            ? min(100.0, round(($totalNewEnrollments / $totalLeadsCount) * 100, 1))
            : ($totalNewEnrollments > 0 ? 100.0 : 0.0);

        $this->schoolsData = $rows;

        $this->totals = [
            'total_schools' => $schools->count(),
            'active_students' => $totalActiveStudents,
            'new_enrollments' => $totalNewEnrollments,
            'revenue' => $totalRevenue,
            'expenses' => $totalExpenses,
            'net_balance' => $totalRevenue - $totalExpenses,
            'overdue_amount' => $totalOverdue,
            'cash_balance' => $totalCashBalance,
            // Novos consolidados 360º da Rede
            'attendance_present' => $totalAttendancePresent,
            'attendance_absent' => $totalAttendanceAbsent,
            'attendance_rate' => $networkAttendanceRate,
            'lms_completed_lessons' => $totalLmsCompletedLessons,
            'lms_avg_score' => $networkLmsAvgScore,
            'lms_study_hours' => round($totalLmsStudySeconds / 3600, 1),
            'at_risk_students' => $totalAtRiskStudents,
            'completed_contracts' => $totalCompletedContracts,
            'canceled_contracts' => $totalCanceledContracts,
            'avg_satisfaction' => $networkSatisfaction,
            'leads_count' => $totalLeadsCount,
            'conversion_rate' => $networkConversionRate,
        ];
    }

    private function countAtRiskStudentsForSchool(int $schoolId): int
    {
        $contracts = Contract::query()
            ->withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('status', 'active')
            ->get(['id']);

        if ($contracts->isEmpty()) {
            return 0;
        }

        $atRiskCount = 0;

        foreach ($contracts as $contract) {
            $recentAbsences = StudentAttendance::query()
                ->withoutGlobalScopes()
                ->where('school_id', $schoolId)
                ->where('status', 'absent')
                ->whereDate('attendance_date', '>=', now()->subDays(30)->toDateString())
                ->whereHas('studentSchedule.contractItem', fn ($q) => $q->withoutGlobalScopes()->where('contract_id', $contract->id))
                ->count();

            if ($recentAbsences >= 2) {
                $atRiskCount++;

                continue;
            }

            $overdueInstallments = Installment::query()
                ->withoutGlobalScopes()
                ->where('contract_id', $contract->id)
                ->whereIn('status', ['pending', 'partial', 'overdue'])
                ->whereDate('due_date', '<', now()->subDays(5)->toDateString())
                ->count();

            if ($overdueInstallments >= 2) {
                $atRiskCount++;

                continue;
            }

            $avgLms = LmsStudentLessonProgress::query()
                ->withoutGlobalScopes()
                ->where('contract_id', $contract->id)
                ->where('status', 'completed')
                ->whereNotNull('score')
                ->avg('score');

            if ($avgLms !== null && (float) $avgLms < 70.0) {
                $atRiskCount++;
            }
        }

        return $atRiskCount;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolveDateRange(): array
    {
        return match ($this->period) {
            'last_month' => [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ],
            'current_year' => [
                now()->startOfYear(),
                now()->endOfYear(),
            ],
            'all_time' => [
                Carbon::create(2000, 1, 1)->startOfDay(),
                now()->endOfDay(),
            ],
            default => [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ],
        };
    }
}
