<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SaasPaymentSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class SaasPaymentProofStorage
{
    public function store(UploadedFile $file, int $barbershopId): string
    {
        $path = $file->store("payment-proofs/{$barbershopId}", 'saas_private');

        if (! is_string($path) || blank($path) || ! Storage::disk('saas_private')->exists($path)) {
            throw new RuntimeException('Não foi possível confirmar a gravação do comprovante no armazenamento privado do SaaS.');
        }

        return $path;
    }

    public function delete(string $path): void
    {
        Storage::disk('saas_private')->delete($path);
    }

    public function resolve(SaasPaymentSubmission $submission): ?string
    {
        $relativePath = $submission->proof_path;
        if (! is_string($relativePath) || blank($relativePath) || str_starts_with($relativePath, '/') || str_starts_with($relativePath, '\\')) {
            return null;
        }

        if (in_array('..', explode('/', str_replace('\\', '/', $relativePath)), true)) {
            return null;
        }

        $candidate = Storage::disk('saas_private')->path($relativePath);
        $resolved = realpath($candidate);

        if ($resolved !== false && is_file($resolved) && is_readable($resolved)) {
            return $resolved;
        }

        return null;
    }

    public function exists(SaasPaymentSubmission $submission): bool
    {
        return $this->resolve($submission) !== null;
    }
}
