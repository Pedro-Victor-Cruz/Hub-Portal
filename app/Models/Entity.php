<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entity extends Model
{
    use HasFactory;

    protected $table = 'entities';

    protected $fillable = [
        'entity_name',
        'table_name',
        'entity_label',
    ];

    public function properties(): HasMany
    {
        return $this->hasMany(EntityProperty::class, 'entity_id', 'id');
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(EntityRelationship::class, 'parent_entity_id', 'id');
    }
}
