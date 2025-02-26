<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntityProperty extends Model
{
    use HasFactory;

    protected $table = 'entity_properties';

    protected $fillable = [
        'entity_id',
        'field_name',
        'field_type',
        'field_label',
        'is_required',
        'is_unique',
        'show_in_form'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_unique'   => 'boolean',
        'show_in_form' => 'boolean'
    ];

    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class, 'entity_id', 'id');
    }
}
