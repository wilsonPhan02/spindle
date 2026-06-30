<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChapterCard extends Model
{
    use HasUuids;

    protected $primaryKey = 'chapter_card_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['chapter_card_id'];

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
}