<?php

declare(strict_types=1);

namespace App\Http\Controllers\Educator;

use App\Http\Controllers\Controller;
use App\Models\Collaborator;
use App\Models\Person;
use App\Services\OuroModernoEducatorSyncService;
use App\Services\OuroModernoService;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class EducatorLmsAutoLoginController extends Controller
{
    public function __invoke(OuroModernoService $ouroModernoService, OuroModernoEducatorSyncService $educatorSync): View|Response|RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('student.login')->with('error', 'Por favor, realize login para acessar.');
        }

        if ($user->isCollaborator() && ! $user->hasDefinedRole()) {
            return response()->view('errors.custom-message', [
                'title' => 'Acesso Bloqueado',
                'message' => 'Este colaborador não possui um papel definido no sistema. Solicite à administração da escola.',
                'actionUrl' => route('student.login'),
                'actionLabel' => 'Voltar ao Login',
            ], 403);
        }

        if (! $user->isEducator() && ! $user->hasDefinedRole()) {
            return response()->view('errors.custom-message', [
                'title' => 'Acesso Restrito ao Educador',
                'message' => 'Seu usuário não possui permissão de educador ou professor para acessar os conteúdos do LMS.',
                'actionUrl' => route('student.portal'),
                'actionLabel' => 'Voltar ao Portal do Aluno',
            ], 403);
        }

        $schoolId = request()->query('school_id')
            ?? session('portal_school_id')
            ?? (class_exists(Filament::class) ? Filament::getTenant()?->id : null)
            ?? $user->schools()->first()?->id;
        $schoolId = is_numeric($schoolId) ? (int) $schoolId : null;

        $ouroModernoService = $ouroModernoService->forSchool($schoolId);

        if (! $ouroModernoService->isConfigured()) {
            return response()->view('errors.custom-message', [
                'title' => 'Integração Ouro Moderno Não Configurada',
                'message' => 'A integração com o LMS Ouro Moderno ainda não foi configurada nesta escola. Acesse o painel administrativo em Configurações > Ouro Moderno para configurar.',
                'actionUrl' => route('student.portal'),
                'actionLabel' => 'Voltar ao Portal do Aluno',
            ], 503);
        }

        $person = $user->person;
        if (! $person) {
            $person = Person::create([
                'name' => $user->name,
                'email' => $user->email,
            ]);
            $user->update(['person_id' => $person->id]);
        }

        // Se o educador ainda não foi sincronizado com a Ouro Moderno, tenta sincronizar automaticamente
        if (blank($person->ouro_moderno_usuario) || blank($person->ouro_moderno_senha)) {
            $collaborator = Collaborator::firstOrCreate(
                ['person_id' => $person->id],
                [
                    'admission_date' => now(),
                    'role' => $user->hasRole('super_admin') ? 'Administrador / Coordenador' : 'Educador / Professor',
                    'active' => true,
                    'is_educator' => true,
                ]
            );

            try {
                $educatorSync->sync($collaborator, null, $schoolId);
                $person->refresh();
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $username = trim((string) $person->ouro_moderno_usuario);
        $password = (string) $person->ouro_moderno_senha;

        if ($username === '' || $password === '') {
            return response()->view('errors.custom-message', [
                'title' => 'Credenciais Ouro Moderno Pendentes',
                'message' => 'Não foi possível autenticar automaticamente no servidor da Ouro Moderno. Verifique a conexão com a internet ou acesse o cadastro de colaboradores no painel administrativo para sincronizar este usuário.',
                'actionUrl' => route('student.portal'),
                'actionLabel' => 'Voltar ao Portal do Aluno',
            ], 500);
        }

        $actionUrl = $ouroModernoService->getLoginUrl();

        return view('kiosk.lms-autologin', [
            'actionUrl' => $actionUrl,
            'username' => $username,
            'password' => $password,
            'studentName' => 'Educador: '.$person->name,
        ]);
    }
}
