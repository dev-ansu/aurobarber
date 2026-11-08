<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\StudentAccessLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class StudentKioskAutoLoginController extends Controller
{
    public function __invoke(Request $request, int|string $contract): RedirectResponse
    {
        $contract = Contract::withoutGlobalScopes()
            ->with(['student', 'school'])
            ->findOrFail($contract);

        if ($contract->status !== 'active') {
            return redirect()->route('student.login')->with('errorMessage', 'Acesso não disponível. Sua matrícula não está com status ativo. Por favor, procure a secretaria da sua escola.');
        }

        $student = $contract->student;
        if (! $student) {
            abort(404, 'Aluno não encontrado para este contrato.');
        }

        // Obtém ou cria a conta User para o aluno
        $user = User::query()->where('person_id', $student->id)->first();

        if (! $user) {
            $user = User::create([
                'person_id' => $student->id,
                'email' => $student->email ?: 'aluno.'.$student->id.'@aurosoft.local',
                'password' => bcrypt(Str::random(32)),
                'panel_access' => false,
            ]);
        }

        // Garante as permissões necessárias para o Portal do Aluno e o LMS Player
        Permission::findOrCreate('View:StudentPortal', 'web');
        Permission::findOrCreate('View:LmsPlayer', 'web');
        $user->givePermissionTo(['View:StudentPortal', 'View:LmsPlayer']);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }
        session([
            'portal_auth_type' => 'student_contract',
            'portal_contract_id' => $contract->id,
            'portal_school_id' => $contract->school_id,
        ]);

        // Autentica o aluno na sessão web
        Auth::login($user, remember: true);

        StudentAccessLog::log('portal_login', 'Aluno acessou o portal via terminal de autoatendimento (Kiosk)', $contract, null, $student);

        return redirect()->route('student.portal');
    }
}
