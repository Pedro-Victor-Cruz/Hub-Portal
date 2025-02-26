<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntityRelationship extends Model
{
    use HasFactory;

    protected $table = 'entity_relationships';

    protected $fillable = [
        'parent_entity_id',
        'child_entity_id',
        'parent_field_name',
        'child_field_name',
        'relationship_type',
    ];

    // Relacionamento com a entidade pai
    public function parentEntity()
    {
        return $this->belongsTo(Entity::class, 'parent_entity_id');
    }

    // Relacionamento com a entidade filha
    public function childEntity()
    {
        return $this->belongsTo(Entity::class, 'child_entity_id');
    }
}
