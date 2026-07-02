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
    // Rute untuk ke detail project
    Volt::route('/projects/{project}', 'projects.show')->name('projects.show');
    // Rute untuk ke halaman Characters Sheet
    Volt::route('/projects/{project}/characters', 'projects.characters')->name('projects.characters');
    // Rute untuk lihat & edit detail satu character
    Volt::route('/projects/{project}/characters/{character}', 'projects.character-detail')->name('projects.characters.show');
});
