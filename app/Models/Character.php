<?php

namespace App\Models;

use App\Helpers\TextHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    use HasUuids;

    protected $primaryKey = 'character_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_id', 'full_name', 'nick_name', 'bio',
        'image_path', 'canvas_top', 'canvas_left',
    ];

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TextHelper::localizeDefaultName($value),
            set: fn ($value) => TextHelper::normalizeDefaultName($value),
        );
    }

    protected function nickName(): Attribute
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

    public function hashtags(): BelongsToMany
    {
        return $this->belongsToMany(Hashtag::class, 'character_hashtag', 'character_id', 'hashtag_id');
    }

    public function outgoingRelationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'from_id', 'character_id');
    }

    public function incomingRelationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'to_id', 'character_id');
    }

    public function detailValues(): HasMany
    {
        return $this->hasMany(CharacterDetailValue::class, 'character_id', 'character_id');
    }

    public function chapters(): BelongsToMany
    {
        return $this->belongsToMany(ChapterCard::class, 'chapter_character', 'character_id', 'chapter_card_id');
    }
}
