<?php

namespace App\Services;

use App\Models\CommercialLead;
use App\Models\CommercialVisit;
use App\Models\Contract;
use App\Models\ContractHistory;
use App\Models\Installment;
use App\Models\LmsStudentLessonProgress;
use App\Models\Person;
use App\Models\Responsible;
use App\Models\School;
use App\Models\SchoolMessage;
use App\Models\StudentAttendance;
use App\Models\StudentSchedule;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SchoolTransferService
{
    /**
     * Transfere uma Pessoa e (opcionalmente, por padrão true) todos os dados vinculados a ela
     * (Responsável Financeiro, Contratos/Matrículas, Progresso LMS, Parcelas em aberto,
     * Frequências, Leads e Visitas) da unidade de origem (Escola X) para a unidade de destino (Escola Y).
     * Parcelas já pagas permanecem vinculadas à unidade de origem (Escola X).
     */
    public function transferPerson(
        Person $person,
        School $targetSchool,
        ?School $originSchool = null,
        ?string $reason = null,
        bool $keepOriginPersonLink = false,
        bool $sendNotificationMessage = true,
        bool $transferContracts = true,
    ): ?SchoolMessage {
        $originSchool ??= (Filament::getTenant() instanceof School ? Filament::getTenant() : null)
            ?? $person->schools()->first();

        if ($originSchool && (int) $originSchool->id === (int) $targetSchool->id) {
            throw ValidationException::withMessages([
                'target_school_id' => 'A unidade de destino deve ser diferente da unidade de origem.',
            ]);
        }

        return DB::transaction(function () use (
            $person,
            $targetSchool,
            $originSchool,
            $reason,
            $keepOriginPersonLink,
            $sendNotificationMessage,
            $transferContracts
        ): ?SchoolMessage {
            $originName = $originSchool?->getFilamentName() ?? 'Unidade de Origem';
            $targetName = $targetSchool->getFilamentName();

            // 1. Vincula a Pessoa à unidade de destino
            $this->attachPersonToSchool((int) $person->id, (int) $targetSchool->id);

            $contracts = collect();
            $transferredContractNumbers = [];
            $transferredResponsibleNames = [];
            $totalReleasedSchedules = 0;
            $totalOpenInstallmentsMoved = 0;
            $totalPaidInstallmentsKept = 0;

            if ($transferContracts) {
                // 2. Localiza todos os contratos desta pessoa na unidade de origem (como aluno)
                $contractsQuery = Contract::query()
                    ->withoutGlobalScopes()
                    ->where('student_id', $person->id);

                if ($originSchool) {
                    $contractsQuery->where('school_id', $originSchool->id);
                }

                $contracts = $contractsQuery->get();

                foreach ($contracts as $contract) {
                    $result = $this->moveSingleContractData(
                        contract: $contract,
                        originSchool: $originSchool,
                        targetSchool: $targetSchool,
                        reason: $reason,
                        keepOriginPersonLink: $keepOriginPersonLink,
                    );

                    $transferredContractNumbers[] = '#'.$contract->contract_number;
                    $totalReleasedSchedules += $result['released_schedules'];
                    $totalOpenInstallmentsMoved += $result['moved_open_installments'];
                    $totalPaidInstallmentsKept += $result['kept_paid_installments'];

                    if ($result['responsible_name']) {
                        $transferredResponsibleNames[] = $result['responsible_name'];
                    }
                }

                // 3. Caso a pessoa possua outros responsáveis vinculados a contratos dela,
                // garante que o responsável também seja vinculado à unidade de destino
                $responsiblePersonIds = Contract::query()
                    ->withoutGlobalScopes()
                    ->where('student_id', $person->id)
                    ->whereNotNull('responsible_id')
                    ->pluck('responsible_id')
                    ->unique();

                if ($responsiblePersonIds->isNotEmpty()) {
                    $responsibles = Responsible::query()
                        ->withoutGlobalScopes()
                        ->with('person')
                        ->whereIn('id', $responsiblePersonIds)
                        ->get();

                    foreach ($responsibles as $resp) {
                        if ($resp->person_id) {
                            $this->attachPersonToSchool((int) $resp->person_id, (int) $targetSchool->id);
                            if ($resp->person?->name) {
                                $transferredResponsibleNames[] = $resp->person->name;
                            }

                            if (! $keepOriginPersonLink && $originSchool) {
                                $this->detachPersonFromOriginIfNoRemainingLinks((int) $resp->person_id, (int) $originSchool->id);
                            }
                        }
                    }
                }
            }

            // 4. Transfere Leads Comerciais e Visitas vinculados a esta pessoa na escola de origem
            $leadsQuery = CommercialLead::query()
                ->withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('person_id', $person->id);

            if ($originSchool) {
                $leadsQuery->where('school_id', $originSchool->id);
            }

            if (! $transferContracts) {
                $leadsQuery->whereDoesntHave('contract');
            }

            $leads = $leadsQuery->get();
            foreach ($leads as $lead) {
                $lead->forceFill(['school_id' => $targetSchool->id])->save();

                CommercialVisit::query()
                    ->withoutGlobalScopes()
                    ->where('commercial_lead_id', $lead->id)
                    ->when(! $transferContracts, fn ($q) => $q->whereNull('contract_id'))
                    ->update(['school_id' => $targetSchool->id]);
            }

            // 5. Se for transferência definitiva (!keepOriginPersonLink), remove o vínculo da escola de origem
            // (apenas se não restarem contratos ativos/vinculados na escola de origem)
            if (! $keepOriginPersonLink && $originSchool) {
                $this->detachPersonFromOriginIfNoRemainingLinks((int) $person->id, (int) $originSchool->id);
            }

            if (! $sendNotificationMessage || ! Schema::hasTable('school_messages')) {
                return null;
            }

            $transferredResponsibleNames = array_values(array_unique($transferredResponsibleNames));
            $contractsSummary = count($transferredContractNumbers) > 0
                ? implode(', ', $transferredContractNumbers)
                : ($transferContracts
                    ? 'Nenhum contrato ativo (apenas cadastro pessoal/documentos)'
                    : 'Mantidos na unidade de origem (transferido apenas o cadastro pessoal)');

            $responsiblesSummary = count($transferredResponsibleNames) > 0
                ? implode(', ', $transferredResponsibleNames)
                : 'Não possui / O próprio aluno';

            $bodyLines = [
                "O cadastro de **{$person->name}** foi transferido da unidade **{$originName}** para **{$targetName}**.",
                '',
                '📋 **Dados Transferidos:**',
                "- **Pessoa / Aluno:** {$person->name}".($person->cpf ? " (CPF: {$person->cpf})" : ''),
                "- **Responsável Financeiro vinculado:** {$responsiblesSummary}",
                "- **Contrato(s) transferido(s):** {$contractsSummary}",
            ];

            if (count($transferredContractNumbers) > 0) {
                $bodyLines[] = "- **Financeiro transferido (em aberto):** {$totalOpenInstallmentsMoved} parcela(s) (recebimentos já pagos mantidos na origem: {$totalPaidInstallmentsKept})";
                $bodyLines[] = "- **Horários liberados na origem:** {$totalReleasedSchedules} horário(s)";
            }

            if (filled($reason)) {
                $bodyLines[] = "- **Motivo / Observação:** {$reason}";
            }

            return SchoolMessage::query()->create([
                'sender_school_id' => $originSchool?->id,
                'recipient_school_id' => $targetSchool->id,
                'sender_user_id' => Auth::id(),
                'contract_id' => $contracts->first()?->id,
                'category' => 'transfer',
                'subject' => "🔄 Transferência de Cadastro: {$person->name}",
                'body' => implode("\n", $bodyLines),
            ]);
        });
    }

    /**
     * Transfere um Contrato (Matrícula) e TODOS os dados vinculados ao Aluno,
     * seu Responsável Financeiro, Progresso LMS e Parcelas em aberto (exceto as já pagas)
     * da unidade atual (Escola X) para a unidade de destino (Escola Y).
     */
    public function transferContract(
        Contract $contract,
        School $targetSchool,
        ?string $reason = null,
        bool $keepOriginPersonLink = false,
        bool $sendNotificationMessage = true,
    ): ?SchoolMessage {
        if ((int) $contract->school_id === (int) $targetSchool->id) {
            throw ValidationException::withMessages([
                'target_school_id' => 'A unidade de destino deve ser diferente da unidade atual do contrato.',
            ]);
        }

        return DB::transaction(function () use (
            $contract,
            $targetSchool,
            $reason,
            $keepOriginPersonLink,
            $sendNotificationMessage
        ): ?SchoolMessage {
            $originSchool = $contract->school_id
                ? School::query()->withoutGlobalScopes()->find($contract->school_id)
                : null;

            $originName = $originSchool?->getFilamentName() ?? 'Unidade Anterior';
            $targetName = $targetSchool->getFilamentName();

            $result = $this->moveSingleContractData(
                contract: $contract,
                originSchool: $originSchool,
                targetSchool: $targetSchool,
                reason: $reason,
                keepOriginPersonLink: $keepOriginPersonLink,
            );

            // Se for transferência definitiva (!keepOriginPersonLink), desvincula aluno e responsável da escola de origem se não tiverem outros contratos lá
            if (! $keepOriginPersonLink && $originSchool) {
                if ($contract->student_id) {
                    $this->detachPersonFromOriginIfNoRemainingLinks((int) $contract->student_id, (int) $originSchool->id);
                }
                if ($result['responsible_person_id']) {
                    $this->detachPersonFromOriginIfNoRemainingLinks((int) $result['responsible_person_id'], (int) $originSchool->id);
                }
            }

            if (! $sendNotificationMessage || ! Schema::hasTable('school_messages')) {
                return null;
            }

            $studentName = $contract->student?->name ?? 'Aluno #'.$contract->student_id;
            $completedLessons = LmsStudentLessonProgress::query()
                ->where('contract_id', $contract->id)
                ->where('status', 'completed')
                ->count();

            $bodyLines = [
                "O aluno **{$studentName}** (Contrato **#{$contract->contract_number}**) e todos os seus dados vinculados foram transferidos da unidade **{$originName}** para **{$targetName}**.",
                '',
                '📋 **Resumo da Transferência:**',
                "- **Aluno transferido:** {$studentName}",
                '- **Responsável Financeiro transferido:** '.($result['responsible_name'] ?: 'O próprio aluno / Não informado'),
                "- **Progresso LMS preservado:** {$completedLessons} aula(s) concluída(s)",
                "- **Parcelas em aberto enviadas para {$targetName}:** {$result['moved_open_installments']} parcela(s)",
                "- **Parcelas já pagas mantidas em {$originName}:** {$result['kept_paid_installments']} parcela(s)",
                "- **Horários na unidade anterior:** {$result['released_schedules']} horário(s) encerrado(s) para alocação em nova turma",
            ];

            if (filled($reason)) {
                $bodyLines[] = "- **Observação / Motivo:** {$reason}";
            }

            return SchoolMessage::query()->create([
                'sender_school_id' => $originSchool?->id,
                'recipient_school_id' => $targetSchool->id,
                'sender_user_id' => Auth::id(),
                'contract_id' => $contract->id,
                'category' => 'transfer',
                'subject' => "🔄 Transferência de Matrícula: {$studentName} (#{$contract->contract_number})",
                'body' => implode("\n", $bodyLines),
            ]);
        });
    }

    /**
     * Transfere um Lead Comercial para outra unidade da rede.
     */
    public function transferLead(
        CommercialLead $lead,
        School $targetSchool,
        ?string $reason = null,
        bool $sendNotificationMessage = true,
    ): ?SchoolMessage {
        if ((int) $lead->school_id === (int) $targetSchool->id) {
            throw ValidationException::withMessages([
                'target_school_id' => 'A unidade de destino deve ser diferente da unidade atual do lead.',
            ]);
        }

        return DB::transaction(function () use ($lead, $targetSchool, $reason, $sendNotificationMessage): ?SchoolMessage {
            $originSchool = $lead->school_id
                ? School::query()->withoutGlobalScopes()->find($lead->school_id)
                : null;

            $originName = $originSchool?->getFilamentName() ?? 'Unidade Anterior';
            $targetName = $targetSchool->getFilamentName();

            if ($lead->person_id) {
                $this->attachPersonToSchool((int) $lead->person_id, (int) $targetSchool->id);
            }

            $timestamp = now()->format('d/m/Y H:i');
            $transferNote = "[{$timestamp}] Transferido de {$originName} para {$targetName}"
                .(filled($reason) ? " — Motivo: {$reason}" : '');

            $updatedNotes = filled($lead->notes)
                ? $lead->notes."\n".$transferNote
                : $transferNote;

            $lead->forceFill([
                'school_id' => $targetSchool->id,
                'notes' => $updatedNotes,
            ])->save();

            CommercialVisit::query()
                ->withoutGlobalScopes()
                ->where('commercial_lead_id', $lead->id)
                ->whereNull('contract_id')
                ->update(['school_id' => $targetSchool->id]);

            if (! $sendNotificationMessage || ! Schema::hasTable('school_messages')) {
                return null;
            }

            $bodyLines = [
                "O lead comercial **{$lead->name}** foi encaminhado da unidade **{$originName}** para **{$targetName}**.",
                '- **Telefone:** '.($lead->phone ?: 'Não informado'),
                '- **E-mail:** '.($lead->email ?: 'Não informado'),
            ];

            if (filled($reason)) {
                $bodyLines[] = "- **Motivo / Observação:** {$reason}";
            }

            return SchoolMessage::query()->create([
                'sender_school_id' => $originSchool?->id,
                'recipient_school_id' => $targetSchool->id,
                'sender_user_id' => Auth::id(),
                'category' => 'transfer',
                'subject' => "🔄 Lead Transferido: {$lead->name}",
                'body' => implode("\n", $bodyLines),
            ]);
        });
    }

    /**
     * Move um único contrato e seus dados correlatos (Aluno, Responsável, Frequências,
     * Parcelas em aberto, Leads e Horários). Parcelas já pagas permanecem na unidade de origem.
     *
     * @return array{
     *     released_schedules: int,
     *     responsible_person_id: int|null,
     *     responsible_name: string|null,
     *     moved_open_installments: int,
     *     kept_paid_installments: int
     * }
     */
    private function moveSingleContractData(
        Contract $contract,
        ?School $originSchool,
        School $targetSchool,
        ?string $reason,
        bool $keepOriginPersonLink,
    ): array {
        $originName = $originSchool?->getFilamentName() ?? 'Unidade Anterior';
        $targetName = $targetSchool->getFilamentName();

        // 1. Vincula o Aluno (Person) à escola de destino
        if ($contract->student_id) {
            $this->attachPersonToSchool((int) $contract->student_id, (int) $targetSchool->id);
        }

        // 2. Vincula o Responsável Financeiro (Person) à escola de destino
        $responsiblePersonId = null;
        $responsibleName = null;
        if ($contract->responsible_id) {
            $responsible = Responsible::query()
                ->withoutGlobalScopes()
                ->with('person')
                ->find($contract->responsible_id);

            if ($responsible?->person_id) {
                $responsiblePersonId = (int) $responsible->person_id;
                $responsibleName = $responsible->person?->name;
                $this->attachPersonToSchool($responsiblePersonId, (int) $targetSchool->id);
            }
        }

        // 3. Libera as vagas/horários ativos nas salas de aula da escola de origem e migra o histórico de presença
        $allContractSchedules = StudentSchedule::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->whereHas('contractItem', fn ($q) => $q->withoutGlobalScopes()->withTrashed()->where('contract_id', $contract->id))
            ->get();

        $scheduleIds = $allContractSchedules->pluck('id')->all();
        if (! empty($scheduleIds)) {
            StudentAttendance::query()
                ->withoutGlobalScopes()
                ->whereIn('student_schedule_id', $scheduleIds)
                ->update(['school_id' => $targetSchool->id]);
        }

        $activeSchedules = $allContractSchedules->filter(fn (StudentSchedule $s) => $s->deleted_at === null);
        $releasedSchedulesCount = $activeSchedules->count();

        foreach ($activeSchedules as $schedule) {
            $schedule->delete();
        }

        // 4. Transfere o Financeiro EM ABERTO (pending / partial sem baixa integral) para a escola de destino,
        // mantendo as parcelas JÁ PAGAS (e suas movimentações de caixa) na escola de origem!
        $movedOpenInstallments = 0;
        $keptPaidInstallments = 0;

        if (Schema::hasColumn('installments', 'school_id')) {
            // Garante que parcelas pagas tenham o school_id da unidade de origem preservado
            if ($originSchool) {
                Installment::query()
                    ->withoutGlobalScopes()
                    ->where('contract_id', $contract->id)
                    ->whereNull('school_id')
                    ->update(['school_id' => $originSchool->id]);
            }

            $keptPaidInstallments = Installment::query()
                ->withoutGlobalScopes()
                ->where('contract_id', $contract->id)
                ->where(function ($q) {
                    $q->where('status', 'paid')
                        ->orWhereNotNull('paid_at')
                        ->orWhereHas('transactions');
                })
                ->count();

            $movedOpenInstallments = Installment::query()
                ->withoutGlobalScopes()
                ->where('contract_id', $contract->id)
                ->where('status', '!=', 'paid')
                ->whereNull('paid_at')
                ->whereDoesntHave('transactions')
                ->update(['school_id' => $targetSchool->id]);
        }

        // 5. Transfere o Lead Comercial e Visitas associadas a este contrato/aluno
        if ($contract->commercial_lead_id) {
            CommercialLead::query()
                ->withoutGlobalScopes()
                ->where('id', $contract->commercial_lead_id)
                ->update(['school_id' => $targetSchool->id]);

            CommercialVisit::query()
                ->withoutGlobalScopes()
                ->where('commercial_lead_id', $contract->commercial_lead_id)
                ->update(['school_id' => $targetSchool->id]);
        } elseif ($contract->student_id && $originSchool) {
            CommercialLead::query()
                ->withoutGlobalScopes()
                ->where('person_id', $contract->student_id)
                ->where('school_id', $originSchool->id)
                ->update(['school_id' => $targetSchool->id]);
        }

        // 6. Atualiza a unidade do contrato e seu snapshot cadastral
        $contract->forceFill([
            'school_id' => $targetSchool->id,
            'school_snapshot' => $targetSchool->toContractSnapshot(),
        ])->save();

        // 7. Registra no Histórico do Contrato (Dossiê Auditável)
        $reasonText = filled($reason) ? " Motivo: {$reason}." : '';
        $schedulesNote = $releasedSchedulesCount > 0
            ? " ({$releasedSchedulesCount} horário(s) liberado(s) na unidade de origem)."
            : '';
        $respNote = $responsibleName ? " Responsável transferido: {$responsibleName}." : '';
        $finNote = " Financeiro: {$movedOpenInstallments} parcela(s) em aberto enviada(s) para {$targetName}; {$keptPaidInstallments} parcela(s) paga(s) mantida(s) em {$originName}.";

        ContractHistory::query()->create([
            'contract_id' => $contract->id,
            'user_id' => Auth::id(),
            'type' => 'general',
            'description' => "Transferência de unidade realizada de [{$originName}] para [{$targetName}].{$respNote}{$finNote}{$schedulesNote}{$reasonText}",
            'occurred_at' => now(),
        ]);

        return [
            'released_schedules' => $releasedSchedulesCount,
            'responsible_person_id' => $responsiblePersonId,
            'responsible_name' => $responsibleName,
            'moved_open_installments' => $movedOpenInstallments,
            'kept_paid_installments' => $keptPaidInstallments,
        ];
    }

    private function attachPersonToSchool(int $personId, int $schoolId): void
    {
        DB::table('school_person')->updateOrInsert(
            ['school_id' => $schoolId, 'person_id' => $personId],
            ['created_at' => now(), 'updated_at' => now()]
        );
    }

    private function detachPersonFromOriginIfNoRemainingLinks(int $personId, int $originSchoolId): void
    {
        $hasContractsAsStudent = Contract::query()
            ->withoutGlobalScopes()
            ->where('school_id', $originSchoolId)
            ->where('student_id', $personId)
            ->exists();

        if ($hasContractsAsStudent) {
            return;
        }

        $hasContractsAsResponsible = Contract::query()
            ->withoutGlobalScopes()
            ->where('school_id', $originSchoolId)
            ->whereHas('responsible', fn ($q) => $q->withoutGlobalScopes()->where('person_id', $personId))
            ->exists();

        if ($hasContractsAsResponsible) {
            return;
        }

        DB::table('school_person')
            ->where('school_id', $originSchoolId)
            ->where('person_id', $personId)
            ->delete();
    }
}
