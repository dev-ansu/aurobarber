<?php

declare(strict_types=1);

namespace App\Http\Controllers\Educator;

use App\Http\Controllers\Controller;
use App\Models\StudentAccessLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class EducatorKioskAutoLoginController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        if ($user->isCollaborator() && ! $user->hasDefinedRole()) {
            abort(403, 'Acesso bloqueado: Este colaborador não possui um papel definido no sistema.');
        }

        if (! $user->isEducator() && ! $user->hasDefinedRole()) {
            abort(403, 'Este usuário não possui privilégios de educador ou papel definido.');
        }

        // Garante as permissões necessárias para o Portal do Aluno e o LMS Player
        Permission::findOrCreate('View:StudentPortal', 'web');
        Permission::findOrCreate('View:LmsPlayer', 'web');
        Permission::findOrCreate('View:LmsLesson', 'web');
        $user->givePermissionTo(['View:StudentPortal', 'View:LmsPlayer', 'View:LmsLesson']);

        // Autentica o educador na sessão web
        Auth::login($user, remember: true);

        $schoolId = $request->query('school_id');
        if (is_numeric($schoolId)) {
            session(['portal_school_id' => (int) $schoolId]);
        } elseif ($firstSchoolId = $user->schools()->first()?->id) {
            session(['portal_school_id' => (int) $firstSchoolId]);
        }

        StudentAccessLog::log('portal_login', "Educador {$user->name} acessou o portal via terminal de autoatendimento (Kiosk)", null, null, $user->person);

        return redirect()->route('student.portal');
    }
}
