<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Wajib import ini
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    protected $primaryKey = 'user_id';

    // Because we use UUID, we must tell Eloquent that it's not auto-incrementing
    public $incrementing = false;
    protected $keyType = 'string';

    // Fields that are mass assignable
    protected $fillable = [
        'email',
        'password',
    ];

    // Fields to be hidden when serialized to array/json
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // One-to-one relationship with Profile
    public function profile(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Profile::class, 'user_id', 'user_id');
    }

    // Typecast to specific data types
    protected function casts(): array
    {
        return [
            'password' => 'hashed', // Automatically hash password on save
        ];
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

    public function sections(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Section::class, 'user_id', 'user_id');
    }

    public function projects(): \Illuminate\Database\Eloquent\Relations\HasMany
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
