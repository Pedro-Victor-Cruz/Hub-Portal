<?php

namespace App\Services\Query;

use App\Facades\ServiceManager;
use App\Models\Company;
use App\Models\DynamicQuery;
use App\Models\DynamicQueryFilter;
use App\Repositories\Query\DynamicQueryRepository;
use App\Services\Core\ApiResponse;
use App\Services\Utils\ResponseFormatters\SankhyaResponseFormatter;
use Illuminate\Support\Facades\Log;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

/**
 * Gerenciador para execução de consultas dinâmicas
 */
class DynamicQueryManager
{
    private DynamicQueryRepository $repository;
    private DynamicQueryFilterService $filterService;

    public function __construct(
        DynamicQueryRepository $repository,
        DynamicQueryFilterService $filterService
    )
    {
        $this->repository = $repository;
        $this->filterService = $filterService;
    }

    /**
     * Executa uma consulta dinâmica por chave
     */
    public function executeQuery(string $key, ?Company $company = null, array $additionalParams = []): ApiResponse
    {
        try {
            // Busca a configuração da consulta
            $query = $this->repository->findByKey($key, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$key}' não encontrada");
            }

            if (!$query->active) {
                return ApiResponse::error("Consulta '{$key}' está inativa");
            }

            // Verifica se a classe do serviço existe
            if (!$query->isValidServiceSlug()) {
                return ApiResponse::error("Slug do serviço não encontrada: {$query->service_slug}");
            }

            // Verifica filtros obrigatórios
            $missingFilters = $query->getMissingRequiredFilters($additionalParams);
            if (!empty($missingFilters)) {
                $missing = collect($missingFilters)->pluck('name')->implode(', ');
                return ApiResponse::error(
                    "Filtros obrigatórios não fornecidos: {$missing}",
                    ['missing_required_filters' => collect($missingFilters)->pluck('var_name')->toArray()]
                );
            }

            // Substitui variáveis usando os filtros
            $configWithVariables = $query->replaceVariables($additionalParams);

            // Prepara os parâmetros do serviço mesclando tudo
            $serviceParams = array_merge(
                $configWithVariables['service_params'],
                $additionalParams,
                $this->prepareQueryConfig($configWithVariables['query_config'])
            );

            // Executa o serviço
            $response = ServiceManager::executeService($query->service_slug, $serviceParams);

            if (!$response->isSuccess()) {
                return $response;
            }

            // Aplica formatação personalizada se configurada
            $formattedData = $this->applyCustomFormatting($response->getData(), $query);

            return ApiResponse::success(
                $formattedData,
                $response->getMessage(),
                array_merge($response->getMetadata(), [
                    'query_key'  => $key,
                    'query_name' => $query->name,
                    'is_global'  => $query->is_global,
                    'company_id' => $query->company_id,
                    'filters_applied' => $query->activeFilters()->count(),
                    'variables_replaced' => $this->extractVariablesFromConfig($query)
                ])
            );

        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error(
                'Erro de validação nos filtros',
                [$e->getMessage()]
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao executar consulta dinâmica',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Duplica uma consulta global para uma empresa específica, incluindo filtros
     */
    public function duplicateQueryForCompany(string $key, Company $company, array $overrides = [], bool $duplicateFilters = true): ApiResponse
    {
        try {
            $duplicatedQuery = $this->repository->duplicateForCompany($key, $company, $overrides);

            if (!$duplicatedQuery) {
                return ApiResponse::error("Consulta global '{$key}' não encontrada");
            }

            // Duplicar filtros se solicitado
            $filtersCopied = 0;
            if ($duplicateFilters) {
                $originalQuery = $this->repository->findGlobalByKey($key);
                if ($originalQuery && $originalQuery->filters->isNotEmpty()) {
                    $filtersCopied = $this->duplicateQueryFilters($originalQuery, $duplicatedQuery);
                }
            }

            return ApiResponse::success(
                $duplicatedQuery->toArray(),
                'Consulta duplicada com sucesso para a empresa',
                [
                    'filters_copied' => $filtersCopied,
                    'total_filters' => $duplicatedQuery->filters()->count()
                ]
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao duplicar consulta para empresa',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Cria uma nova consulta dinâmica, opcionalmente com filtros
     */
    public function createQuery(array $data, array $filtersData = []): ApiResponse
    {
        try {
            // Valida dados obrigatórios
            $this->validateQueryData($data);

            if (isset($data['company_id']) && $data['company_id']) {
                $data['is_global'] = false;
            } else {
                $data['is_global'] = true;
                $data['company_id'] = null;
            }

            $query = $this->repository->createOrUpdate($data);

            // Criar filtros se fornecidos
            $filtersCreated = 0;
            if (!empty($filtersData)) {
                foreach ($filtersData as $filterData) {
                    DynamicQueryFilter::createFromConfig($query->id, $filterData);
                    $filtersCreated++;
                }
            }

            return ApiResponse::success(
                array_merge($query->toArray(), [
                    'filters' => $query->filters->toArray()
                ]),
                'Consulta dinâmica criada com sucesso',
                ['filters_created' => $filtersCreated]
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao criar consulta dinâmica',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Obtém informação completa de uma consulta incluindo filtros
     */
    public function getQueryWithFilters(string $key, ?Company $company = null): ApiResponse
    {
        try {
            $query = $this->repository->findByKey($key, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$key}' não encontrada");
            }

            $queryData = $query->toArray();
            $queryData['filters'] = $query->visibleFilters->toArray();
            $queryData['filters_config'] = $query->getFiltersConfig();
            $queryData['required_filters'] = $query->activeFilters()->where('required', true)->pluck('var_name')->toArray();
            $queryData['variables_in_config'] = $this->extractVariablesFromConfig($query);

            return ApiResponse::success(
                $queryData,
                "Detalhes completos da consulta '{$key}'"
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao obter informações da consulta',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Preview da consulta com substituição de variáveis (sem executar)
     */
    public function previewQueryWithFilters(string $key, array $params = [], ?Company $company = null): ApiResponse
    {
        try {
            $query = $this->repository->findByKey($key, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$key}' não encontrada");
            }

            // Substitui variáveis usando os filtros
            $configWithVariables = $query->replaceVariables($params);

            return ApiResponse::success([
                'original_config' => [
                    'service_params' => $query->service_params,
                    'query_config' => $query->query_config
                ],
                'processed_config' => $configWithVariables,
                'filters_applied' => $query->processFilterValues($params),
                'service_slug' => $query->service_slug,
                'variables_found' => $this->extractVariablesFromConfig($query)
            ], 'Preview da consulta gerado com sucesso');

        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error(
                'Erro de validação nos filtros',
                [$e->getMessage()]
            );
        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao gerar preview da consulta',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Duplica filtros de uma consulta para outra
     */
    private function duplicateQueryFilters(DynamicQuery $sourceQuery, DynamicQuery $targetQuery): int
    {
        $copied = 0;
        foreach ($sourceQuery->activeFilters as $sourceFilter) {
            try {
                $filterData = $sourceFilter->toArray();
                unset($filterData['id'], $filterData['dynamic_query_id'], $filterData['created_at'], $filterData['updated_at']);

                DynamicQueryFilter::createFromConfig($targetQuery->id, $filterData);
                $copied++;
            } catch (\Exception $e) {
                Log::warning("Erro ao duplicar filtro '{$sourceFilter->var_name}': " . $e->getMessage());
            }
        }
        return $copied;
    }

    /**
     * Extrai variáveis encontradas na configuração da consulta
     */
    private function extractVariablesFromConfig(DynamicQuery $query): array
    {
        $variables = [];

        // Junta service_params e query_config em um array
        $allConfig = [
            'service_params' => $query->service_params,
            'query_config' => $query->query_config
        ];

        // Converte recursivamente para string e procura variáveis
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($allConfig));

        foreach ($iterator as $value) {
            if (is_string($value)) {
                if (preg_match_all('/:([\w_]+)/', $value, $matches)) {
                    $variables = array_merge($variables, $matches[1]);
                }
            }
        }

        return array_values(array_unique($variables));
    }


    /**
     * Valida se uma consulta pode ser executada para uma empresa, incluindo filtros
     */
    public function validateQueryExecution(string $key, ?Company $company = null, array $params = []): ApiResponse
    {
        $query = $this->repository->findByKey($key, $company);

        if (!$query) {
            return ApiResponse::error("Consulta '{$key}' não encontrada");
        }

        if (!$query->active) {
            return ApiResponse::error("Consulta '{$key}' está inativa");
        }

        if (!$query->isValidServiceSlug()) {
            return ApiResponse::error("Identificador (slug) do serviço inválida: {$query->service_slug}");
        }

        // Validações específicas dos filtros
        $filterValidation = [];
        $hasFilterErrors = false;

        if ($query->activeFilters()->count() > 0) {
            // Verifica filtros obrigatórios
            $missingFilters = $query->getMissingRequiredFilters($params);
            if (!empty($missingFilters)) {
                $hasFilterErrors = true;
                $filterValidation['missing_required'] = collect($missingFilters)->pluck('var_name')->toArray();
            }

            // Valida valores fornecidos
            try {
                $query->processFilterValues($params);
                $filterValidation['values_valid'] = true;
            } catch (\InvalidArgumentException $e) {
                $hasFilterErrors = true;
                $filterValidation['values_valid'] = false;
                $filterValidation['validation_errors'] = [$e->getMessage()];
            }
        }

        $response = [
            'valid' => !$hasFilterErrors,
            'query' => $query->toArray(),
            'filters_validation' => $filterValidation,
            'total_filters' => $query->activeFilters()->count(),
            'required_filters' => $query->activeFilters()->where('required', true)->pluck('var_name')->toArray()
        ];

        $message = $hasFilterErrors ?
            'Consulta com erros de validação' :
            'Consulta válida para execução';

        return ApiResponse::success($response, $message);
    }

    // Mantém os métodos existentes...

    /**
     * Duplica uma consulta global para uma empresa específica
     */
    public function duplicateQueryForCompanyLegacy(string $key, Company $company, array $overrides = []): ApiResponse
    {
        return $this->duplicateQueryForCompany($key, $company, $overrides, false);
    }

    /**
     * Atualiza metadados de um campo específico
     */
    public function updateFieldMetadata(string $queryKey, string $fieldName, array $metadata, ?Company $company = null): ApiResponse
    {
        try {
            $query = $this->repository->findByKey($queryKey, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$queryKey}' não encontrada");
            }

            $query->setFieldMetadata($fieldName, $metadata);
            $query->save();

            return ApiResponse::success(
                $query->getFieldMetadata($fieldName),
                'Metadados do campo atualizados com sucesso'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao atualizar metadados do campo',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Exporta configuração de consulta para array
     */
    public function exportQuery(string $key, ?Company $company = null, bool $includeFilters = true): ApiResponse
    {
        try {
            $query = $this->repository->findByKey($key, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$key}' não encontrada");
            }

            $export = $query->toArray();

            // Remove campos específicos da instância
            unset($export['id'], $export['created_at'], $export['updated_at']);

            // Inclui filtros se solicitado
            if ($includeFilters) {
                $export['filters'] = $query->filters->map(function($filter) {
                    $filterData = $filter->toArray();
                    unset($filterData['id'], $filterData['dynamic_query_id'], $filterData['created_at'], $filterData['updated_at']);
                    return $filterData;
                })->toArray();
            }

            // Adiciona metadados de exportação
            $export['exported_at'] = now()->toISOString();
            $export['exported_by'] = 'system';

            return ApiResponse::success(
                $export,
                'Consulta exportada com sucesso'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao exportar consulta',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Importa configuração de consulta de array, incluindo filtros
     */
    public function importQuery(array $queryData, ?Company $company = null, bool $overwrite = false): ApiResponse
    {
        try {
            // Remove metadados de exportação
            unset($queryData['id'], $queryData['created_at'], $queryData['updated_at'],
                $queryData['exported_at'], $queryData['exported_by']);

            // Extrai dados dos filtros
            $filtersData = $queryData['filters'] ?? [];
            unset($queryData['filters']);

            // Se não for para sobrescrever, verifica se já existe
            if (!$overwrite && $this->repository->exists($queryData['key'], $company)) {
                return ApiResponse::error("Consulta '{$queryData['key']}' já existe. Use overwrite=true para sobrescrever.");
            }

            // Adiciona company_id se necessário
            if ($company && !($queryData['is_global'] ?? false)) {
                $queryData['company_id'] = $company->id;
            }

            $response = $this->createQuery($queryData, $filtersData);

            if ($response->isSuccess()) {
                return ApiResponse::success(
                    $response->getData(),
                    'Consulta importada com sucesso'
                );
            }

            return $response;

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao importar consulta',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Prepara a configuração da query para execução
     */
    private function prepareQueryConfig(mixed $queryConfig): array
    {
        $config = [];

        if ($queryConfig) {
            // Se é uma string simples (SQL), adiciona como parâmetro sql
            if (is_string($queryConfig)) {
                $config['sql'] = $queryConfig;
            } else {
                // Se é array/json, mescla com os parâmetros
                $config = array_merge($config, (array)$queryConfig);
            }
        }

        return $config;
    }

    /**
     * Aplica formatação personalizada aos dados
     */
    private function applyCustomFormatting(?array $data, DynamicQuery $query): array
    {
        // Se não há dados, retorna array vazio
        if (empty($data)) {
            return [];
        }

        // Se não há metadata, retorna os dados como vieram
        if (!$query->fields_metadata) {
            return $data;
        }

        $formattedData = [];

        foreach ($data as $row) {
            // Garante que $row seja array
            if (!is_array($row)) {
                continue;
            }

            $formattedRow = [];

            foreach ($row as $fieldName => $value) {
                $fieldMeta = $query->getFieldMetadata($fieldName);

                // Ignora se o campo não for visível
                if (!$query->isFieldVisible($fieldName)) {
                    continue;
                }

                // Aplica formatação se houver configuração
                if ($fieldMeta && isset($fieldMeta['format'])) {
                    try {
                        $value = SankhyaResponseFormatter::applyCustomFormat($value, $fieldMeta['format']);
                    } catch (\Throwable $e) {
                        // Loga erro mas não quebra a execução
                        Log::warning("Erro ao formatar campo {$fieldName}: " . $e->getMessage());
                    }
                }

                // Usa label customizado se existir
                $displayName = $query->getFieldLabel($fieldName) ?? $fieldName;
                $formattedRow[$displayName] = $value;
            }

            $formattedData[] = $formattedRow;
        }

        return $formattedData;
    }

    /**
     * Extrai parâmetros obrigatórios da configuração da consulta
     */
    public function extractRequiredParams(DynamicQuery $query): array
    {
        $requiredParams = [];

        try {
            $serviceInstance = ServiceManager::getServiceInstance($query->service_slug);
            $requiredParams = $serviceInstance->getRequiredParams();

            // Adiciona filtros obrigatórios
            $requiredFilters = $query->activeFilters()->where('required', true)->pluck('var_name')->toArray();
            $requiredParams = array_merge($requiredParams, $requiredFilters);

        } catch (\Exception $e) {
            // Só retorna os filtros obrigatórios se houver erro no serviço
            $requiredParams = $query->activeFilters()->where('required', true)->pluck('var_name')->toArray();
        }

        return array_unique($requiredParams);
    }

    /**
     * Valida os dados de uma consulta dinâmica
     */
    private function validateQueryData(array $data): void
    {
        $required = ['key', 'name', 'service_slug'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Campo obrigatório '{$field}' não informado");
            }
        }

        // Valida se a classe do serviço existe
        if (!ServiceManager::existsService($data['service_slug'])) {
            throw new \InvalidArgumentException("Slug do serviço não encontrada: {$data['service_slug']}");
        }

        // Valida se o tipo de serviço é suportado pela empresa
        if (isset($data['company_id']) && $data['company_id']) {
            $company = Company::find($data['company_id']);
            if (!$company) {
                throw new \InvalidArgumentException("Empresa com ID {$data['company_id']} não encontrada");
            }

            if (!ServiceManager::isServiceAvailableForCompany($data['service_slug'], $company)) {
                throw new \InvalidArgumentException("Serviço '{$data['service_slug']}' não disponível para a empresa");
            }
        }

        // Valida formato da chave (apenas letras, números e hifens)
        if (!preg_match('/^[a-z0-9-]+$/', $data['key'])) {
            throw new \InvalidArgumentException("Chave '{$data['key']}' inválida. Deve conter apenas letras minúsculas, números e hífenes (-).");
        }
    }

    /**
     * Lista todas as consultas disponíveis para uma empresa
     */
    public function getAvailableQueries(?Company $company = null): array
    {
        return $this->repository->getAvailableQueries($company)->toArray();
    }

    /**
     * Atualiza uma consulta dinâmica existente
     */
    public function updateQuery(string $key, array $data, ?Company $company = null): ApiResponse
    {
        try {
            $existingQuery = $this->repository->findByKey($key, $company);

            if (!$existingQuery) {
                return ApiResponse::error("Consulta '{$key}' não encontrada");
            }

            // Preserva alguns campos importantes
            $data['key'] = $key;
            $data['company_id'] = $existingQuery->company_id;

            $query = $this->repository->createOrUpdate($data);

            return ApiResponse::success(
                $query->toArray(),
                'Consulta dinâmica atualizada com sucesso'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao atualizar consulta dinâmica',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Remove uma consulta dinâmica
     */
    public function deleteQuery(string $key, ?Company $company = null): ApiResponse
    {
        try {
            $deleted = $this->repository->delete($key, $company);

            if (!$deleted) {
                return ApiResponse::error("Consulta '{$key}' não encontrada ou não pôde ser removida");
            }

            return ApiResponse::success(
                null,
                'Consulta dinâmica removida com sucesso'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao remover consulta dinâmica',
                [$e->getMessage()]
            );
        }
    }
}