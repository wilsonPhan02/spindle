<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Helpers\TextHelper;

class ChapterCard extends Model
{
    use HasUuids;

    protected $primaryKey = 'chapter_card_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'structure_section_id',
        'title',
        'summary',
        'is_custom_summary',
        'cover_image_path',
        'order_index',
        'status',
    ];

    protected function title(): Attribute
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

    public function section(): BelongsTo
    {
        return $this->belongsTo(StructureSection::class, 'structure_section_id', 'structure_section_id');
    }

    public function manuscript(): HasMany
    {
        return $this->hasMany(Manuscript::class, 'chapter_card_id', 'chapter_card_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'chapter_tag', 'chapter_card_id', 'tag_id');
    }

    public function characters()
    {
        return $this->belongsToMany(Character::class, 'chapter_character', 'chapter_card_id', 'character_id');
    }
}
