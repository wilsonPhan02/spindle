<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Manuscript extends Model
{
    use HasUuids;

    protected $primaryKey = 'manuscript_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = ['manuscript_id'];

    public function chapter()
    {
        return $this->belongsTo(ChapterCard::class, 'chapter_card_id', 'chapter_card_id');
    }
}