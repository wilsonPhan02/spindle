<?php

use Illuminate\Support\Facades\Password;
use Livewire\Volt\Component;
use Livewire\Attributes\Rule;

new class extends Component {
    #[Rule('required|email|exists:users,email')]
    public $email = '';
    
    public $sent = false;

    public function mount()
    {
        if (Auth::check()) {
            $this->email = Auth::user()->email;
        }
    }

    public function sendLink()
    {
        $this->validate();

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->sent = true;
        } else {
            $this->addError('email', __($status));
        }
    }
}; ?>

{{-- Bagian Luar: Sekarang jadi Modal Listener --}}
<div 
    x-data="{ show: false }" 
    x-show="show"
    @open-forgot-password.window="show = true"
    @keydown.escape.window="show = false"
    style="display: none;"
    class="fixed inset-0 z-50 flex items-center justify-center bg-text-80/75 backdrop-blur-[1.5px]"
>
    <div 
        @click.away="show = false" 
        class="bg-brand-10 rounded-2xl border-2 border-brand-150 shadow-2xl w-full max-w-lg text-center relative {{ $sent ? 'max-w-md px-10 py-5' : 'max-w-lg py-12 px-15' }}"
    >
        
        @if (!$sent)
            {{-- Form State --}}
            <div class="flex flex-col gap-8">
                <div class="flex flex-col gap-5">
                    <h2 class="text-app-heading-1 text-text-100">Forgot Password</h2>
                    <p class="text-app-subfeature text-text-80 px-3">
                        Drop your email, and we'll send a magic link to help you write the next line.
                    </p>
                </div>

                <div class="text-left space-y-2">
                    <x-form-input 
                        label="Email" 
                        placeholder="Enter your email" 
                        model="email" 
                    />
                </div>

                <div class="flex gap-4 w-full max-w-2xl mx-auto">
                    <x-button 
                        type="button"
                        @click="show = false" 
                        variant="secondary" 
                        class="flex-1"
                    >
                        Cancel
                    </x-button>

                    <x-button 
                        wire:click="sendLink" 
                        wire:loading.attr="disabled" 
                        variant="primary" 
                        class="flex-1"
                    >
                        <span wire:loading.remove>Send Link</span>
                        
                        <span wire:loading.flex class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5 text-subtext-60" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Sending...
                        </span>
                    </x-button>
                </div>
            </div>
        @else
            {{-- Success State --}}
            <div class="py-10 animate-fade-in" 
                x-data="{ 
                    timer: 60, 
                    startTimer() {
                        this.timer = 60;
                        let interval = setInterval(() => {
                            if (this.timer > 0) {
                                this.timer--;
                            } else {
                                clearInterval(interval);
                            }
                        }, 1000);
                    }
                }" 
                x-init="startTimer()">
                
                <div class="w-20 h-20 bg-secondary-200 rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-subtext-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <div class="flex flex-col gap-5 mb-5">
                    <h2 class="text-app-heading-1 text-text-100">Check Your Email!</h2>
                    <p class="text-app-subfeature text-text-80 px-3">
                        We've sent the magic link to <strong>{{ $email }}</strong>.
                    </p>
                </div>

                {{-- Resend Email --}}
                <p class="text-app-desc-feature text-text-80 px-3 mb-3">
                    Didn't receive the email yet? 
                    <button 
                        type="button"
                        :disabled="timer > 0" 
                        @click="startTimer(); $wire.sendLink()"
                        class="text-app-desc-feature text-text-80 hover:underline disabled:opacity-50 disabled:no-underline transition-all"
                        :class="timer > 0 ? 'cursor-not-allowed' : 'cursor-pointer'"
                    >
                        <span x-show="timer === 0">Resend</span>
                        <span x-show="timer > 0">Resend in 00:<span x-text="timer < 10 ? '0' + timer : timer"></span></span>
                    </button>
                </p>

                <x-button 
                    type="button"
                    @click="show = false" 
                    variant="secondary" 
                    class="w-full"
                >
                    Got it, Thanks!
                </x-button>
            </div>
        @endif
    </div>
</div>