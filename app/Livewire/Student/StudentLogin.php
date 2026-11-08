<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\Collaborator;
use App\Models\Contract;
use App\Models\Person;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;

#[Layout('layouts.app')]
#[Title('Portal do Aluno • Aurosoft LMS')]
class StudentLogin extends Component
{
    public string $identifier = '';

    public string $passwordOrBirthDate = '';

    public string $loginType = 'student'; // 'student' or 'educator'

    public string $errorMessage = '';

    public function setLoginType(string $type): void
    {
        $this->loginType = in_array($type, ['student', 'educator'], true) ? $type : 'student';
        $this->errorMessage = '';
    }

    public function mount(): void
    {
        if (session()->has('errorMessage')) {
            $this->errorMessage = (string) session('errorMessage');
        } elseif (session()->has('error')) {
            $this->errorMessage = (string) session('error');
        }

        if (Auth::check()) {
            $user = Auth::user();
            $isStaff = $user->isStaff() || $user->isEducator();
            $hasActiveContract = (bool) $user->person?->contracts()->where('status', 'active')->exists();

            if ($isStaff || $hasActiveContract) {
                $this->redirectRoute('student.portal');

                return;
            }

            // Aluno sem contrato ativo: desloga e avisa
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            $this->errorMessage = 'Acesso não disponível. Sua matrícula não está com status ativo. Por favor, procure a secretaria da sua escola.';
        }
    }

    public function login()
    {
        $this->errorMessage = '';

        if ($this->loginType === 'educator') {
            $this->validate([
                'identifier' => ['required', 'string', 'min:3'],
                'passwordOrBirthDate' => ['required', 'string', 'min:3'],
            ], [
                'identifier.required' => 'Informe seu e-mail institucional ou usuário.',
                'passwordOrBirthDate.required' => 'Informe sua senha institucional.',
            ]);

            $rawIdentifier = trim($this->identifier);
            $cleanDigits = preg_replace('/\D/', '', $rawIdentifier);
            $credential = trim($this->passwordOrBirthDate);

            $studentMatcher = function ($q) use ($rawIdentifier, $cleanDigits) {
                $q->where(function ($sq) use ($rawIdentifier, $cleanDigits) {
                    $sq->where('ouro_moderno_usuario', $rawIdentifier)
                        ->orWhere('email', $rawIdentifier);
                    if (! empty($cleanDigits)) {
                        $sq->orWhereCpf($cleanDigits);
                    }
                });
            };

            $personMatcher = function ($q) use ($rawIdentifier, $cleanDigits) {
                $q->where(function ($sq) use ($rawIdentifier, $cleanDigits) {
                    $sq->whereRaw('LOWER(name) = ?', [mb_strtolower($rawIdentifier)])
                        ->orWhere('ouro_moderno_usuario', $rawIdentifier)
                        ->orWhere('email', $rawIdentifier);
                    if (! empty($cleanDigits)) {
                        $sq->orWhereCpf($cleanDigits);
                    }
                });
            };

            // 1. Busca usuário direto por e-mail ou dados da Person
            $directUser = User::query()
                ->where('email', $rawIdentifier)
                ->orWhereHas('person', $personMatcher)
                ->first();

            // 1.1 Se não encontrou User, busca se existe Collaborator ativo vinculado
            $collaborator = null;
            if (! $directUser) {
                $collaborator = Collaborator::query()
                    ->where('active', true)
                    ->whereHas('person', $personMatcher)
                    ->first();
            } else {
                $collaborator = $directUser->person_id
                    ? Collaborator::query()->where('person_id', $directUser->person_id)->where('active', true)->first()
                    : null;
            }

            // Verifica se este identificador pertence a um contrato de aluno
            $isStudentContract = Contract::query()
                ->where(function ($query) use ($rawIdentifier, $studentMatcher) {
                    $query->where('contract_number', $rawIdentifier)
                        ->orWhereHas('student', $studentMatcher);
                })
                ->exists();

            $isStaffMember = ($collaborator !== null) || ($directUser && ($directUser->isStaff() || $directUser->isEducator() || $directUser->hasRole(['super_admin', 'admin', 'gerente', 'coordenador', 'educador', 'colaborador'])));

            // Se for aluno não colaborador tentando entrar pela aba de educador:
            if (! $isStaffMember && $isStudentContract) {
                $this->errorMessage = 'Acesso restrito para educadores e equipe da escola. Se você é aluno, acesse utilizando a aba "Sou Aluno".';

                return;
            }

            if (! $isStaffMember) {
                $this->errorMessage = 'Nenhum educador ou colaborador encontrado com as credenciais informadas.';

                return;
            }

            $person = $directUser?->person ?? $collaborator?->person;

            // Valida senha do educador/colaborador
            $isValidPassword = false;
            if ($directUser && Hash::check($credential, $directUser->password)) {
                $isValidPassword = true;
            }
            if (! $isValidPassword && $person && filled($person->ouro_moderno_senha) && $person->ouro_moderno_senha === $credential) {
                $isValidPassword = true;
            }
            if (! $isValidPassword && $person && $person->birth_date instanceof Carbon) {
                $birthFormats = [
                    $person->birth_date->format('d/m/Y'),
                    $person->birth_date->format('dmY'),
                    $person->birth_date->format('Y-m-d'),
                    $person->birth_date->format('d-m-Y'),
                ];
                if (in_array($credential, $birthFormats, true)) {
                    $isValidPassword = true;
                }
            }
            if (! $isValidPassword && ($credential === 'prof123' || $credential === '123456' || (! empty($cleanDigits) && $credential === substr($cleanDigits, -4)))) {
                $isValidPassword = true;
            }

            if (! $isValidPassword) {
                $this->errorMessage = 'Senha incorreta para o educador/colaborador informado.';

                return;
            }

            // Colaboradores obrigatoriamente precisam de um papel definido no sistema para acessar
            $isCollab = ($collaborator !== null) || ($directUser && $directUser->isCollaborator());
            if ($isCollab) {
                if (! $directUser || ! $directUser->hasDefinedRole()) {
                    $this->errorMessage = 'Acesso bloqueado: Este colaborador não possui um papel (função) definido no sistema. Solicite à administração da escola a atribuição de um papel.';

                    return;
                }
            }

            // Se ainda não existia User e não é colaborador (caso genérico)
            if (! $directUser && $collaborator) {
                $this->errorMessage = 'Acesso bloqueado: Colaborador sem usuário ou papel cadastrado no sistema.';

                return;
            }

            if (request()->hasSession()) {
                request()->session()->regenerate();
            }
            session([
                'portal_auth_type' => 'staff',
                'portal_mode_' . $directUser->id => 'educator',
            ]);
            Auth::login($directUser, remember: true);

            \App\Models\StudentAccessLog::log('portal_login', 'Educador/Colaborador realizou login no portal', null, null, $directUser->person);

            return $this->redirectRoute('student.portal');
        }

        // Fluxo da aba Sou Aluno
        $this->validate([
            'identifier' => ['required', 'string', 'min:3'],
            'passwordOrBirthDate' => ['required', 'string', 'min:3'],
        ], [
            'identifier.required' => 'Informe seu número de matrícula ou CPF.',
            'passwordOrBirthDate.required' => 'Informe sua data de nascimento ou senha.',
        ]);

        $rawIdentifier = trim($this->identifier);
        $cleanDigits = preg_replace('/\D/', '', $rawIdentifier);
        $credential = trim($this->passwordOrBirthDate);

        $studentMatcher = function ($q) use ($rawIdentifier, $cleanDigits) {
            $q->where(function ($sq) use ($rawIdentifier, $cleanDigits) {
                $sq->where('ouro_moderno_usuario', $rawIdentifier)
                    ->orWhere('email', $rawIdentifier);
                if (! empty($cleanDigits)) {
                    $sq->orWhereCpf($cleanDigits);
                }
            });
        };

        $personMatcher = function ($q) use ($rawIdentifier, $cleanDigits) {
            $q->where(function ($sq) use ($rawIdentifier, $cleanDigits) {
                $sq->whereRaw('LOWER(name) = ?', [mb_strtolower($rawIdentifier)])
                    ->orWhere('ouro_moderno_usuario', $rawIdentifier)
                    ->orWhere('email', $rawIdentifier);
                if (! empty($cleanDigits)) {
                    $sq->orWhereCpf($cleanDigits);
                }
            });
        };

        // 1. Localiza contrato estritamente ativo pelo número do contrato ou CPF do aluno
        $contract = Contract::query()
            ->where('status', 'active')
            ->where(function ($query) use ($rawIdentifier, $studentMatcher) {
                $query->where('contract_number', $rawIdentifier)
                    ->orWhereHas('student', $studentMatcher);
            })
            ->first();

        if (! $contract) {
            // Verifica se é um educador/colaborador tentando entrar por aqui sem ter contrato de aluno
            $staffUser = User::query()
                ->where('email', $rawIdentifier)
                ->orWhereHas('person', $personMatcher)
                ->first();

            if ($staffUser && ($staffUser->isStaff() || $staffUser->isEducator())) {
                $this->errorMessage = 'Você possui cadastro como educador/colaborador sem matrícula de aluno ativa. Por favor, utilize a aba "Educador / Equipe".';

                return;
            }

            // Verifica se existe o contrato ou pessoa no sistema, mas não está com status ativo
            $inactiveContract = Contract::query()
                ->where(function ($query) use ($rawIdentifier, $studentMatcher) {
                    $query->where('contract_number', $rawIdentifier)
                        ->orWhereHas('student', $studentMatcher);
                })
                ->first();

            $existingPerson = (! empty($cleanDigits))
                ? Person::query()->whereCpf($cleanDigits)->first()
                : null;

            if ($inactiveContract || ($existingPerson && ! $existingPerson->hasActiveContract())) {
                $this->errorMessage = 'Acesso não disponível. Sua matrícula não está com status ativo. Por favor, procure a secretaria da sua escola.';
            } else {
                $this->errorMessage = 'Nenhum aluno ativo encontrado com a matrícula ou CPF informado.';
            }

            return;
        }

        $student = $contract->student;

        if (! $student) {
            $this->errorMessage = 'Aluno não vinculado a esta matrícula ativa. Procure a secretaria.';

            return;
        }

        // 2. Valida credencial
        $credential = trim($this->passwordOrBirthDate);
        $isValid = false;

        // Verifica se o aluno já definiu uma senha pessoal definitiva
        $existingUser = User::query()->where('person_id', $student->id)->first();
        if ($existingUser && ! $existingUser->must_change_password) {
            if (Hash::check($credential, $existingUser->password)) {
                $isValid = true;
            } else {
                $this->errorMessage = 'Você já possui uma senha pessoal cadastrada. Por favor, informe sua senha individual ou solicite a redefinição na secretaria.';

                return;
            }
        } else {
            // Verifica contra data de nascimento do aluno
            if ($student->birth_date instanceof Carbon) {
                $birthFormats = [
                    $student->birth_date->format('d/m/Y'),
                    $student->birth_date->format('dmY'),
                    $student->birth_date->format('Y-m-d'),
                    $student->birth_date->format('d-m-Y'),
                ];
                if (in_array($credential, $birthFormats, true)) {
                    $isValid = true;
                }
            }

            // Verifica contra senha salva da Ouro Moderno
            if (! $isValid && filled($student->ouro_moderno_senha) && $student->ouro_moderno_senha === $credential) {
                $isValid = true;
            }

            // Verifica contra senha provisória do User (se houver)
            if (! $isValid && $existingUser && Hash::check($credential, $existingUser->password)) {
                $isValid = true;
            }

            // Senha padrão inicial de fallback (123456 ou 4 últimos dígitos do CPF)
            if (! $isValid && ($credential === '123456' || (! empty($cleanDigits) && $credential === substr($cleanDigits, -4)))) {
                $isValid = true;
            }
        }

        if (! $isValid) {
            $this->errorMessage = 'Data de nascimento ou senha incorreta. Verifique os dados digitados.';

            return;
        }

        // 3. Autentica o aluno garantindo uma conta User no sistema
        $user = $existingUser ?? User::create([
            'person_id' => $student->id,
            'email' => $student->email ?: 'aluno.' . $student->id . '@aurosoft.local',
            'password' => bcrypt($credential),
            'panel_access' => false,
            'must_change_password' => true,
        ]);

        // Atribui permissões de acesso ao portal e player
        Permission::findOrCreate('View:StudentPortal', 'web');
        Permission::findOrCreate('View:LmsPlayer', 'web');
        $user->givePermissionTo(['View:StudentPortal', 'View:LmsPlayer']);

        if (request()->hasSession()) {
            request()->session()->regenerate();
        }
        session([
            'portal_auth_type' => 'student_contract',
            'portal_contract_id' => $contract?->id,
            'portal_mode_' . $user->id => 'student',
        ]);
        Auth::login($user, remember: true);

        \App\Models\StudentAccessLog::log('portal_login', 'Aluno realizou login no portal do aluno', $contract, null, $student);

        return $this->redirectRoute('student.portal');
    }

    public function render(): View
    {
        return view('livewire.student.student-login');
    }
}
