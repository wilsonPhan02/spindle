<?php

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Livewire\Volt\Component;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Layout;
use App\Models\User;

new #[Layout('layouts.guest')] class extends Component {
    public $token;

    #[Rule('required|email|exists:users,email')]
    public $email = '';

    #[Rule('required|min:8|confirmed')]
    public $password = '';
    public $password_confirmation = '';

    public function mount($token)
    {
        $this->token = $token;
        // Ambil email dari URL ?email=...
        $this->email = request()->query('email', '');
    }

    public function resetPassword()
    {
        $this->validate();

        $status = Password::broker()->reset(
            [
                'token' => $this->token,
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
            ],
            function ($user, $password) {
                if (!$user) {
                    $user = User::where('email', $this->email)->first();
                }

                if ($user) {
                    $user->forceFill([
                        'password' => $password
                    ]);

                    $user->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));
                }
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', __($status));
    
            $message = 'Your masterpiece is secured. The password has been rewritten.';

            if (auth()->check()) {
                return redirect()->route('settings')->with('success', $message);
            }

            return redirect()->route('login')->with('success', $message);
        } else {
            $this->addError('email', __($status));
        }
    }
}; ?>

<div class="min-h-screen flex items-center justify-center bg-text-80">
    <div class="bg-brand-10 rounded-2xl shadow-2xl w-full max-w-lg py-12 px-15 text-center border-2 border-brand-150 relative">
        <div class="flex flex-col gap-8">
            <div class="flex flex-col gap-5">
                <h2 class="text-app-heading-1 text-text-100">{{ __('Rewrite Your Story') }}</h2>
                <p class="text-app-subfeature text-text-80 px-3">
                    {{ __('Enter your new credentials to continue your masterpiece.') }}
                </p>
            </div>

            <form wire:submit="resetPassword" class="text-left space-y-4">

                <div class="flex flex-col gap-5">
                    <x-form-input 
                        label="{{ __('Confirm your email') }}" 
                        type="email"
                        placeholder="{{ __('Your registered email') }}" 
                        model="email" 
                    />

                    <x-form-input 
                        label="{{ __('New Password (Min. 8 characters)') }}" 
                        type="password" 
                        placeholder="{{ __('Enter your new password') }}" 
                        model="password"
                    />

                    <x-form-input 
                        label="{{ __('Confirm New Password') }}" 
                        type="password" 
                        placeholder="{{ __('Re-enter password') }}" 
                        model="password_confirmation" 
                    />
                </div>

                <x-button 
                    type="submit" 
                    wire:loading.attr="disabled"
                    variant="primary" 
                    class="w-full mt-8"
                >
                    <span wire:loading.remove>{{ __('Update Password') }}</span>

                    <div wire:loading.flex class="items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5 text-subtext-60" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ __('Processing...') }}</span>
                    </div>
                </x-button>
            </form>
        </div>
    </div>
</div>