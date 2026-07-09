<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CharacterDetailField extends Model
{
    use HasUuids;

    protected $primaryKey = 'character_detail_field_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['character_detail_group_id', 'name', 'order'];

    public function group(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CharacterDetailGroup::class, 'character_detail_group_id', 'character_detail_group_id');
    }

    public function values(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CharacterDetailValue::class, 'character_detail_field_id', 'character_detail_field_id');
    }
}
