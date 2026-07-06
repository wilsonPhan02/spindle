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
        'is_pinned', 'archived_at', 'icon_type', 
        'icon',
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

    public function characters() {
        return $this->hasMany(Character::class, 'project_id', 'project_id');
    }

    public function relationshipTypes() {
        return $this->hasMany(RelationshipType::class, 'project_id', 'project_id');
    }

    public function characterDetailGroups() {
        return $this->hasMany(CharacterDetailGroup::class, 'project_id', 'project_id');
    }

    protected static function booted()
    {
        static::created(function (Project $project) {
            $project->seedDefaultRelationshipTypes();
            $project->seedDefaultCharacterDetailGroups();
        });
    }

    public static function defaultRelationshipTypes(): array
    {
        return [
            ['name' => 'Parent', 'text_color' => '#1565C0', 'bg_color' => '#BBDEFB'],
            ['name' => 'Sibling', 'text_color' => '#6A1B9A', 'bg_color' => '#E1BEE7'],
            ['name' => 'Friend', 'text_color' => '#2E7D32', 'bg_color' => '#C8E6C9'],
            ['name' => 'Enemy', 'text_color' => '#C62828', 'bg_color' => '#FFCDD2'],
            ['name' => 'Neighbor', 'text_color' => '#00838F', 'bg_color' => '#B2EBF2'],
            ['name' => 'Lover', 'text_color' => '#AD1457', 'bg_color' => '#F8BBD0'],
        ];
    }

    public function seedDefaultRelationshipTypes(): void
    {
        foreach (self::defaultRelationshipTypes() as $type) {
            $this->relationshipTypes()->firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }

    public static function defaultCharacterDetailGroups(): array
    {
        return [
            [
                'name' => 'Personal Identity',
                'fields' => ['Gender', 'Age', 'Place of Birth', 'Date of Birth'],
            ],
            [
                'name' => 'Physical Appearance',
                'fields' => ['Height', 'Weight', 'Blood Type', 'Hair Color', 'Eye Color', 'Skin Color'],
            ],
        ];
    }

    public function seedDefaultCharacterDetailGroups(): void
    {
        foreach (self::defaultCharacterDetailGroups() as $groupIndex => $groupData) {
            $group = $this->characterDetailGroups()->firstOrCreate(
                ['name' => $groupData['name']],
                ['order' => $groupIndex]
            );

            foreach ($groupData['fields'] as $fieldIndex => $fieldName) {
                $group->fields()->firstOrCreate(
                    ['name' => $fieldName],
                    ['order' => $fieldIndex]
                );
            }
        }
    }
}
