<?php

use Livewire\Volt\Component;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

new class extends Component {
    public $current_password = '';
    public $new_password = '';
    public $new_password_confirmation = '';

    public function updatePassword()
    {
        $this->validate([
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
            ],
            'new_password_confirmation' => ['required', 'same:new_password']
        ], [
            'current_password.required' => 'Please enter your current password.',
            'new_password.required' => 'Please enter a new password.',
            'new_password.min' => 'Password must be at least 8 characters and contain at least one uppercase character, one lowercase character, one number, and one special character.',
            'new_password_confirmation.required' => 'Please confirm your new password.',
            'new_password_confirmation.same' => 'The password confirmation does not match.',
        ]);

        if (!Hash::check($this->current_password, Auth::user()->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Invalid current password.',
            ]);
        }

        Auth::user()->update([
            'password' => $this->new_password,
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->dispatch('password-updated');
        $this->dispatch('close-modal');
    }
}; ?>

<div 
    x-data="{ show: false }" 
    x-show="show"
    @keydown.escape.window="show = false"
    @open-change-password.window="show = true"
    @close-modal.window="show = false"
    style="display: none;"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
>
    <div 
        @click.away="show = false"
        class="bg-brand-10 rounded-2xl border-2 border-brand-150 shadow-2xl w-full max-w-3xl py-12 px-15 text-center"
    >
        <div class="flex flex-col gap-6">
            <h2 class="text-app-heading-1 text-text-100">{{ __('Change Password') }}</h2>

            <form wire:submit="updatePassword" class="flex flex-col gap-6">
                <x-form-input 
                    label="{{ __('Current Password') }}" 
                    type="password" 
                    placeholder="{{ __('Enter your current password') }}" 
                    model="current_password" 
                />
                
                <div class="text-right -mt-4">
                    <button 
                        type="button"
                        @click="show = false; $dispatch('open-forgot-password')" 
                        class="text-app-desc-feature text-interactive-100 hover:underline italic focus:outline-none"
                    >
                        {{ __('Forgot Password?') }}
                    </button>
                </div>

                <x-form-input 
                    label="{{ __('New Password') }}" 
                    type="password" 
                    placeholder="{{ __('Enter your new password') }}" 
                    model="new_password" 
                />

                <x-form-input 
                    label="{{ __('Confirm New Password') }}" 
                    type="password" 
                    placeholder="{{ __('Re-enter your new password') }}" 
                    model="new_password_confirmation" 
                />

                <x-dialog-footer 
                    message="{{ __('Are you sure want to change your password?') }}" 
                />
            </form>
        </div>
    </div>
</div>