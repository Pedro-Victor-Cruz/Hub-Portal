<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Class CompanyErpSetting
 *
 * Representa as configurações de ERP vinculadas a uma empresa.
 * Cada empresa pode ter uma ou mais configurações dependendo do ERP utilizado.
 *
 * @property int $id
 * @property int $company_id ID da empresa associada
 * @property string $erp_name Nome do ERP (ex: sankhya, totvs, bling)
 * @property string|null $username Nome de usuário usado na autenticação (se aplicável)
 * @property string|null $secret_key Chave secreta ou senha de autenticação
 * @property string|null $token Token fixo (usado em integrações por token)
 * @property string|null $base_url URL base da API do ERP
 * @property string|null $auth_type Tipo de autenticação: token, session ou oauth
 * @property array|null $extra_config Configurações adicionais em formato JSON
 * @property bool $active Indica se esta configuração está ativa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CompanyErpSetting extends Model
{
    use HasFactory;

    protected $table = 'company_erp_settings';

    protected $fillable = [
        'company_id',
        'erp_name',
        'username',
        'secret_key',
        'token',
        'base_url',
        'auth_type',
        'extra_config',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'extra_config' => 'array',
    ];

    public function setErpNameAttribute($value): void
    {
        $this->attributes['erp_name'] = strtoupper(str_replace(' ', '-', $value));
    }

    /**
     * Relacionamento: Configuração pertence a uma empresa
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Retorna a classe do driver ERP configurada
     */
    public function getDriverClass(): mixed
    {
        return config("erp.drivers.{$this->erp_name}");
    }

    /**
     * Retorna uma configuração específica do ERP para esta empresa.
     * Se a configuração não existir, retorna o valor padrão fornecido.
     *
     * Exemplo de uso:
     * $timeout = $companyErpSetting->getErpConfig('timeout', 30);
     *
     * @param string $key Chave da configuração desejada
     * @param mixed|null $default Valor padrão se a configuração não existir
     */
    public function getErpConfig(string $key, mixed $default = null)
    {
        return config("erp.settings.{$this->erp_name}.{$key}", $default);
    }

    /**
     * Verifica se a configuração de ERP está ativa.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

}
