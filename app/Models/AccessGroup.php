<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessGroup extends Model
{
    use HasFactory;

    protected $table = 'access_groups';

    protected $fillable = [
        'department_id',
        'name',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function permissions(): BelongsTo
    {
        return $this->belongsTo(Permission::class, 'access_group_permissions');
    }

    public function users(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_access_groups');
    }
}
