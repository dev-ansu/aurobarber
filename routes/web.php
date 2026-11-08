<?php

use App\Http\Controllers\Admin\AgendaPrintController;
use App\Http\Controllers\Admin\CashRegisterReportController;
use App\Http\Controllers\Admin\ContractCarnePrintController;
use App\Http\Controllers\Admin\ContractCertificateController;
use App\Http\Controllers\Admin\ContractDocumentController;
use App\Http\Controllers\Admin\ContractPrintController;
use App\Http\Controllers\Admin\DrePrintController;
use App\Http\Controllers\Admin\InstallmentReceiptController;
use App\Http\Controllers\Admin\OuroModernoStudentTranscriptController;
use App\Http\Controllers\Admin\ReceivedInstallmentsPrintController;
use App\Http\Controllers\Educator\EducatorKioskAutoLoginController;
use App\Http\Controllers\Educator\EducatorLmsAutoLoginController;
use App\Http\Controllers\Kiosk\KioskLmsAutoLoginController;
use App\Http\Controllers\Auth\ForceChangePasswordController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\Student\StudentCertificateController;
use App\Http\Controllers\Student\StudentKioskAutoLoginController;
use App\Livewire\Lms\LmsPlayer;
use App\Livewire\Student\StudentLogin;
use App\Livewire\Student\StudentPortal;
use App\Livewire\Student\StudentTypingWarmup;
use App\Models\Contract;
use App\Models\LmsStudentLessonProgress;
use App\Models\StudentAccessLog;
use App\Services\LocalBackupManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/licenca', [LicenseController::class, 'show'])->name('license.blocked');
Route::post('/licenca/verificar', [LicenseController::class, 'verify'])->name('license.verify');

// Autenticação em Duas Etapas (2FA por E-mail)
Route::middleware(['web'])->group(function () {
    Route::get('/auth/2fa', [TwoFactorChallengeController::class, 'show'])->name('auth.2fa.challenge');
    Route::post('/auth/2fa', [TwoFactorChallengeController::class, 'verify'])->name('auth.2fa.verify');
    Route::post('/auth/2fa/reenviar', [TwoFactorChallengeController::class, 'resend'])->name('auth.2fa.resend');
    Route::post('/auth/2fa/cancelar', [TwoFactorChallengeController::class, 'cancel'])->name('auth.2fa.cancel');

    // Troca Obrigatória de Senha no 1º Acesso (Onboarding Seguro)
    Route::get('/auth/trocar-senha', [ForceChangePasswordController::class, 'show'])->name('password.force_change');
    Route::post('/auth/trocar-senha', [ForceChangePasswordController::class, 'update'])->name('password.force_change.update');
});

// Alias nomeado 'login' para compatibilidade com middlewares e rotinas internas
Route::name('login')->get('/auth/login', fn () => redirect('/login'));

Route::get('/redefinir-senha/{token}', function (\Illuminate\Http\Request $request, string $token) {
    $email = $request->query('email');
    $saasPath = env('SAAS_PANEL_PATH', 'saas');
    return redirect("/{$saasPath}/password-reset/reset/{$token}?email=" . urlencode((string) $email));
})->name('password.reset');

// Rotas de demonstração / visualização das telas de erro sem disparar exceções
Route::prefix('/preview-errors')->group(function () {
    Route::get('/', function () {
        return view('errors.preview-index');
    })->name('errors.preview.index');

    Route::get('/403', function () {
        return response()->view('errors.403', [
            'exception' => new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Você não possui permissão para visualizar o relatório financeiro deste contrato.'),
        ]);
    })->name('errors.preview.403');

    Route::get('/404', function () {
        return response()->view('errors.404', [
            'exception' => new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('O aluno ou contrato solicitado não foi localizado no banco de dados.'),
        ]);
    })->name('errors.preview.404');

    Route::get('/500', function () {
        return response()->view('errors.500', [
            'exception' => new \RuntimeException('SQLSTATE[08006] Connection to database server failed: connection timeout.'),
        ]);
    })->name('errors.preview.500');

    Route::get('/419', function () {
        return response()->view('errors.419', [
            'exception' => new \Symfony\Component\HttpKernel\Exception\HttpException(419, 'Sua sessão expirou por inatividade.'),
        ]);
    })->name('errors.preview.419');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/backups/download/{filename}', function (string $filename) {
        abort_unless(
            Auth::user()?->can('Download:BackupManagement')
            || Auth::user()?->hasRole('super_admin'),
            403
        );

        $path = app(LocalBackupManager::class)->pathForDownload($filename);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/zip',
        ]);
    })->name('admin.backups.download');

    Route::get('/saas/backups/download/{filename}', function (string $filename) {
        abort_unless(
            Auth::user()?->hasRole('super_admin'),
            403,
            'Apenas o Super Administrador pode baixar backups do SaaS.'
        );

        $path = app(\App\Services\SaasBackupManager::class)->getDownloadPath($filename);
        abort_unless($path && file_exists($path), 404, 'Arquivo de backup não encontrado.');

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/octet-stream',
        ]);
    })->name('saas.backups.download');

    Route::get('/caixas/{cashRegister}/relatorio', [CashRegisterReportController::class, 'print'])
        ->name('admin.caixa.relatorio');
    Route::get('/agenda/folha-presenca', [AgendaPrintController::class, '__invoke'])
        ->name('admin.agenda.folha-presenca');
    Route::get('/relatorios/dre/print', [DrePrintController::class, '__invoke'])
        ->name('admin.dre.print');
    Route::get('/relatorios/contas-recebidas/print', [ReceivedInstallmentsPrintController::class, '__invoke'])
        ->name('admin.contas-recebidas.print');

    Route::get('/contracts/{contract}/print', [ContractPrintController::class, '__invoke'])->name('contracts.print');
    Route::get('/contracts/{contract}/carne', [ContractCarnePrintController::class, '__invoke'])->name('contracts.carne');
    Route::get('/contracts/{contract}/ouro-moderno-transcript', OuroModernoStudentTranscriptController::class)
        ->name('contracts.ouro-moderno-transcript');
    Route::get('/contracts/{contract}/ouro-moderno-transcript/course/{contractCourse}', [OuroModernoStudentTranscriptController::class, 'showCourse'])
        ->name('contracts.ouro-moderno-transcript.course');
    Route::get('/contracts/{contract}/certificate', [ContractCertificateController::class, '__invoke'])
        ->name('contracts.certificate');
    Route::get('/contracts/{contract}/certificate/course/{contractCourse}', [ContractCertificateController::class, 'showCourse'])
        ->name('contracts.certificate.course');
    Route::get('/contracts/{contract}/declaracao-matricula', [ContractDocumentController::class, 'declaracaoMatricula'])
        ->name('contracts.declaracao-matricula');
    Route::get('/contracts/{contract}/declaracao-quitacao', [ContractDocumentController::class, 'declaracaoQuitacao'])
        ->name('contracts.declaracao-quitacao');
    Route::get('/contracts/{contract}/ficha-matricula', [ContractDocumentController::class, 'fichaMatricula'])
        ->name('contracts.ficha-matricula');
    Route::get('/contracts/{contract}/relatorio-frequencia', [ContractDocumentController::class, 'relatorioFrequencia'])
        ->name('contracts.attendance-report');
    Route::get('/contracts/{contract}/termo-acordo/{debtAgreement?}', [ContractDocumentController::class, 'termoAcordo'])
        ->name('contracts.termo-acordo');
    Route::get('/contracts/{contract}/declaracao-comparecimento', [ContractDocumentController::class, 'declaracaoComparecimento'])
        ->name('contracts.declaracao-comparecimento');
    Route::get('/contracts/{contract}/termo-cancelamento', [ContractDocumentController::class, 'termoCancelamento'])
        ->name('contracts.termo-cancelamento');
    Route::get('/contracts/{contract}/termo-trancamento', [ContractDocumentController::class, 'termoTrancamento'])
        ->name('contracts.termo-trancamento');
    Route::get('/contracts/{contract}/autorizacao-imagem', [ContractDocumentController::class, 'autorizacaoImagem'])
        ->name('contracts.autorizacao-imagem');
    Route::get('/contracts/{contract}/carteirinha-estudantil', [ContractDocumentController::class, 'carteirinhaEstudantil'])
        ->name('contracts.carteirinha-estudantil');
    Route::get('/installments/{installment}/receipt', [InstallmentReceiptController::class, '__invoke'])
        ->name('installments.receipt');
    Route::get('/installments/{installment}/attachment', [\App\Http\Controllers\Admin\SecureReceiptController::class, 'installmentAttachment'])
        ->name('installments.attachment');
    Route::get('/payables/{payable}/attachment', [\App\Http\Controllers\Admin\SecureReceiptController::class, 'payableAttachment'])
        ->name('payables.attachment');

    Route::get('/lms/player/{lesson}', LmsPlayer::class)
        ->name('lms.player');

    Route::get('/aluno', StudentPortal::class)
        ->name('student.portal');

    Route::get('/aluno/digitacao', StudentTypingWarmup::class)
        ->name('student.typing');

    Route::get('/aluno/certificado/{contract}', StudentCertificateController::class)
        ->name('student.certificate');

    Route::get('/aluno/boletim/{contract?}', OuroModernoStudentTranscriptController::class)
        ->name('student.boletim');
});

Route::get('/validar-certificado/{code?}', [StudentCertificateController::class, 'verify'])
    ->name('student.certificate.verify');
Route::get('/validar/{code?}', [StudentCertificateController::class, 'verify'])
    ->name('student.certificate.verify.alias');

Route::get('/aluno/login', StudentLogin::class)
    ->name('student.login');

Route::post('/aluno/logout', function () {
    $user = Auth::user();
    if ($user) {
        $contract = null;
        if ($user->person_id) {
            $contract = Contract::where('student_id', $user->person_id)->where('status', 'active')->first();
        }
        StudentAccessLog::log('portal_logout', 'Aluno realizou logout do portal', $contract, null, $user->person);

        if ($contract) {
            LmsStudentLessonProgress::query()
                ->where('contract_id', $contract->id)
                ->where('status', 'in_progress')
                ->whereNull('exited_at')
                ->update(['exited_at' => now()]);
        }
    }

    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('student.login');
})->name('student.logout');

Route::post('/lms/player/beacon-exit/{progress}', function (LmsStudentLessonProgress $progress) {
    $progress->update([
        'exited_at' => now(),
    ]);

    StudentAccessLog::log(
        'lesson_exit',
        "Aluno saiu da tela da aula '{$progress->lesson?->title}'",
        $progress->contract,
        $progress->lesson_id,
        $progress->contract?->student
    );

    return response()->noContent();
})->name('lms.player.beacon-exit');

Route::get('/presenca/lms-autologin/{contract}', KioskLmsAutoLoginController::class)
    ->name('kiosk.lms-autologin');

Route::get('/aluno/kiosk-autologin/{contract}', StudentKioskAutoLoginController::class)
    ->name('student.kiosk-autologin');

Route::get('/presenca/educador-autologin/{user}', EducatorKioskAutoLoginController::class)
    ->name('educator.kiosk-autologin');

Route::get('/educador/lms-autologin', EducatorLmsAutoLoginController::class)
    ->middleware(['auth'])
    ->name('educator.lms-autologin');

Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');
    if (file_exists($path)) {
        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }
    abort(404);
});

Route::get('/storage/{path}', function (string $path) {
    $disk = \Illuminate\Support\Facades\Storage::disk('public');
    if (! $disk->exists($path)) {
        abort(404);
    }

    return response()->file($disk->path($path));
})->where('path', '.*')->name('storage.local');

// Rotas do Ambiente de Demonstração (Schema 'demo' isolado no PostgreSQL)
Route::prefix('/demo')->group(function () {
    Route::get('/', function () {
        \App\Services\PortalTrafficTracker::log(request(), 'aurosoft_landing', 'cta_demo', null, null, 'Ambiente Demo');
        $saasDomain = config('app.saas_domain', env('SAAS_DOMAIN', 'aurosoft.com.br'));
        if (env('SAAS_ENABLED', false) && ! str_starts_with(request()->getHost(), 'demo.')) {
            return redirect("https://demo.{$saasDomain}/admin");
        }
        session(['active_tenant_schema' => 'demo']);
        return redirect('/admin');
    })->name('demo.index');

    Route::get('/entrar', function () {
        \App\Services\PortalTrafficTracker::log(request(), 'aurosoft_landing', 'cta_demo', null, null, 'Entrar Demo');
        $saasDomain = config('app.saas_domain', env('SAAS_DOMAIN', 'aurosoft.com.br'));
        if (env('SAAS_ENABLED', false) && ! str_starts_with(request()->getHost(), 'demo.')) {
            return redirect("https://demo.{$saasDomain}/admin/login");
        }
        session(['active_tenant_schema' => 'demo']);
        return redirect('/admin/login')->with('demo_credentials_hint', true);
    })->name('demo.enter');

    Route::get('/sair', function () {
        session()->forget('active_tenant_schema');
        \Illuminate\Support\Facades\Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('demo.sair');
});

Route::post('/solicitar-demonstracao', function (\Illuminate\Http\Request $request) {
    // Honeypot anti-spam
    if ($request->filled('website_url_check')) {
        return back()->with('contact_success', true);
    }

    $validated = $request->validate([
        'name' => [
            'required',
            'string',
            'min:3',
            'max:120',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $clean = mb_strtolower(trim((string) $value));
                if (in_array($clean, ['teste', 'test', 'asdf', 'xxx', '123', 'fake', 'admin'], true)) {
                    $fail('Por favor, informe seu nome real.');
                }
            },
        ],
        'school_name' => [
            'required',
            'string',
            'min:3',
            'max:160',
            function (string $attribute, mixed $value, \Closure $fail): void {
                $clean = mb_strtolower(trim((string) $value));
                if (in_array($clean, ['teste', 'test', 'asdf', 'xxx', 'fake'], true)) {
                    $fail('Por favor, informe o nome real da sua instituição.');
                }
            },
        ],
        'email' => ['required', 'email:rfc', 'max:160', new \App\Rules\ValidEmailDomain()],
        'phone' => ['required', 'string', 'max:30', new \App\Rules\ValidBrazilianPhone()],
        'city' => ['nullable', 'string', 'max:100'],
        'students_count' => ['nullable', 'string', 'max:50'],
        'message' => ['nullable', 'string', 'max:1000'],
    ]);

    // Formata o telefone de forma padronizada para o WhatsApp
    $digits = preg_replace('/\D/', '', $validated['phone']);
    if (strlen($digits) === 11) {
        $validated['phone'] = '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 5) . '-' . substr($digits, 7);
    } elseif (strlen($digits) === 10) {
        $validated['phone'] = '(' . substr($digits, 0, 2) . ') ' . substr($digits, 2, 4) . '-' . substr($digits, 6);
    }

    $validated['ip'] = $request->ip();

    // 1. Salva no banco de dados para consulta no Painel SaaS Master
    try {
        \App\Models\LandingLead::create($validated);
        \App\Services\PortalTrafficTracker::log($request, 'aurosoft_landing', 'lead_submit', null, null, 'Demonstração Solicitada');
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Erro ao salvar lead no banco: ' . $e->getMessage());
    }

    // 2. Dispara e-mail de alerta para o administrador
    $adminEmail = env('ADMIN_NOTIFICATION_EMAIL', 'dev.andersondesouzafeitosa@gmail.com');

    try {
        \Illuminate\Support\Facades\Mail::to($adminEmail)->send(new \App\Mail\LeadContactMail($validated));
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Erro ao enviar e-mail de lead Aurosoft: ' . $e->getMessage());
    }

    if ($request->expectsJson()) {
        return response()->json([
            'success' => true,
            'message' => 'Solicitação recebida com sucesso! Em breve entraremos em contato.',
        ]);
    }

    return back()->with('contact_success', true);
})->name('landing.lead.store');

$pwaManifestHandler = function (\Illuminate\Http\Request $request) {
    $panel = $request->query('panel');
    $host = strtolower($request->getHost());

    $name = 'Aurosoft - Gestão Escolar';
    $shortName = 'Aurosoft';
    $scope = '/admin/';
    $startUrl = '/admin';
    $id = '/school-admin-app';
    $themeColor = '#f59e0b';

    if ($panel === 'saas' || str_starts_with($host, 'master.')) {
        $name = 'Aurosoft SaaS Master';
        $shortName = 'SaaS Master';
        $scope = '/';
        $startUrl = '/';
        $id = '/saas-master-app';
        $themeColor = '#f59e0b';
    } elseif ($panel === 'student' || $request->is('portal-aluno*')) {
        $name = 'Aurosoft - Portal do Aluno';
        $shortName = 'Portal Aluno';
        $scope = '/portal-aluno/';
        $startUrl = '/portal-aluno';
        $id = '/student-portal-app';
        $themeColor = '#6366f1';
    }

    $manifest = [
        'name' => $name,
        'short_name' => $shortName,
        'description' => 'Sistema Integrado de Gestão Escolar e LMS',
        'start_url' => $startUrl,
        'scope' => $scope,
        'id' => $id,
        'display' => 'standalone',
        'orientation' => 'any',
        'background_color' => '#0f172a',
        'theme_color' => $themeColor,
        'icons' => [
            [
                'src' => '/icons/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/icons/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any',
            ],
            [
                'src' => '/icons/icon-maskable-192.png',
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
            [
                'src' => '/icons/icon-maskable-512.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
            [
                'src' => '/icons/icon.svg',
                'sizes' => 'any',
                'type' => 'image/svg+xml',
            ],
        ],
    ];

    return response()->json($manifest, 200, [
        'Content-Type' => 'application/manifest+json',
        'Cache-Control' => 'no-cache, private',
    ]);
};

Route::get('/pwa/manifest.webmanifest', $pwaManifestHandler)->name('pwa.manifest');
Route::get('/manifest.webmanifest', $pwaManifestHandler);
Route::get('/manifest.json', $pwaManifestHandler);

Route::get('/termos', fn () => view('legal.terms'))->name('terms');
Route::get('/privacidade', fn () => view('legal.privacy'))->name('privacy');

// Rotas da Vitrine Pública / Mini-site da Escola
Route::get('/vitrine', [\App\Http\Controllers\SchoolShowcaseController::class, 'show'])->name('school.showcase');
Route::get('/cursos', [\App\Http\Controllers\SchoolShowcaseController::class, 'show'])->name('school.courses');
Route::get('/vitrine/{school_id}', [\App\Http\Controllers\SchoolShowcaseController::class, 'show'])->name('school.showcase.unit');
Route::get('/formacao/{formation}', [\App\Http\Controllers\SchoolShowcaseController::class, 'showFormation'])->name('school.formation.detail');
Route::get('/cursos/{formation}', [\App\Http\Controllers\SchoolShowcaseController::class, 'showFormation'])->name('school.formation.detail.alias');
Route::post('/vitrine/lead', [\App\Http\Controllers\SchoolShowcaseController::class, 'storeLead'])->name('school.showcase.lead');

Route::get('/', function () {
    $host = strtolower(trim(explode(':', request()->getHost())[0]));

    // Se o acesso for através de um subdomínio de escola cliente ou demo
    if (app()->bound('current_tenant_license') || str_starts_with($host, 'demo.')) {
        $school = \App\Models\School::getHeadquarters() ?? \App\Models\School::first();

        // 1. Se a vitrine pública estiver ativada, sempre exibe o mini-site/vitrine
        if ($school && ($school->showcase_enabled ?? true)) {
            return app(\App\Http\Controllers\SchoolShowcaseController::class)->show(request());
        }

        // 2. Se a vitrine estiver desativada e houver usuário autenticado
        if (\Illuminate\Support\Facades\Auth::check()) {
            $user = \Illuminate\Support\Facades\Auth::user();
            if (session('portal_auth_type') === 'student_contract' || ($user && ! $user->panel_access)) {
                return redirect()->route('student.portal');
            }
            if ($school) {
                return redirect('/' . $school->id);
            }
        }

        return redirect('/login');
    }

    // Registra visita na landing page institucional do Aurosoft
    \App\Services\PortalTrafficTracker::log(request(), 'aurosoft_landing', 'page_view', null, null, 'Página Inicial Aurosoft');

    return view('welcome');
})->name('home');

// Redirecionamento legado de /admin para rotas com tenant da escola (fallback)
Route::any('/admin/{any?}', function ($any = null) {
    $host = strtolower(trim(explode(':', request()->getHost())[0]));
    if (str_starts_with($host, 'master.') || $host === 'master.aurosoft.com.br') {
        return redirect('/login');
    }

    $schoolId = session('filament_tenant_id')
        ?? \App\Models\School::getActiveSchool()?->id
        ?? \App\Models\School::getHeadquarters()?->id
        ?? 1;

    $suffix = $any ? '/' . ltrim((string) $any, '/') : '';

    return redirect('/' . $schoolId . $suffix);
})->where('any', '.*');
