<?php

namespace App\Models;

use App\Facades\DynamicQueryManager;
use App\Facades\ErpManager;
use App\Facades\ServiceManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
 * @property DynamicQueryFilter[] $filters Filtros associados à consulta
 * @property DynamicQueryFilter[] $activeFilters Filtros ativos associados à consulta
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
        'service_params'  => 'array',
        'fields_metadata' => 'array',
        'response_format' => 'array',
        'active'          => 'boolean',
        'is_global'       => 'boolean',
        'priority'        => 'integer',
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
     * Relacionamento: Consulta pode ter múltiplos filtros
     */
    public function filters(): HasMany
    {
        return $this->hasMany(DynamicQueryFilter::class)->ordered();
    }

    /**
     * Relacionamento: Apenas filtros ativos
     */
    public function activeFilters(): HasMany
    {
        return $this->hasMany(DynamicQueryFilter::class)->active()->ordered();
    }

    /**
     * Relacionamento: Apenas filtros visíveis e ativos
     */
    public function visibleFilters(): HasMany
    {
        return $this->hasMany(DynamicQueryFilter::class)->active()->visible()->ordered();
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

    /**
     * Substitui variáveis na configuração da consulta usando os filtros
     */
    public function replaceVariables(array $params = []): array
    {
        $config = [
            'service_params' => $this->service_params ?? [],
            'query_config'   => $this->query_config
        ];

        // Valida e obtém valores dos filtros
        $filterValues = $this->processFilterValues($params);

        // Substitui variáveis recursivamente
        $config['service_params'] = $this->replaceVariablesInData($config['service_params'], $filterValues);
        $config['query_config'] = $this->replaceVariablesInData($config['query_config'], $filterValues);

        return $config;
    }

    /**
     * Processa valores dos filtros com validação
     */
    public function processFilterValues(array $params = []): array
    {
        $filterValues = [];
        $errors = [];

        foreach ($this->activeFilters as $filter) {
            $value = $params[$filter->var_name] ?? null;
            $validation = $filter->validateValue($value);

            if (!$validation['valid']) {
                $errors = array_merge($errors, $validation['errors']);
            } else {
                $filterValues[$filter->var_name] = $validation['value'];
            }
        }

        if (!empty($errors)) {
            throw new \InvalidArgumentException('Erros de validação nos filtros: ' . implode('; ', $errors));
        }

        return $filterValues;
    }

    /**
     * Substitui variáveis recursivamente em qualquer estrutura de dados
     */
    private function replaceVariablesInData($data, array $variables): mixed
    {
        if (is_string($data)) {
            return $this->replaceVariablesInString($data, $variables);
        }

        if (is_array($data)) {
            $result = [];
            foreach ($data as $key => $value) {
                $newKey = $this->replaceVariablesInData($key, $variables);
                $newValue = $this->replaceVariablesInData($value, $variables);
                $result[$newKey] = $newValue;
            }
            return $result;
        }

        return $data;
    }

    /**
     * Substitui variáveis em uma string
     */
    private function replaceVariablesInString(string $text, array $variables): mixed
    {
        // Padrão para encontrar variáveis: :NOME_VARIAVEL
        $pattern = '/:([\w_]+)/';

        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $varName = $matches[1];

            if (array_key_exists($varName, $variables)) {
                $value = $variables[$varName];

                // Se o valor é null, retorna null literal
                if ($value === null) {
                    return 'NULL';
                }

                // Para strings em SQL, adiciona aspas
                if (is_string($value) && $this->isLikelySql($matches[0])) {
                    return "'" . str_replace("'", "''", $value) . "'";
                }

                // Para outros tipos, retorna o valor convertido
                if (is_bool($value)) {
                    return $value ? '1' : '0';
                }

                if (is_array($value)) {
                    return json_encode($value);
                }

                return (string)$value;
            }

            // Se a variável não foi encontrada, mantém o placeholder
            return $matches[0];
        }, $text);
    }

    /**
     * Verifica se provavelmente é um contexto SQL
     */
    private function isLikelySql(string $context): bool
    {
        $sqlKeywords = ['SELECT', 'FROM', 'WHERE', 'INSERT', 'UPDATE', 'DELETE'];
        $upperContext = strtoupper($context);

        foreach ($sqlKeywords as $keyword) {
            if (str_contains($upperContext, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtém filtros obrigatórios não preenchidos
     */
    public function getMissingRequiredFilters(array $params = []): array
    {
        $missing = [];

        foreach ($this->activeFilters->where('required', true) as $filter) {
            $value = $params[$filter->var_name] ?? null;

            if ($value === null || $value === '' || (is_array($value) && empty($value))) {
                $missing[] = $filter;
            }
        }

        return $missing;
    }

    /**
     * Verifica se todos os filtros obrigatórios foram fornecidos
     */
    public function hasAllRequiredFilters(array $params = []): bool
    {
        return empty($this->getMissingRequiredFilters($params));
    }

    /**
     * Obtém configuração dos filtros para a interface
     */
    public function getFiltersConfig(): array
    {
        return $this->visibleFilters->map(function ($filter) {
            return $filter->toParameterConfig();
        })->toArray();
    }

    /**
     * Cria filtros a partir de uma configuração
     */
    public function createFiltersFromConfig(array $filtersConfig): void
    {
        foreach ($filtersConfig as $config) {
            DynamicQueryFilter::createFromConfig($this->id, $config);
        }
    }

}