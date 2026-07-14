<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterDetailValue extends Model
{
    use HasUuids;

    protected $primaryKey = 'character_detail_value_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['character_id', 'character_detail_field_id', 'value'];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class, 'character_id', 'character_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(CharacterDetailField::class, 'character_detail_field_id', 'character_detail_field_id');
    }
}
