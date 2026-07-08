<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Illuminate\Support\Facades\Auth;
use App\Models\OtpCode;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

new #[Layout('layouts.guest')] #[Title('Verify OTP - Spindle')] class extends Component
{
    public $otp = '';
    public $isSuccess = false;

    public function mount()
    {
        // Redirect if not logged in
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Redirect if already verified
        if (Auth::user()->email_verified_at) {
            return redirect()->route('dashboard');
        }
    }

    public function verify()
    {
        $this->validate([
            'otp' => ['required', 'string', 'size:6'],
        ], [
            'otp.required' => 'Please enter the OTP code.',
            'otp.size' => 'OTP code must be 6 digits.',
        ]);

        $user = Auth::user();
        
        // Find OTP
        $otpRecord = OtpCode::where('user_id', $user->user_id)
            ->where('otp_code', $this->otp)
            ->first();

        if (!$otpRecord) {
            $this->addError('otp', 'Invalid OTP code.');
            return;
        }

        if (now()->greaterThan($otpRecord->expires_at)) {
            $this->addError('otp', 'OTP code has expired. Please request a new one.');
            return;
        }

        // Valid OTP
        $user->email_verified_at = now();
        $user->save();

        // Delete all OTPs for this user
        OtpCode::where('user_id', $user->user_id)->delete();

        $this->isSuccess = true;
        $this->js("setTimeout(() => window.location.href = '/onboarding', 1200)");
    }

    public function resend()
    {
        $user = Auth::user();

        // Delete old OTPs
        OtpCode::where('user_id', $user->user_id)->delete();

        // Generate new OTP
        $newOtp = sprintf("%06d", mt_rand(1, 999999));
        
        OtpCode::create([
            'user_id' => $user->user_id,
            'otp_code' => $newOtp,
            'expires_at' => now()->addMinutes(15),
        ]);

        // Send Email & Log
        Log::info('Kode OTP (Resend) untuk ' . $user->email . ' adalah: ' . $newOtp);
        Mail::to($user->email)->send(new OtpMail($newOtp));

        session()->flash('message', 'A new OTP has been sent to your email.');
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

        <div class="w-full max-w-[460px] p-10 bg-[#fdfbf8] border border-[#e8dfd5] rounded-xl shadow-sm">

            <h1 class="mb-6 text-[1.75rem] font-merriweather text-center text-[#1a1a1a]">Email Verification</h1>
            
            <p class="mb-2 text-center text-[15px] font-medium text-[#8a8a8a]">
                We've sent a One-Time Password (OTP) to your<br>registered email address
            </p>
            <p class="mb-8 text-center text-[15px] font-medium text-[#8a8a8a]">
                Enter the code below to verify your email
            </p>

            @if (session()->has('message'))
                <div class="p-3 mb-6 rounded-lg bg-green-100/10 border border-green-500/50 flex items-center justify-center gap-2">
                    <span class="text-[14px] text-green-500 font-medium">{{ session('message') }}</span>
                </div>
            @endif

            <form wire:submit="verify" novalidate 
                x-data="{
                    otp: ['', '', '', '', '', ''],
                    updateWire() { $wire.set('otp', this.otp.join('')) },
                    handleInput(index, event) {
                        const val = event.target.value;
                        if (val) {
                            this.otp[index] = val.slice(-1);
                            if (index < 5) {
                                let nextInput = document.getElementById('otp_' + (index + 1));
                                if(nextInput) nextInput.focus();
                            }
                        }
                        this.updateWire();
                    },
                    handleKeydown(index, event) {
                        if (event.key === 'Backspace' && !this.otp[index] && index > 0) {
                            let prevInput = document.getElementById('otp_' + (index - 1));
                            if(prevInput) {
                                prevInput.focus();
                                // Optional: clear previous value on backspace
                                // this.otp[index - 1] = '';
                                // this.updateWire();
                            }
                        }
                    },
                    handlePaste(event) {
                        const paste = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                        if(paste) {
                            for(let i = 0; i < paste.length; i++) {
                                this.otp[i] = paste[i];
                            }
                            this.updateWire();
                            const nextFocus = Math.min(paste.length, 5);
                            let nextInput = document.getElementById('otp_' + nextFocus);
                            if(nextInput) nextInput.focus();
                        }
                    }
                }"
                class="space-y-6">

                <div class="flex justify-center gap-2 sm:gap-3">
                    <template x-for="(digit, index) in otp" :key="index">
                        <input type="text" :id="'otp_' + index" x-model="otp[index]" @input="handleInput(index, $event)" @keydown="handleKeydown(index, $event)" @paste.prevent="handlePaste($event)" maxlength="2" inputmode="numeric" pattern="[0-9]*" class="w-12 h-16 sm:w-14 sm:h-20 text-center text-2xl border border-[#c4b5a3] rounded-lg focus:border-[#78563c] focus:ring-1 focus:ring-[#78563c] outline-none bg-transparent text-[#1a1a1a]">
                    </template>
                </div>
                @error('otp') <span class="text-xs text-red-500 mt-2 block text-center">{{ $message }}</span> @enderror

                <button type="submit" 
                    class="w-full py-3.5 text-[15px] font-medium text-white transition-colors bg-[#78563c] rounded-md hover:bg-[#684a32] focus:outline-none">
                    Next
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-[13px] font-medium text-[#8a8a8a]">
                    Didn't receive the email?
                    <button type="button" wire:click="resend" class="text-[#3b82f6] hover:underline underline-offset-2">Resend Code</button>
                </p>
            </div>
        </div>

    </div>

    @if($isSuccess)
    <div>
        <div class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-[#f3ede6]/80 backdrop-blur-sm transition-all duration-300">
            <svg class="animate-spin mb-4 h-12 w-12 text-[#78563c]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-xl font-merriweather font-semibold text-[#1a1a1a] animate-pulse">Verifying...</p>
        </div>
    </div>
    @endif
</div>
