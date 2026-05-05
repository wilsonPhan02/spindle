<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Wajib import ini

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids;

    protected $primaryKey = 'user_id';

    // karena pakai UUID, kita harus ngasih tau kalau kita ga auto increment
    public $incrementing = false;
    protected $keyType = 'string';

    // field yang boleh diisi secara massal
    protected $fillable = [
        'email',
        'password',
    ];

    // field yang harus disembunyikan ketika data diubah jadi array/json
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // function untuk relasi one-to-one dengan Profile
    public function profile() {
        return $this->hasOne(Profile::class, 'user_id', 'user_id');
    }

    // typecast ke tipe data tertentu
    protected function casts(): array
    {
        return [
            'password' => 'hashed', // biar password otomatis dihash pas disimpan
        ];
    }
}