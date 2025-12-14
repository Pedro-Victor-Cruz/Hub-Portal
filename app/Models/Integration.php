<?php

namespace App\Models;

use App\Enums\IntegrationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Integration
 *
 * Representa uma integração vinculada a uma empresa.
 * Cada empresa pode ter múltiplas integrações com diferentes sistemas.
 *
 * @property int $id
 * @property string $integration_name Nome da integração (ex: sankhya, totvs, calendar)
 * @property array $configuration Configurações específicas da integração
 * @property bool $active Indica se esta integração está ativa
 * @property Carbon|null $last_sync_at Data da última sincronização
 * @property array|null $sync_status Status da última sincronização
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 */
class Integration extends Model
{
    use HasFactory;

    protected $table = 'integrations';

    protected $fillable = [
        'integration_name',
        'configuration',
        'active',
        'last_sync_at',
        'sync_status',
    ];

    protected $casts = [
        'active' => 'boolean',
        'configuration' => 'array',
        'sync_status' => 'array',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Verifica se a integração está ativa
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Obtém uma configuração específica da integração
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->configuration, $key, $default);
    }

    /**
     * Define uma configuração específica da integração
     */
    public function setConfig(string $key, mixed $value): void
    {
        $config = $this->configuration ?? [];
        data_set($config, $key, $value);
        $this->configuration = $config;
    }

    /**
     * Atualiza o status da sincronização
     */
    public function updateSyncStatus(array $status): void
    {
        $this->sync_status = $status;
        $this->last_sync_at = now();
        $this->save();
    }

    /**
     * Verifica se a integração teve sincronização bem-sucedida recentemente
     */
    public function hasRecentSuccessfulSync(int $minutesAgo = 60): bool
    {
        if (!$this->last_sync_at) {
            return false;
        }

        $isRecent = $this->last_sync_at->isAfter(now()->subMinutes($minutesAgo));
        $isSuccessful = ($this->sync_status['success'] ?? false) === true;

        return $isRecent && $isSuccessful;
    }

    /**
     * Retorna a classe de integração correspondente
     */
    public function getIntegrationClass(): ?string
    {
        return config("integration.drivers.{$this->integration_name}");
    }

    /**
     * Scope para filtrar apenas integrações ativas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para filtrar por tipo de integração
     */
    public function scopeType($query, IntegrationType $integrationType)
    {
        return $query->where('integration_name', $integrationType->value);
    }

    public function getIntegrationName(): string
    {
        return IntegrationType::tryFrom($this->integration_name)?->label ?? $this->integration_name;
    }

    public function getIntegrationType(): IntegrationType
    {
        return IntegrationType::from($this->integration_name);
    }
}