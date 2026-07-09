<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    use HasUuids;

    protected $primaryKey = 'template_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['template_id'];

    public function sections(): HasMany
    {
        return $this->hasMany(StructureSection::class, 'template_id', 'template_id');
    }
}
