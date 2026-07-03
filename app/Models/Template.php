<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Template extends Model
{
    use HasUuids;

    protected $primaryKey = 'template_id'; 
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $guarded = ['template_id'];

    public function sections()
    {
        return $this->hasMany(StructureSection::class, 'template_id', 'template_id');
    }
}
