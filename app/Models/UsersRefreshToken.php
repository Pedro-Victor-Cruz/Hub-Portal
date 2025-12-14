<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UsersRefreshToken extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'users_refresh_tokens';

    protected $casts = [
        'user_id' => 'int',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked' => 'bool'
    ];

    protected $hidden = [
        'token'
    ];

    protected $fillable = [
        'user_id',
        'token',
        'last_used_at',
        'last_ip',
        'login_method',
        'operating_system',
        'os_version',
        'platform',
        'model',
        'device_name',
        'manufacturer',
        'latitude',
        'longitude',
        'expires_at',
        'revoked'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
