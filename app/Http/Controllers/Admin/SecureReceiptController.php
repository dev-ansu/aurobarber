<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Installment;
use App\Models\Payable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SecureReceiptController extends Controller
{
    /**
     * Exibe/baixa com segurança o comprovante anexado a uma parcela de aluno.
     */
    public function installmentAttachment(Request $request, Installment $installment): BinaryFileResponse
    {
        $path = $installment->receipt_path;

        if (blank($path)) {
            abort(404, 'Comprovante não encontrado nesta parcela.');
        }

        return $this->serveProtectedFile($path);
    }

    /**
     * Exibe/baixa com segurança o comprovante anexado a uma conta a pagar.
     */
    public function payableAttachment(Request $request, Payable $payable): BinaryFileResponse
    {
        $path = $payable->receipt_path;

        if (blank($path)) {
            abort(404, 'Comprovante não encontrado nesta conta a pagar.');
        }

        return $this->serveProtectedFile($path);
    }

    /**
     * Resolve o arquivo físico com segurança a partir dos discos local (privado) ou public (fallback).
     */
    protected function serveProtectedFile(string $path): BinaryFileResponse
    {
        $fullPath = null;

        // 1. Procura no disco local privado
        if (Storage::disk('local')->exists($path)) {
            $fullPath = Storage::disk('local')->path($path);
        }
        // 2. Fallback para disco público (arquivos legados enviados antes da blindagem)
        elseif (Storage::disk('public')->exists($path)) {
            $fullPath = Storage::disk('public')->path($path);
        }

        if (! $fullPath || ! file_exists($fullPath)) {
            abort(404, 'O arquivo físico do comprovante não foi localizado no servidor.');
        }

        $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
        $fileName = basename($fullPath);

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
