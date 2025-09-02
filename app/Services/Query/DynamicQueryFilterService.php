<?php

namespace App\Services\Query;

use App\Models\Company;
use App\Models\DynamicQuery;
use App\Models\DynamicQueryFilter;
use App\Services\Core\ApiResponse;

/**
 * Serviço para gerenciar filtros de consultas dinâmicas
 */
class DynamicQueryFilterService
{
    /**
     * Cria um novo filtro para uma consulta dinâmica
     */
    public function createFilter(string $queryKey, array $filterData, ?Company $company = null): ApiResponse
    {
        try {
            $query = $this->findQuery($queryKey, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$queryKey}' não encontrada");
            }

            // Verifica se já existe filtro com mesmo var_name
            if ($query->filters()->where('var_name', $filterData['var_name'])->exists()) {
                return ApiResponse::error("Filtro com variável '{$filterData['var_name']}' já existe nesta consulta");
            }

            $filter = DynamicQueryFilter::createFromConfig($query->id, $filterData);

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
     * Lista filtros de uma consulta dinâmica
     */
    public function listFilters(string $queryKey, ?Company $company = null, bool $onlyActive = true): ApiResponse
    {
        try {
            $query = $this->findQuery($queryKey, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$queryKey}' não encontrada");
            }

            /** @var DynamicQueryFilter $filters */
            $filters = $onlyActive ? $query->activeFilters : $query->filters;

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
    public function updateFilter(string $queryKey, string $varName, array $filterData, ?Company $company = null): ApiResponse
    {
        try {
            $query = $this->findQuery($queryKey, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$queryKey}' não encontrada");
            }

            $filter = $query->filters()->where('var_name', $varName)->first();

            if (!$filter) {
                return ApiResponse::error("Filtro '{$varName}' não encontrado nesta consulta");
            }

            // Se mudou o var_name, verifica se não há conflito
            if (isset($filterData['var_name']) && $filterData['var_name'] !== $varName) {
                if ($query->filters()->where('var_name', $filterData['var_name'])->exists()) {
                    return ApiResponse::error("Filtro com variável '{$filterData['var_name']}' já existe nesta consulta");
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
    public function deleteFilter(string $queryKey, string $varName, ?Company $company = null): ApiResponse
    {
        try {
            $query = $this->findQuery($queryKey, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$queryKey}' não encontrada");
            }

            $filter = $query->filters()->where('var_name', $varName)->first();

            if (!$filter) {
                return ApiResponse::error("Filtro '{$varName}' não encontrado nesta consulta");
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
    public function getFilter(string $queryKey, string $varName, ?Company $company = null): ApiResponse
    {
        try {
            $query = $this->findQuery($queryKey, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$queryKey}' não encontrada");
            }

            $filter = $query->filters()->where('var_name', $varName)->first();

            if (!$filter) {
                return ApiResponse::error("Filtro '{$varName}' não encontrado nesta consulta");
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
     * Cria múltiplos filtros de uma vez
     */
    public function createMultipleFilters(string $queryKey, array $filtersData, ?Company $company = null): ApiResponse
    {
        try {
            $query = $this->findQuery($queryKey, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$queryKey}' não encontrada");
            }

            $created = [];
            $errors = [];

            foreach ($filtersData as $index => $filterData) {
                try {
                    // Verifica se já existe filtro com mesmo var_name
                    if ($query->filters()->where('var_name', $filterData['var_name'])->exists()) {
                        $errors[] = "Filtro #{$index}: Variável '{$filterData['var_name']}' já existe";
                        continue;
                    }

                    $filter = DynamicQueryFilter::createFromConfig($query->id, $filterData);
                    $created[] = $filter->toArray();

                } catch (\Exception $e) {
                    $errors[] = "Filtro #{$index}: {$e->getMessage()}";
                }
            }

            $message = count($created) . ' filtros criados com sucesso';
            if (!empty($errors)) {
                $message .= '. Erros: ' . implode('; ', $errors);
            }

            return ApiResponse::success(
                $created,
                $message,
                ['total_created' => count($created), 'errors' => $errors]
            );

        } catch (\Exception $e) {
            return ApiResponse::error(
                'Erro ao criar filtros',
                [$e->getMessage()]
            );
        }
    }

    /**
     * Reordena filtros
     */
    public function reorderFilters(string $queryKey, array $varNamesOrder, ?Company $company = null): ApiResponse
    {
        try {
            $query = $this->findQuery($queryKey, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$queryKey}' não encontrada");
            }

            foreach ($varNamesOrder as $index => $varName) {
                $filter = $query->filters()->where('var_name', $varName)->first();

                if ($filter) {
                    $filter->update(['order' => $index + 1]);
                }
            }

            return ApiResponse::success(
                $query->filters()->ordered()->get()->toArray(),
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
    public function validateFilterValues(string $queryKey, array $params, ?Company $company = null): ApiResponse
    {
        try {
            $query = $this->findQuery($queryKey, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$queryKey}' não encontrada");
            }

            $validationResults = [];
            $hasErrors = false;

            foreach ($query->activeFilters as $filter) {
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
     * Encontra uma consulta dinâmica por chave e empresa
     */
    private function findQuery(string $key, ?Company $company): ?DynamicQuery
    {
        $query = DynamicQuery::where('key', $key);

        if ($company) {
            // Primeiro tenta encontrar consulta específica da empresa
            $companyQuery = (clone $query)->where('company_id', $company->id)->first();

            if ($companyQuery) {
                return $companyQuery;
            }

            // Se não encontrou, procura consulta global
            return $query->where('is_global', true)->first();
        }

        // Se não tem empresa, só busca consultas globais
        return $query->where('is_global', true)->first();
    }

    /**
     * Obtém configuração completa dos filtros para interface
     */
    public function getFiltersConfigForUI(string $queryKey, ?Company $company = null): ApiResponse
    {
        try {
            $query = $this->findQuery($queryKey, $company);

            if (!$query) {
                return ApiResponse::error("Consulta '{$queryKey}' não encontrada");
            }

            $config = [
                'query_info' => [
                    'key' => $query->key,
                    'name' => $query->name,
                    'description' => $query->description,
                ],
                'filters' => $query->getFiltersConfig(),
                'required_filters' => $query->activeFilters()->where('required', true)->pluck('var_name')->toArray(),
                'total_filters' => $query->activeFilters()->count()
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
}