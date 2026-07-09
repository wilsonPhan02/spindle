<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StructureSection extends Model
{
    use HasUuids;

    protected $primaryKey = 'structure_section_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['structure_section_id'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id', 'template_id');
    }
}
