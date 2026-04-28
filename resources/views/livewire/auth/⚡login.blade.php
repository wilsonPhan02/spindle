<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;

new #[Layout('layouts.guest')] class extends Component
{
    public $email = '';
    public $password = '';
    public $remember = false;

    public function login()
    {
        $this->validate([
            'email' => ['required'],
            'password' => ['required'],
        ]);

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        $this->addError('email', 'The provided credentials do not match our records.');
    }

};
?>

<div class="flex flex-col items-center justify-center min-h-screen bg-brand-50">
    
    <div class="mb-5 text-4xl font-bold font-merriweather tracking-tight text-text-80">
        <x-logo class="h-10 w-auto text-text-80" />
    </div>

    <div class="w-full max-w-md p-6 bg-card-bg border border-transparent rounded-xl shadow-md">
        
        <h1 class="mb-5 text-2xl font-merriweather text-center text-text-80">Sign In</h1>

        <form wire:submit="login" class="space-y-3">
            
            <div>
                <label class="block mb-1 text-app-body-medium text-text-80">Email / Username</label>
                <input type="text" wire:model="email" placeholder="Enter your email or username" 
                    class="w-full px-4 py-2 bg-white border border-subtext-70 rounded-md focus:ring-2 focus:ring-secondary-200 outline-none transition-all placeholder-subtext-90 text-app-body-medium text-text-80">
                @error('email') <span class="text-app-body-small text-danger-100 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div x-data="{ show: false }">
                <label class="block mb-1 text-app-body-medium text-text-80">Password</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" wire:model="password" placeholder="Enter your password" 
                        class="w-full px-4 py-2 pr-10 bg-white border border-subtext-70 rounded-md focus:ring-2 focus:ring-secondary-200 outline-none transition-all placeholder-subtext-90 text-app-body-medium text-text-80">
                    
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-subtext-90 hover:text-text-80 focus:outline-none transition-colors">
                        <svg x-show="!show" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.978 9.978 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                        </svg>
                        <svg x-show="show" style="display: none;" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
                @error('password') <span class="text-app-body-small text-danger-100 mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="flex items-center justify-between pt-1">
                <div class="flex items-center">
                    <input type="checkbox" wire:model="remember" id="remember" 
                        class="w-4 h-4 bg-white border-transparent rounded text-secondary-200 focus:ring-secondary-200 focus:ring-offset-card-bg">
                    <label for="remember" class="ml-2 text-app-body-medium text-text-80">Remember me</label>
                </div>
                <a href="#" class="text-app-body-medium text-interactive-100 hover:underline">Forgot Password?</a>
            </div>

            <button type="submit" 
                class="w-full py-2.5 mt-2 text-app-feature text-bg-main transition-colors bg-secondary-200 rounded-md hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary-200 focus:ring-offset-card-bg">
                Sign In
            </button>
            
        </form>

        <div class="flex items-center my-4">
            <div class="flex-grow border-t border-subtext-70"></div>
            <span class="px-3 text-app-body-small text-subtext-90 bg-card-bg">Or</span>
            <div class="flex-grow border-t border-subtext-70"></div>
        </div>

        <button type="button" 
            class="flex items-center justify-center w-full py-2.5 mb-4 text-app-feature text-text-80 transition-colors bg-white border border-subtext-70 rounded-md hover:bg-card-hover">
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            Sign in with Google
        </button>

        <p class="text-app-body-medium text-center text-text-80">
            Don't have an account? 
            <a href="{{ route('register') }}" class="text-interactive-100 hover:underline">Sign up</a>
        </p>
    </div>
</div>