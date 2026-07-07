<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});

// Pintu keluar sementara untuk testing
Route::get('/logout-test', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
});

Volt::route('/forgot-password', 'auth.forgot-password')->name('password.request');
Volt::route('/reset-password/{token}', 'auth.reset-password')->name('password.reset');

// AREA GUEST: Hanya bisa diakses oleh orang yang BELUM LOGIN
Route::middleware('guest')->group(function () {

    Volt::route('/register', 'auth.register')->name('register');
    Volt::route('/login', 'auth.login')->name('login');
});

// AREA AUTH: Hanya bisa diakses oleh orang yang SUDAH LOGIN
Route::middleware('auth')->group(function () {

    // Rute Dashboard setelah sukses register/login
    Volt::route('/dashboard', 'dashboard.index')->name('dashboard');
    // Rute untuk ke Settings Page
    Volt::route('/settings', 'settings.index')->name('settings');
    $missingProject = function (\Illuminate\Http\Request $request) {
        return redirect()->route('dashboard');
    };

    Route::middleware('project.access')->group(function () use ($missingProject) {
        // Rute untuk ke detail project
        Volt::route('/projects/{project}', 'projects.show')->name('projects.show')->missing($missingProject);
        // Rute untuk halaman characters project
        Volt::route('/projects/{project}/characters', 'projects.characters')->name('projects.characters')->missing($missingProject);
        // Rute untuk halaman characters detail project
        Volt::route('/projects/{project}/characters/{character}', 'projects.character-detail')->name('projects.character.show')->missing($missingProject);
        //Rute untuk ke structure project
        Volt::route('/projects/{project}/structure', 'projects.structure')->name('projects.structure')->missing($missingProject);
        // Rute untuk halaman notes project
        Volt::route('/projects/{project}/notes', 'projects.notes')->name('projects.notes')->missing($missingProject);
    });
    // Rute untuk halaman archive
    Volt::route('/archive', 'archive.index')->name('archive');
    
});

// Fallback route to ensure 404 pages have access to the web session
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
