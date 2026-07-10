<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Helpers\TextHelper;

class Section extends Model
{
    use HasUuids;

    // Inform Laravel that the primary key is section_id
    protected $primaryKey = 'section_id';

    public $incrementing = false;

    protected $keyType = 'string';

    // Adjust fillable fields with the database columns
    protected $fillable = [
        'section_id',
        'user_id',
        'title',
        'archived_at',
    ];

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => TextHelper::localizeDefaultName($value),
            set: fn ($value) => TextHelper::normalizeDefaultName($value),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'section_id', 'section_id');
    }

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }
}
