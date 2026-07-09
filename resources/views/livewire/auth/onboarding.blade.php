<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;

new #[Layout('layouts.guest')] #[Title('Profile Setup - Spindle')] class extends Component
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
            'username.required' => 'Please enter your preferred name.'
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

<div class="relative flex flex-col items-center justify-center min-h-screen w-full bg-[#f3ede6]">
    
    <div class="absolute inset-0 z-0 pointer-events-none opacity-70">
        @include('components.auth-bg') 
    </div>

    <div class="relative z-10 flex flex-col items-center justify-center w-full px-4">
          
        <div class="mb-8">
            <x-logo class="h-10 w-auto text-black" />
        </div>

        <div class="w-full max-w-[420px] p-10 bg-[#fdfbf8] border border-[#e8dfd5] rounded-xl shadow-sm">
            
            <h1 class="mb-10 text-[1.75rem] font-merriweather text-center text-[#1a1a1a]">Profile Setup</h1>
            
            <div class="flex justify-center mb-8">
                <!-- Avatar placeholder -->
                <div class="w-32 h-32 rounded-full border border-[#e8dfd5] bg-[#ebdccc] flex items-end justify-center overflow-hidden">
                    <svg class="w-32 h-32 text-[#8c5c3c] translate-y-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v3h16v-3c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
            </div>

            <p class="mb-5 text-center text-[15px] font-medium text-[#4a4a4a]">What should we call you?</p>

            <form wire:submit="save" novalidate class="space-y-6">
                
                <div>
                    <input type="text" wire:model="username" placeholder="Enter your preferred name" 
                        class="w-full px-4 py-3 bg-[#f3ede6] border border-[#e8dfd5] rounded-md focus:ring-1 focus:ring-[#8c5c3c] focus:border-[#8c5c3c] outline-none transition-all placeholder-[#a89f91] text-[15px] text-[#1a1a1a]">
                    @error('username') <span class="text-xs text-red-500 mt-2 block text-center">{{ $message }}</span> @enderror
                </div>

                <button type="submit" 
                    class="w-full py-3.5 text-[15px] font-medium text-white transition-colors bg-[#78563c] rounded-md hover:bg-[#684a32] focus:outline-none">
                    Next
                </button>
                
            </form>
        </div>
    </div>

    @if($isSuccess)
    <div>
        <div class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-[#f3ede6]/80 backdrop-blur-sm transition-all duration-300">
            <svg class="animate-spin mb-4 h-12 w-12 text-[#78563c]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-xl font-merriweather font-semibold text-[#1a1a1a] animate-pulse">Setting up your profile...</p>
        </div>
    </div>
    @endif
</div>
