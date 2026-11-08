<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractCourse;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ContractCertificateController extends Controller
{
    public function __invoke(Contract $contract, Request $request): View
    {
        $this->authorizeContract($contract);

        $contract->loadMissing([
            'student.documents',
            'items.formation',
            'items.contractCourses.course',
        ]);

        $school = $contract->resolved_school;

        $schoolRepresentative = $request->filled('school_representative')
            ? (string) $request->query('school_representative')
            : (collect($school['partners'] ?? [])
                ->first(fn (array $partner): bool => (bool) ($partner['legal_representative'] ?? false))['name'] ?? null);

        // Cursos concluídos ou todos os cursos do contrato
        $courses = $contract->items
            ->flatMap(fn ($item) => $item->contractCourses)
            ->filter(fn ($cc) => $cc->course !== null);

        $formations = $contract->items
            ->map(fn ($item) => $item->formation?->name)
            ->filter()
            ->unique()
            ->join(', ');

        $defaultCourseTitle = $formations ?: ($courses->map(fn ($cc) => $cc->course->name)->unique()->join(', ') ?: 'Curso Profissionalizante');
        $courseTitle = $request->filled('course_title') ? (string) $request->query('course_title') : $defaultCourseTitle;

        // Carga horária total
        $defaultWorkload = $courses->sum(fn ($cc) => (int) ($cc->course->workload ?? 0));
        $totalWorkload = $request->filled('total_workload') ? (int) $request->query('total_workload') : $defaultWorkload;

        // Média de aproveitamento (das notas válidas)
        $grades = $courses->map(fn ($cc) => is_numeric($cc->grade) ? (float) $cc->grade : null)->filter();
        $defaultAverageGrade = $grades->isNotEmpty() ? round($grades->avg(), 1) : null;
        $averageGrade = $request->filled('average_grade') ? (float) $request->query('average_grade') : $defaultAverageGrade;

        // Data de início e conclusão
        $defaultStartDate = $contract->start_date ?? $courses->min('created_at') ?? now();
        $defaultCompletionDate = $contract->finished_at
            ?? $courses->max('completed_at')
            ?? $contract->end_date
            ?? now();

        $startDate = $request->filled('start_date') ? Carbon::parse($request->query('start_date')) : Carbon::parse($defaultStartDate);
        $completionDate = $request->filled('completion_date') ? Carbon::parse($request->query('completion_date')) : Carbon::parse($defaultCompletionDate);

        $bookNumber = $request->filled('book_number') ? (string) $request->query('book_number') : '01';
        $sheetNumber = $request->filled('sheet_number') ? (string) $request->query('sheet_number') : str_pad((string) (($contract->id % 99) + 1), 3, '0', STR_PAD_LEFT);
        $registrationNumber = $request->filled('registration_number') ? (string) $request->query('registration_number') : str_pad((string) $contract->id, 4, '0', STR_PAD_LEFT);
        $customLegalBase = $request->filled('legal_base') ? (string) $request->query('legal_base') : null;

        $verifier = app(\App\Services\CertificateVerificationService::class);
        $verificationCode = $verifier->generateContractCode($contract);
        $verificationUrl = url('/validar-certificado/' . $verificationCode);
        $verificationQr = $verifier->generateQrCodeDataUri($verificationUrl);

        return view('contracts.certificate', [
            'contract' => $contract,
            'school' => $school,
            'schoolRepresentative' => $schoolRepresentative,
            'courseTitle' => $courseTitle,
            'selectedCourse' => null,
            'courses' => $courses,
            'totalWorkload' => $totalWorkload,
            'averageGrade' => $averageGrade,
            'startDate' => $startDate,
            'completionDate' => $completionDate,
            'bookNumber' => $bookNumber,
            'sheetNumber' => $sheetNumber,
            'registrationNumber' => $registrationNumber,
            'customLegalBase' => $customLegalBase,
            'verificationCode' => $verificationCode,
            'verificationQr' => $verificationQr,
        ]);
    }

    public function showCourse(Contract $contract, ContractCourse $contractCourse, Request $request): View
    {
        $this->authorizeContract($contract);

        abort_unless($contractCourse->contractItem?->contract_id === $contract->getKey(), 404);

        $contractCourse->loadMissing(['course', 'contractItem.formation', 'contractItem.contract.student.documents']);
        $contract->loadMissing(['student.documents']);

        $school = $contract->resolved_school;

        $schoolRepresentative = $request->filled('school_representative')
            ? (string) $request->query('school_representative')
            : (collect($school['partners'] ?? [])
                ->first(fn (array $partner): bool => (bool) ($partner['legal_representative'] ?? false))['name'] ?? null);

        $defaultCourseTitle = $contractCourse->course?->name ?? 'Curso Profissionalizante';
        $courseTitle = $request->filled('course_title') ? (string) $request->query('course_title') : $defaultCourseTitle;

        $defaultWorkload = (int) ($contractCourse->course?->workload ?? 0);
        $totalWorkload = $request->filled('total_workload') ? (int) $request->query('total_workload') : $defaultWorkload;

        $defaultAverageGrade = is_numeric($contractCourse->grade) ? (float) $contractCourse->grade : null;
        $averageGrade = $request->filled('average_grade') ? (float) $request->query('average_grade') : $defaultAverageGrade;

        $defaultStartDate = $contractCourse->created_at ?? $contract->start_date ?? now();
        $defaultCompletionDate = $contractCourse->completed_at ?? $contract->finished_at ?? now();

        $startDate = $request->filled('start_date') ? Carbon::parse($request->query('start_date')) : Carbon::parse($defaultStartDate);
        $completionDate = $request->filled('completion_date') ? Carbon::parse($request->query('completion_date')) : Carbon::parse($defaultCompletionDate);

        $bookNumber = $request->filled('book_number') ? (string) $request->query('book_number') : '01';
        $sheetNumber = $request->filled('sheet_number') ? (string) $request->query('sheet_number') : str_pad((string) (($contract->id % 99) + 1), 3, '0', STR_PAD_LEFT);
        $registrationNumber = $request->filled('registration_number') ? (string) $request->query('registration_number') : str_pad((string) $contract->id, 4, '0', STR_PAD_LEFT);
        $customLegalBase = $request->filled('legal_base') ? (string) $request->query('legal_base') : null;

        $verifier = app(\App\Services\CertificateVerificationService::class);
        $verificationCode = $verifier->generateCourseCode($contractCourse, $contract);
        $verificationUrl = url('/validar-certificado/' . $verificationCode);
        $verificationQr = $verifier->generateQrCodeDataUri($verificationUrl);

        return view('contracts.certificate', [
            'contract' => $contract,
            'school' => $school,
            'schoolRepresentative' => $schoolRepresentative,
            'courseTitle' => $courseTitle,
            'selectedCourse' => $contractCourse,
            'courses' => collect([$contractCourse]),
            'totalWorkload' => $totalWorkload,
            'averageGrade' => $averageGrade,
            'startDate' => $startDate,
            'completionDate' => $completionDate,
            'bookNumber' => $bookNumber,
            'sheetNumber' => $sheetNumber,
            'registrationNumber' => $registrationNumber,
            'customLegalBase' => $customLegalBase,
            'verificationCode' => $verificationCode,
            'verificationQr' => $verificationQr,
        ]);
    }

    private function authorizeContract(Contract $contract): void
    {
        $user = auth()->user();
        if ($user) {
            $isStudentOwner = ($user->person_id !== null && (int) $user->person_id === (int) $contract->student_id)
                || ((int) session('portal_contract_id') === (int) $contract->id);
            $isStaff = $user->isStaff() || $user->isEducator() || $user->hasRole('super_admin');
            abort_unless($isStudentOwner || $isStaff, 403, 'Acesso não autorizado ao certificado.');
        }
    }
}
