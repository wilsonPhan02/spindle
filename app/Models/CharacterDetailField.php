<?php

namespace App\Models;

use App\Helpers\TextHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharacterDetailField extends Model
{
    use HasUuids;

    protected $primaryKey = 'character_detail_field_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['character_detail_group_id', 'name', 'order'];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TextHelper::localizeDefaultName($value),
            set: fn ($value) => TextHelper::normalizeDefaultName($value),
        );
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CharacterDetailGroup::class, 'character_detail_group_id', 'character_detail_group_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(CharacterDetailValue::class, 'character_detail_field_id', 'character_detail_field_id');
    }
}
