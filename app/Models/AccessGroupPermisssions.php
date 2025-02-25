<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessGroupPermisssions extends Model
{
    use HasFactory;

    protected $table = 'access_group_permissions';

    protected $fillable = [
        'access_group_id',
        'permission_id',
    ];

    public function accessGroup(): BelongsTo
    {
        return $this->belongsTo(AccessGroup::class);
    }

    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }
}
