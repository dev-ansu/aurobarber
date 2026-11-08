<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractCourse;
use App\Models\School;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Carbon;

class CertificateVerificationService
{
    /**
     * Gera o código alfanumérico seguro para o certificado geral da formação
     */
    public function generateContractCode(Contract $contract): string
    {
        $idHex = strtoupper(dechex((int) $contract->id));
        $checksum = strtoupper(substr(hash('sha256', "CERT:{$contract->id}:{$contract->contract_number}:{$contract->student_id}"), 0, 8));

        return "AURO-{$idHex}-{$checksum}";
    }

    /**
     * Gera o código alfanumérico seguro para o mini-certificado de uma disciplina individual
     */
    public function generateCourseCode(ContractCourse $contractCourse, Contract $contract): string
    {
        $idHex = strtoupper(dechex((int) $contractCourse->id));
        $checksum = strtoupper(substr(hash('sha256', "CERT-COURSE:{$contractCourse->id}:{$contract->id}:{$contractCourse->course_id}"), 0, 8));

        return "AURO-C{$idHex}-{$checksum}";
    }

    /**
     * Gera a Data URI do QR Code apontando para a URL pública de validação
     */
    public function generateQrCodeDataUri(string $verificationUrl): string
    {
        if (class_exists(QRCode::class) && class_exists(QROptions::class)) {
            try {
                $options = new QROptions([
                    'outputType' => defined(QRCode::class . '::OUTPUT_MARKUP_SVG') ? constant(QRCode::class . '::OUTPUT_MARKUP_SVG') : 'svg',
                    'outputInterface' => class_exists(QRMarkupSVG::class) ? QRMarkupSVG::class : null,
                    'svgUseFill' => true,
                    'imageBase64' => true,
                    'addQuietzone' => true,
                ]);

                return (new QRCode($options))->render($verificationUrl);
            } catch (\Throwable) {
                // Fallback para serviço online se chillerlan falhar
            }
        }

        $encoded = rawurlencode($verificationUrl);

        return "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data={$encoded}";
    }

    /**
     * Valida um código de certificado e retorna todos os metadados do documento
     *
     * @return array<string, mixed>
     */
    public function verify(?string $code): array
    {
        $code = trim((string) $code);

        if ($code === '') {
            return [
                'is_valid' => false,
                'status' => 'empty',
                'message' => 'Nenhum código fornecido para validação.',
                'code' => null,
                'certificate' => null,
            ];
        }

        $cleanCode = strtoupper($code);

        // 1. Mini-Certificado por Curso: AURO-C{idHex}-{checksum}
        if (preg_match('/^AURO-C([0-9A-F]+)-([0-9A-F]{8})$/i', $cleanCode, $matches)) {
            $courseContractId = (int) hexdec($matches[1]);
            $expectedChecksum = strtoupper($matches[2]);

            $contractCourse = ContractCourse::withoutGlobalScopes()
                ->with([
                    'contractItem' => fn ($q) => $q->withoutGlobalScopes(),
                    'contractItem.contract' => fn ($q) => $q->withoutGlobalScopes()->with(['student', 'school']),
                    'contractItem.formation' => fn ($q) => $q->withoutGlobalScopes(),
                    'course' => fn ($q) => $q->withoutGlobalScopes(),
                ])
                ->find($courseContractId);

            $contract = $contractCourse?->contractItem?->contract;

            if ($contractCourse && $contract) {
                $computedChecksum = strtoupper(substr(hash('sha256', "CERT-COURSE:{$contractCourse->id}:{$contract->id}:{$contractCourse->course_id}"), 0, 8));

                if (hash_equals($computedChecksum, $expectedChecksum)) {
                    return [
                        'is_valid' => true,
                        'status' => 'valid',
                        'type' => 'course',
                        'type_label' => 'Certificado Oficial de Disciplina / Curso',
                        'code' => $cleanCode,
                        'certificate' => $this->formatCourseCertificateData($contractCourse, $contract),
                    ];
                }
            }
        }

        // 2. Certificado Geral da Formação: AURO-{idHex}-{checksum}
        if (preg_match('/^AURO-([0-9A-F]+)-([0-9A-F]{8})$/i', $cleanCode, $matches)) {
            $contractId = (int) hexdec($matches[1]);
            $expectedChecksum = strtoupper($matches[2]);

            $contract = Contract::withoutGlobalScopes()
                ->with([
                    'student',
                    'school',
                    'items' => fn ($q) => $q->withoutGlobalScopes(),
                    'items.formation' => fn ($q) => $q->withoutGlobalScopes(),
                    'items.contractCourses' => fn ($q) => $q->withoutGlobalScopes(),
                    'items.contractCourses.course' => fn ($q) => $q->withoutGlobalScopes(),
                ])
                ->find($contractId);

            if ($contract) {
                $computedChecksum = strtoupper(substr(hash('sha256', "CERT:{$contract->id}:{$contract->contract_number}:{$contract->student_id}"), 0, 8));

                if (hash_equals($computedChecksum, $expectedChecksum)) {
                    return [
                        'is_valid' => true,
                        'status' => 'valid',
                        'type' => 'formation',
                        'type_label' => 'Certificado Oficial de Formação Profissional',
                        'code' => $cleanCode,
                        'certificate' => $this->formatContractCertificateData($contract),
                    ];
                }
            }
        }

        // 3. Fallback para códigos legados (MD5 de 12 dígitos ou SHA256 antigo)
        $legacyContract = Contract::withoutGlobalScopes()
            ->with(['student', 'school', 'items.formation', 'items.contractCourses.course'])
            ->get()
            ->first(function (Contract $c) use ($cleanCode) {
                $md5Code = strtoupper(substr(md5("CERT-{$c->id}-{$c->contract_number}-{$c->student_id}"), 0, 12));
                $shaCode = 'AURO-' . strtoupper(substr(hash('sha256', "CERT:{$c->id}:{$c->contract_number}:{$c->student_id}"), 0, 12));

                return $cleanCode === $md5Code || $cleanCode === $shaCode;
            });

        if ($legacyContract) {
            return [
                'is_valid' => true,
                'status' => 'valid',
                'type' => 'formation',
                'type_label' => 'Certificado Oficial de Formação Profissional',
                'code' => $cleanCode,
                'certificate' => $this->formatContractCertificateData($legacyContract),
            ];
        }

        return [
            'is_valid' => false,
            'status' => 'invalid',
            'message' => 'O código informado não corresponde a nenhum certificado homologado na base de dados.',
            'code' => $cleanCode,
            'certificate' => null,
        ];
    }

    /**
     * Formata os metadados do certificado da formação para exibição
     *
     * @return array<string, mixed>
     */
    private function formatContractCertificateData(Contract $contract): array
    {
        $school = $contract->resolved_school;
        $schoolName = $school['trade_name'] ?? ($school['legal_name'] ?? School::getSchoolName());
        $schoolCnpj = $school['cnpj'] ?? null;
        $schoolCity = $school['city'] ?? 'Fortaleza';
        $schoolState = $school['state'] ?? 'CE';

        $formations = $contract->items
            ->map(fn ($item) => $item->formation?->name)
            ->filter()
            ->unique()
            ->join(', ');

        $courses = $contract->items
            ->flatMap(fn ($item) => $item->contractCourses)
            ->filter(fn ($cc) => $cc->course !== null);

        $courseTitle = $formations ?: ($courses->map(fn ($cc) => $cc->course->name)->unique()->join(', ') ?: 'Formação Profissionalizante');
        $totalWorkload = (int) $courses->sum(fn ($cc) => (int) ($cc->course->workload ?? 20));

        $grades = $courses->map(fn ($cc) => is_numeric($cc->grade) ? (float) $cc->grade : null)->filter();
        $averageGrade = $grades->isNotEmpty() ? round($grades->avg(), 1) : 100.0;

        $startDate = $contract->start_date ?? $contract->created_at ?? now();
        $completionDate = $contract->finished_at ?? $courses->max('completed_at') ?? $contract->end_date ?? now();

        $bookNumber = '01';
        $sheetNumber = str_pad((string) (($contract->id % 99) + 1), 3, '0', STR_PAD_LEFT);
        $registrationNumber = str_pad((string) $contract->id, 4, '0', STR_PAD_LEFT);

        return [
            'student_name' => $contract->student?->name ?? 'Aluno Registrado',
            'student_cpf' => $contract->student?->cpf,
            'student_registration' => $contract->student?->ouro_moderno_cod_int ?: $contract->student_id,
            'title' => $courseTitle,
            'formation_name' => $formations,
            'workload' => $totalWorkload > 0 ? $totalWorkload : 80,
            'average_grade' => $averageGrade,
            'start_date' => Carbon::parse($startDate)->format('d/m/Y'),
            'completion_date' => Carbon::parse($completionDate)->format('d/m/Y'),
            'contract_number' => $contract->contract_number,
            'school_name' => $schoolName,
            'school_cnpj' => $schoolCnpj,
            'school_city' => $schoolCity,
            'school_state' => $schoolState,
            'book_number' => $bookNumber,
            'sheet_number' => $sheetNumber,
            'registration_number' => $registrationNumber,
            'legal_base' => 'Lei nº 9.394/1996 (LDB), Arts. 39 a 42 · Decreto Federal nº 5.154/2004',
            'issued_at' => ($contract->finished_at ? Carbon::parse($contract->finished_at) : now())->format('d/m/Y H:i'),
            'is_formation' => true,
            'courses_count' => $courses->count(),
        ];
    }

    /**
     * Formata os metadados do mini-certificado por curso para exibição
     *
     * @return array<string, mixed>
     */
    private function formatCourseCertificateData(ContractCourse $contractCourse, Contract $contract): array
    {
        $school = $contract->resolved_school;
        $schoolName = $school['trade_name'] ?? ($school['legal_name'] ?? School::getSchoolName());
        $schoolCnpj = $school['cnpj'] ?? null;
        $schoolCity = $school['city'] ?? 'Fortaleza';
        $schoolState = $school['state'] ?? 'CE';

        $course = $contractCourse->course;
        $courseTitle = $course?->name ?? 'Curso Prático de Qualificação';
        $workload = (int) ($course?->workload ?? 20);

        $grade = $contractCourse->grade !== null ? (float) $contractCourse->grade : 100.0;
        $startDate = $contractCourse->created_at ?? $contract->start_date ?? now();
        $completionDate = $contractCourse->completed_at ?? $contractCourse->updated_at ?? now();

        $bookNumber = '01';
        $sheetNumber = str_pad((string) (($contractCourse->id % 99) + 1), 3, '0', STR_PAD_LEFT);
        $registrationNumber = str_pad((string) $contractCourse->id, 4, '0', STR_PAD_LEFT);

        return [
            'student_name' => $contract->student?->name ?? 'Aluno Registrado',
            'student_cpf' => $contract->student?->cpf,
            'student_registration' => $contract->student?->ouro_moderno_cod_int ?: $contract->student_id,
            'title' => $courseTitle,
            'formation_name' => $contractCourse->contractItem?->formation?->name ?? 'Formação Profissional',
            'workload' => $workload > 0 ? $workload : 20,
            'average_grade' => $grade,
            'start_date' => Carbon::parse($startDate)->format('d/m/Y'),
            'completion_date' => Carbon::parse($completionDate)->format('d/m/Y'),
            'contract_number' => $contract->contract_number,
            'school_name' => $schoolName,
            'school_cnpj' => $schoolCnpj,
            'school_city' => $schoolCity,
            'school_state' => $schoolState,
            'book_number' => $bookNumber,
            'sheet_number' => $sheetNumber,
            'registration_number' => $registrationNumber,
            'legal_base' => 'Lei nº 9.394/1996 (LDB), Arts. 39 a 42 · Decreto Federal nº 5.154/2004',
            'issued_at' => Carbon::parse($completionDate)->format('d/m/Y H:i'),
            'is_formation' => false,
            'courses_count' => 1,
        ];
    }
}
