<?php

namespace App\Models;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany; // Wajib import ini
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasUuids, Notifiable;

    protected $primaryKey = 'user_id';

    // Because we use UUID, we must tell Eloquent that it's not auto-incrementing
    public $incrementing = false;

    protected $keyType = 'string';

    // Fields that are mass assignable
    protected $fillable = [
        'email',
        'password',
        'google_id',
        'google_token',
        'email_otp',
        'email_otp_expires_at',
        'email_verified_at',
    ];

    // Fields to be hidden when serialized to array/json
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // One-to-one relationship with Profile
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class, 'user_id', 'user_id');
    }

    // Typecast to specific data types
    protected function casts(): array
    {
        return [
            'password' => 'hashed', // Automatically hash password on save
            'email_otp_expires_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    public function generateOtp()
    {
        $code = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $this->update([
            'email_otp' => $code,
            'email_otp_expires_at' => now()->addMinutes(10),
        ]);
        return $code;
    }

    public function verifyOtp($code)
    {
        if ($this->email_otp === $code && $this->email_otp_expires_at && now()->lessThanOrEqualTo($this->email_otp_expires_at)) {
            $this->update([
                'email_verified_at' => now(),
                'email_otp' => null,
                'email_otp_expires_at' => null,
            ]);
            return true;
        }
        return false;
    }

    public function hasVerifiedEmail()
    {
        return !is_null($this->email_verified_at);
    }

    public function sendPasswordResetNotification($token)
    {
        $url = url(route('password.reset', [
            'token' => $token,
            'email' => $this->getEmailForPasswordReset(),
        ], false));

        ResetPassword::toMailUsing(function ($notifiable, $token) use ($url) {
            return (new MailMessage)
                ->subject('Rewrite Your Story - Reset Password')
                ->view('emails.forgot-password', ['url' => $url]);
        });

        $this->notify(new ResetPassword($token));
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'user_id', 'user_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'user_id', 'user_id');
    }

    protected static function booted(): void
    {
        static::created(function ($user) {
            // Automatically create profile data when user is created
            $user->profile()->create([
                'username' => null,
                // leave other fields like avatar_url default to null for now
            ]);
        });
    }
}
