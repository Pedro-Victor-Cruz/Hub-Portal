<?php

namespace App\Models;

use App\Facades\DynamicQueryManager;
use App\Facades\ErpManager;
use App\Facades\ServiceManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class DynamicQuery
 *
 * Representa uma consulta dinâmica configurável no sistema.
 * Pode ser específica para uma empresa ou global.
 *
 * @property int $id
 * @property string $key Chave única da consulta (ex: produtos, clientes)
 * @property string $name Nome amigável da consulta
 * @property string|null $description Descrição da consulta
 * @property int|null $company_id ID da empresa (null para consultas globais)
 * @property string $service_slug Slug do serviço a ser utilizado
 * @property array|null $service_params Parâmetros específicos do serviço
 * @property string|null $query_config Configuração da query (SQL, endpoint, etc.)
 * @property array|null $fields_metadata Configuração dos campos de retorno
 * @property array|null $response_format Configuração de formatação da resposta
 * @property bool $active Se a consulta está ativa
 * @property bool $is_global Se a consulta é global ou específica da empresa
 * @property int $priority Prioridade para resolver conflitos
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property Company|null $company Empresa associada (se não for global)
 */
class DynamicQuery extends Model
{
    use HasFactory;

    protected $table = 'dynamic_queries';

    protected $fillable = [
        'key',
        'name',
        'description',
        'company_id',
        'service_slug',
        'service_params',
        'query_config',
        'fields_metadata',
        'response_format',
        'active',
        'is_global',
        'priority',
    ];

    protected $casts = [
        'service_params' => 'array',
        'fields_metadata' => 'array',
        'response_format' => 'array',
        'active' => 'boolean',
        'is_global' => 'boolean',
        'priority' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'required_params'
    ];

    /**
     * Relacionamento: Consulta pode pertencer a uma empresa
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope para consultas ativas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para consultas globais
     */
    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    /**
     * Scope para consultas de uma empresa específica
     */
    public function scopeForCompany($query, Company $company)
    {
        return $query->where('company_id', $company->id);
    }

    /**
     * Verifica se a classe do serviço existe
     */
    public function isValidServiceSlug(): bool
    {
        return ServiceManager::getServiceInfo($this->service_slug, $this->company) !== null;
    }

    /**
     * Obtém uma configuração específica dos metadados de campo
     */
    public function getFieldMetadata(string $fieldName, string $key = null): mixed
    {
        $metadata = $this->fields_metadata ?? [];

        if (!isset($metadata[$fieldName])) {
            return null;
        }

        return $key ? ($metadata[$fieldName][$key] ?? null) : $metadata[$fieldName];
    }

    /**
     * Define os metadados de um campo específico
     */
    public function setFieldMetadata(string $fieldName, array $metadata): self
    {
        $currentMetadata = $this->fields_metadata ?? [];
        $currentMetadata[$fieldName] = array_merge($currentMetadata[$fieldName] ?? [], $metadata);

        $this->fields_metadata = $currentMetadata;

        return $this;
    }

    /**
     * Verifica se um campo deve ser exibido
     */
    public function isFieldVisible(string $fieldName): bool
    {
        return $this->getFieldMetadata($fieldName, 'visible') !== false;
    }

    /**
     * Obtém o label amigável de um campo
     */
    public function getFieldLabel(string $fieldName): string
    {
        return $this->getFieldMetadata($fieldName, 'label') ?? $fieldName;
    }

    /**
     * Obtém o tipo de formatação de um campo
     */
    public function getFieldFormat(string $fieldName): ?string
    {
        return $this->getFieldMetadata($fieldName, 'format');
    }

    /**
     * Obtém os parâmetros obrigatórios para a consulta
     */
    public function getRequiredParamsAttribute(): array
    {
        try {
            return DynamicQueryManager::extractRequiredParams($this);
        } catch (\Exception $e) {
            return [];
        }
    }

}