<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Jika user daftar pakai Google, anggap sudah verified
        if ($user->google_id) {
            return $next($request);
        }

        if (!$user->hasVerifiedEmail()) {
            return redirect()->route('email.verification');
        }

        return $next($request);
    }
}
