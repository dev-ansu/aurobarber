<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\SaasPaymentSubmission;
use App\Services\SaasPaymentProofStorage;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaasPaymentProofController extends Controller
{
    public function show(Request $request, SaasPaymentSubmission $submission): BinaryFileResponse
    {
        $user = $request->user();
        abort_unless($user && $user->role === 'master_admin', 403);

        $proofPath = app(SaasPaymentProofStorage::class)->resolve($submission);
        abort_if(
            $proofPath === null,
            404,
            'O registro do comprovante existe, mas o arquivo não foi encontrado no armazenamento. Recuse este comprovante no painel SaaS e peça ao cliente para enviá-lo novamente.',
        );

        return response()->file($proofPath);
    }
}
