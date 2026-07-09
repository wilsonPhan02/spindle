<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Hashtag extends Model
{
    use HasUuids;

    protected $primaryKey = 'hashtag_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['name', 'slug'];

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class, 'character_hashtag', 'hashtag_id', 'character_id');
    }
}
