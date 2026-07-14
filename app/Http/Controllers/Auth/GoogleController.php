<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect()
    {
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function callback()
    {
        try {
            // Kita tambahkan setHttpClient(['verify' => false]) untuk menghindari 
            // "cURL error 60: SSL certificate problem" yang sering terjadi di localhost Windows.
            $googleUser = Socialite::driver('google')
                ->setHttpClient(new \GuzzleHttp\Client(['verify' => false]))
                ->user();

            // Find an existing user by email or google_id
            $user = User::where('email', $googleUser->getEmail())
                        ->orWhere('google_id', $googleUser->getId())
                        ->first();

            $isNewUser = false;

            if ($user) {
                // If the user exists, update their google_id and token
                $user->update([
                    'google_id' => $googleUser->getId(),
                    'google_token' => $googleUser->token,
                ]);
            } else {
                // Create a new user
                $user = User::create([
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'google_token' => $googleUser->token,
                ]);
                
                $isNewUser = true;
            }

            Auth::login($user, true);
            
            // Memastikan kita mendapatkan data profile terbaru
            $user->refresh();

            // Jika username-nya masih kosong, paksa ke halaman onboarding
            if (!$user->profile || empty($user->profile->username)) {
                return redirect('/onboarding');
            }

            return redirect()->route('dashboard');

        } catch (\Exception $e) {
            // Mencatat error aslinya ke laravel.log agar kita tahu masalah sebenarnya
            \Illuminate\Support\Facades\Log::error('Google Auth Error: ' . $e->getMessage());
            
            return redirect()->route('login')->withErrors([
                'email' => __('Authentication failed. Please try again.'),
            ]);
        }
    }
}
