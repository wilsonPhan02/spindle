<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Section extends Model
{
    use HasUuids;

    // Kasih tahu Laravel kalau primary key-nya adalah section_id
    protected $primaryKey = 'section_id';

    public $incrementing = false;
    protected $keyType = 'string';

    // Sesuaikan fillable dengan kolom di foto database lu
    protected $fillable = [
        'section_id',
        'user_id',
        'title',
        'archived_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Tambahkan di dalam class Section
    public function projects()
    {
        return $this->hasMany(Project::class, 'section_id', 'section_id');
    }
}
