<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ForceChangePasswordController extends Controller
{
    /**
     * Exibe a tela obrigatória de troca de senha no primeiro acesso.
     */
    public function show(): View | RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect('/admin/login');
        }

        if (! $user->must_change_password) {
            return redirect('/admin');
        }

        return view('auth.force-change-password', [
            'user' => $user,
        ]);
    }

    /**
     * Valida e atualiza a senha do usuário, removendo o flag de obrigatoriedade.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect('/admin/login');
        }

        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'different:current_password',
            ],
        ], [
            'current_password.required' => 'Informe a senha provisória atual.',
            'current_password.current_password' => 'A senha provisória informada está incorreta.',
            'password.required' => 'Informe a sua nova senha pessoal.',
            'password.min' => 'A nova senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação da nova senha não confere.',
            'password.different' => 'A nova senha deve ser diferente da senha provisória.',
        ]);

        $user->forceFill([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ])->save();

        session()->flash('status', 'Sua nova senha foi cadastrada com sucesso! Bem-vindo(a) ao Aurosoft.');

        return redirect('/admin');
    }
}
