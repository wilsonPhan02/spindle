<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

Volt::route('/forgot-password', 'auth.forgot-password')->name('password.request');
Volt::route('/reset-password/{token}', 'auth.reset-password')->name('password.reset');

// Rute untuk mengubah bahasa (bisa diakses guest maupun auth)
Route::get('/lang/{locale}', function ($locale) {
    $validCodes = ['en', 'id', 'ja', 'zh', 'ko'];
    if (in_array($locale, $validCodes, true)) {
        session()->put('app_locale', $locale);
        if (Auth::check() && Auth::user()->profile) {
            Auth::user()->profile->update(['language' => $locale]);
        }
    }
    return redirect()->back();
})->name('lang.switch');

// AREA GUEST: Hanya bisa diakses oleh orang yang BELUM LOGIN
Route::middleware('guest')->group(function () {

    Volt::route('/register', 'auth.register')->name('register');
    Volt::route('/login', 'auth.login')->name('login');
});

// AREA AUTH: Hanya bisa diakses oleh orang yang SUDAH LOGIN
Route::middleware('auth')->group(function () {

    // Rute Dashboard setelah sukses register/login
    Volt::route('/dashboard', 'dashboard.index')->name('dashboard');
    // Rute Onboarding setelah register
    Volt::route('/onboarding', 'auth.onboarding')->name('onboarding');
    // Rute untuk ke Settings Page
    Volt::route('/settings', 'settings.index')->name('settings');
    $missingProject = function (Request $request) {
        return redirect()->route('dashboard');
    };

    Route::middleware('project.access')->group(function () use ($missingProject) {
        // Rute untuk ke detail project
        Volt::route('/projects/{project}', 'projects.show')->name('projects.show')->missing($missingProject);
        // Rute untuk halaman characters project
        Volt::route('/projects/{project}/characters', 'projects.characters')->name('projects.characters')->missing($missingProject);
        // Rute untuk halaman characters detail project
        Volt::route('/projects/{project}/characters/{character}', 'projects.character-detail')->name('projects.character.show')->missing($missingProject);
        // Rute untuk ke structure project
        Volt::route('/projects/{project}/structure', 'projects.structure')->name('projects.structure')->missing($missingProject);
        // Rute untuk halaman notes project
        Volt::route('/projects/{project}/notes', 'projects.notes')->name('projects.notes')->missing($missingProject);
        // Rute untuk halaman manuscript chapter
        Volt::route('/projects/{project}/structure/{chapterCard}/manuscript', 'projects.manuscript')->name('projects.manuscript');
    });
    // Rute untuk halaman archive
    Volt::route('/archive', 'archive.index')->name('archive');

});

// Fallback route to ensure 404 pages have access to the web session
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
