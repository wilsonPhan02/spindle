<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\OtpVerificationMail;

new #[Layout('layouts.guest')] class extends Component
{
    public $otp = ['', '', '', ''];
    public $resendCooldown = 0;
    public $isSuccess = false;
    public $errorMessage = '';

    public function mount()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Jika user Google OAuth, otomatis skip
        if ($user->google_id) {
            if (empty($user->email_verified_at)) {
                $user->update(['email_verified_at' => now()]);
            }
            return redirect()->route('onboarding');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('onboarding');
        }

        // Generate OTP pertama kali jika belum ada atau sudah expired
        if (empty($user->email_otp) || ($user->email_otp_expires_at && now()->greaterThan($user->email_otp_expires_at))) {
            $this->sendOtp();
        }
    }

    public function sendOtp()
    {
        $user = auth()->user();
        $code = $user->generateOtp();
        Mail::to($user->email)->send(new OtpVerificationMail($code));
        $this->resendCooldown = 60;
    }

    public function resendOtp()
    {
        if ($this->resendCooldown > 0) {
            return;
        }

        $this->sendOtp();
        $this->errorMessage = '';
        $this->otp = ['', '', '', ''];
    }

    public function verify()
    {
        $this->errorMessage = '';
        $code = implode('', $this->otp);

        if (strlen(trim($code)) < 4) {
            $this->errorMessage = __('Please enter all 4 digits.');
            return;
        }

        $user = auth()->user();
        if ($user->verifyOtp($code)) {
            $this->isSuccess = true;
            $this->js("setTimeout(() => window.location.href = '/onboarding', 1200)");
        } else {
            $this->errorMessage = __('Invalid or expired OTP code.');
            $this->otp = ['', '', '', ''];
            $this->js("\$wire.\$el.querySelector('[data-otp-input=\"0\"]').focus()");
        }
    }

    public function decrementCooldown()
    {
        if ($this->resendCooldown > 0) {
            $this->resendCooldown--;
        }
    }
};
?>

<div class="relative flex flex-col items-center justify-center min-h-screen w-full">
    <x-slot:title>{{ __('Email Verification - Spindle') }}</x-slot>

    {{-- Background --}}
    <div class="absolute inset-0 z-0 pointer-events-none">
        <img src="{{ asset('images/auth-bg.svg') }}" class="absolute inset-0 w-full h-full object-cover" alt="{{ __('Background') }}">
    </div>

    <div class="relative z-10 flex flex-col items-center justify-center w-full px-4">

        {{-- Logo --}}
        <div class="mb-5">
            <x-logo class="h-10 w-auto text-text-80" />
        </div>

        {{-- Card --}}
        <div class="w-full max-w-md px-8 py-10 bg-card-bg border border-transparent rounded-xl shadow-md">

            <h1 class="mb-4 text-2xl font-merriweather text-center text-text-80">{{ __('Email Verification') }}</h1>
            <p class="text-app-body-medium text-center text-subtext-90 mb-1">
                {{ __("We've sent a One-Time Password (OTP) to your") }}
            </p>
            <p class="text-app-body-medium text-center text-subtext-90 mb-2">
                {{ __('registered email address') }}
            </p>
            <p class="text-app-body-medium text-center text-subtext-90 mb-8">
                {{ __('Enter the code below to verify your email') }}
            </p>

            <form wire:submit="verify" novalidate>

                {{-- 4 OTP Inputs (static, not x-for, so Alpine $refs work reliably) --}}
                <div
                    class="flex justify-center gap-3 mb-6"
                    x-data="{
                        syncToWire() {
                            const vals = [
                                $refs.otp0.value,
                                $refs.otp1.value,
                                $refs.otp2.value,
                                $refs.otp3.value
                            ];
                            $wire.set('otp', vals);
                        },
                        handleInput(idx, event) {
                            // Hanya izinkan angka
                            let val = event.target.value.replace(/\D/g, '');
                            if (val.length > 1) val = val.slice(-1);
                            event.target.value = val;
                            this.syncToWire();
                            // Auto-advance ke input berikutnya
                            if (val !== '' && idx < 3) {
                                $refs['otp' + (idx + 1)].focus();
                            }
                        },
                        handleKeydown(idx, event) {
                            if (event.key === 'Backspace' && event.target.value === '' && idx > 0) {
                                $refs['otp' + (idx - 1)].focus();
                            }
                        },
                        handlePaste(event) {
                            event.preventDefault();
                            const text = (event.clipboardData || window.clipboardData)
                                .getData('text').replace(/\D/g, '').slice(0, 4);
                            [$refs.otp0, $refs.otp1, $refs.otp2, $refs.otp3].forEach((el, i) => {
                                el.value = text[i] || '';
                            });
                            this.syncToWire();
                            const nextIdx = Math.min(text.length, 3);
                            $refs['otp' + nextIdx].focus();
                        }
                    }"
                    x-init="$nextTick(() => $refs.otp0.focus())"
                >
                    @foreach([0,1,2,3] as $i)
                    <input
                        x-ref="otp{{ $i }}"
                        data-otp-input="{{ $i }}"
                        type="text"
                        inputmode="numeric"
                        maxlength="1"
                        autocomplete="off"
                        @input="handleInput({{ $i }}, $event)"
                        @keydown="handleKeydown({{ $i }}, $event)"
                        @paste="handlePaste($event)"
                        class="w-16 h-20 text-center text-3xl font-merriweather text-text-80 bg-brand-50 border border-card-border rounded-xl focus:ring-2 focus:ring-secondary-200 focus:border-secondary-200 outline-none transition-all"
                    >
                    @endforeach
                </div>

                @if($errorMessage)
                    <p class="text-app-body-small text-danger-100 mb-4 text-center">{{ $errorMessage }}</p>
                @endif

                <button
                    type="submit"
                    class="w-full py-3 text-app-feature text-white font-medium transition-colors bg-secondary-300 rounded-lg hover:bg-[#634735] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary-200 shadow-md"
                >
                    {{ __('Next') }}
                </button>

                <div class="text-center mt-5 text-app-body-small" wire:poll.1s="decrementCooldown">
                    <span class="text-subtext-90">{{ __("Didn't receive the email?") }}</span>
                    @if($resendCooldown > 0)
                        <span class="text-text-80 ml-1">{{ __('Resend Code in :seconds s', ['seconds' => $resendCooldown]) }}</span>
                    @else
                        <button type="button" wire:click="resendOtp" class="text-interactive-100 hover:underline ml-1 focus:outline-none">
                            {{ __('Resend Code') }}
                        </button>
                    @endif
                </div>

            </form>
        </div>
    </div>

    @if($isSuccess)
    <div class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-bg-main/40 backdrop-blur-md transition-all duration-300">
        <svg class="animate-spin mb-4 h-12 w-12 text-secondary-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="text-xl font-merriweather font-semibold text-text-80 animate-pulse">{{ __('Verifying...') }}</p>
    </div>
    @endif
</div>
