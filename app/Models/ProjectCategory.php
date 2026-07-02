<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProjectCategory extends Model
{
    use HasUuids;
    protected $primaryKey = 'category_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $touches = ['project'];

    protected $fillable = ['project_id', 'name'];

    public function project() {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
