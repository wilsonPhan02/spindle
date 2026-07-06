<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Relationship extends Model
{
    use HasUuids;

    protected $primaryKey = 'relationship_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['from_id', 'to_id', 'relationship_type_id', 'context', 'curve_offset'];

    public function from()
    {
        return $this->belongsTo(Character::class, 'from_id', 'character_id');
    }

    public function to()
    {
        return $this->belongsTo(Character::class, 'to_id', 'character_id');
    }

    public function relationshipType()
    {
        return $this->belongsTo(RelationshipType::class, 'relationship_type_id', 'relationship_type_id');
    }
}
