<?php

namespace App\Services\Query;

use App\Models\DynamicQuery;
use App\Models\Dashboard;
use App\Models\Filter;
use App\Services\Core\ApiResponse;

/**
 * Serviço para gerenciar filtros de consultas dinâmicas e dashboards
 */
class FilterService
{
    /**
     * Tipos de entidade suportadas
     */
    const ENTITY_QUERY = 'query';
    const ENTITY_DASHBOARD = 'dashboard';

    /**
     * @var array Mapeamento de tipos de entidade para modelos
     */
    private array $entityModels = [
        self::ENTITY_QUERY => DynamicQuery::class,
        self::ENTITY_DASHBOARD => Dashboard::class,
    ];

    /**
     * @var array Mapeamento de tipos de entidade para campos
     */
    private array $entityFields = [
        self::ENTITY_QUERY => 'dynamic_query_id',
        self::ENTITY_DASHBOARD => 'dashboard_id',
    ];

    /**
     * Cria um novo filtro
     */
    public function createFilter(string $entityType, string $entityKey, array $filterData): ApiResponse
    {
        try {
            $entity = $this->findEntity($entityType, $entityKey);

            if (!$entity) {
                return $this->entityNotFoundResponse($entityType, $entityKey);
            }

            if ($this->filterExists($entity, $filterData['var_name'])) {
                return ApiResponse::error("Filtro com variável '{$filterData['var_name']}' já existe nesta entidade");
            }

            $filter = $this->createFilterForEntity($entityType, $entity->id, $filterData);

            return ApiResponse::success(
                $filter->toArray(),
                'Filtro criado com sucesso'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao criar filtro',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Lista filtros de uma entidade
     */
    public function listFilters(string $entityType, string $entityKey, bool $onlyActive = true): ApiResponse
    {
        try {
            $entity = $this->findEntity($entityType, $entityKey);

            if (!$entity) {
                return $this->entityNotFoundResponse($entityType, $entityKey);
            }

            $filters = $onlyActive
                ? ($entity->activeFilters ?? $entity->filters()->where('active', true)->get())
                : $entity->filters;

            return ApiResponse::success(
                $filters->toArray(),
                'Lista de filtros obtida com sucesso'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao listar filtros',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Atualiza um filtro existente
     */
    public function updateFilter(string $entityType, string $entityKey, string $varName, array $filterData): ApiResponse
    {
        try {
            $entity = $this->findEntity($entityType, $entityKey);

            if (!$entity) {
                return $this->entityNotFoundResponse($entityType, $entityKey);
            }

            $filter = $entity->filters()->where('var_name', $varName)->first();

            if (!$filter) {
                return ApiResponse::error("Filtro '{$varName}' não encontrado nesta entidade");
            }

            // Se mudou o var_name, verifica se não há conflito
            if (isset($filterData['var_name']) && $filterData['var_name'] !== $varName) {
                if ($this->filterExists($entity, $filterData['var_name'])) {
                    return ApiResponse::error("Filtro com variável '{$filterData['var_name']}' já existe nesta entidade");
                }
            }

            $filter->update($filterData);

            return ApiResponse::success(
                $filter->fresh()->toArray(),
                'Filtro atualizado com sucesso'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao atualizar filtro',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Remove um filtro
     */
    public function deleteFilter(string $entityType, string $entityKey, string $varName): ApiResponse
    {
        try {
            $entity = $this->findEntity($entityType, $entityKey);

            if (!$entity) {
                return $this->entityNotFoundResponse($entityType, $entityKey);
            }

            $filter = $entity->filters()->where('var_name', $varName)->first();

            if (!$filter) {
                return ApiResponse::error("Filtro '{$varName}' não encontrado nesta entidade");
            }

            $filter->delete();

            return ApiResponse::success(
                null,
                'Filtro removido com sucesso'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao remover filtro',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Obtém um filtro específico
     */
    public function getFilter(string $entityType, string $entityKey, string $varName): ApiResponse
    {
        try {
            $entity = $this->findEntity($entityType, $entityKey);

            if (!$entity) {
                return $this->entityNotFoundResponse($entityType, $entityKey);
            }

            $filter = $entity->filters()->where('var_name', $varName)->first();

            if (!$filter) {
                return ApiResponse::error("Filtro '{$varName}' não encontrado nesta entidade");
            }

            return ApiResponse::success(
                $filter->toArray(),
                'Filtro obtido com sucesso'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao obter filtro',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Reordena filtros
     */
    public function reorderFilters(string $entityType, string $entityKey, array $varNamesOrder): ApiResponse
    {
        try {
            $entity = $this->findEntity($entityType, $entityKey);

            if (!$entity) {
                return $this->entityNotFoundResponse($entityType, $entityKey);
            }

            foreach ($varNamesOrder as $index => $varName) {
                $filter = $entity->filters()->where('var_name', $varName)->first();

                if ($filter) {
                    $filter->update(['order' => $index + 1]);
                }
            }

            return ApiResponse::success(
                $entity->filters()->ordered()->get()->toArray(),
                'Filtros reordenados com sucesso'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao reordenar filtros',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Valida valores de filtros sem executar a consulta
     */
    public function validateFilterValues(string $entityType, string $entityKey, array $params): ApiResponse
    {
        try {
            $entity = $this->findEntity($entityType, $entityKey);

            if (!$entity) {
                return $this->entityNotFoundResponse($entityType, $entityKey);
            }

            $validationResults = [];
            $hasErrors = false;

            $filters = $entity->activeFilters ?? $entity->filters()->where('active', true)->get();

            foreach ($filters as $filter) {
                $value = $params[$filter->var_name] ?? null;
                $validation = $filter->validateValue($value);

                $validationResults[$filter->var_name] = [
                    'filter_name' => $filter->name,
                    'valid' => $validation['valid'],
                    'value' => $validation['value'] ?? null,
                    'errors' => $validation['errors'] ?? []
                ];

                if (!$validation['valid']) {
                    $hasErrors = true;
                }
            }

            return ApiResponse::success(
                $validationResults,
                $hasErrors ? 'Validação concluída com erros' : 'Validação concluída com sucesso',
                ['has_errors' => $hasErrors]
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao validar filtros',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Obtém configuração completa dos filtros para interface
     */
    public function getFiltersConfigForUI(string $entityType, string $entityKey): ApiResponse
    {
        try {
            $entity = $this->findEntity($entityType, $entityKey);

            if (!$entity) {
                return $this->entityNotFoundResponse($entityType, $entityKey);
            }

            $config = [
                'entity_info' => $this->getEntityInfo($entityType, $entity),
                'filters' => $entity->getFiltersConfig(),
                'required_filters' => $entity->activeFilters()->where('required', true)->pluck('var_name')->toArray(),
                'total_filters' => $entity->activeFilters()->count()
            ];

            return ApiResponse::success(
                $config,
                'Configuração de filtros obtida com sucesso'
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao obter configuração de filtros',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Métodos auxiliares
     */

    /**
     * Encontra uma entidade pelo tipo e chave
     */
    private function findEntity(string $entityType, string $key): ?object
    {
        if (!isset($this->entityModels[$entityType])) {
            return null;
        }

        $model = $this->entityModels[$entityType];

        return $model::where('key', $key)->first();
    }

    /**
     * Verifica se um filtro já existe na entidade
     */
    private function filterExists(object $entity, string $varName): bool
    {
        return $entity->filters()->where('var_name', $varName)->exists();
    }

    /**
     * Cria um filtro para uma entidade
     */
    private function createFilterForEntity(string $entityType, int $entityId, array $config): Filter
    {
        return match($entityType) {
            self::ENTITY_QUERY => Filter::createFromConfigByQuery($entityId, $config),
            self::ENTITY_DASHBOARD => Filter::createFromConfigByDashboard($entityId, $config),
            default => throw new \InvalidArgumentException("Tipo de entidade inválido: {$entityType}"),
        };
    }

    /**
     * Retorna resposta de entidade não encontrada
     */
    private function entityNotFoundResponse(string $entityType, string $key): ApiResponse
    {
        $entityName = $this->getEntityName($entityType);
        return ApiResponse::error("{$entityName} '{$key}' não encontrada");
    }

    /**
     * Obtém o nome amigável da entidade
     */
    private function getEntityName(string $entityType): string
    {
        return match($entityType) {
            self::ENTITY_QUERY => 'Consulta',
            self::ENTITY_DASHBOARD => 'Dashboard',
            default => 'Entidade',
        };
    }

    /**
     * Obtém informações da entidade para resposta
     */
    private function getEntityInfo(string $entityType, object $entity): array
    {
        $baseInfo = [
            'key' => $entity->key,
            'name' => $entity->name,
            'description' => $entity->description ?? null,
            'entity_type' => $entityType,
        ];

        // Adiciona campos específicos de cada entidade
        if ($entityType === self::ENTITY_QUERY && method_exists($entity, 'getQueryInfo')) {
            $baseInfo['query_info'] = $entity->getQueryInfo();
        }

        if ($entityType === self::ENTITY_DASHBOARD && method_exists($entity, 'getDashboardInfo')) {
            $baseInfo['dashboard_info'] = $entity->getDashboardInfo();
        }

        return $baseInfo;
    }

    /**
     * Métodos de conveniência para compatibilidade com código existente
     */

    /**
     * Métodos para consultas dinâmicas (legacy)
     */
    public function createFilterByQuery(string $queryKey, array $filterData): ApiResponse
    {
        return $this->createFilter(self::ENTITY_QUERY, $queryKey, $filterData);
    }

    public function listFiltersByQuery(string $queryKey, bool $onlyActive = true): ApiResponse
    {
        return $this->listFilters(self::ENTITY_QUERY, $queryKey, $onlyActive);
    }

    public function updateFilterByQuery(string $queryKey, string $varName, array $filterData): ApiResponse
    {
        return $this->updateFilter(self::ENTITY_QUERY, $queryKey, $varName, $filterData);
    }

    public function deleteFilterByQuery(string $queryKey, string $varName): ApiResponse
    {
        return $this->deleteFilter(self::ENTITY_QUERY, $queryKey, $varName);
    }

    public function getFilterByQuery(string $queryKey, string $varName): ApiResponse
    {
        return $this->getFilter(self::ENTITY_QUERY, $queryKey, $varName);
    }

    public function reorderFiltersByQuery(string $queryKey, array $varNamesOrder): ApiResponse
    {
        return $this->reorderFilters(self::ENTITY_QUERY, $queryKey, $varNamesOrder);
    }

    public function validateFilterValuesByQuery(string $queryKey, array $params): ApiResponse
    {
        return $this->validateFilterValues(self::ENTITY_QUERY, $queryKey, $params);
    }

    public function getFiltersConfigForUIByQuery(string $queryKey): ApiResponse
    {
        return $this->getFiltersConfigForUI(self::ENTITY_QUERY, $queryKey);
    }

    /**
     * Métodos para dashboards
     */
    public function createFilterByDashboard(string $dashboardKey, array $filterData): ApiResponse
    {
        return $this->createFilter(self::ENTITY_DASHBOARD, $dashboardKey, $filterData);
    }

    public function listFiltersByDashboard(string $dashboardKey, bool $onlyActive = true): ApiResponse
    {
        return $this->listFilters(self::ENTITY_DASHBOARD, $dashboardKey, $onlyActive);
    }

    public function updateFilterByDashboard(string $dashboardKey, string $varName, array $filterData): ApiResponse
    {
        return $this->updateFilter(self::ENTITY_DASHBOARD, $dashboardKey, $varName, $filterData);
    }

    public function deleteFilterByDashboard(string $dashboardKey, string $varName): ApiResponse
    {
        return $this->deleteFilter(self::ENTITY_DASHBOARD, $dashboardKey, $varName);
    }

    public function getFilterByDashboard(string $dashboardKey, string $varName): ApiResponse
    {
        return $this->getFilter(self::ENTITY_DASHBOARD, $dashboardKey, $varName);
    }

    public function reorderFiltersByDashboard(string $dashboardKey, array $varNamesOrder): ApiResponse
    {
        return $this->reorderFilters(self::ENTITY_DASHBOARD, $dashboardKey, $varNamesOrder);
    }

    public function validateFilterValuesByDashboard(string $dashboardKey, array $params): ApiResponse
    {
        return $this->validateFilterValues(self::ENTITY_DASHBOARD, $dashboardKey, $params);
    }

    public function getFiltersConfigForUIByDashboard(string $dashboardKey): ApiResponse
    {
        return $this->getFiltersConfigForUI(self::ENTITY_DASHBOARD, $dashboardKey);
    }
}