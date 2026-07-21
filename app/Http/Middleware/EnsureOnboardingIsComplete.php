<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingIsComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $profile = auth()->user()->profile;
            if (! $profile || ! $profile->username) {
                // If they are not already on the onboarding page, redirect them
                if (! $request->routeIs('onboarding') &&
                    ! $request->routeIs('logout') &&
                    ! $request->routeIs('email.verification') &&
                    ! $request->routeIs('verification.*')) {
                    return redirect()->route('onboarding');
                }
            }
        }

        return $next($request);
    }
}
