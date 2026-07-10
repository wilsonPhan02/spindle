<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('app_locale')) {
            App::setLocale(session()->get('app_locale'));
        } elseif (Auth::check() && Auth::user()->profile && Auth::user()->profile->language) {
            $locale = Auth::user()->profile->language;
            App::setLocale($locale);
            session()->put('app_locale', $locale);
        } else {
            // Default locale
            App::setLocale(config('app.locale'));
        }

        return $next($request);
    }
}
