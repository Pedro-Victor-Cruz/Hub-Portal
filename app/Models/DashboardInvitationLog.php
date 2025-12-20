<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model DashboardInvitationLog
 *
 * Registra cada acesso realizado através de um convite
 *
 * @property int $id
 * @property int $invitation_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property int|null $user_id
 * @property \Carbon\Carbon $accessed_at
 */
class DashboardInvitationLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'invitation_id',
        'ip_address',
        'user_agent',
        'user_id',
        'accessed_at',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
    ];

    public function invitation(): BelongsTo
    {
        return $this->belongsTo(DashboardInvitation::class, 'invitation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}