<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = ['name'];

    public function chapters(): BelongsToMany
    {
        return $this->belongsToMany(ChapterCard::class, 'chapter_tag', 'tag_id', 'chapter_card_id');
    }

    public function addTag(string $tagName)
    {
        $tagName = strtolower(trim($tagName));

        $tag = Tag::firstOrCreate(['name' => $tagName]);

        return $this->tags()->syncWithoutDetaching([$tag->id]);
    }
}
