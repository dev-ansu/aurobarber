<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Barbershop;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateBarbershopController extends Controller
{
    /**
     * Permite ao Master Admin acessar o painel de qualquer barbearia
     * com 1 clique (para suporte técnico e auditoria) sem precisar de senha.
     */
    public function __invoke(Request $request, Barbershop $barbershop): RedirectResponse
    {
        // 1. Valida se a URL assinada é válida e se não expirou (protegido por middleware 'signed')
        if (! $request->hasValidSignature()) {
            abort(403, 'O link de acesso seguro expirou ou é inválido. Retorne ao SaaS Master e clique em "Acessar Painel" novamente.');
        }

        // 2. Identifica o usuário alvo para suporte:
        // Prefere o dono da barbearia para simular 100% o ambiente real do cliente,
        // ou qualquer usuário com papel owner vinculado à barbearia, ou mantém o master_admin autenticado
        $targetUser = $barbershop->owner;

        if (! $targetUser) {
            $targetUser = $barbershop->users()
                ->where('role', 'owner')
                ->first();
        }

        // Se a barbearia ainda não tiver proprietário cadastrado, busca o master_admin
        if (! $targetUser) {
            $targetUser = User::where('role', 'master_admin')->first();
        }

        if (! $targetUser) {
            abort(404, 'Nenhum usuário qualificado encontrado para autenticar nesta barbearia.');
        }

        // 3. Autentica o usuário na sessão do painel da barbearia
        Auth::login($targetUser, remember: true);
        $request->session()->regenerate();
        $request->session()->put('aurobarber.active_tenant_id', $barbershop->id);
        $request->session()->put('impersonated_by_master_admin', true);

        // 4. Redireciona diretamente para o Dashboard da barbearia
        return redirect()->to(url("/admin/{$barbershop->slug}"));
    }
}
