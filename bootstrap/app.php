<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'project.access' => \App\Http\Middleware\CheckProjectAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e) {
            $message = "CRITICAL ERROR ALERT in " . config('app.name') . "\n\n" .
                       "Message: " . $e->getMessage() . "\n" .
                       "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n" .
                       "URL: " . request()->fullUrl() . "\n\n" .
                       "Time: " . now()->toDateTimeString();

            // 1. Send alert via Webhook if configured
            $webhookUrl = env('ERROR_WEBHOOK_URL');
            if ($webhookUrl) {
                try {
                    \Illuminate\Support\Facades\Http::post($webhookUrl, [
                        'content' => "🚨 **Critical Error!** 🚨\n```text\n" . $message . "\n```"
                    ]);
                } catch (\Throwable $t) {
                    // Suppress webhook errors
                }
            }

            // 2. Send alert via Email
            try {
                // You can change this via .env later using ERROR_EMAIL_ADDRESS
                $email = env('ERROR_EMAIL_ADDRESS', 'bingle.spindle@gmail.com');
                \Illuminate\Support\Facades\Mail::raw($message, function ($mail) use ($email) {
                    $mail->to($email)
                         ->subject('🚨 Critical Error in Spindle: /' . request()->path());
                });
            } catch (\Throwable $t) {
                // Suppress mail errors to avoid infinite loops if SMTP is not set up
            }
        });
    })->create();
