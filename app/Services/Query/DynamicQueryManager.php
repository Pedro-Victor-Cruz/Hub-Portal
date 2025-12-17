<?php

namespace App\Services\Query;

use App\Facades\ServiceManager;
use App\Models\DynamicQuery;
use App\Models\Filter;
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
    private FilterService $filterService;

    public function __construct(
        DynamicQueryRepository $repository,
        FilterService $filterService
    )
    {
        $this->repository = $repository;
        $this->filterService = $filterService;
    }

    /**
     * Executa uma consulta dinâmica por chave
     */
    public function executeQuery(string $key, array $additionalParams = []): ApiResponse
    {
        try {
            // Busca a configuração da consulta
            $query = $this->repository->findByKey($key);

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

            // Processa os dados com formatação e agregação
            $processedResult = $this->processQueryData($response->getData(), $query);

            return ApiResponse::success(
                $processedResult,
                $response->getMessage(),
                array_merge($response->getMetadata(), [
                    'query_key'  => $key,
                    'query_name' => $query->name,
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
     * Processa os dados aplicando formatações e calculando agregações
     */
    private function processQueryData(?array $rawData, DynamicQuery $query): array
    {
        if (empty($rawData)) {
            return [
                'data' => [],
                'fieldsMetadata' => $this->buildFieldsMetadata($query),
                'aggregation' => []
            ];
        }

        // Aplica formatações aos dados
        $formattedData = $this->applyFormatting($rawData, $query);

        // Calcula agregações
        $aggregations = $this->calculateAggregations($rawData, $query);

        // Monta fieldsMetadata (sem format e aggregation, pois já foram resolvidos)
        $fieldsMetadata = $this->buildFieldsMetadata($query);

        return [
            'data' => $formattedData,
            'fieldsMetadata' => $fieldsMetadata,
            'aggregation' => $aggregations
        ];
    }

    /**
     * Aplica formatações aos dados conforme configurado
     */
    private function applyFormatting(array $data, DynamicQuery $query): array
    {
        $formattedData = [];

        foreach ($data as $row) {
            if (!is_array($row)) {
                continue;
            }

            $formattedRow = [];

            foreach ($row as $fieldName => $value) {
                // Verifica se o campo é visível
                if (!$query->isFieldVisible($fieldName)) {
                    continue;
                }

                // Aplica formatação se configurada
                $format = $query->getFieldFormat($fieldName);
                if ($format) {
                    try {
                        $value = SankhyaResponseFormatter::applyCustomFormat($value, $format);
                    } catch (\Throwable $e) {
                        Log::warning("Erro ao formatar campo {$fieldName}: " . $e->getMessage());
                    }
                }

                $formattedRow[$fieldName] = $value;
            }

            $formattedData[] = $formattedRow;
        }

        return $formattedData;
    }

    /**
     * Calcula agregações para os campos configurados
     */
    private function calculateAggregations(array $data, DynamicQuery $query): array
    {
        $aggregations = [];
        $fieldsMetadata = $query->fields_metadata ?? [];

        foreach ($fieldsMetadata as $fieldName => $metadata) {
            // Verifica se há configuração de agregação
            if (!isset($metadata['aggregation']) || !$query->isFieldVisible($fieldName)) {
                continue;
            }

            $aggregationType = $metadata['aggregation'];

            // Extrai os valores do campo de todos os registros
            $values = $this->extractFieldValues($data, $fieldName);

            // Calcula a agregação
            $result = $this->performAggregation($values, $aggregationType);

            if ($result !== null) {
                // Aplica formatação ao resultado da agregação, se configurada
                $format = $metadata['format'] ?? null;
                if ($format) {
                    try {
                        $result = SankhyaResponseFormatter::applyCustomFormat($result, $format);
                    } catch (\Throwable $e) {
                        Log::warning("Erro ao formatar agregação do campo {$fieldName}: " . $e->getMessage());
                    }
                }

                $aggregations[$fieldName] = $result;
            }
        }

        return $aggregations;
    }

    /**
     * Extrai valores de um campo específico de todos os registros
     */
    private function extractFieldValues(array $data, string $fieldName): array
    {
        $values = [];

        foreach ($data as $row) {
            if (is_array($row) && isset($row[$fieldName])) {
                $values[] = $row[$fieldName];
            }
        }

        return $values;
    }

    /**
     * Executa a agregação conforme o tipo especificado
     */
    private function performAggregation(array $values, string $type): mixed
    {
        if (empty($values)) {
            return null;
        }

        return match ($type) {
            'sum' => $this->aggregateSum($values),
            'average' => $this->aggregateAverage($values),
            'count' => $this->aggregateCount($values),
            'min' => $this->aggregateMin($values),
            'max' => $this->aggregateMax($values),
            default => null
        };
    }

    /**
     * Calcula a soma dos valores numéricos
     */
    private function aggregateSum(array $values): ?float
    {
        $sum = 0;
        $hasNumericValue = false;

        foreach ($values as $value) {
            if ($this->isNumericValue($value)) {
                $sum += $this->parseNumericValue($value);
                $hasNumericValue = true;
            }
        }

        return $hasNumericValue ? $sum : null;
    }

    /**
     * Calcula a média dos valores numéricos
     */
    private function aggregateAverage(array $values): ?float
    {
        $numericValues = [];

        foreach ($values as $value) {
            if ($this->isNumericValue($value)) {
                $numericValues[] = $this->parseNumericValue($value);
            }
        }

        if (empty($numericValues)) {
            return null;
        }

        return array_sum($numericValues) / count($numericValues);
    }

    /**
     * Conta o número de valores não nulos
     */
    private function aggregateCount(array $values): int
    {
        return count(array_filter($values, fn($v) => $v !== null && $v !== ''));
    }

    /**
     * Encontra o valor mínimo
     */
    private function aggregateMin(array $values): mixed
    {
        $numericValues = [];

        foreach ($values as $value) {
            if ($this->isNumericValue($value)) {
                $numericValues[] = $this->parseNumericValue($value);
            }
        }

        return !empty($numericValues) ? min($numericValues) : null;
    }

    /**
     * Encontra o valor máximo
     */
    private function aggregateMax(array $values): mixed
    {
        $numericValues = [];

        foreach ($values as $value) {
            if ($this->isNumericValue($value)) {
                $numericValues[] = $this->parseNumericValue($value);
            }
        }

        return !empty($numericValues) ? max($numericValues) : null;
    }

    /**
     * Verifica se um valor pode ser considerado numérico
     */
    private function isNumericValue(mixed $value): bool
    {
        if (is_null($value) || $value === '') {
            return false;
        }

        if (is_numeric($value)) {
            return true;
        }

        // Tenta converter string para número (remove formatações comuns)
        if (is_string($value)) {
            $cleaned = str_replace(['.', ',', ' ', 'R$', '$', '€', '%'], '', $value);
            return is_numeric($cleaned);
        }

        return false;
    }

    /**
     * Converte valor para numérico
     */
    private function parseNumericValue(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_string($value)) {
            // Remove formatações comuns de moeda/número
            $cleaned = str_replace(['.', ',', ' ', 'R$', '$', '€', '%'], '', $value);
            return (float) $cleaned;
        }

        return 0;
    }

    /**
     * Constrói os metadados dos campos (sem format e aggregation)
     */
    private function buildFieldsMetadata(DynamicQuery $query): array
    {
        $fieldsMetadata = [];
        $metadata = $query->fields_metadata ?? [];

        foreach ($metadata as $fieldName => $config) {
            // Ignora campos não visíveis
            if (!$query->isFieldVisible($fieldName)) {
                continue;
            }

            // Monta metadata sem format e aggregation (já resolvidos)
            $fieldMeta = [];

            if (isset($config['label'])) {
                $fieldMeta['label'] = $config['label'];
            }

            if (isset($config['visible'])) {
                $fieldMeta['visible'] = $config['visible'];
            }

            if (isset($config['order'])) {
                $fieldMeta['order'] = $config['order'];
            }

            // Adiciona outras configurações personalizadas, exceto format e aggregation
            foreach ($config as $key => $value) {
                if (!in_array($key, ['format', 'aggregation', 'label', 'visible', 'order'])) {
                    $fieldMeta[$key] = $value;
                }
            }

            $fieldsMetadata[$fieldName] = $fieldMeta;
        }

        return $fieldsMetadata;
    }

    /**
     * Cria uma nova consulta dinâmica, opcionalmente com filtros
     */
    public function createQuery(array $data, array $filtersData = []): ApiResponse
    {
        try {
            // Valida dados obrigatórios
            $this->validateQueryData($data);

            $query = $this->repository->createOrUpdate($data);

            // Criar filtros se fornecidos
            $filtersCreated = 0;
            if (!empty($filtersData)) {
                foreach ($filtersData as $filterData) {
                    Filter::createFromConfigByQuery($query->id, $filterData);
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
    public function getQueryWithFilters(string $key): ApiResponse
    {
        try {
            $query = $this->repository->findByKey($key);

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
    public function previewQueryWithFilters(string $key, array $params = []): ApiResponse
    {
        try {
            $query = $this->repository->findByKey($key);

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

                Filter::createFromConfigByQuery($targetQuery->id, $filterData);
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

    public function validateQueryExecution(string $key, array $params = []): ApiResponse
    {
        $query = $this->repository->findByKey($key);

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

        $message = $hasFilterErrors ? 'Consulta com erros de validação' : 'Consulta válida para execução';

        return ApiResponse::success($response, $message);
    }

    /**
     * Atualiza metadados de um campo específico
     */
    public function updateFieldMetadata(string $queryKey, string $fieldName, array $metadata): ApiResponse
    {
        try {
            $query = $this->repository->findByKey($queryKey);

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

        // Valida formato da chave (apenas letras, números e hifens)
        if (!preg_match('/^[a-z0-9-]+$/', $data['key'])) {
            throw new \InvalidArgumentException("Chave '{$data['key']}' inválida. Deve conter apenas letras minúsculas, números e hífenes (-).");
        }
    }

    public function getAvailableQueries(): array
    {
        return $this->repository->getAvailableQueries()->toArray();
    }

    /**
     * Atualiza uma consulta dinâmica existente
     */
    public function updateQuery(string $key, array $data): ApiResponse
    {
        try {
            $existingQuery = $this->repository->findByKey($key);

            if (!$existingQuery) {
                return ApiResponse::error("Consulta '{$key}' não encontrada");
            }

            // Preserva alguns campos importantes
            $data['key'] = $key;

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
    public function deleteQuery(string $key): ApiResponse
    {
        try {
            $deleted = $this->repository->delete($key);

            if (!$deleted) return ApiResponse::error("Consulta '{$key}' não encontrada ou não pôde ser removida");

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