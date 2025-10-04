<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class SystemLog extends Model
{
    const UPDATED_AT = null; // Logs não são atualizados

    protected $fillable = [
        'level',
        'action',
        'module',
        'loggable_type',
        'loggable_id',
        'old_values',
        'new_values',
        'changes',
        'description',
        'ip_address',
        'user_agent',
        'method',
        'url',
        'user_id',
        'user_name',
        'batch_id',
        'session_id',
        'metadata',
        'trace_id',
        'response_time',
        'status_code',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changes' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    // Níveis de log
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_NOTICE = 'notice';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    const LEVEL_ALERT = 'alert';
    const LEVEL_EMERGENCY = 'emergency';

    // Actions comuns
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_LOGIN_FAILED = 'login_failed';
    const ACTION_CREATED = 'created';
    const ACTION_UPDATED = 'updated';
    const ACTION_DELETED = 'deleted';
    const ACTION_VIEWED = 'viewed';
    const ACTION_RESTORED = 'restored';
    const ACTION_ACCESSED = 'accessed';
    const ACTION_UNAUTHORIZED_ACCESS = 'unauthorized_access';
    const ACTION_UNAUTHORIZED_EDIT_ATTEMPT = 'unauthorized_edit_attempt';

    /**
     * Relacionamento com o usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento polimórfico com o modelo logado
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope para filtrar por nível
     */
    public function scopeLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    /**
     * Scope para filtrar por ação
     */
    public function scopeAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * Scope para filtrar por módulo
     */
    public function scopeModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    /**
     * Scope para filtrar por usuário
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para filtrar por período
     */
    public function scopeDateRange(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope para logs críticos
     */
    public function scopeCritical(Builder $query): Builder
    {
        return $query->whereIn('level', [
            self::LEVEL_ERROR,
            self::LEVEL_CRITICAL,
            self::LEVEL_ALERT,
            self::LEVEL_EMERGENCY
        ]);
    }

    /**
     * Scope para agrupar por batch
     */
    public function scopeByBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Formata a mensagem do log
     */
    public function getFormattedMessageAttribute(): string
    {
        $user = $this->user_name ?? 'Sistema';
        $action = $this->action;
        $module = $this->module ?? 'sistema';

        return "{$user} executou a ação '{$action}' no módulo '{$module}'";
    }

    /**
     * Verifica se é um log de erro
     */
    public function isError(): bool
    {
        return in_array($this->level, [
            self::LEVEL_ERROR,
            self::LEVEL_CRITICAL,
            self::LEVEL_ALERT,
            self::LEVEL_EMERGENCY
        ]);
    }
}