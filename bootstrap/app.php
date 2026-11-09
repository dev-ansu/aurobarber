<?php

use App\Http\Middleware\EnsureLivewireChildrenMemoExists;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Exceptions\PayloadTooLargeException;
use Livewire\Mechanisms\HandleComponents\CorruptComponentPayloadException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

if (! getenv('LARAVEL_ENV_ENCRYPTION_KEY') && empty($_ENV['LARAVEL_ENV_ENCRYPTION_KEY'])) {
    if (PHP_OS_FAMILY === 'Windows') {
        $regOutput = (string) @shell_exec('reg query "HKCU\Environment" /v LARAVEL_ENV_ENCRYPTION_KEY 2>nul');
        if ($regOutput === '') {
            $regOutput = (string) @shell_exec('reg query "HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Environment" /v LARAVEL_ENV_ENCRYPTION_KEY 2>nul');
        }
        if (preg_match('/LARAVEL_ENV_ENCRYPTION_KEY\s+REG_SZ\s+(\S+)/i', $regOutput, $regMatches)) {
            putenv('LARAVEL_ENV_ENCRYPTION_KEY='.$regMatches[1]);
            $_ENV['LARAVEL_ENV_ENCRYPTION_KEY'] = $regMatches[1];
            $_SERVER['LARAVEL_ENV_ENCRYPTION_KEY'] = $regMatches[1];
        }
    } else {
        // No Linux, além de variáveis de ambiente nativas (export / systemd / /etc/environment),
        // verifica se há chave persistida no armazenamento privado ou na raiz
        $keyFile = __DIR__.'/../storage/app/private/.env.key';
        if (! file_exists($keyFile)) {
            $keyFile = __DIR__.'/../.env.key';
        }
        if (file_exists($keyFile)) {
            $key = trim((string) @file_get_contents($keyFile));
            if (! empty($key)) {
                putenv('LARAVEL_ENV_ENCRYPTION_KEY='.$key);
                $_ENV['LARAVEL_ENV_ENCRYPTION_KEY'] = $key;
                $_SERVER['LARAVEL_ENV_ENCRYPTION_KEY'] = $key;
            }
        }
    }
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                Request::HEADER_X_FORWARDED_HOST |
                Request::HEADER_X_FORWARDED_PORT |
                Request::HEADER_X_FORWARDED_PROTO
        );
        $middleware->validateCsrfTokens(except: [
            '*/book',
        ]);
        $middleware->web(prepend: [
            EnsureLivewireChildrenMemoExists::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->hasHeader('X-Livewire'),
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (app()->runningUnitTests()) {
                return null;
            }

            if ($e instanceof ValidationException || $e instanceof AuthenticationException) {
                return null;
            }

            if (! $request->hasHeader('X-Livewire') && ! $request->expectsJson()) {
                return null;
            }

            Log::error('LIVEWIRE_ERROR: '.$e::class.': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine()."\n".$e->getTraceAsString());

            $title = 'Não foi possível concluir a ação';
            $message = 'Ocorreu um imprevisto ao processar sua solicitação. Tente novamente em instantes.';
            $status = 422;

            if ($e instanceof PayloadTooLargeException || $e instanceof PostTooLargeException) {
                $title = 'Arquivo ou envio muito grande';
                $message = 'O volume de dados enviados ultrapassou o limite da requisição. Tente um arquivo menor ou aguarde o envio em partes.';
                $status = 413;
            } elseif ($e instanceof TokenMismatchException) {
                $title = 'Sessão expirada';
                $message = 'Sua sessão ficou inativa. Por favor, recarregue a página para continuar.';
                $status = 419;
            } elseif ($e instanceof AuthorizationException || $e instanceof AccessDeniedHttpException) {
                $title = 'Acesso não autorizado';
                $message = 'Você não possui permissão para realizar esta operação.';
                $status = 403;
            } elseif ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                $title = 'Registro não encontrado';
                $message = 'O item selecionado não foi encontrado ou já foi removido.';
                $status = 404;
            } elseif ($e instanceof QueryException) {
                $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
                if (in_array($sqlState, ['23000', '23503', '23505'], true) || str_contains($e->getMessage(), 'FOREIGN KEY') || str_contains($e->getMessage(), 'Integrity constraint')) {
                    $title = 'Operação bloqueada por vínculo de dados';
                    $message = 'Este registro está vinculado a outros cadastros ou já existe um cadastro com estes mesmos dados.';
                } else {
                    $title = 'Erro ao salvar informações';
                    $message = 'Não foi possível gravar as alterações no banco de dados. Verifique os campos preenchidos.';
                }
            } elseif ($e instanceof TooManyRequestsHttpException || $e instanceof CorruptComponentPayloadException) {
                if ($request->ip()) {
                    RateLimiter::clear('livewire-checksum-failures:'.$request->ip());
                }

                return response()->json(['status' => 'refresh_required'], 200);
            } elseif ($request->hasHeader('X-Livewire') && in_array($status, [401, 403, 419])) {
                return response()->json(['status' => 'refresh_required'], 200);
            } elseif (str_contains($e->getMessage(), 'Undefined array key "children"') || str_contains($e->getMessage(), 'Undefined array key "locale"') || str_contains($e->getMessage(), 'Livewire requests')) {
                if ($request->ip()) {
                    RateLimiter::clear('livewire-checksum-failures:'.$request->ip());
                }

                return response()->json(['status' => 'refresh_required'], 200);
            } elseif ($e->getMessage() !== '') {
                $cleanMsg = strip_tags($e->getMessage());
                if (mb_strlen($cleanMsg) <= 180 && ! str_contains($cleanMsg, 'SQLSTATE') && ! str_contains($cleanMsg, 'vendor/')) {
                    $message = $cleanMsg;
                }
            }

            return response()->json([
                'aurobarber_friendly_error' => true,
                'title' => $title,
                'message' => $message,
            ], $status);
        });
    })->create();
