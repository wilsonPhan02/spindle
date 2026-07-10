<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Helpers\TextHelper;

class RelationshipType extends Model
{
    use HasUuids;

    protected $primaryKey = 'relationship_type_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['project_id', 'name', 'text_color', 'bg_color'];

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

    public function relationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'relationship_type_id', 'relationship_type_id');
    }
}
