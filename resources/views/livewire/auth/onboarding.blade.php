<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\User;

new #[Layout('layouts.guest')] class extends Component
{
    public $username = '';
    public $isSuccess = false;

    public function mount() {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        $profile = auth()->user()->profile;
        if ($profile && $profile->username) {
            return redirect()->route('dashboard'); // Already has username
        }
    }

    public function save()
    {
        $this->validate([
            'username' => ['required', 'string', 'max:255', 'unique:profiles,username,' . auth()->user()->profile->profile_id . ',profile_id'],
        ], [
            'username.required' => __('Please enter your preferred name.'),
            'username.unique' => __('This name is already taken. Please choose another.'),
        ]);

        $profile = auth()->user()->profile;
        $profile->update([
            'username' => $this->username,
        ]);

        $this->isSuccess = true;
        $this->js("setTimeout(() => window.location.href = '/dashboard', 1200)");
    }
};
?>

<div class="relative flex flex-col items-center justify-center min-h-screen w-full">
    <x-slot:title>{{ __('Profile Setup - Spindle') }}</x-slot>
    
    <div class="absolute inset-0 z-0 pointer-events-none">
        <img src="{{ asset('images/auth-bg.svg') }}" class="absolute inset-0 w-full h-full object-cover" alt="{{ __('Background') }}"> 
    </div>

    <div class="relative z-10 flex flex-col items-center justify-center w-full px-4">
          
        <div class="mb-5">
            <x-logo class="h-10 w-auto text-text-80" />
        </div>

        <div class="w-full max-w-md px-8 py-10 bg-card-bg border border-transparent rounded-xl shadow-md">
            
            <h1 class="mb-6 text-2xl font-merriweather text-center text-text-80">{{ __('Profile Setup') }}</h1>

            {{-- Avatar --}}
            <div class="flex justify-center mb-6">
                <div class="w-32 h-32 rounded-full bg-brand-100 flex items-center justify-center overflow-hidden">
                    <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                        <circle cx="40" cy="40" r="40" fill="#f0e8e0"/>
                        <circle cx="40" cy="30" r="13" fill="#8B5E3C"/>
                        <ellipse cx="40" cy="64" rx="20" ry="14" fill="#8B5E3C"/>
                    </svg>
                </div>
            </div>

            <form wire:submit="save" novalidate class="space-y-4">

                <p class="text-app-body-medium text-center text-text-80 mb-3">{{ __('What should we call you?') }}</p>

                <div>
                    <input 
                        type="text" 
                        wire:model="username" 
                        placeholder="{{ __('Enter your preferred name') }}" 
                        class="w-full px-4 py-3 bg-brand-50 border border-brand-100 rounded-lg focus:ring-2 focus:ring-secondary-200 focus:border-secondary-200 outline-none transition-all placeholder-subtext-90 text-app-body-medium text-text-80"
                        autofocus
                    >
                    @error('username') 
                        <span class="text-app-body-small text-danger-100 mt-1 block">{{ $message }}</span> 
                    @enderror
                </div>

                <button type="submit" 
                    class="w-full py-3 mt-2 text-app-feature text-subtext-60 transition-colors bg-secondary-300 rounded-lg hover:bg-[#634735] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary-200 focus:ring-offset-card-bg shadow-md">
                    {{ __('Next') }}
                </button>
                
            </form>
        </div>
    </div>

    @if($isSuccess)
    <div>
        <div class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-bg-main/40 backdrop-blur-md transition-all duration-300">
            <svg class="animate-spin mb-4 h-12 w-12 text-secondary-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-xl font-merriweather font-semibold text-text-80 animate-pulse">{{ __('Arriving at creative realm...') }}</p>
        </div>
    </div>
    @endif
</div>
