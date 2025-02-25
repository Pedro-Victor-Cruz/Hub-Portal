<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'departments';

    protected $fillable = [
        'portal_id',
        'name',
        'slug',
        'is_default',
    ];

    protected $appends = [
        'is_default_text',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function portal(): BelongsTo
    {
        return $this->belongsTo(Portal::class);
    }

    public function accessGroups(): HasMany
    {
        return $this->hasMany(AccessGroup::class);
    }

    public function getIsDefaultTextAttribute(): string
    {
        return $this->is_default ? 'Sim' : 'Não';
    }
}
