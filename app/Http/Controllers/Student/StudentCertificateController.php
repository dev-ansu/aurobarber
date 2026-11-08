<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Course;
use App\Models\LmsStudentLessonProgress;
use App\Models\School;
use App\Services\LmsProgressionService;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class StudentCertificateController extends Controller
{
    public function __invoke(Contract $contract, LmsProgressionService $progressionService): View
    {
        $user = Auth::user();
        $isOwnContract = $user && ((int) $user->person_id === (int) $contract->student_id);
        $canManage = $user && ($user->can('View:LmsLesson') || $user->hasRole('super_admin'));

        if (! $isOwnContract && ! $canManage) {
            abort(403, 'Acesso não autorizado ao certificado.');
        }

        // Verifica se a formação foi integralmente concluída (gestores podem visualizar como preview)
        if (! $canManage && ! $progressionService->isFormationCompleted($contract)) {
            abort(403, 'O Certificado Oficial de Conclusão só pode ser emitido após a conclusão integral de todas as aulas da formação.');
        }

        $contract->loadMissing([
            'student.documents',
            'items.formation.courses',
            'items.contractCourses.course',
        ]);

        $school = $contract->resolved_school;
        $schoolRepresentative = collect($school['partners'] ?? [])
            ->first(fn (array $partner): bool => (bool) ($partner['legal_representative'] ?? false))['name'] ?? null;

        // Cursos ordenados da formação
        $orderedCourses = $progressionService->getOrderedCoursesForContract($contract);

        $formations = $contract->items
            ->map(fn ($item) => $item->formation?->name)
            ->filter()
            ->unique()
            ->join(', ');

        $courseTitle = $formations ?: ($orderedCourses->pluck('name')->join(', ') ?: 'Formação Profissional');

        $totalWorkload = (int) $orderedCourses->sum(fn (Course $c) => $c->workload ?? 20);

        // Média de aproveitamento das aulas LMS concluídas
        $progressRecords = LmsStudentLessonProgress::query()
            ->where('contract_id', $contract->id)
            ->where('status', 'completed')
            ->get();

        $averageGrade = $progressRecords->isNotEmpty() ? round((float) $progressRecords->avg('score'), 1) : 100.0;

        $startDate = $contract->start_date ?? $contract->created_at ?? now();
        $completionDate = $progressRecords->max('completed_at') ?? $contract->finished_at ?? now();

        $verifier = app(\App\Services\CertificateVerificationService::class);
        $verificationCode = $verifier->generateContractCode($contract);
        $verificationUrl = url('/validar-certificado/' . $verificationCode);
        $verificationQr = $verifier->generateQrCodeDataUri($verificationUrl);

        // Formata os cursos para a tabela do verso
        $courses = $orderedCourses->map(function (Course $c) use ($contract, $startDate, $completionDate) {
            $lessonIds = $c->lmsModules->flatMap->lessons->pluck('id');
            $avgScore = LmsStudentLessonProgress::query()
                ->where('contract_id', $contract->id)
                ->whereIn('lesson_id', $lessonIds)
                ->where('status', 'completed')
                ->avg('score');

            return (object) [
                'course' => $c,
                'grade' => $avgScore !== null ? round((float) $avgScore, 1) : 100.0,
                'created_at' => Carbon::parse($startDate),
                'completed_at' => Carbon::parse($completionDate),
                'status' => 'completed',
            ];
        });

        return view('contracts.certificate', [
            'contract' => $contract,
            'school' => $school,
            'schoolRepresentative' => $schoolRepresentative,
            'courseTitle' => $courseTitle,
            'selectedCourse' => null,
            'courses' => $courses,
            'totalWorkload' => $totalWorkload,
            'averageGrade' => $averageGrade,
            'startDate' => Carbon::parse($startDate),
            'completionDate' => Carbon::parse($completionDate),
            'verificationCode' => $verificationCode,
            'verificationQr' => $verificationQr,
        ]);
    }

    public function verify(?string $code = null): View
    {
        $inputCode = $code ?: request()->query('code');
        $verifier = app(\App\Services\CertificateVerificationService::class);
        $result = $verifier->verify($inputCode);

        $schoolName = $result['certificate']['school_name'] ?? School::getSchoolName();

        return view('student.certificate-verify', [
            'code' => $result['code'],
            'isValid' => $result['is_valid'],
            'status' => $result['status'],
            'message' => $result['message'] ?? null,
            'typeLabel' => $result['type_label'] ?? null,
            'cert' => $result['certificate'] ?? null,
            'schoolName' => $schoolName,
        ]);
    }
}
