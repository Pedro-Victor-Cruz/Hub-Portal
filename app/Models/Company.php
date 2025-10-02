<?php

namespace App\Models;

use App\Enums\IntegrationType;
use App\Services\Utils\Helpers\CpfCnpjHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Company
 *
 * Representa uma empresa cliente no sistema.
 * Cada empresa pode ter vários usuários e múltiplas integrações vinculadas.
 *
 * @property int $id
 * @property string $name Nome da empresa
 * @property string $key Chave única da empresa (geralmente um identificador único)
 * @property string|null $email Email de contato da empresa
 * @property string|null $cnpj CNPJ da empresa (apenas números)
 * @property int|null $responsible_user_id ID do usuário responsável principal pela empresa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property User|null $responsibleUser Usuário responsável pela empresa
 * @property Collection|User[] $users Lista de usuários da empresa
 * @property Collection|Integration[] $integrations Lista de integrações da empresa
 */
class Company extends Model
{
    use HasFactory;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'key',
        'email',
        'responsible_user_id',
        'cnpj',
    ];

    protected $appends = [
        'cnpj_formatted',
    ];

    /**
     * Usuário responsável principal pela empresa.
     */
    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    /**
     * Lista de usuários vinculados à empresa.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
    }

    /**
     * Lista de integrações vinculadas à empresa.
     */
    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class, 'company_id');
    }

    /**
     * Obtém integrações ativas da empresa.
     */
    public function activeIntegrations(): HasMany
    {
        return $this->integrations()->active();
    }

    /**
     * Obtém uma integração específica por nome.
     */
    public function getIntegration(string $integrationName): ?Integration
    {
        return $this->integrations()->byType($integrationName)->first();
    }

    /**
     * Obtém uma integração ativa específica por nome.
     */
    public function getActiveIntegration(IntegrationType $integration): ?Integration
    {
        return $this->activeIntegrations()->byType($integration->value)->first();
    }

    /**
     * Verifica se a empresa possui uma integração específica ativa.
     */
    public function hasActiveIntegration(string $integrationName): bool
    {
        return $this->activeIntegrations()->byType($integrationName)->exists();
    }

    /**
     * Verifica se a empresa possui alguma integração ativa.
     */
    public function hasAnyActiveIntegration(): bool
    {
        return $this->activeIntegrations()->exists();
    }

    /**
     * Define o valor do atributo 'key' como uma string formatada.
     * Removendo espaços e caracteres especiais, e convertendo para maiúsculas.
     * Exemplo: SAFIA, SAOPEDRO
     *
     * @param string $value
     */
    public function setKeyAttribute(string $value): void
    {
        $this->attributes['key'] = strtoupper(preg_replace('/[^A-Z0-9]/', '', $value));
    }

    /**
     * Define o valor do atributo 'cnpj' como uma string formatada.
     * Removendo espaços e caracteres especiais, e garantindo que tenha 14 dígitos.
     *
     * @param string $value
     */
    public function setCnpjAttribute(string $value): void
    {
        $this->attributes['cnpj'] = CpfCnpjHelper::unformat($value);
    }

    /**
     * Obtém o CNPJ formatado com pontos e traço.
     *
     * @return string
     */
    public function getCnpjFormattedAttribute(): string
    {
        return CpfCnpjHelper::format($this->cnpj);
    }
}