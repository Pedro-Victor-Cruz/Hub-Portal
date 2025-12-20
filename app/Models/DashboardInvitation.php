<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Model DashboardInvitation
 *
 * Representa um convite temporário para acesso a um dashboard
 *
 * @property int $id
 * @property int $dashboard_id
 * @property string $token
 * @property string|null $name
 * @property string|null $description
 * @property \Carbon\Carbon|null $expires_at
 * @property bool $active
 * @property int|null $max_uses
 * @property int $uses_count
 * @property int|null $created_by
 */
class DashboardInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'dashboard_id',
        'token',
        'name',
        'description',
        'expires_at',
        'active',
        'max_uses',
        'uses_count',
        'created_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'active' => 'boolean',
        'max_uses' => 'integer',
        'uses_count' => 'integer',
    ];

    protected $hidden = [
        'id',
        'dashboard_id',
        'created_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::uuid()->toString();
            }
        });
    }

    // Relacionamentos
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(DashboardInvitationLog::class, 'invitation_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeValid($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereRaw('uses_count < max_uses');
            });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    // Métodos de validação
    public function isValid(): bool
    {
        if (!$this->active) return false;
        if ($this->isExpired()) return false;
        if ($this->hasReachedMaxUses()) return false;
        return true;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasReachedMaxUses(): bool
    {
        return $this->max_uses !== null && $this->uses_count >= $this->max_uses;
    }

    public function getRemainingUses(): ?int
    {
        // Se max_uses for null, significa usos ilimitados
        if ($this->max_uses === null) return null;
        return max(0, $this->max_uses - $this->uses_count);
    }

    public function getDaysUntilExpiration(): ?int
    {
        if ($this->expires_at === null) return null;
        return max(0, now()->startOfDay()->diffInDays($this->expires_at->startOfDay()));
    }

    public function getTotalDaysUntilExpiration(): ?int
    {
        if ($this->expires_at === null) return null;
        return max(0, $this->created_at->startOfDay()->diffInDays($this->expires_at->startOfDay()));
    }

    /**
     * Registra um acesso usando este convite
     */
    public function recordAccess(?int $userId = null, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        $this->increment('uses_count');

        DashboardInvitationLog::create([
            'invitation_id' => $this->id,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'accessed_at' => now(),
        ]);
    }

    /**
     * Gera URL completa do convite
     */
    public function getUrl(): string
    {
        return "http://localhost:4200/dashboard/invite/{$this->token}";
    }

    /**
     * Revoga o convite (desativa)
     */
    public function revoke(): void
    {
        $this->update(['active' => false]);
    }

    /**
     * Obtém informações do status do convite
     */
    public function getStatusInfo(): array
    {
        $status = 'valid';
        $message = 'Convite válido';

        if (!$this->active) {
            $status = 'revoked';
            $message = 'Convite revogado';
        } elseif ($this->isExpired()) {
            $status = 'expired';
            $message = 'Convite expirado';
        } elseif ($this->hasReachedMaxUses()) {
            $status = 'max_uses_reached';
            $message = 'Limite de usos atingido';
        }

        return [
            'status' => $status,
            'message' => $message,
            'is_valid' => $this->isValid(),
            'expires_at' => $this->expires_at,
            'days_until_expiration' => $this->getDaysUntilExpiration(),
            'total_days_until_expiration' => $this->getTotalDaysUntilExpiration(),
            'uses_count' => $this->uses_count,
            'max_uses' => $this->max_uses,
            'remaining_uses' => $this->getRemainingUses(),
        ];
    }
}