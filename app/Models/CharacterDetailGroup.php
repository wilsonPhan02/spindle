<?php

namespace App\Models;

use App\Helpers\TextHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharacterDetailGroup extends Model
{
    use HasUuids;

    protected $primaryKey = 'character_detail_group_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['project_id', 'name', 'order'];

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TextHelper::localizeDefaultName($value),
            set: fn ($value) => TextHelper::normalizeDefaultName($value),
        );
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(CharacterDetailField::class, 'character_detail_group_id', 'character_detail_group_id');
    }
}
