<?php

use App\Http\Middleware\CheckProjectAccess;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias([
            'project.access' => CheckProjectAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e) {
            $message = 'CRITICAL ERROR ALERT in '.config('app.name')."\n\n".
                       'Message: '.$e->getMessage()."\n".
                       'File: '.$e->getFile().' (Line: '.$e->getLine().")\n".
                       'URL: '.request()->fullUrl()."\n\n".
                       'Time: '.now()->toDateTimeString();

            // 1. Send alert via Webhook if configured
            $webhookUrl = config('app.error_webhook_url');
            if ($webhookUrl) {
                try {
                    Http::post($webhookUrl, [
                        'content' => "🚨 **Critical Error!** 🚨\n```text\n".$message."\n```",
                    ]);
                } catch (Throwable $t) {
                    // Suppress webhook errors
                }
            }

            // 2. Send alert via Email
            try {
                // You can change this via .env later using ERROR_EMAIL_ADDRESS
                $email = config('app.error_email_address');
                Mail::mailer('smtp')->raw($message, function ($mail) use ($email) {
                    $mail->to($email)
                        ->subject('🚨 Critical Error in Spindle: /'.request()->path());
                });
            } catch (Throwable $t) {
                // Suppress mail errors to avoid infinite loops if SMTP is not set up
            }
        });

        $exceptions->render(function (Throwable $e, Request $request) {
            // Optional toggle to easily get Ignition back if needed
            if (config('app.force_custom_errors') === false && config('app.debug')) {
                return null;
            }

            // Biarkan Laravel menangani error validasi, auth, dan redirect
            if ($e instanceof ValidationException ||
                $e instanceof AuthenticationException ||
                $e instanceof HttpResponseException) {
                return null;
            }

            $status = 500;

            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
            } elseif ($e instanceof QueryException || $e instanceof PDOException) {
                // Tangani khusus jika database terputus (connection refused)
                if (str_contains($e->getMessage(), '2002')) {
                    $status = 503;
                }
            }

            $allowedStatuses = [403, 404, 419, 500, 503];
            if (! in_array($status, $allowedStatuses)) {
                $status = 500;
            }

            if (! $request->wantsJson() && ! $request->is('api/*')) {
                return response()->view("errors.{$status}", ['exception' => $e], $status);
            }

            return null;
        });
    })->create();
