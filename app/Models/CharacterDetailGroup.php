<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CharacterDetailGroup extends Model
{
    use HasUuids;

    protected $primaryKey = 'character_detail_group_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['project_id', 'name', 'order'];

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function fields(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CharacterDetailField::class, 'character_detail_group_id', 'character_detail_group_id');
    }
}
