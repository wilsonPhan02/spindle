<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\User;

new #[Layout('layouts.guest')] #[Title('Complete Your Profile - Spindle')] class extends Component
{
    public $username = '';
    public $occupation = '';
    public $gender = '';
    public $birthdate = '';
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
            'occupation' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'in:male,female'],
            'birthdate' => ['nullable', 'date'],
        ]);

        $profile = auth()->user()->profile;
        $profile->update([
            'username' => $this->username,
            'occupation' => $this->occupation ?: null,
            'gender' => $this->gender ?: null,
            'birthdate' => $this->birthdate ?: null,
        ]);

        $this->isSuccess = true;
        $this->js("setTimeout(() => window.location.href = '/dashboard', 1200)");
    }
};
?>

<div class="relative flex flex-col items-center justify-center min-h-screen w-full">
    
    <div class="absolute inset-0 z-0 pointer-events-none">
        <img src="{{ asset('images/auth-bg.svg') }}" class="absolute inset-0 w-full h-full object-cover" alt="Background"> 
    </div>

    <div class="relative z-10 flex flex-col items-center justify-center w-full px-4">
          
        <div class="mb-5 text-4xl font-bold font-merriweather tracking-tight text-text-80">
            <x-logo class="h-10 w-auto text-text-80" />
        </div>

        <div class="w-full max-w-md p-6 bg-card-bg border border-transparent rounded-xl shadow-md">
            
            <h1 class="mb-2 text-2xl font-merriweather text-center text-text-80">Complete Profile</h1>
            <p class="mb-6 text-app-body-medium text-center text-subtext-90">Let's set up your creative identity.</p>

            <form wire:submit="save" novalidate class="space-y-4">
                
                <div>
                    <label class="block mb-1 text-app-body-medium text-text-80">Username <span class="text-danger-100">*</span></label>
                    <input type="text" wire:model="username" placeholder="Enter your username" 
                        class="w-full px-4 py-2 bg-white border border-subtext-70 rounded-md focus:ring-2 focus:ring-secondary-200 outline-none transition-all placeholder-subtext-90 text-app-body-medium text-text-80">
                    @error('username') <span class="text-app-body-small text-danger-100 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block mb-1 text-app-body-medium text-text-80">Occupation (Optional)</label>
                    <input type="text" wire:model="occupation" placeholder="e.g. Writer, Student" 
                        class="w-full px-4 py-2 bg-white border border-subtext-70 rounded-md focus:ring-2 focus:ring-secondary-200 outline-none transition-all placeholder-subtext-90 text-app-body-medium text-text-80">
                    @error('occupation') <span class="text-app-body-small text-danger-100 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block mb-1 text-app-body-medium text-text-80">Gender (Optional)</label>
                    <select wire:model="gender" class="w-full px-4 py-2 bg-white border border-subtext-70 rounded-md focus:ring-2 focus:ring-secondary-200 outline-none transition-all text-app-body-medium text-text-80">
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                    @error('gender') <span class="text-app-body-small text-danger-100 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="mb-5">
                    <label class="block mb-1 text-app-body-medium text-text-80">Birthdate (Optional)</label>
                    <input type="date" wire:model="birthdate" 
                        class="w-full px-4 py-2 bg-white border border-subtext-70 rounded-md focus:ring-2 focus:ring-secondary-200 outline-none transition-all text-app-body-medium text-text-80">
                    @error('birthdate') <span class="text-app-body-small text-danger-100 mt-1 block">{{ $message }}</span> @enderror
                </div>

                <button type="submit" 
                    class="w-full py-2.5 mt-2 text-app-feature text-bg-main transition-colors bg-secondary-200 rounded-md hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary-200 focus:ring-offset-card-bg">
                    Continue to Dashboard
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
            <p class="text-xl font-merriweather font-semibold text-text-80 animate-pulse">Arriving at creative realm...</p>
        </div>
    </div>
    @endif
</div>
