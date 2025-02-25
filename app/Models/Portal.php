<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Portal extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'portals';

    protected $fillable = [
        'name',
        'slug',
        'phone',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'portal_users', 'portal_id', 'user_id');
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class, 'portal_id');
    }
}
