<?php

declare(strict_types=1);

namespace App\Http\Controllers\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\LmsProgressionService;
use App\Services\OuroModernoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

class KioskLmsAutoLoginController extends Controller
{
    public function __invoke(int|string $contract, OuroModernoService $ouroModernoService): View|Response|RedirectResponse
    {
        $contract = Contract::withoutGlobalScopes()
            ->with(['student', 'school'])
            ->findOrFail($contract);

        if ($contract->status !== 'active') {
            return redirect()->route('student.login')->with('errorMessage', 'Acesso não disponível. Sua matrícula não está com status ativo. Procure a secretaria da sua escola.');
        }

        if ($contract->hasOverdueInstallments()) {
            return redirect()->route('student.portal');
        }

        $scheduleCheck = app(LmsProgressionService::class)->checkStudentScheduleWindow($contract);
        if (! $scheduleCheck['is_allowed']) {
            return redirect()->route('student.portal');
        }

        $student = $contract->student;
        if (! $student) {
            abort(404, 'Aluno não encontrado para este contrato.');
        }

        $ouroModernoService = $ouroModernoService->forSchool($contract->school_id);

        if (! $ouroModernoService->isConfigured()) {
            if ($contract->hasActiveAurosoftLmsCourse()) {
                return redirect()->route('student.portal');
            }

            $schoolName = $contract->school?->getFilamentName() ?? 'desta unidade';

            return response()->view('errors.custom-message', [
                'title' => 'Integração Ouro Moderno Não Configurada',
                'message' => "A URL e o Token do LMS Ouro Moderno ainda não foram configurados para a unidade {$schoolName}. Solicite à secretaria ou coordenação da escola.",
                'actionUrl' => route('student.login'),
                'actionLabel' => 'Voltar',
            ], 503);
        }

        if (! $student->isMigratedToOuroModerno()) {
            if ($contract->hasActiveAurosoftLmsCourse()) {
                return redirect()->route('student.portal');
            }

            abort(404, 'O aluno deste contrato ainda não foi migrado para o LMS Ouro Moderno.');
        }

        // Se o contrato possuir catálogo de cursos e nenhum for da Ouro Moderno
        $hasAnyCourses = $contract->items()
            ->where(function ($q) {
                $q->whereHas('contractCourses')
                    ->orWhereHas('formation.courses');
            })
            ->exists();

        if ($hasAnyCourses && ! $contract->hasActiveOuroModernoCourse()) {
            if ($contract->hasActiveAurosoftLmsCourse()) {
                return redirect()->route('student.portal');
            }

            abort(404, 'Este contrato não possui cursos vinculados ao LMS Ouro Moderno.');
        }

        $username = trim((string) ($contract->contract_number ?: $student->ouro_moderno_usuario));
        $password = (string) ($student->ouro_moderno_senha ?? '');

        if ($password === '') {
            try {
                $omPayload = $ouroModernoService->students();
                $omStudents = $omPayload['alunos'] ?? $omPayload['Alunos'] ?? $omPayload['students'] ?? $omPayload['data'] ?? $omPayload;
                if (is_array($omStudents)) {
                    foreach ($omStudents as $omStudent) {
                        if (! is_array($omStudent)) {
                            continue;
                        }
                        $matchesCode = isset($omStudent['cod_int']) && (string) $omStudent['cod_int'] === (string) $student->ouro_moderno_cod_int;
                        $matchesUser = isset($omStudent['usuario']) && (
                            strcasecmp((string) $omStudent['usuario'], $username) === 0
                            || strcasecmp((string) $omStudent['usuario'], (string) $contract->contract_number) === 0
                        );

                        if ($matchesCode || $matchesUser) {
                            $remotePassword = (string) ($omStudent['senha'] ?? '');
                            if ($remotePassword !== '') {
                                $decoded = base64_decode($remotePassword, true);
                                if ($decoded !== false && base64_encode($decoded) === $remotePassword && ctype_print($decoded)) {
                                    $password = $decoded;
                                } else {
                                    $password = $remotePassword;
                                }

                                $student->forceFill(['ouro_moderno_senha' => $password])->save();
                                break;
                            }
                        }
                    }
                }
            } catch (\Throwable) {
                // Ignore API lookup error and continue with fallback
            }
        }

        if ($password === '') {
            $password = $username;
        }

        $actionUrl = $ouroModernoService->getLoginUrl();

        return view('kiosk.lms-autologin', [
            'actionUrl' => $actionUrl,
            'username' => $username,
            'password' => $password,
            'studentName' => $student->name,
            'schoolName' => $contract->school?->getFilamentName(),
        ]);
    }
}
