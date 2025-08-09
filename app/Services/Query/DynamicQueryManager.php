<?php

namespace App\Services\Query;

use App\Facades\ServiceManager;
use App\Models\Company;
use App\Models\DynamicQuery;
use App\Repositories\Query\DynamicQueryRepository;
use App\Services\Core\ApiResponse;
use App\Services\Utils\ResponseFormatters\SankhyaResponseFormatter;

/**
 * Gerenciador para execução de consultas dinâmicas
 */
class DynamicQueryManager
{
    private DynamicQueryRepository $repository;

    public function __construct(
        DynamicQueryRepository $repository
    )
    {
        $this->repository = $repository;
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

            // Prepara os parâmetros do serviço
            $serviceParams = array_merge(
                $query->service_params ?? [],
                $additionalParams,
                $this->prepareQueryConfig($query)
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
                    'company_id' => $query->company_id
                ])
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao remover consulta dinâmica',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Duplica uma consulta global para uma empresa específica
     */
    public function duplicateQueryForCompany(string $key, Company $company, array $overrides = []): ApiResponse
    {
        try {
            $duplicatedQuery = $this->repository->duplicateForCompany($key, $company, $overrides);

            if (!$duplicatedQuery) {
                return ApiResponse::error("Consulta global '{$key}' não encontrada");
            }

            return ApiResponse::success(
                $duplicatedQuery->toArray(),
                'Consulta duplicada com sucesso para a empresa'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao duplicar consulta para empresa',
                [$e->getMessage()]
            );
        }
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
     * Obtém estatísticas de uso das consultas
     */
    public function getQueryStatistics(?Company $company = null): array
    {
        $queries = $this->repository->getAvailableQueries($company);

        $stats = [
            'total_queries'    => $queries->count(),
            'global_queries'   => $queries->where('is_global', true)->count(),
            'company_queries'  => $queries->where('is_global', false)->count(),
            'active_queries'   => $queries->where('active', true)->count(),
            'inactive_queries' => $queries->where('active', false)->count(),
            'by_service'       => []
        ];

        // Agrupa por classe de serviço
        $byService = $queries->groupBy('service_slug');
        foreach ($byService as $serviceClass => $serviceQueries) {
            $stats['by_service'][class_basename($serviceClass)] = $serviceQueries->count();
        }

        return $stats;
    }

    /**
     * Executa múltiplas consultas em lote
     */
    public function executeBatch(array $queryKeys, ?Company $company = null, array $globalParams = []): ApiResponse
    {
        try {
            $results = [];
            $errors = [];

            foreach ($queryKeys as $key) {
                $params = array_merge($globalParams, $globalParams[$key] ?? []);
                $response = $this->executeQuery($key, $company, $params);

                if ($response->isSuccess()) {
                    $results[$key] = [
                        'success'  => true,
                        'data'     => $response->getData(),
                        'metadata' => $response->getMetadata()
                    ];
                } else {
                    $errors[$key] = [
                        'success' => false,
                        'message' => $response->getMessage(),
                        'errors'  => $response->getErrors()
                    ];
                }
            }

            return ApiResponse::success([
                'results'       => $results,
                'errors'        => $errors,
                'success_count' => count($results),
                'error_count'   => count($errors)
            ], 'Execução em lote concluída');

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro na execução em lote',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Busca consultas por termo
     */
    public function searchQueries(string $searchTerm, ?Company $company = null): array
    {
        $queries = $this->repository->getAvailableQueries($company);

        return $queries->filter(function (DynamicQuery $query) use ($searchTerm) {
            $term = strtolower($searchTerm);
            return str_contains(strtolower($query->name), $term) ||
                str_contains(strtolower($query->key), $term) ||
                str_contains(strtolower($query->description ?? ''), $term);
        })->values()->toArray();
    }

    /**
     * Exporta configuração de consulta para array
     */
    public function exportQuery(string $key, ?Company $company = null): ApiResponse
    {
        try {
            $query = $this->repository->findByKey($key, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$key}' não encontrada");
            }

            $export = $query->toArray();

            // Remove campos específicos da instância
            unset($export['id'], $export['created_at'], $export['updated_at']);

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
     * Importa configuração de consulta de array
     */
    public function importQuery(array $queryData, ?Company $company = null, bool $overwrite = false): ApiResponse
    {
        try {
            // Remove metadados de exportação
            unset($queryData['id'], $queryData['created_at'], $queryData['updated_at'],
                $queryData['exported_at'], $queryData['exported_by']);

            // Se não for para sobrescrever, verifica se já existe
            if (!$overwrite && $this->repository->exists($queryData['key'], $company)) {
                return ApiResponse::error("Consulta '{$queryData['key']}' já existe. Use overwrite=true para sobrescrever.");
            }

            // Adiciona company_id se necessário
            if ($company && !($queryData['is_global'] ?? false)) {
                $queryData['company_id'] = $company->id;
            }

            $response = $this->createQuery($queryData);

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
    private function prepareQueryConfig(DynamicQuery $query): array
    {
        $config = [];

        if ($query->query_config) {
            // Se é uma string simples (SQL), adiciona como parâmetro sql
            if (is_string($query->query_config)) {
                $config['sql'] = $query->query_config;
            } else {
                // Se é array/json, mescla com os parâmetros
                $config = array_merge($config, (array)$query->query_config);
            }
        }

        return $config;
    }

    /**
     * Aplica formatação personalizada aos dados
     */
    private function applyCustomFormatting(array $data, DynamicQuery $query): array
    {
        if (!$query->fields_metadata || empty($data)) {
            return $data;
        }

        $formattedData = [];

        foreach ($data as $row) {
            $formattedRow = [];

            foreach ($row as $fieldName => $value) {
                $fieldMeta = $query->getFieldMetadata($fieldName);

                // Verifica se o campo deve ser exibido
                if (!$query->isFieldVisible($fieldName)) {
                    continue;
                }

                // Aplica formatação se configurada
                if ($fieldMeta && isset($fieldMeta['format'])) {
                    $value = SankhyaResponseFormatter::applyCustomFormat($value, $fieldMeta['format']);
                }

                // Usa o label customizado se configurado
                $displayName = $query->getFieldLabel($fieldName);
                $formattedRow[$displayName] = $value;
            }

            $formattedData[] = $formattedRow;
        }

        return $formattedData;
    }

    /**
     * Extrai parâmetros obrigatórios da configuração da consulta
     */
    private function extractRequiredParams(DynamicQuery $query): array
    {
        $requiredParams = [];

        // Obtém parâmetros obrigatórios da classe do serviço
        if (class_exists($query->service_slug)) {
            try {
                $serviceInstance = ServiceManager::getServiceInstance($query->service_slug);
                $requiredParams = $serviceInstance->getRequiredParams();
            } catch (\Exception $e) {
                // Ignora erros de instanciação
            }
        }

        return $requiredParams;
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

        // Valida formato da chave (apenas letras, números e underscore)
        if (!preg_match('/^[a-z0-9_]+$/', $data['key'])) {
            throw new \InvalidArgumentException("Chave da consulta deve conter apenas letras minúsculas, números e underscore");
        }
    }

    /**
     * Testa uma consulta dinâmica sem salvar
     */
    public function testQuery(array $queryData, array $testParams = []): ApiResponse
    {
        try {
            $this->validateQueryData($queryData);

            // Cria uma instância temporária sem salvar no banco
            $tempQuery = new DynamicQuery($queryData);

            // Prepara parâmetros
            $serviceParams = array_merge(
                $tempQuery->service_params ?? [],
                $testParams,
                $this->prepareQueryConfig($tempQuery)
            );

            // Executa o serviço
            $response = ServiceManager::executeService($tempQuery->service_slug, $serviceParams);

            if (!$response->isSuccess()) {
                return $response;
            }

            // Aplica formatação de teste
            $formattedData = $this->applyCustomFormatting($response->getData(), $tempQuery);

            return ApiResponse::success(
                $formattedData,
                'Teste da consulta executado com sucesso',
                array_merge($response->getMetadata(), [
                    'is_test'    => true,
                    'query_key'  => $tempQuery->key,
                    'query_name' => $tempQuery->name
                ])
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro no teste da consulta dinâmica',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Valida se uma consulta pode ser executada para uma empresa
     */
    public function validateQueryExecution(string $key, ?Company $company = null): ApiResponse
    {
        $query = $this->repository->findByKey($key, $company);

        if (!$query) {
            return ApiResponse::error("Consulta '{$key}' não encontrada");
        }

        if (!$query->active) {
            return ApiResponse::error("Consulta '{$key}' está inativa");
        }

        if (!$query->isValidServiceSlug()) {
            return ApiResponse::error("Classe do serviço inválida: {$query->service_slug}");
        }

        return ApiResponse::success([
            'valid' => true,
            'query' => $query->toArray()
        ], 'Consulta válida para execução');
    }


    /**
     * Lista todas as consultas disponíveis para uma empresa
     */
    public function getAvailableQueries(?Company $company = null): array
    {
        $queries = $this->repository->getAvailableQueries($company);

        return $queries->map(function (DynamicQuery $query) {
            return [
                'key'             => $query->key,
                'name'            => $query->name,
                'description'     => $query->description,
                'is_global'       => $query->is_global,
                'service_slug'    => $query->service_slug,
                'required_params' => $this->extractRequiredParams($query),
                'fields_metadata' => $query->fields_metadata,
            ];
        })->values()->toArray();
    }

    /**
     * Cria uma nova consulta dinâmica
     */
    public function createQuery(array $data): ApiResponse
    {
        try {
            // Valida dados obrigatórios
            $this->validateQueryData($data);

            $query = $this->repository->createOrUpdate($data);

            return ApiResponse::success(
                $query->toArray(),
                'Consulta dinâmica criada com sucesso'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao criar consulta dinâmica',
                [$e->getMessage()]
            );
        }
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