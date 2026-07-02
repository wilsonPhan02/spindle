<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Project extends Model
{
    use HasUuids;

    protected $primaryKey = 'project_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id', 'template_id', 'section_id',
        'title', 'synopsis', 'cover_image_path',
        'is_pinned', 'archived_at',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'section_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
    
    public function categories() {
        return $this->hasMany(ProjectCategory::class, 'project_id', 'project_id');
    }

    public function template() 
    {
        return $this->belongsTo(Template::class, 'template_id', 'template_id');
    }

    public function chapterCards() 
    {
        return $this->hasMany(ChapterCard::class, 'project_id', 'project_id');
    }

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }
}
