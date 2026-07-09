<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Manuscript extends Model
{
    use HasUuids;

    protected $primaryKey = 'manuscript_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'chapter_card_id',
        'title',
        'content',
        'word_count',
    ];

    protected function casts(): array
    {
        return [
            'word_count' => 'integer',
        ];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(ChapterCard::class, 'chapter_card_id', 'chapter_card_id');
    }
}