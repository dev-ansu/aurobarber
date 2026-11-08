<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LicenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function __construct(
        protected LicenseService $licenseService
    ) {}

    public function show(): View|RedirectResponse
    {
        $status = $this->licenseService->checkLicense(false);

        // Se a licença estiver ativa, manda para o painel administrativo
        if ($status['active']) {
            return redirect('/admin');
        }

        return view('license.blocked', [
            'status' => $status,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        // Força consulta imediata no Google Sheets
        $status = $this->licenseService->checkLicense(true);

        if ($status['active']) {
            return redirect('/admin')
                ->with('status', 'Licença validada e regularizada com sucesso! Bem-vindo de volta.');
        }

        return redirect()->route('license.blocked')
            ->with('error', $status['message'] ?: 'A licença continua suspensa. Verifique o pagamento com o suporte.');
    }
}

